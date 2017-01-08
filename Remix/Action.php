<?php
/**
 * Remix操作类
 *
 * @package Remix
 * @author shingchi
 * @license GNU General Public License 2.0
 */
class Remix_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /** 缓存标识 */
    const SONG_MARK = '-song-';
    const ALBUM_MARK = '-album-';
    const COLLECT_MARK = '-collect-';

    /** 缓存实例 */
    private static $cache;

    /** 服务实例 */
    private static $server = array();

    /** 服务商标识 */
    private static $serve;

    /** 插件配置 */
    private $config;

    /**
     * 构造方法
     *
     * @access public
     * @var void
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /* 获取插件配置 */
        $options = parent::widget('Widget_Options');
        $this->_config = $options->plugin('Remix');

        /* 初始服务标识 */
        if (isset($request->serve) && !empty($request->serve)) {
            static::$serve = $request->filter('strip_tags', 'trim', 'xss')->serve;
        } else {
            static::$serve = 'xiami';
        }

        /* 判断来路 */
        $siteParts = parse_url($options->siteUrl);
        $refParts = parse_url($request->getReferer());
        $hash = $request->getServer('HTTP_REMIX_HASH');

        if (!$request->isAjax()
            || $siteParts['host'] != $refParts['host']
            || !Typecho_Common::hashValidate($this->_config->hash, $hash))
        {
            throw new Typecho_Widget_Exception(_t('Bad Request!'), 403);
        }
    }

    /**
     * 获取单曲
     *
     * @access public
     * @param string $id 歌曲ID
     * @return string
     */
    public function song($sid = NULL)
    {
        $id = $sid ? $sid : $this->request->filter('strip_tags', 'trim', 'xss')->id;
        $key = static::$serve . static::SONG_MARK . $id;
        $cache = $this->getCacheInstance();
        $message = $cache->get($key);

        if (empty($message)) {
            $server = $this->getServeInstance(static::$serve, $cache);
            $result = $server->song($id);
            $message = array($result);

            if ($message) {
                $cache->set($key, $message);
            }
        }

        if ($sid) {
            return $message;
        }

        $this->response->throwJson($message);
    }

    /**
     * 获取列表
     *
     * @access public
     * @return string
     */
    public function songs()
    {
        $id = $this->request->filter('strip_tags', 'trim', 'xss')->id;
        $ids = array_map('trim', array_unique(explode(',', $id)));
        $message = array();

        foreach ($ids as $id) {
            $result = $this->song($id);
            $message[] = $result[0];
        }

        $this->response->throwJson($message);
    }

    /**
     * 获取专辑
     *
     * @access public
     * @return string
     */
    public function album()
    {
        $id = $this->request->filter('strip_tags', 'trim', 'xss')->id;
        $key = static::$serve . static::ALBUM_MARK . $id;
        $cache = $this->getCacheInstance();
        $message = $cache->get($key);

        if (empty($message)) {
            $server = $this->getServeInstance(static::$serve, $cache);
            $message = $server->album($id);

            if ($message) {
                $cache->set($key, $message);
            }
        }

        $this->response->throwJson($message);
    }

    /**
     * 获取精选集
     *
     * @access public
     * @return string
     */
    public function collect()
    {
        $id = $this->request->filter('strip_tags', 'trim', 'xss')->id;
        $key = static::$serve . static::COLLECT_MARK . $id;
        $cache = $this->getCacheInstance();
        $message = $cache->get($key);

        if (empty($message)) {
            $server = $this->getServeInstance(static::$serve, $cache);
            $message = $server->collect($id);

            if ($message) {
                $cache->set($key, $message);
            }
        }

        $this->response->throwJson($message);
    }

    /**
     * 获取缓存服务实例
     *
     * @access private
     * @return object
     */
    private function getCacheInstance()
    {
        if (empty(static::$cache)) {
            $mode = $this->_config->cacheMode;
            $host = $this->_config->cacheHost;
            $port = $this->_config->cachePort;

            if ($mode == 'file') {
                $cacheDir = __DIR__ . '/temp';
                static::$cache = new Remix_Cache_File($cacheDir);
            } elseif ($mode == 'redis') {
                static::$cache = new Remix_Cache_Redis($host, $port);
            } else {
                static::$cache = new Remix_Cache_Memcache($host, $port);
            }
        }

        return static::$cache;
    }

    /**
     * 获取音乐服务实例
     *
     * @access private
     * @return object
     */
    private function getServeInstance($serve, $cache = NULL)
    {
        if (empty(static::$server[$serve])) {
            if ('nets' == $serve) {
                static::$server['nets'] = new Remix_Music_Nets();
            } else {
                $token = $cache->get('xiamiToken');
                static::$server['xiami'] = new Remix_Music_Xiami($token);

                if (empty($token)) {
                    $server = static::$server['xiami'];
                    $token = $server->getToken();

                    /* token保存10个小时 */
                    $cache->set('xiamiToken', $token, 360000);
                }
            }
        }

        return static::$server[$serve];
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->on($this->request->is('do=song'))->song();
        $this->on($this->request->is('do=list'))->songs();
        $this->on($this->request->is('do=album'))->album();
        $this->on($this->request->is('do=collect'))->collect();
    }
}
