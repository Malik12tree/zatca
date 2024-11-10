<?php

namespace Malik12tree\ZATCA;

class InvoiceType
{
	const INVOICE = 388;
	const DEBIT_NOTE = 383;
	const CREDIT_NOTE = 381;

	public static function cases()
	{
		return [
			self::INVOICE,
			self::DEBIT_NOTE,
			self::CREDIT_NOTE,
		];
	}
}
