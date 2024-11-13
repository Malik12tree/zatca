<?php

use function Malik12tree\ZATCA\Utils\zatcaNumberFormat;

?>
    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID="SAR"><?= zatcaNumberFormat($TOTAL_SUBTOTAL) ?></cbc:LineExtensionAmount>
        <cbc:TaxExclusiveAmount currencyID="SAR"><?= zatcaNumberFormat($TOTAL_SUBTOTAL) ?></cbc:TaxExclusiveAmount>
        <cbc:TaxInclusiveAmount currencyID="SAR"><?= $total = zatcaNumberFormat($TOTAL_SUBTOTAL + $TOTAL_TAXES) ?></cbc:TaxInclusiveAmount>
        <cbc:PrepaidAmount currencyID="SAR"><?= 0 ?></cbc:PrepaidAmount>
        <cbc:PayableAmount currencyID="SAR"><?= zatcaNumberFormat($TOTAL_SUBTOTAL + $TOTAL_TAXES) ?></cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
<?php return $total ?>