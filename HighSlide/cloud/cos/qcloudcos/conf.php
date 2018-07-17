<?php

namespace qcloudcos;

class Conf {
    // Cos php sdk version number.
    const VERSION = 'v4.2.1';
    const API_COSAPI_END_POINT = 'http://region.file.myqcloud.com/files/v2/';

    // Please refer to http://console.qcloud.com/cos to fetch your app_id, secret_id and secret_key.
    const APP_ID = '';
    const SECRET_ID = '';
    const SECRET_KEY = '';

    public static $APPID;
    public static $SECRET_ID;
    public static $SECRET_KEY;

    public function __construct()
    {
        $settings = \Helper::options()->plugin('HighSlide');
        self::$APPID = $settings->cosai;
        self::$SECRET_ID = $settings->cossi;
        self::$SECRET_KEY = $settings->cossk;
    }

    /**
     * Get the User-Agent string to send to COS server.
     */
    public static function getUserAgent() {
        return 'cos-php-sdk-' . self::VERSION;
    }
}
