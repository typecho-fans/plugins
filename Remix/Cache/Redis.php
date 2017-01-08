<?php
/**
 * Redis缓存
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
class Remix_Cache_Redis implements Remix_Cache_Interface
{
    /**
     * redis对象
     *
     * @var Redis
     */
    private $redis;

    /**
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     */
    public function __construct($host = '127.0.0.1', $port = 6379, $timeout = 30)
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port, $timeout);
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param string $data
     */
    public function set($key, $data, $expire = 21600)
    {
        $status = $this->redis->set($key, $data, $expire);

        return $status;
    }

    /**
     * 获取缓存
     *
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        return $this->redis->get($key);
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function remove($key)
    {
        $this->redis->delete($key);
    }

    /**
     * 清空缓存
     *
     */
    public function flush()
    {
        $this->redis->flushAll();
    }
}
