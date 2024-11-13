<?php

use Malik12tree\ZATCA\InvoiceType;
use Malik12tree\ZATCA\Invoice;

use function Malik12tree\ZATCA\Utils\getLineItemDiscounts;
use function Malik12tree\ZATCA\Utils\getLineItemSubtotal;
use function Malik12tree\ZATCA\Utils\getLineItemTaxes;
use function Malik12tree\ZATCA\Utils\getLineItemTotal;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormat;

/** @var Invoice $invoice */
$invoice;

$tableAttrs = 'cellpadding="5px" autosize="1" border="1" width="100%" style="overflow: wrap"';
const UNIT = 'SAR';
const F_UNIT = " " . UNIT;

$totalDiscount = 0;
$columns = [
	"name" => [
		"en" => "Nature of goods or services",
		"ar" => "طبیعة السلع أو الخدمات"
	],
	"tax_exclusive_price" => [/*  */
		"en" => "Unit price",
		"ar" => "سعر الوحدة",
 
		"@map" => static function ($value, $row) {
			return zatcaNumberFormat($value) . F_UNIT;
		}
	],
	"quantity" => [
		"en" => "Quantity",
		"ar" => "كمية"
	],
	"taxable_amount" => [
		"en" => "Taxable Amount",
		"ar" => "المبلغ الخاضع للضریبة",

		"@map" => static function ($value, $row) {
			return zatcaNumberFormat(getLineItemSubtotal($row)) . F_UNIT;
		}
	],
	"discount" => [
		"en" => "Discount",
		"ar" => "خصم",

		"@map" => static function ($value, $row) {
			global $totalDiscount;

			$discount = getLineItemDiscounts($row);
			$totalDiscount += $discount;

			return zatcaNumberFormat($discount) . F_UNIT;
		}
	],
	"vat_percent" => [
		"en" => "VAT Percentage",
		"ar" => "النسبة المئوية للضريبة",

		"@map" => static function ($value, $row) {
			return zatcaNumberFormat($value * 100) . '%';
		}
	],
	"tax_amount" => [
		"en" => "Tax Amount",
		"ar" => "المبلغ الضريب",

		"@map" => static function ($value, $row) {
			return zatcaNumberFormat(getLineItemTaxes($row)) . F_UNIT;
		}
	],
	"total" => [
		"en" => "Item Subtotal (Including VAT)",
		"ar" => "ضریبة القیمة المضافة) الفرعي للبند (متضمنـًا المجموع",

		"@map" => static function ($value, $row) {
			return zatcaNumberFormat(getLineItemTotal($row)) . F_UNIT;
		}
	]
];


