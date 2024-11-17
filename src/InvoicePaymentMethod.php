<?php

namespace Malik12tree\ZATCA;

class InvoicePaymentMethod
{
    public const CASH = '10';
    public const CREDIT = '30';
    public const BANK_ACCOUNT = '42';
    public const BANK_CARD = '48';

    public static function cases()
    {
        return [
            self::CASH,
            self::CREDIT,
            self::BANK_ACCOUNT,
            self::BANK_CARD,
        ];
    }
}
