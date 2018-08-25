<?php
/**
 * Typecho update assistant.
 *
 * @package UpdateAssistant
 * @author  mrgeneral
 * @version 1.0.1
 * @link    https://www.chengxiaobai.cn
 */

abstract class Base
{
    protected static $instance;

    protected $handler;

    protected $defaultHeaders = [
        'User-Agent'    => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3_CXB) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36',
        'Referer'       => 'https://github.com/typecho/typecho',
        'Cache-Control' => 'max-age=0',
    ];

    public function __construct()
    {
        if (!extension_loaded('zip')) {
            throw new Exception('Not found zip extension!');
        }

        if (function_exists('curl_exec')) {
            $this->handler = 'curlHandler';
        } elseif (ini_get('allow_url_fopen')) {
            $this->handler = 'streamHandler';
        } else {
            throw new Exception('Requires cURL or set allow_url_fopen = 1 in your php.ini!');
        }
    }

    protected static function getInstance()
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }

        return static::$instance = new static;
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([static::getInstance(), $name], $arguments);
    }

    protected function curlHandler($url, $storedFileRealPath = '', $options = [], $headers = [])
    {
        $defaultOptions = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => $this->buildHeaders(array_merge($this->defaultHeaders, $headers)),
        ];

        if (!empty($storedFileRealPath)) {
            $fileHandler                  = fopen($storedFileRealPath, 'wb+');
            $defaultOptions[CURLOPT_FILE] = $fileHandler;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $options + $defaultOptions);
        $result = curl_exec($curl);

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
            curl_close($curl);
            throw new Exception('Request failed, please try again!');
        }

        curl_close($curl);

        return empty($storedFileRealPath) ? $result : fclose($fileHandler);
    }

    protected function streamHandler($url, $storedFileRealPath = '', $options = [], $headers = [])
    {
        $context = [
            'http' => [
                'header'           => implode("\r\n", $this->buildHeaders(array_merge($this->defaultHeaders, $headers))),
                'protocol_version' => '1.1',
                'ignore_errors'    => true,
                'timeout'          => 60,
                'follow_location'  => 5,
            ],
        ];

        $streamHandler = fopen($url, 'rb', null, stream_context_create(array_merge($context, $options)));
        $result        = empty($storedFileRealPath) ? stream_get_contents($streamHandler) : file_put_contents($storedFileRealPath, $streamHandler, LOCK_EX);
        fclose($streamHandler);

        return $result;
    }

    private function buildHeaders($headers = [])
    {
        $result = [];

        foreach ($headers as $name => $value) {
            $result[] = "$name: $value";
        }

        return $result;
    }
}
