<?php
/**
 * Memcache缓存
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
class Remix_Cache_Memcache implements Remix_Cache_Interface
{
    /**
     * memcache对象
     *
     * @var Memcache
     */
    private $memcache;

    /**
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     */
    public function __construct($host = '127.0.0.1', $port = 11211, $timeout = 30)
    {
        $this->memcache = new Memcache();
        $this->memcache->connect($host, $port, $timeout);
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param string $data
     */
    public function set($key, $data, $expire = 21600)
    {
        return $this->memcache->set($key, $data, 0, $expire);
    }

    /**
     * 获取缓存
     *
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        return $this->memcache->get($key);
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function remove($key)
    {
        $this->memcache->delete($key);
    }

    /**
     * 清空缓存
     *
     */
    public function flush()
    {
        $this->memcache->flush();
    }
}