function symmetricTableStyles($selector, $repeat = 1)
{
	$styles = [];
	for ($i = 0; $i < $repeat; $i++) {
		$first = $i * 3 + 1;
		$second = $i * 3 + 2;
		$last = $i * 3 + 3;

		$styles[] = <<<CSS
			{$selector} td:nth-child({$first}) {
				text-align: left;
				font-weight: bold;
			}
			{$selector} td:nth-child({$second}) {
				text-align: center;
			}
			{$selector} td:nth-child({$last}) {
				text-align: right;
				font-weight: bold;
			}
		CSS;
	}

	$styles =  implode("\n", $styles);
	return $styles;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Tax Invoice</title>

	<style>
		table {
			border-collapse: collapse
		}


		.title {
			text-align: center;
		}

		.invoice_body tbody td {
			width: 16.66%;
		}

		.line_items tbody td {
			width: 12.5%;
		}

		.line_items td {
			text-align: center;
		}

		.totals tbody td {
			width: 33.33%;
		}

		<?= symmetricTableStyles('.invoice_info') ?>
		/*  */
		<?= symmetricTableStyles('.invoice_body', 2) ?>
		/*  */
		<?= symmetricTableStyles('.totals tbody', 2) ?>
		/*  */
	</style>

</head>

<body>
	<h1 class="title">
		<span>Tax Invoice</span>
		-
		<span>الفاتورة الضريبية</span>
	</h1>

	<table width="100%">
		<tr>
			<td>
				<table class="invoice_info" <?= $tableAttrs ?>>
					<tr>
						<td>Invoice Number</td>
						<td><?= $invoice->getSerialNumber(); ?></td>
						<td>رقم الفاتورة</td>
					</tr>
					<tr>
						<td>Invoice Issue Date</td>
						<td><?= $invoice->getFormattedIssueDate(); ?></td>
						<td>تاریخ إصدار الفاتورة</td>
					</tr>
					<tr>
						<td>Date of Supply</td>
						<td><?= $invoice->getDeliveryDate(); ?></td>
						<td>التورید تاریخ</td>
					</tr>
				</table>
			</td>
			<td style="text-align: right">
				<img src="<?= htmlentities($qr) ?>" alt="QR Code">
			</td>
		</tr>
	</table>

	<br>

	<table class="invoice_body" <?= $tableAttrs ?>>
		<thead>
			<tr>
				<th colspan="3">
					<span>Seller</span>
					-
					<span>تاجر</span>
				</th>

				<th colspan="3">
					<span>Buyer</span>
					-
					<span>مشتر</span>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Name</td>
				<td><?= $invoice->getVATName(); ?></td>
				<td>اسم</td>
				<td>Name</td>
				<td><?= $invoice->getCustomerInfo("buyer_name") ?? ''; ?></td>
				<td>اسم</td>
			</tr>
			<tr>
				<td>Building No.</td>
				<td><?= $invoice->getEGS()["location"]["building"]; ?></td>
				<td>No. بني</td>
				<td>Building No.</td>
				<td><?= $invoice->getCustomerInfo("building") ?? ''; ?></td>
				<td>No. بني</td>
			</tr>
			<tr>
				<td>Street Name</td>
				<td><?= $invoice->getEGS()["location"]["street"]; ?></td>
				<td>اسم الشارع</td>
				<td>Street Name</td>
				<td><?= $invoice->getCustomerInfo("street") ?? ''; ?></td>
				<td>اسم الشارع</td>
			</tr>
			<tr>
				<td>District</td>
				<td><?= $invoice->getEGS()["location"]["city_subdivision"]; ?></td>
				<td>المنطقة</td>
				<td>District</td>
				<td><?= $invoice->getCustomerInfo("city_subdivision") ?? ''; ?></td>
				<td>المنطقة</td>
			</tr>
			<tr>
				<td>City</td>
				<td><?= $invoice->getEGS()["location"]["city"]; ?></td>
				<td>المدينة</td>
				<td>City</td>
				<td><?= $invoice->getCustomerInfo("city") ?? ''; ?></td>
				<td>المدينة</td>
			</tr>
			<tr>
				<td>Country</td>
				<td><?= htmlentities($invoice->getEGS()["location"]["country_sub_entity"] ?? '') ?></td>
				<td>البلد</td>
				<td>Country</td>
				<td><?= htmlentities($invoice->getCustomerInfo("country_sub_entity") ?? '') ?></td>
				<td>البلد</td>
			</tr>
			<tr>
				<td>Postal Code</td>
				<td><?= htmlentities($invoice->getEGS()["location"]["postal_zone"]); ?></td>
				<td>الرمز البريدي</td>
				<td>Postal Code</td>
				<td><?= htmlentities($invoice->getCustomerInfo("postal_zone") ?? ''); ?></td>
				<td>الرمز البريدي</td>
			</tr>
			<tr>
				<td>Additional No.</td>
				<td><?= htmlentities($invoice->getEGS()["location"]["additional_no"] ?? ''); ?></td>
				<td>رقم اضافي</td>
				<td>Additional No.</td>
				<td><?= htmlentities($invoice->getCustomerInfo("additional_no") ?? ''); ?></td>
				<td>رقم اضافي</td>
			</tr>
			<tr>
				<td>VAT Number</td>
				<td><?= htmlentities($invoice->getVATNumber()) ?></td>
				<td>رقم الضريبة</td>
				<td>VAT Number</td>
				<td><?= htmlentities($invoice->getCustomerInfo("vat_number") ?? '') ?></td>
				<td>رقم الضريبة</td>
			</tr>
			<tr>
				<td>Other Seller ID</td>
				<td>TODO</td>
				<td>رقم المبيعات الأخرى</td>
				<td>Other Seller ID</td>
				<td>TODO</td>
				<td>رقم المبيعات الأخرى</td>
			</tr>
		</tbody>
	</table>

	<br />
	<br />
	<br />

	<table class="line_items" <?= $tableAttrs ?>>
		<thead>
			<tr>
				<th colspan="4" style="text-align: left;">Line Items</th>
				<th colspan="4" style="text-align: right;">البنود</th>
			</tr>
			<tr>
				<?php foreach ($columns as $columnName => list("en" => $columnTitleEn, "ar" => $columnTitleAr)): ?>
					<th>
						<span><?= $columnTitleEn ?></span>
						<br>
						<span><?= $columnTitleAr ?></span>
					</th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($invoice->getLineItems() as $lineItem): ?>
				<tr>
					<?php foreach ($columns as $columnName => $column): ?>
						<?php if (isset($column["@map"])): ?>
							<td><?= $column["@map"]($lineItem[$columnName] ?? null, $lineItem) ?></td>
						<?php else: ?>
							<td><?= $lineItem[$columnName] ?></td>
						<?php endif; ?>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<br />
	<br />
	<br />


	<table class="totals" <?= $tableAttrs ?>>
		<thead>
			<tr>
				<th colspan="3">
					<span>Total Amounts</span>
					-
					<span>المبالغ الإجمالیة</span>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Total (Excluding VAT)</td>
				<td><?= zatcaNumberFormat($invoice->computeTotalSubtotal()) ?><?= F_UNIT ?></td>
				<td>الإجمالي (باستثناء ضریبة القیمة المضافة)</td>
			</tr>
			<tr>
				<td>Discounts</td>
				<td><?= zatcaNumberFormat($invoice->computeTotalDiscounts()) ?><?= F_UNIT ?></td>
				<td>مجموع الخصومات</td>
			</tr>
			<tr>
				<td>Total Taxable Amount (Excluding VAT)</td>
				<td><?= zatcaNumberFormat($invoice->computeTotalSubtotal()) ?><?= F_UNIT ?></td>
				<td>المبلغ الخاضع للضریبة (باستثناء ضریبة القیمة المضافة إجمالي)</td>
			</tr>
			<tr>
				<td>Total VAT</td>
				<td><?= zatcaNumberFormat($invoice->computeTotalTaxes()) ?><?= F_UNIT ?></td>
				<td>الضريبة المضافة</td>
			</tr>
			<tr>
				<td>Total Amount Due</td>
				<td><?= zatcaNumberFormat($invoice->computeTotal()) ?><?= F_UNIT ?></td>
				<td>إجمالي المبلغ المستحق</td>
			</tr>
		</tbody>
	</table>
</body>

</html>