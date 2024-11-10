<?php

namespace Malik12tree\ZATCA;

class InvoicePaymentMethod
{
	const CASH = '10';
	const CREDIT = '30';
	const BANK_ACCOUNT = '42';
	const BANK_CARD = '48';

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
