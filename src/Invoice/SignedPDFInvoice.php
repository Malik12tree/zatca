<?php

namespace Malik12tree\ZATCA\Invoice;

use Malik12tree\ZATCA\Invoice\Enums\InvoiceCode;
use Malik12tree\ZATCA\Utils\Rendering\Template;
use Mpdf\Mpdf;
use Mpdf\QrCode\Output;
use Mpdf\QrCode\QrCode;

class SignedPDFInvoice
{
    /** @var SignedInvoice */
    protected $signedInvoice;

    /** @var string */
    protected $pdf;

    public function __construct($signedInvoice, $options = [])
    {
        $this->signedInvoice = $signedInvoice;

        $qrCode = new QrCode($this->signedInvoice->getQR());
        $qrOutput = new Output\Png();

        $flavor = InvoiceCode::TAX === $this->signedInvoice->getInvoice()->getCode() ? 'tax' : 'simplified';
        list($pdfRender, $resultOptions) = Template::render(
            '@pdfs/'.$flavor,
            [
                'invoice' => $this->getInvoice(),
                'qr' => 'data:image/png;base64,'.base64_encode($qrOutput->output($qrCode, 124)),

                'hasLogo' => $hasLogo = isset($options['logo']) ? (bool) $options['logo'] : false,
            ],
            true
        );
        $resultOptions = $resultOptions ?? [];
        $resultOptions['mpdf'] = $resultOptions['mpdf'] ?? [];

        $mpdf = new Mpdf($resultOptions['mpdf'] + [
            'PDFA' => true,
            'PDFAauto' => true,
        ]);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDefaultFontSize(7);
        $mpdf->simpleTables = true;
        $mpdf->keep_table_proportions = true;
        $mpdf->packTableData = true;
        $mpdf->shrink_tables_to_fit = 1;

        if ($hasLogo) {
            $mpdf->imageVars['logo'] = file_get_contents($options['logo']);
        }

        $mpdf->WriteHTML($pdfRender);

        $tmpXml = tmpfile();
        fwrite($tmpXml, $this->signedInvoice->getSignedInvoiceXML());

        $mpdf->SetAssociatedFiles([[
            'name' => $this->getInvoice()->attachmentName('xml'),
            'mime' => 'text/xml',
            'description' => '',
            'AFRelationship' => 'Alternative',
            'path' => stream_get_meta_data($tmpXml)['uri'],
        ]]);

        $data = $mpdf->OutputBinaryData();
        fclose($tmpXml);

        $this->pdf = $data;
    }

    public function getSignedInvoice()
    {
        return $this->signedInvoice;
    }

    public function getInvoice()
    {
        return $this->signedInvoice->getInvoice();
    }

    public function getPDF()
    {
        return $this->pdf;
    }

    public function saveAt($directoryPath)
    {
        $filePath = $directoryPath.DIRECTORY_SEPARATOR.$this->getInvoice()->attachmentName('pdf');
        file_put_contents($filePath, $this->pdf);
    }
}
