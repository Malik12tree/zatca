<?php

namespace Malik12tree\ZATCA\Utils;

if (!function_exists('zatcaNumberFormat')) {
	function zatcaNumberFormat($number)
	{
		return number_format($number, 2, '.', '');
	}
}

if (!function_exists('getLineItemDiscounts')) {
	function getLineItemDiscounts($item)
	{
		if (!isset($item['discounts'])) return 0;
		if (!is_array($item['discounts'])) return 0;

		return array_reduce($item['discounts'], static function ($acc, $item) {
			return $acc + $item['amount'];
		}, 0);
	}
}
if (!function_exists('getLineItemSubtotal')) {
	function getLineItemSubtotal($item)
	{
		$discounts = getLineItemDiscounts($item);
		return ($item['tax_exclusive_price'] * $item['quantity']) - $discounts;
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
