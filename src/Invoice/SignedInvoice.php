<?php

namespace Malik12tree\ZATCA\Invoice;

use Malik12tree\ZATCA\Invoice;

class SignedInvoice
{
	/** @var Invoice */
	protected $invoice;

	/**	@var string */
	protected $signedInvoiceXML;

	/**	@var string */
	protected $invoiceHash;

	/**	@var string */
	protected $qr;

	public function __construct($invoice, $signedInvoiceXML, $invoiceHash, $qr)
	{
		$this->invoice = $invoice;
		$this->signedInvoiceXML = $signedInvoiceXML;
		$this->invoiceHash = $invoiceHash;
		$this->qr = $qr;
	}

	public function getInvoice()
	{
		return $this->invoice;
	}
	public function getSignedInvoiceXML()
	{
		return $this->signedInvoiceXML;
	}
	public function getInvoiceHash()
	{
		return $this->invoiceHash;
	}
	public function getQR()
	{
		return $this->qr;
	}

	public function toPDF($options = [])
	{
		return new SignedPDFInvoice($this, $options);
	}
}
