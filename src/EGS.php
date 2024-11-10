<?php

namespace Malik12tree\ZATCA;

use Exception;
use Malik12tree\ZATCA\Utils\API;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;
use Malik12tree\ZATCA\Utils\Rendering\Template;

class EGS
{
	private $api;
	private $unit;
	private $isProduction;
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
}
