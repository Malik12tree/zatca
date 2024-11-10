<?php

namespace Malik12tree\ZATCA;

use DOMDocument;
use Malik12tree\ZATCA\Utils\Encoding\Crypto;
use Malik12tree\ZATCA\Utils\Encoding\TLV;
use Malik12tree\ZATCA\Utils\Rendering\Template;

class Invoice
{
	private $invoiceXML;

	private $egsUnit;
	private $issueDate;
	private $issueTime;

	private $total;
	private $totalTax;

	private $cachedHash = null;

	public function __construct($data)
	{
		$this->egsUnit = $data["egs_info"];
		$this->issueDate = $data["issue_date"];
		$this->issueTime = $data["issue_time"];

		list($this->invoiceXML, list(
			"total" => $this->total,
			"totalTax" => $this->totalTax
		)) =
			Template::render('simplified-tax-invoice', [
				"EGS_INFO" => $data["egs_info"],
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
	}

	public function hash()
	{
		if ($this->cachedHash) return $this->cachedHash;

		$cleanInvoice = $this->cleanedXML();

		$hash = Crypto::hashSHA256HighNibble(trim($cleanInvoice));
		$this->cachedHash = base64_encode($hash);

		return $this->cachedHash;
	}

	public function sign($certificate, $privateKey)
	{
		$invoiceHash = $this->hash();
		$certificateInfo = Crypto::getCertificateInfo($certificate);

		$digitalSignature = base64_encode(Crypto::signSHA256($invoiceHash, $privateKey));

		$qr = $this->qr($digitalSignature, $certificateInfo["publicKey"], $certificateInfo["signature"]);

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

		$signedUBLSignaturePropertiesRender = base64_encode(Crypto::hashSHA256($ublSignaturePropertiesRenderForSigning));

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

		return [
			"signedInvoice" => $invoiceRender,
			"hash" => $invoiceHash,
			"qr" => $qr,
		];
	}


	private function qr($digitalSignature, $publicKey, $signature)
	{
		$sellerName = $this->egsUnit["vat_name"];
		$vatNumber = $this->egsUnit["vat_number"];
		$total = $this->total;
		$vatTotal = $this->totalTax;
		$dateTime = date('Y-m-d\TH:i:s\Z', strtotime("{$this->issueDate} {$this->issueTime}"));

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

		return str_replace(
			[
				"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n",
				"<cbc:ProfileID>",
				"<cac:AccountingSupplierParty>"
			],
			[
				"",
				// https://github.com/Repzo/zatca-xml-js/blob/9a9045eff227c58fde27c96dd939a636f5d8a26b/src/zatca/signing/index.ts#L44-L50
				// A dumb workaround for whatever reason ZATCA XML devs decided
				// to include those trailing spaces and a newlines.
				// (without it the hash is incorrect)
				"\n    <cbc:ProfileID>",
				"\n    \n    <cac:AccountingSupplierParty>"
			],
			$document->saveXML()
		);
	}
}
