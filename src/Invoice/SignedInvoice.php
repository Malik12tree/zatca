<?php

namespace Malik12tree\ZATCA\Invoice;

use Malik12tree\ZATCA\Invoice;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceCode;
use Malik12tree\ZATCA\Utils\Rendering\Template;
use Mpdf\QrCode\Output\Png;
use Mpdf\QrCode\QrCode;

class SignedInvoice
{
    /** @var Invoice */
    protected $invoice;

    /** @var string */
    protected $signedInvoiceXML;

    /** @var string */
    protected $invoiceHash;

    /** @var string */
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

    public function toHTML($options = [], $internalOnlyParameter__withResult = false)
    {
        $qrCode = new QrCode($this->getQR());
        $qrOutput = new Png();

        $flavor = InvoiceCode::TAX === $this->getInvoice()->getCode() ? 'tax' : 'simplified';
        list($render, $resultOptions) = Template::render(
            $options['custom_template'] ?: ("@pdfs/{$flavor}"),
            [
                'invoice' => $this->getInvoice(),
                'qr' => 'data:image/png;base64,'.base64_encode($qrOutput->output($qrCode, 256)),

                'hasLogo' => $hasLogo = isset($options['logo']) ? (bool) $options['logo'] : false,
            ],
            true,
            (bool) $options['custom_template']
        );

        $resultOptions = $resultOptions ?? [];
        $resultOptions['mpdf'] = $resultOptions['mpdf'] ?? [];
        $resultOptions['hasLogo'] = $hasLogo;

        if ($internalOnlyParameter__withResult) {
            return [$render, $resultOptions];
        }

        return $render;
    }

    public function toPDF($options = [])
    {
        return new SignedPDFInvoice($this, $options);
    }
}
