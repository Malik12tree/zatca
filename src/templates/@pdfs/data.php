<?php

use Malik12tree\ZATCA\Invoice\Enums\InvoicePaymentMethod;

$paymentTitleByMethod = [
    'en' => [
        InvoicePaymentMethod::CREDIT => 'Credit',
        InvoicePaymentMethod::CASH => 'Cash',
        InvoicePaymentMethod::BANK_ACCOUNT => 'Bank Account',
        InvoicePaymentMethod::BANK_CARD => 'Bank Card',
    ],
    'ar' => [
        InvoicePaymentMethod::CREDIT => 'ائتمان',
        InvoicePaymentMethod::CASH => 'نقدي',
        InvoicePaymentMethod::BANK_ACCOUNT => 'مصرفي',
        InvoicePaymentMethod::BANK_CARD => 'بطاقة بنك',
    ],
];
