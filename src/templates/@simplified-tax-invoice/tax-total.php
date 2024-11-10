<?php

use function Malik12tree\ZATCA\Utils\zatcaNumberFormat;

$subTotals = [];
// BR-DEC-13, MESSAGE : [BR-DEC-13]-The allowed maximum number of decimals for the Invoice total VAT amount (BT-110) is 2.

$taxesTotal = 0;
foreach ($LINE_ITEMS as $lineItem) {
	$totalLineItemDiscount = array_reduce($lineItem['discounts'] ?? [], static function ($p, $c) {
		return $p + $c['amount'];
	}, 0);
	$taxableAmount = ($lineItem['tax_exclusive_price'] * $lineItem['quantity']) - ($totalLineItemDiscount ?? 0);

	$taxAmount = ((float)$lineItem['vat_percent']) * ((float)$taxableAmount);
	$subTotals[] = [$taxableAmount, $taxAmount, $lineItem['vat_percent']];


	$taxesTotal += $taxAmount;

	foreach ($lineItem['other_taxes'] ?? [] as $tax) {
		$taxAmount = $tax['percent_amount'] * $taxableAmount;
		$subTotals[] = [$taxableAmount, $taxAmount, $tax['percent_amount']];
		$taxesTotal += $taxAmount;
	}
}
// BT-110
$taxesTotal = zatcaNumberFormat($taxesTotal);

// BR-DEC-13, MESSAGE : [BR-DEC-13]-The allowed maximum number of decimals for the Invoice total VAT amount (BT-110) is 2.
?>
<cac:TaxTotal>
	<!-- Total tax amount for the full invoice -->
	<cbc:TaxAmount currencyID="SAR"><?= $taxesTotal ?></cbc:TaxAmount>
	<?php foreach ($subTotals as list($taxableAmount, $taxAmount, $taxPercent)): ?>
		<cac:TaxSubtotal>
			<!-- BR-DEC-19 -->
			<cbc:TaxableAmount currencyID="SAR">
				<?= zatcaNumberFormat((float)($taxableAmount)) ?>
			</cbc:TaxableAmount>
			<cbc:TaxAmount currencyID="SAR">
				<?= zatcaNumberFormat((float)($taxAmount)) ?>
			</cbc:TaxAmount>


			<cbc:TaxExemptionReason>
				<?= $taxPercent ? '' : 'Not subject to VAT' ?>
			</cbc:TaxExemptionReason>`


			<cac:TaxCategory>
				<cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5305"><?= $taxPercent ? 'S' : 'O' ?></cbc:ID>
				<cbc:Percent>
					<?= zatcaNumberFormat((float)$taxPercent * 100.00) ?>
				</cbc:Percent>
				<cac:TaxScheme>
					<cbc:ID schemeAgencyID="6" schemeID="UN/ECE 5153">VAT</cbc:ID>
				</cac:TaxScheme>
			</cac:TaxCategory>
		</cac:TaxSubtotal>
	<?php endforeach; ?>
</cac:TaxTotal>
<cac:TaxTotal>
	<!-- KSA Rule for VAT tax -->
	<cbc:TaxAmount currencyID="SAR"><?= $taxesTotal ?></cbc:TaxAmount>
</cac:TaxTotal>

<?php return $taxesTotal ?>