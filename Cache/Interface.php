<?php
/**
 * 缓存接口类
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
interface Remix_Cache_Interface
{
    /**
     * 设置缓存
     *
     * @param string $key
     * @param string $data
     */
    public function set($key, $data, $expire);

    /**
     * 获取缓存
     *
     * @param string $key
     * @return string
     */
    public function get($key);

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function remove($key);

    /**
     * 清空缓存
     *
     */
    public function flush();
}
