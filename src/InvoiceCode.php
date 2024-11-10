<?php

namespace Malik12tree\ZATCA;

class InvoiceCode
{
	const TAX = '0100000';
	const SIMPLE = '0200000';

	public static function cases()
	{
		return [
			self::TAX,
			self::SIMPLE,
		];
	}
}
