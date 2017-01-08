<?php
/**
 * 音乐接口类
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
interface Remix_Music_Interface
{
    /**
     * 获取歌曲
     *
     * @param string $id
     * @param string $data
     */
    public function song($id);

    /**
     * 获取列表
     *
     * @param string $id
     * @return string
     */
    public function songs($ids);

    /**
     * 获取专辑
     *
     * @param string $id
     */
    public function album($id);

    /**
     * 获取精选集
     *
     * @param string $id
     */
    public function collect($id);

    /**
     * 请求
     *
     * @param string $url
     */
    public function http($url);
}
