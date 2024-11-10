<?php

namespace Malik12tree\ZATCA\Utils;

if (!function_exists('zatcaNumberFormat')) {
	function zatcaNumberFormat($number)
	{
		return number_format($number, 2, '.', '');
	}
}
