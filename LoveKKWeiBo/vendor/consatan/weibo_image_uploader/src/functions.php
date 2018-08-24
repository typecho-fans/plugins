<?php

/*
 * This file is part of the Consatan\Weibo\ImageUploader package.
 *
 * (c) Chopin Ngo <consatan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Consatan\Weibo\ImageUploader;

/**
 * asn1_length
 *
 * @param int $length
 * @return string
 * @link https://github.com/yangyuan/weibo-publisher/blob/520dbc24f775db8caa3b48ea6dbbc838e5142850/weibo.php#L92
 */
function asn1_length($length)
{
    if ($length <= 0x7f) {
        return chr($length);
    }

    $tmp = ltrim(pack('N', $length), chr(0));
    return pack('Ca*', 0x80 | strlen($tmp), $tmp);
}

/**
 * rsa_pkey
 *
 * @param string $exponent
 * @param string $modulus
 * @return string
 * @link https://github.com/yangyuan/weibo-publisher/blob/520dbc24f775db8caa3b48ea6dbbc838e5142850/weibo.php#L99
 */
function rsa_pkey($exponent, $modulus)
{
    $pkey = pack('Ca*a*', 0x02, asn1_length(strlen($modulus)), $modulus)
        . pack('Ca*a*', 0x02, asn1_length(strlen($exponent)), $exponent);

    $pkey = pack('Ca*a*', 0x30, asn1_length(strlen($pkey)), $pkey);
    $pkey = pack('Ca*', 0x00, $pkey);
    $pkey = pack('Ca*a*', 0x03, asn1_length(strlen($pkey)), $pkey);
    $pkey = pack('H*', '300d06092a864886f70d0101010500') . $pkey;
    $pkey = pack('Ca*a*', 0x30, asn1_length(strlen($pkey)), $pkey);

    return "-----BEGIN PUBLIC KEY-----\r\n" . chunk_split(base64_encode($pkey)) . '-----END PUBLIC KEY-----';
}

/**
 * rsa_encrypt
 *
 * @param string $message
 * @param string $exponent
 * @param string $pubkey
 * @return string
 * @link https://github.com/yangyuan/weibo-publisher/blob/520dbc24f775db8caa3b48ea6dbbc838e5142850/weibo.php#L114
 */
function rsa_encrypt($message, $exponent, $pubkey)
{
    openssl_public_encrypt($message, $result, rsa_pkey(hex2bin($exponent), hex2bin($pubkey)), OPENSSL_PKCS1_PADDING);
    return $result;
}
