<?php

use function Malik12tree\ZATCA\Utils\getLineItemDiscounts;
use function Malik12tree\ZATCA\Utils\getLineItemSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemTaxes;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormat;

$lineItemTotalDiscounts = getLineItemDiscounts($LINE_ITEM);
$lineItemSubtotal = getLineItemSubtotal($LINE_ITEM);
$lineItemTotalTaxes = getLineItemTaxes($LINE_ITEM);

// foreach ($LINE_ITEM['other_taxes'] ?? [] as $tax) {
// 	$lineItemTotalTaxes += floatval($tax['percent_amount']) * $lineItemSubtotal;
// }

?>
<cac:InvoiceLine>

	<cbc:ID><?= $LINE_ITEM['id'] ?></cbc:ID>
	<cbc:InvoicedQuantity unitCode="PCE"><?= $LINE_ITEM['quantity'] ?></cbc:InvoicedQuantity>

	<!-- BR-DEC-23 -->
	<cbc:LineExtensionAmount currencyID="SAR"><?= zatcaNumberFormat($lineItemSubtotal) ?></cbc:LineExtensionAmount>

	<!-- BR-KSA-DEC-03, BR-KSA-51 -->
	<cac:TaxTotal>
		<cbc:TaxAmount currencyID="SAR"><?= zatcaNumberFormat($lineItemTotalTaxes) ?></cbc:TaxAmount>
		<cbc:RoundingAmount currencyID="SAR"><?= zatcaNumberFormat($lineItemSubtotal + $lineItemTotalTaxes) ?></cbc:RoundingAmount>
	</cac:TaxTotal>

	<cac:Item>
		<cbc:Name><?= $LINE_ITEM['name'] ?></cbc:Name>
		<!-- VAT -->
		<!-- BR-KSA-DEC-02 -->
		<cac:ClassifiedTaxCategory>

			<cbc:ID><?= $LINE_ITEM['vat_percent'] ? 'S' : 'O' ?></cbc:ID>
			<cbc:Percent><?= zatcaNumberFormat($LINE_ITEM['vat_percent'] ? ($LINE_ITEM['vat_percent'] * 100) : 0) ?></cbc:Percent>
			<cac:TaxScheme>
				<cbc:ID>VAT</cbc:ID>
			</cac:TaxScheme>
		</cac:ClassifiedTaxCategory>

		<?php /* foreach ($LINE_ITEM['other_taxes'] ?? [] as $tax): */ ?>
		<?php /* <cac:ClassifiedTaxCategory>
				<cbc:ID>S</cbc:ID>
				<cbc:Percent><? /* = zatcaNumberFormat($tax['percent_amount'] * 100) *!/ ?></cbc:Percent>
				<cac:TaxScheme>
					<cbc:ID>VAT</cbc:ID>
				</cac:TaxScheme>
			</cac:ClassifiedTaxCategory> */ ?>
		<?php /* endforeach; */ ?>
	</cac:Item>

	<cac:Price>
		<cbc:PriceAmount currencyID="SAR"><?= $LINE_ITEM['tax_exclusive_price'] ?></cbc:PriceAmount>
		<?php foreach ($LINE_ITEM['discounts'] ?? [] as $discount): ?>
			<cac:AllowanceCharge>
				<cbc:ChargeIndicator>false</cbc:ChargeIndicator>
				<cbc:AllowanceChargeReason><?= $discount['reason'] ?></cbc:AllowanceChargeReason>
				<cbc:Amount currencyID="SAR"><?= zatcaNumberFormat($discount['amount']) ?></cbc:Amount>
			</cac:AllowanceCharge>
		<?php endforeach; ?>
	</cac:Price>
</cac:InvoiceLine>

<?php
return [
	'taxes_total' => $lineItemTotalTaxes,
	'discounts_total' => $lineItemTotalDiscounts,
	'subtotal' => $lineItemSubtotal
];
?>