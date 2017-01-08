<?php
/**
 * 虾米音乐类
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
class Remix_Music_Xiami implements Remix_Music_Interface
{
    /** api地址 */
    const API_URL = 'http://m.xiami.com/web/get-songs?type=0';

    /**
     * 因为Typecho默认没有HEAD模式
     * 只能靠这开关设置只取头信息
     */
    private $isCookie;

    /** TOKEN */
    private static $token;

    /**
     * 构造方法
     *
     * @param string $token
     */
    public function __construct($token = NULL)
    {
        $this->setToken($token);
    }

    /**
     * 获取歌曲
     *
     * @param string $id
     * @param string $data
     */
    public function song($id)
    {
        $url = static::API_URL . '&rtype=song&id=' . $id . '&_xiamitoken=' . static::$token;
        $this->isCookie = false;
        $song = $this->http($url);

        if (is_null($song)) {
            return;
        }

        return $song[0];
    }

    /**
     * 获取列表
     *
     * @param string $id
     * @return string
     */
    public function songs($ids)
    {
        $list = array();

        foreach ($ids as $id) {
            $list[] = $this->song($id);
        }

        return $list;
    }

    /**
     * 获取专辑
     *
     * @param string $id
     */
    public function album($id)
    {
        $url = static::API_URL . '&rtype=album&id=' . $id . '&_xiamitoken=' . static::$token;
        $this->isCookie = false;
        $album = $this->http($url);

        if (is_null($album)) {
            return;
        }

        return $album;
    }

    /**
     * 获取精选集
     *
     * @param string $id
     */
    public function collect($id)
    {
        $url = static::API_URL . '&rtype=collect&id=' . $id . '&_xiamitoken=' . static::$token;
        $this->isCookie = false;
        $collect = $this->http($url);

        if (is_null($collect)) {
            return;
        }

        return $collect;
    }

    /**
     * 请求
     *
     * @param string $url
     */
    public function http($url)
    {
        $client = Typecho_Http_Client::get();
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_1_2 like Mac OS X) AppleWebKit/537.51.2 (KHTML, like Gecko) Version/7.0 Mobile/11D257 Safari/9537.53';

        /* 设置Cookie */
        if (static::$token) {
            $client->setHeader('Cookie', '_xiamitoken=' . static::$token . '; visit=1');
        }

        $client->setHeader('User-Agent', $userAgent)
        ->setHeader('Proxy-Connection', 'keep-alive')
        ->setHeader('X-Requested-With', 'XMLHttpRequest')
        ->setHeader('X-FORWARDED-FOR', '42.156.140.238')
        ->setHeader('CLIENT-IP', '42.156.140.238')
        ->setHeader('Referer', 'http://m.xiami.com')
        ->setTimeout(50)
        ->send($url);

        if (200 === $client->getResponseStatus()) {
            /* 获取token时只返回头信息的Cookie */
            if ($this->isCookie) {
                $responseCookie = $client->getResponseHeader('set-cookie');
                $cookies = explode(';', $responseCookie);

                return $cookies;
            }

            $response = Json::decode($client->getResponseBody(), true);
            if (isset($response['status']) && 'ok' == $response['status'] && !empty($response['data'])) {
                return $response['data'];
            }
            return;
        }

        return;
    }

    /**
     * 设置TOKEN
     *
     * @param string $token token值
     */
    private function setToken($token = NULL)
    {
        /* token存在返回 */
        if ($token) {
            static::$token = $token;
            $this->isCookie = false;

            return;
        }

        $this->isCookie = true;
        $cookies = $this->http('http://m.xiami.com');

        foreach ($cookies as $cookie) {
            list($key, $value) = explode('=', $cookie);

            if ('_xiamitoken' == $key) {
                static::$token = $value;
                break;
            }
        }
    }

    /**
     * 获取TOKEN
     *
     * @return string token值
     */
    public function getToken()
    {
        if (empty(static::$token)) {
            $this->setToken();
        }
        return static::$token;
    }
}
