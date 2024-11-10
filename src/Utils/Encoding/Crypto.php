<?php

namespace Malik12tree\ZATCA\Utils\Encoding;

class Crypto
{
	public static function uuid4()
	{
		$b = random_bytes(16);
		$b[6] = chr(ord($b[6]) & 0x0f | 0x40);
		$b[8] = chr(ord($b[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
	}
	public static function hashSHA256($data)
	{
		return hash('sha256', $data);
	}
	public static function hashSHA256HighNibble($data)
	{
		return pack('H*', hash('sha256', $data));
	}
	public static function signSHA256($data, $privateKey)
	{
		openssl_sign($data, $signature, $privateKey, 'sha256');
		return $signature;
	}

	public static function generateSecp256k1KeyPair()
	{
		$res = openssl_pkey_new([
			'config' => getenv('OPENSSL_CONF'),
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name' => 'secp256k1',
		]);

		openssl_pkey_export($res, $privateKey);
		$publicKey = openssl_pkey_get_details($res)['key'];
		return [$privateKey, $publicKey];
	}
	public static function generateEcdsaWithSHA256($config = null)
	{
		$res = openssl_pkey_new([
			'config' => $config || getenv('OPENSSL_CONF'),
			'default_md' => "sha256",
		]);

		openssl_pkey_export($res, $privateKey);
		$publicKey = openssl_pkey_get_details($res)['key'];
		return [$privateKey, $publicKey];
	}

	public static function getCertificateSignature($certificate)
	{
		$res = openssl_x509_read($certificate);
		openssl_x509_export($res, $out, false);

		$out = explode('Signature Algorithm:', $out);
		$out = explode('-----BEGIN CERTIFICATE-----', $out[2]);
		$out = explode("\n", $out[0]);
		$out = $out[1] . $out[2] . $out[3] . $out[4];
		$out = str_replace([':', ' ', 'SignatureValue'], '', $out);

		return pack('H*', $out);
	}
	public static function getCertificateInfo($certificate)
	{
		$certificate = Crypto::setCertificateTitle($certificate, "CERTIFICATE");
		$certificateHash = base64_encode(Crypto::hashSHA256($certificate));

		/** @var any */
		$x509 = openssl_x509_parse($certificate);

		$res = openssl_get_publickey($certificate);
		$cert = openssl_pkey_get_details($res);

		$issuer = "CN=" . implode(', ', array_reverse($x509['issuer']));
		$serialNumber = $x509['serialNumber'];

		$publicKey = Crypto::cleanCertificate($cert['key']);

		$signature = Crypto::getCertificateSignature($certificate);

		return [
			"hash" => $certificateHash,
			"issuer" => $issuer,
			"serialNumber" => $serialNumber,
			"publicKey" => $publicKey,
			"signature" => $signature,
		];
	}

	public static function cleanCertificate($certificate)
	{
		return trim(preg_replace('/(-----[^-]+)-----/', '', $certificate));
	}
	public static function wrapCertificate($certificate, $title)
	{
		return "-----BEGIN $title-----\n" . $certificate . "\n-----END $title-----";
	}
	public static function setCertificateTitle($certificate, $title)
	{
		return self::wrapCertificate(self::cleanCertificate($certificate), $title);
	}
}
