<?php

namespace Malik12tree\ZATCA;

use Exception;
use Malik12tree\ZATCA\Utils\API;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;
use Malik12tree\ZATCA\Utils\Rendering\Template;
use Malik12tree\ZATCA\EGSDatabase;

class EGS
{
	private $api;
	private $unit;
	private $isProduction;
	/** @var EGSDatabase|null */
	private $database;
	public function __construct($unit, $env = "sandbox")
	{
		$this->unit = $unit;
		$this->api = new API($env);
		$this->isProduction = $env == "production";
	}

	public function generateNewKeysAndCSR($solutionName)
	{
		$privateKey = Crypto::generateSecp256k1KeyPair()[0];

		$csrConfigFile = tmpfile();
		$csrConfig = Template::render('csr', [
			'PRODUCTION_VALUE' => "PREZATCA-Code-Signing",
			'EGS_SERIAL_NUMBER' => "1-$solutionName|2-{$this->unit['model']}|3-{$this->unit['uuid']}",
			'VAT_REGISTRATION_NUMBER' => $this->unit['vat_number'],
			'BRANCH_LOCATION' => "{$this->unit['location']['building']} {$this->unit['location']['street']} {$this->unit['location']['city']}",
			'BRANCH_INDUSTRY' => $this->unit['branch_industry'],
			'BRANCH_NAME' => $this->unit['branch_name'],
			'TAXPAYER_NAME' => $this->unit['vat_name'],
			'COMMON_NAME' => $this->unit['common_name'],
			'PRIVATE_KEY_PASS' => 'SET_PRIVATE_KEY_PASS',
			'PRODUCTION' => $this->isProduction
		]);

		fwrite($csrConfigFile, $csrConfig);
		fclose($csrConfigFile);

		$csr = Crypto::generateEcdsaWithSHA256($csrConfigFile)[0];

		$this->unit['private_key'] = Crypto::setCertificateTitle($privateKey, "EC PRIVATE KEY");
		$this->unit['csr'] = Crypto::setCertificateTitle($csr, "CERTIFICATE REQUEST");
	}

	public function issueComplianceCertificate(string $otp)
	{
		if (!$this->unit['csr']) throw new Exception('EGS needs to generate a CSR first.');

		$res = $this->api->issueComplianceCertificate($this->unit['csr'], $otp);

		$this->unit['compliance_certificate'] = $res->issued_certificate;
		$this->unit['compliance_api_secret'] = $res->api_secret;

		return $res->request_id;
	}
	public function issueProductionCertificate(int $complianceRequestId)
	{
		if (!$this->unit['compliance_certificate'] || !$this->unit['compliance_api_secret']) throw new Exception('EGS is missing a certificate/private key/api secret to request a production certificate.');

		$res = $this->api->issueProductionCertificate(
			$this->unit['compliance_certificate'],
			$this->unit['compliance_api_secret'],
			$complianceRequestId
		);

		$this->unit['production_certificate'] = $res->issued_certificate;
		$this->unit['production_api_secret'] = $res->api_secret;

		return $res->request_id;
	}

	public function checkInvoiceCompliance(string $signedInvoice, string $invoiceHash)
	{
		if (!$this->unit['compliance_certificate'] || !$this->unit['compliance_api_secret']) throw new Exception('EGS is missing a certificate/private key/api secret to check the invoice compliance.');

		$res = $this->api->checkInvoiceCompliance(
			$this->unit['compliance_certificate'],
			$this->unit['compliance_api_secret'],
			$signedInvoice,
			$invoiceHash,
			$this->unit['uuid']
		);

		return $res;
	}

	/**
	 * @param Invoice $invoice
	 */
	public function signInvoice($invoice)
	{
		$certificate =  $this->isProduction ? $this->unit['production_certificate'] : $this->unit['compliance_certificate'];

		if (!$certificate || !$this->unit['private_key']) throw new Exception("EGS is missing a certificate/private key to sign the invoice.");

		return $invoice->sign($certificate, $this->unit['private_key']);
	}


	public function setDatabase($database)
	{
		$this->database = $database;

		return $this;
	}

	public function save()
	{
		if (!$this->database) throw new Exception("EGS database is not set.");

		$this->database->save($this);

		return $this;
	}
	public function register($solutionName, $otp)
	{
		// Incase of a failure, we need to rollback the state of the EGS unit.
		$unitCopy = $this->unit;

		try {
			$this->generateNewKeysAndCSR($solutionName);
			$complianceRequestId = $this->issueComplianceCertificate($otp);

			// * Checking invoice compliance 6 times each of the following types is
			// * required to be able to issue a production certificate.
			// * ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
			// * ┃  standard-invoice  ━━  standard-credit-note  ━━  standard-debit-note  ┃
			// * ┃ simplified-invoice ━━ simplified-credit-note ━━ simplified-debit-note ┃
			// * ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
			foreach (InvoiceCode::cases() as $invoiceCode) {
				foreach (InvoiceType::cases() as $invoiceType) {
					$data = [
						"egs_info" => $this->unit,

						"invoice_code" => $invoiceCode,
						"invoice_type" => $invoiceType,

						"invoice_counter_number" => 1,
						"invoice_serial_number" => Crypto::uuid4(),
						"issue_date" => date('Y-m-d'),
						"issue_time" => date('H:i:s'),
						"previous_invoice_hash" => '',

						"line_items" => [
							[
								"id" => "dummy",
								"name" => "Dummy Item",
								"quantity" => 1,
								"tax_exclusive_price" => 10,
								"vat_percent" => 0.15,
							]
						],
					];
					if ($invoiceType != InvoiceType::INVOICE) {
						$data["cancellation"] = [
							"canceled_serial_invoice_number" => $data["invoice_serial_number"],
							"payment_method" => InvoicePaymentMethod::CASH,
							"reason" => "KSA-10",
						];
					}

					$invoice = new Invoice($data);
					list(
						"signedInvoice" => $signedInvoice,
						"hash" => $invoiceHash,
					) = $this->signInvoice($invoice);
					$this->checkInvoiceCompliance($signedInvoice, $invoiceHash);
				}
			}

			$this->issueProductionCertificate($complianceRequestId);
			return $this;
		} catch (Exception $e) {
			$this->unit = $unitCopy;
			throw $e;
		}
	}

	public function getUUID()
	{
		return $this->unit['uuid'];
	}
	public function toJSON()
	{
		return $this->unit;
	}
}
