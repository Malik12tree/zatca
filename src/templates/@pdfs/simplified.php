<?php

use Malik12tree\ZATCA\Invoice;
use Malik12tree\ZATCA\Invoice\Enums\InvoiceType;

use function Malik12tree\ZATCA\Utils\getLineItemDiscount;
use function Malik12tree\ZATCA\Utils\getLineItemTotal;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatFree;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatLong;
use function Malik12tree\ZATCA\Utils\zatcaNumberFormatShort;

require_once __DIR__.'/data.php';

/** @var Invoice $invoice */
$tableAttrs = 'cellpadding="5px" autosize="1" border="1" width="100%"';

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
            return zatcaNumberFormatFree($value).F_UNIT;
        },
    ],
    'discount' => [
        'ar' => 'الخصم',
        '@map' => static function ($value, $row) {
            return +zatcaNumberFormatLong(getLineItemDiscount($row)).F_UNIT;
        },
    ],
    'vat_percent' => [
        'en' => 'VAT Percentage',
        'ar' => 'نسبة للضريبة',

        '@map' => static function ($value, $row) {
            return zatcaNumberFormatFree($value * 100).'%';
        },
    ],
    'total' => [
        'ar' => 'المجموع',
        '@map' => static function ($value, $row) {
            return zatcaNumberFormatShort(getLineItemTotal($row)).F_UNIT;
        },
    ],
];

$titleByType = [
    'en' => [
        InvoiceType::CREDIT_NOTE => 'Simplified Tax Invoice (Credit Note)',
        InvoiceType::DEBIT_NOTE => 'Simplified Tax Invoice (Debit Note)',
        InvoiceType::INVOICE => 'Simplified Tax Invoice',
    ],
    'ar' => [
        InvoiceType::CREDIT_NOTE => 'فاتورة ضريبية مبسطة (إشعار دائن)&rlm;',
        InvoiceType::DEBIT_NOTE => 'فاتورة ضريبية مبسطة (إشعار مدين)&rlm;',
        InvoiceType::INVOICE => 'فاتورة ضريبية مبسطة',
    ],
];
?>
<div class="invoice-render" dir="rtl">
	<style>
		.invoice-render table {
			border-collapse: collapse;
			text-align: center;
		}

		.invoice-render__totals td:nth-child(1) {
			width: 75%;
			text-align: start;
		}
		.invoice-render__totals td:nth-child(2) {
			width: 25%;
		}
	</style>
	<h1 align="center">
		<?= $titleByType['ar'][$invoice->getType()]; ?>
	</h1>
	<h2 align="center" style="margin: 0;"><?= $invoice->getEGS()['vat_name']; ?></h2>
	<h4 align="center" style="margin: 0;color: grey;"><?= $invoice->getFormattedEGSLocation(); ?></h4>
	<h3 align="center" style="margin: 0;">
		<span>رقم الفاتورة</span>
		<span>:</span>
		<?= $invoice->getSerialNumber(); ?>
	</h3>


	<p style="margin: 0;">
		<b>تاریخ إصدار الفاتورة</b>
		<span>:</span>
		<?= $invoice->getFormattedIssueDate(); ?>
	</p>
	<p style="margin: 0;">
		<b>رقم تسجيل ضريبة القيمة المضافة</b>
		<span>:</span>
		<?= $invoice->getEGS()['crn_number']; ?>
	</p>
	<?php if ($invoice->getPaymentMethod()) { ?>
		<p style="margin: 0;">
			<b>طريقة الدفع</b>
			<span>:</span>
			<?= $paymentTitleByMethod['ar'][$invoice->getPaymentMethod()]; ?>
		</p>
	<?php } ?>
	
	<table <?= $tableAttrs; ?>>
		<thead>
			<tr>
				<?php foreach ($lineItemsTable as $columnName => list('ar' => $columnTitleAr)) { ?>
					<th>
						<span><?= $columnTitleAr; ?></span>
					</th>
				<?php } ?>
			</tr>
		</thead>
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
	</table>

	<br />
	<br />

	<table <?= $tableAttrs; ?> class="invoice-render__totals">
		<tr>
			<td>المبلغ الخاضع للضریبة</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotalSubtotal()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>الضريبة المضافة</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotalTaxes()); ?><?= F_UNIT; ?></td>
		</tr>
		<tr>
			<td>إجمالي المبلغ المستحق</td>
			<td><?= zatcaNumberFormatShort($invoice->computeTotal()); ?><?= F_UNIT; ?></td>
		</tr>
	</table>

	<?php if ($invoice->getCancellation()) { ?>
		<br />
		<table <?= $tableAttrs; ?>>
			<tr>
				<th colspan="2">المرجع</th>
			</tr>
			<tr>
				<td>رقم الفاتورة المرجعية</td>
				<td><?= $invoice->getCancellation('serial_number'); ?></td>
			</tr>
			<tr>
				<td>سبب إصدار الإشعار </td>
				<td><?= $invoice->getCancellation('reason'); ?></td>
			</tr>
		</table>
	<?php } ?>
	
	<br />
	<hr />
	<br />

	<table style="width:100%;">
		<tr>
			<td align="center">
				<img src="<?= htmlentities($qr); ?>" width="124" height="124" alt="QR Code" />
			</td>
		</tr>
	</table>

</div>
<?php return [
    'mpdf' => [
        'format' => [128, 128 * 1.5],
        'margin_left' => 8,
        'margin_right' => 8,
        'margin_top' => 8,
        'margin_bottom' => 8,
    ],
];
