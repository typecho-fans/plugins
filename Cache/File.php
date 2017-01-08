<?php

/**
 * 文件式缓存
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
class Remix_Cache_File implements Remix_Cache_Interface
{
    /**
     * 缓存路径
     */
    private $_cacheDir;

    /**
     * @param $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->_cacheDir = rtrim($cacheDir, '/') . '/';
    }

    /**
     * 设置缓存
     *
     * @param string $key
     * @param string $data
     * @param string $expire 默认缓存时间为6小时
     */
    public function set($key, $data, $expire = 21600)
    {
        $data = array(
            /** 记录时效 */
            'II' => pack('II', $expire, time()),
            'data' => $data
        );

        $content = '<?php ';
        $content .= 'return ' . var_export($data, true) .';';
        $content .= '?>';

        /** 写入缓存 */
        file_put_contents($this->_cacheDir . $key . '.php', $content, LOCK_EX);

        /** 释放内存  */
        unset($data, $content);
    }

    /**
     * 获取缓存
     *
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        $path = $this->_cacheDir . $key . '.php';
        $cache = array();

        if (file_exists($path)) {
            $cache = include $path;
        }

        if ($cache) {
            $tmp = isset($cache['II']) && $cache['II'] ? unpack('Il/IL', $cache['II']) : '';

            /** 检测时效性 */
            if ($tmp && (!$tmp['l'] || (time() - $tmp['L'] <= $tmp['l']))) {
                return $cache['data'];
            }
        }

        /** 清除已经过时的缓存  */
        @unlink($path);
        return '';
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function remove($key)
    {
        $path = $this->_cacheDir . $key . '.php';
        @unlink($path);
    }

    /**
     * 清空缓存
     *
     */
    public function flush()
    {}
}
