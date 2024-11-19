<?php

use Malik12tree\ZATCA\Invoice;

use function Malik12tree\ZATCA\Utils\getLineItemDiscount;
use function Malik12tree\ZATCA\Utils\getLineItemTotal;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormat;

/** @var Invoice $invoice */
$tableAttrs = 'cellpadding="5px" autosize="1" border="1" width="100%" style="overflow: wrap"';

const UNIT = 'ريال';
const F_UNIT = ' '.UNIT;

$lineItemsTable = [
    'name' => [
        'ar' => 'السلع والخدمات',
    ],
    'quantity' => [
        'ar' => 'الكمية',
    ],
    'unit_price' => [
        'ar' => 'سعر الوحدة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormat($value).F_UNIT;
        },
    ],
    'discount' => [
        'ar' => 'الخصم',
        '@map' => static function ($value, $row) {
            return zatcaNumberFormat(getLineItemDiscount($row)).F_UNIT;
        },
    ],
    'vat_percent' => [
        'en' => 'VAT Percentage',
        'ar' => 'نسبة للضريبة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormat($value * 100).'%';
        },
    ],
    'total' => [
        'ar' => 'المجموع',
        '@map' => static function ($value, $row) {
            return zatcaNumberFormat(getLineItemTotal($row)).F_UNIT;
        },
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Tax Invoice</title>

	<style>
		table {
			border-collapse: collapse;
			text-align: center;
		}

		#totals td:nth-child(1) {
			width: 75%;
			text-align: start;
		}
		#totals td:nth-child(2) {
			width: 25%;
		}
	</style>
</head>
<body dir="rtl">
	<h1 class="title" align="center">
		فاتورة ضريبية مبسطة
	</h1>
	<h2 align="center"><?= $invoice->getEGS()['vat_name']; ?></h2>
	<h3 align="center">
		<span>رقم الفاتورة</span>
		<span>:</span>
		<?= $invoice->getSerialNumber(); ?>
	</h3>


	<p>
		<b>تاریخ إصدار الفاتورة</b>
		<span>:</span>
		<?= $invoice->getFormattedIssueDate(); ?>
	</p>
	<p>
		<b>رقم تسجيل ضريبة القيمة المضافة</b>
		<span>:</span>
		<?= $invoice->getEGS()['crn_number']; ?>
	</p>
	
	<table <?= $tableAttrs; ?> id="line-items">
		<thead>
			<tr>
				<?php foreach ($lineItemsTable as $columnName => list('ar' => $columnTitleAr)) { ?>
					<th>
						<span><?= $columnTitleAr; ?></span>
					</th>
				<?php } ?>
			</tr>
			<tbody>
				<?php foreach ($invoice->getLineItems() as $lineItem) { ?>
					<tr>
						<?php foreach ($lineItemsTable as $columnName => $column) { ?>
							<?php if (isset($column['@map'])) { ?>
								<td><?= $column['@map']($lineItem[$columnName] ?? null, $lineItem); ?></td>
							<?php } else { ?>
								<td><?= $lineItem[$columnName]; ?></td>
							<?php } ?>
						<?php } ?>
					</tr>
				<?php } ?>
			</tbody>
		</thead>
	</table>

	<br />
	<br />

	<table <?= $tableAttrs; ?> id="totals">
		<tr>
			<td>المبلغ الخاضع للضریبة</td>
			<td><?= zatcaNumberFormat($invoice->computeTotalSubtotal()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>الضريبة المضافة</td>
			<td><?= zatcaNumberFormat($invoice->computeTotalTaxes()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>إجمالي المبلغ المستحق</td>
			<td><?= zatcaNumberFormat($invoice->computeTotal()); ?><?= F_UNIT; ?></td>
		</tr>
	</table>
	
	<br />
	<hr />
	<br />

	<table style="width:100%;">
		<tr>
			<td align="center">
				<img src="<?= htmlentities($qr); ?>" alt="QR Code" />
			</td>
		</tr>
	</table>

</body>
</html>