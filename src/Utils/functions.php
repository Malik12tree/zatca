<?php

namespace Malik12tree\ZATCA\Utils;

use Malik12tree\ZATCA\Invoice\Enums\InvoiceVATCategory;

if (!function_exists('zatcaNumberFormat')) {
    function zatcaNumberFormat($number)
    {
        return number_format($number, 2, '.', '');
    }
}

if (!function_exists('getLineItemDiscounts')) {
    function getLineItemDiscounts($item)
    {
        if (!isset($item['discounts'])) {
            return 0;
        }
        if (!is_array($item['discounts'])) {
            return 0;
        }

        return array_reduce($item['discounts'], static function ($acc, $item) {
            return $acc + $item['amount'];
        }, 0);
    }
}
if (!function_exists('getLineItemSubtotal')) {
    function getLineItemSubtotal($item)
    {
        return $item['quantity'] * ($item['tax_exclusive_price'] - getLineItemDiscounts($item));
    }
}

if (!function_exists('getLineItemSubtotalExcludingDiscount')) {
    function getLineItemSubtotalExcludingDiscount($item)
    {
        return $item['quantity'] * $item['tax_exclusive_price'];
    }
}

if (!function_exists('getLineItemTaxes')) {
    function getLineItemTaxes($item)
    {
        // BR-KSA-DEC-02
        return getLineItemSubtotal($item) * $item['vat_percent'];
    }
}
if (!function_exists('getLineItemTotal')) {
    function getLineItemTotal($item)
    {
        return getLineItemSubtotal($item) + getLineItemTaxes($item);
    }
}

if (!function_exists('getLineItemVATCategory')) {
    function getLineItemVATCategory($item)
    {
        if (0.15 === $item['vat_percent'] || 0.05 === $item['vat_percent']) {
            return [
                'percent' => (int) ($item['vat_percent'] * 100),
                'category' => 'S',
                'reason' => null,
                'reason_code' => null,
            ];
        }

        if ($item['vat_percent'] > 0) {
            throw new \Exception('Invalid VAT Percent');
        }

        if (!InvoiceVATCategory::isValidZeroValue($item['vat_category']['category'] ?? null)) {
            throw new \Exception('Invalid VAT Category');
        }

        return [
            'percent' => 0,
            'category' => $item['vat_category']['category'],
            'reason' => $item['vat_category']['reason'] ?? null,
            'reason_code' => $item['vat_category']['reason_code'] ?? null,
        ];
    }
}
