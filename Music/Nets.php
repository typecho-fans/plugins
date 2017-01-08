<?php
/**
 * 网易音乐类
 *
 * @package Remix
 * @author shingchi <shingchi@sina.cn>
 * @license GNU General Public License 2.0
 */
class Remix_Music_Nets implements Remix_Music_Interface
{
    /**
     * 获取歌曲
     *
     * @param string $id
     */
    public function song($id)
    {
        $url = 'http://music.163.com/api/song/detail/?id=' . $id . '&ids=%5B' . $id . '%5D';
        $result = $this->http($url);

        if (is_null($result)) {
            return;
        }

        /* 解析歌曲 */
        $track = $result['songs'][0];
        $song = $this->parse($track);

        return $song;
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
        $url = 'http://music.163.com/api/album/' . $id;
        $result = $this->http($url);

        if (is_null($result)) {
            return;
        }

        /* 解析专辑 */
        $tracks = $result['album']['songs'];
        $album = array();

        foreach ($tracks as $track) {
            $album[] = $this->parse($track);
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
        $url = 'http://music.163.com/api/playlist/detail?id=' . $id;
        $result = $this->http($url);

        if (is_null($result)) {
            return;
        }

        /* 解析列表 */
        $tracks = $result['result']['tracks'];
        $collect = array();

        foreach ($tracks as $track) {
            $collect[] = $this->parse($track);
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

        $client->setHeader('Cookie', 'appver=2.0.2')
        ->setHeader('Referer', 'http://music.163.com')
        ->setTimeout(20)
        ->send($url);

        if (200 === $client->getResponseStatus()) {
            $response = Json::decode($client->getResponseBody(), true);
            if (200 === $response['code']) {
                unset($response['code']);
                return $response;
            }
            return;
        }
        return;
    }

    /**
     * 解析歌曲
     *
     * @param string $track
     */
    protected function parse($track)
    {
        $src = str_replace('http://m', 'http://p', $track['mp3Url']);
        $authors = array();

        foreach ($track['artists'] as $artist) {
            $authors[] = $artist['name'];
        }

        $author = implode(',', $authors);

        $song = array(
            'id'     => $track['id'],
            'title'  => $track['name'],
            'author' => $author,
            'cover'  => $track['album']['picUrl'],
            'src'    => $src
        );
        $song = array_map('trim', $song);

        return $song;
    }
}
