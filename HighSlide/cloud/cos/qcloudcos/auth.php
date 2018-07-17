<?php
/**
 * Signature create related functions for authenticating with cos system.
 */

namespace qcloudcos;

/**
 * Auth class for creating reusable or nonreusable signature.
 */
class Auth {
    // Secret id or secret key is not valid.
    const AUTH_SECRET_ID_KEY_ERROR = -1;

    /**
     * Create reusable signature for listDirectory in $bucket or uploadFile into $bucket.
     * If $filepath is not null, this signature will be binded with this $filepath.
     * This signature will expire at $expiration timestamp.
     * Return the signature on success.
     * Return error code if parameter is not valid.
     */
    public static function createReusableSignature($expiration, $bucket, $filepath = null) {
        $conf_object = new Conf();
        $appId = $conf_object::$APPID;
        $secretId = $conf_object::$SECRET_ID;
        $secretKey = $conf_object::$SECRET_KEY;

        if (empty($appId) || empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        if (empty($filepath)) {
            return self::createSignature($appId, $secretId, $secretKey, $expiration, $bucket, null);
        } else {
            if (preg_match('/^\//', $filepath) == 0) {
                $filepath = '/' . $filepath;
            }

            return self::createSignature($appId, $secretId, $secretKey, $expiration, $bucket, $filepath);
        }
    }

    /**
     * Create nonreusable signature for delete $filepath in $bucket.
     * This signature will expire after single usage.
     * Return the signature on success.
     * Return error code if parameter is not valid.
     */
    public static function createNonreusableSignature($bucket, $filepath) {
        $conf_object = new Conf();
        $appId = $conf_object::$APPID;
        $secretId = $conf_object::$SECRET_ID;
        $secretKey = $conf_object::$SECRET_KEY;

        if (empty($appId) || empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        if (preg_match('/^\//', $filepath) == 0) {
            $filepath = '/' . $filepath;
        }
        $fileId = '/' . $appId . '/' . $bucket . $filepath;

        return self::createSignature($appId, $secretId, $secretKey, 0, $bucket, $fileId);
    }

    /**
     * A helper function for creating signature.
     * Return the signature on success.
     * Return error code if parameter is not valid.
     */
    private static function createSignature(
            $appId, $secretId, $secretKey, $expiration, $bucket, $fileId) {
        if (empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        $now = time();
        $random = rand();
        $plainText = "a=$appId&k=$secretId&e=$expiration&t=$now&r=$random&f=$fileId&b=$bucket";
        $bin = hash_hmac('SHA1', $plainText, $secretKey, true);
        $bin = $bin.$plainText;

        $signature = base64_encode($bin);

        return $signature;
    }
}
