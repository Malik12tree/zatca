<?php

namespace Malik12tree\ZATCA;

use DOMDocument;
use Malik12tree\ZATCA\Invoice\SignedInvoice;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;
use Malik12tree\ZATCA\Utils\Encoding\TLV;
use Malik12tree\ZATCA\Utils\Rendering\Template;

use function Malik12tree\ZATCA\Utils\getLineItemDiscounts;
use function Malik12tree\ZATCA\Utils\getLineItemSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemTaxes;

class Invoice
{
	/* BR-KSA-26 */
	const INITIAL_PREVIOUS_HASH = "NWZlY2ViNjZmZmM4NmYzOGQ5NTI3ODZjNmQ2OTZjNzljMmRiYzIzOWRkNGU5MWI0NjcyOWQ3M2EyN2ZiNTdlOQ==";
	private $invoiceXML;

	private $egsUnit;
	private $issueDate;
	private $issueTime;
	private $vatNumber;
	private $vatName;
	private $deliveryDate;
	private $invoiceSerialNumber;
	private $invoiceType;
	private $customerInfo;
	private $lineItems;

	private $total;
	private $totalTax;

	private $cachedHash = null;

	public function __construct($data)
	{
		$this->egsUnit = $data["egs_info"];
		$this->issueDate = $data["issue_date"];
		$this->issueTime = $data["issue_time"];
		$this->invoiceType = $data["invoice_type"];
		$this->invoiceSerialNumber = $data["invoice_serial_number"];
		$this->vatNumber = $data["egs_info"]["vat_number"];
		$this->vatName = $data["egs_info"]["vat_name"];
		$this->deliveryDate = $data["actual_delivery_date"] ?? null;
		$this->customerInfo = $data["customer_info"] ?? [];
		$this->lineItems = $data["line_items"] ?? [];

		list($this->invoiceXML, list(
			"total" => $this->total,
			"totalTax" => $this->totalTax
		)) =
			Template::render('simplified-tax-invoice', [
				"invoice" => $this,
				"EGS_INFO" => $data["egs_info"],
				"CUSTOMER_INFO" => $this->customerInfo,
				"LINE_ITEMS" => $data["line_items"] ?? [],
				"INVOICE_SERIAL_NUMBER" => $data["invoice_serial_number"],
				"ISSUE_DATE" => $data["issue_date"],
				"ISSUE_TIME" => $data["issue_time"],
				"INVOICE_CODE" => $data["invoice_code"],
				"INVOICE_TYPE" => $data["invoice_type"],
				"INVOICE_COUNTER_NUMBER" => $data["invoice_counter_number"],
				"PREVIOUS_INVOICE_HASH" => $data["previous_invoice_hash"],
				"CANCELLATION" => isset($data["cancellation"])
					? $data["cancellation"]
					: (isset($data["cancelation"]) // 🦅
						? $data["cancelation"]
						: null),
				"ACTUAL_DELIVERY_DATE" => isset($data["actual_delivery_date"]) ? $data["actual_delivery_date"] : null,
				"LATEST_DELIVERY_DATE" => isset($data["latest_delivery_date"]) ? $data["latest_delivery_date"] : null,
				"PAYMENT_METHOD" => isset($data["payment_method"]) ? $data["payment_method"] : null
			], true);
		$this->invoiceXML = str_replace("\r\n", "\n", $this->invoiceXML);
	}

	public function getVATNumber()
	{
		return $this->vatNumber;
	}
	public function getVATName()
	{
		return $this->vatName;
	}
	public function getDeliveryDate()
	{
		return $this->deliveryDate;
	}
	public function getSerialNumber()
	{
		return $this->invoiceSerialNumber;
	}
	public function getType()
	{
		return $this->invoiceType;
	}
	public function getIssueDate()
	{
		return $this->issueDate;
	}
	public function getIssueTime()
	{
		return $this->issueTime;
	}
	public function getEGS()
	{
		return $this->egsUnit;
	}
	public function getLineItems()
	{
		return $this->lineItems;
	}
	public function getCustomerInfo($key = null)
	{
		return $key ? ($this->customerInfo[$key] ?? null) : $this->customerInfo;
		return $this->customerInfo;
	}
	public function getFormattedIssueDate()
	{
		return "{$this->issueDate} {$this->issueTime}";
	}
	public function computeTotalTaxes()
	{
		$totalTaxes = 0;
		foreach ($this->lineItems as $lineItem) {
			$totalTaxes += getLineItemTaxes($lineItem);
		}
		return $totalTaxes;
	}
	public function computeTotalDiscounts()
	{
		$totalDiscounts = 0;
		foreach ($this->lineItems as $lineItem) {
			$totalDiscounts += getLineItemDiscounts($lineItem);
		}
		return $totalDiscounts;
	}
	public function computeTotalSubtotal()
	{
		$totalSubtotal = 0;
		foreach ($this->lineItems as $lineItem) {
			$totalSubtotal += getLineItemSubtotal($lineItem);
		}
		return $totalSubtotal;
	}
	public function computeTotal()
	{
		return $this->computeTotalSubtotal() + $this->computeTotalTaxes();
	}
	public function attachmentName($extension = '')
	{
		$name = "{$this->vatNumber}_" . date('Ymd\THis', strtotime("{$this->issueDate} {$this->issueTime}")) . "_{$this->invoiceSerialNumber}";
		if ($extension) {
			$name .= ".{$extension}";
		}
		return $name;
	}

