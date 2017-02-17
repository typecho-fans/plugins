<?php
/*!
 * Xiami Music Api
 * https://i-meto.com
 * Version 0.1.0 beta
 *
 * Copyright 2016, METO
 * Released under the MIT license
 */
class XiamiMusicAPI{
    // General
    protected $_USERAGENT='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36';
    protected $_COOKIE='user_from=2;XMPLAYER_addSongsToggler=0;XMPLAYER_isOpen=0;_xiamitoken=cb8bfadfe130abdbf5e2282c30f0b39a;';
    protected $_REFERER='http://h.xiami.com/';
    // CURL
    protected function curl($url,$data=null){
        $curl=curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        if($data){
            if(is_array($data))$data=http_build_query($data);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
            curl_setopt($curl,CURLOPT_POST,1);
        }
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl,CURLOPT_REFERER,$this->_REFERER);
        curl_setopt($curl,CURLOPT_COOKIE,$this->_COOKIE);
        curl_setopt($curl,CURLOPT_USERAGENT,$this->_USERAGENT);
        $result=curl_exec($curl);
        curl_close($curl);
        return $result;
    }
    // main function
    public function search($s,$limit=30,$offset=0,$type=1){
        $url='http://api.xiami.com/web?';
        $data=array(
            'v'=>'2.0',
            'app_key'=>'1',
            'key'=>$s,
            'page'=>($offset+1),
            'limit'=>$limit,
            'r'=>'search/songs',
        );
        return $this->curl($url.http_build_query($data));
    }
    public function artist($artist_id){
        $url='http://api.xiami.com/web?';
        $data=array(
            'v'=>'2.0',
            'app_key'=>'1',
            'id'=>$artist_id,
            'page'=>1,
            'limit'=>30,
            'r'=>'artist/hot-songs',
        );
        return $this->curl($url.http_build_query($data));
    }
    public function album($album_id){
        $url='http://api.xiami.com/web?';
        $data=array(
            'v'=>'2.0',
            'app_key'=>'1',
            'id'=>$album_id,
            'r'=>'album/detail',
        );
        return $this->curl($url.http_build_query($data));
    }
    public function detail($song_id){
        $url='http://api.xiami.com/web?';
        $data=array(
            'v'=>'2.0',
            'app_key'=>'1',
            'id'=>$song_id,
            'r'=>'song/detail',
        );
        return $this->curl($url.http_build_query($data));
    }
    public function url($song_id){
        $url='http://www.xiami.com/song/playlist/id/'.$song_id.'/object_name/default/object_id/0/cat/json';
        return $this->curl($url);
    }
    public function playlist($playlist_id){
        $url='http://api.xiami.com/web?';
        $data=array(
            'v'=>'2.0',
            'app_key'=>'1',
            'id'=>$playlist_id,
            'r'=>'collect/detail',
        );
        return $this->curl($url.http_build_query($data));
    }
    public function lyric($song_id){
        // Todo
        return "...";
    }
}
