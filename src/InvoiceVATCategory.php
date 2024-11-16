<?php

namespace Malik12tree\ZATCA;

use Malik12tree\ZATCA\Utils\Enum7;

/**
 * 11.2.4 VAT categories code From
 * https://istitlaa.ncc.gov.sa/ar/finance/gazt/einvoicingimplementationresolution/Documents/ZATCA_Electronic_Invoice_XML_Implementation_Standard.pdf
 */
class InvoiceVATCategory extends Enum7
{
	const STANDARD = "S";
	const ZERO_RATED = "Z";
	const EXEMPT = "E";
	const OUT_OF_SCOPE = "O";

	static function isValidZeroValue($value)
	{
		return $value !== self::STANDARD && self::isValidValue($value);
	}
}