	public function hash()
	{
		if ($this->cachedHash) return $this->cachedHash;

		$cleanInvoice = $this->cleanedXML();

		$hash = Crypto::hashSHA256($cleanInvoice);
		$this->cachedHash = base64_encode($hash);

		return $this->cachedHash;
	}

	public function sign($certificate, $privateKey)
	{
		$invoiceHash = $this->hash();
		$certificateInfo = Crypto::getCertificateInfo($certificate);

		$digitalSignature = base64_encode(Crypto::signSHA256($invoiceHash, $privateKey));

		$qr = $this->qr($digitalSignature, $certificateInfo["publicKey"], $certificateInfo['signature']);

		$ublPropertiesVariables = [
			"SIGN_TIMESTAMP" => date('Y-m-d\TH:i:s\Z'),
			"CERTIFICATE_HASH" => $certificateInfo["hash"],
			"CERTIFICATE_ISSUER" => $certificateInfo["issuer"],
			"CERTIFICATE_SERIAL_NUMBER" => $certificateInfo["serialNumber"],
		];

		$ublSignaturePropertiesRender =
			Template::render('@simplified-tax-invoice/ubl-signature/properties', $ublPropertiesVariables);
		$ublSignaturePropertiesRenderForSigning =
			Template::render('@simplified-tax-invoice/ubl-signature/properties/for-signing', $ublPropertiesVariables);

		$signedUBLSignaturePropertiesRender = base64_encode(bin2hex(Crypto::hashSHA256($ublSignaturePropertiesRenderForSigning)));

		$ublSignatureRender = Template::render('@simplified-tax-invoice/ubl-signature', [
			"INVOICE_HASH" => $invoiceHash,
			"SIGNED_PROPERTIES_HASH" => $signedUBLSignaturePropertiesRender,
			"DIGITAL_SIGNATURE" => $digitalSignature,
			"CERTIFICATE" => Crypto::cleanCertificate($certificate),
			"SIGNED_PROPERTIES_XML" => $ublSignaturePropertiesRender,
		]);

		$invoiceRender = $this->invoiceXML;
		$invoiceRender = str_replace(
			['%UBL_EXTENSIONS_STRING%', '%QR_CODE_DATA%'],
			[$ublSignatureRender, $qr],
			$invoiceRender
		);

		return new SignedInvoice($this, $invoiceRender, $invoiceHash, $qr);
	}


	private function qr($digitalSignature, $publicKey, $signature)
	{
		$sellerName = $this->vatName;
		$vatNumber = $this->vatNumber;
		$total = $this->total;
		$vatTotal = $this->totalTax;
		$dateTime = date('Y-m-d\TH:i:s', strtotime("{$this->issueDate} {$this->issueTime}"));

		$qrTLV = TLV::encodeAll([
			0x01 => $sellerName,
			0x02 => $vatNumber,
			0x03 => $dateTime,
			0x04 => $total,
			0x05 => $vatTotal,
			0x06 => $this->hash(),
			0x07 => $digitalSignature,
			0x08 => $publicKey,
			0x09 => $signature,
		]);

		return base64_encode($qrTLV);
	}


	private function cleanedXML()
	{
		$document = new DOMDocument();
		$document->loadXML($this->invoiceXML);

		if ($element = $document->getElementsByTagName('UBLExtensions')->item(0))
			$element->remove();

		if ($element = $document->getElementsByTagName('Signature')->item(0))
			$element->remove();

		// Remove QR Code Tag
		if ($element = $document->getElementsByTagName('AdditionalDocumentReference')->item(2))
			$element->remove();

		$cleanXML = str_replace(
			[
				"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n",
			],
			[
				"",
			],
			$document->saveXML(null, LIBXML_NOEMPTYTAG)
		);
		$cleanXML = trim($cleanXML);
		return $cleanXML;
	}
}
