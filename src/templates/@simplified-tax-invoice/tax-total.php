<?php

use function Malik12tree\ZATCA\Utils\getLineItemSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemTaxes;
use function Malik12tree\ZATCA\Utils\getLineItemVATCategory;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormat;

$fifteenSubTotal = null;
$fiveSubTotal = null;
$zeroSubTotalByCategory = [];

$subTotals = [];

foreach ($invoice->getLineItems() as $item) {
    list(
        'percent' => $percent,
        'category' => $category,
        'reason' => $reason,
        'reason_code' => $reason_code,
    ) = getLineItemVATCategory($item);

    switch ($percent) {
        case 15:
            $fifteenSubTotal ??= [
                'percent' => $percent,
                'category' => $category,
                'taxableAmount' => 0,
                'taxAmount' => 0,
            ];
            $fifteenSubTotal['taxableAmount'] += getLineItemSubtotal($item);
            $fifteenSubTotal['taxAmount'] += getLineItemTaxes($item);

            break;

        case 5:
            $fiveSubTotal ??= [
                'percent' => $percent,
                'category' => $category,
                'taxableAmount' => 0,
                'taxAmount' => 0,
            ];
            $fiveSubTotal['taxableAmount'] += getLineItemSubtotal($item);
            $fiveSubTotal['taxAmount'] += getLineItemTaxes($item);

            break;

        case 0:
            $zeroSubTotalByCategory[$category] ??= [
                'percent' => $percent,
                'category' => $category,
                'taxableAmount' => 0,
                'taxAmount' => 0,
                // ? Only first occurring reason and code
                'reason' => $reason ?? '',
                'reason_code' => $reason_code ?? '',
            ];
            $zeroSubTotalByCategory[$category]['taxableAmount'] += getLineItemSubtotal($item);

            break;
    }
}

if ($fifteenSubTotal) {
    $subTotals[] = $fifteenSubTotal;
}
if ($fiveSubTotal) {
    $subTotals[] = $fiveSubTotal;
}
foreach ($zeroSubTotalByCategory as $_ => $zeroSubTotal) {
    $subTotals[] = $zeroSubTotal;
}

$taxesTotal = zatcaNumberFormat($invoice->computeTotalTaxes());
?>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="SAR"><?= zatcaNumberFormat($taxesTotal); ?></cbc:TaxAmount>
<?php foreach ($subTotals as $subTotal) { ?>
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="SAR"><?= zatcaNumberFormat($subTotal['taxableAmount']); ?></cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="SAR"><?= zatcaNumberFormat($subTotal['taxAmount']); ?></cbc:TaxAmount>
            <cac:TaxCategory>
                <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5305"><?= $subTotal['category']; ?></cbc:ID>
                <cbc:Percent><?= zatcaNumberFormat($subTotal['percent']); ?></cbc:Percent>
<?php if (isset($subTotal['reason_code'])) { ?>
                <cbc:TaxExemptionReasonCode><?= $subTotal['reason_code']; ?></cbc:TaxExemptionReasonCode>
<?php } ?>
<?php if (isset($subTotal['reason'])) { ?>
                <cbc:TaxExemptionReason><?= $subTotal['reason']; ?></cbc:TaxExemptionReason>
<?php } ?>
                <cac:TaxScheme>
                    <cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5153">VAT</cbc:ID>
                </cac:TaxScheme>
            </cac:TaxCategory>
        </cac:TaxSubtotal>
<?php } ?>
    </cac:TaxTotal>
    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="SAR"><?= zatcaNumberFormat($taxesTotal); ?></cbc:TaxAmount>
    </cac:TaxTotal>