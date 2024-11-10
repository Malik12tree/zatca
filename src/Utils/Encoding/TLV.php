<?php

namespace Malik12tree\ZATCA\Utils\Encoding;

use Exception;

class TLV
{
	public static function encode($tag, $value)
	{
		$value = strval($value);
		$length = strlen($value);

		// TODO: Test if length should be calculated by UTF-8 length or by byte length
		if ($length > 0xFF) {
			throw new Exception("Data field '$value' is too long! (max: 255)");
		}

		$tagHex = chr($tag);
		$lengthHex = chr($length);
		$valueHex = mb_convert_encoding($value, "UTF-8");

		return "$tagHex$lengthHex$valueHex";
	}
	public static function encodeAll($valueByTag)
	{
		$hex = "";
		foreach ($valueByTag as $tag => $value) {
			$hex .= self::encode($tag, $value);
		}

		return $hex;
	}
}
