<?php

use function Malik12tree\ZATCA\Utils\zatcaNumberFormat;

?>
<cac:LegalMonetaryTotal>
	<!-- BR-DEC-09 -->
	<cbc:LineExtensionAmount currencyID="SAR">
		<?= zatcaNumberFormat($TOTAL_SUBTOTAL) ?>
	</cbc:LineExtensionAmount>

	<!-- BR-DEC-12 -->
	<cbc:TaxExclusiveAmount currencyID="SAR">
		<?= zatcaNumberFormat($TOTAL_SUBTOTAL) ?>
	</cbc:TaxExclusiveAmount>

	<!-- BR-DEC-14, BT-112 -->
	<cbc:TaxInclusiveAmount currencyID="SAR">
		<?= $total = zatcaNumberFormat($TOTAL_SUBTOTAL + $TOTAL_TAXES) ?>
	</cbc:TaxInclusiveAmount>

	<cbc:AllowanceTotalAmount currencyID="SAR">
		<?= 0 ?>
	</cbc:AllowanceTotalAmount>

	<cbc:PrepaidAmount currencyID="SAR">
		<?= 0 ?>
	</cbc:PrepaidAmount>

	<!-- BR-DEC-18, BT-112 -->
	<cbc:PayableAmount currencyID="SAR">
		<?= zatcaNumberFormat($TOTAL_SUBTOTAL + $TOTAL_TAXES) ?>
	</cbc:PayableAmount>
</cac:LegalMonetaryTotal>

<?php return $total ?>