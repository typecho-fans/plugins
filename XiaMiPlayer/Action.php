<?php
if(!defined('__TYPECHO_ROOT_DIR__'))exit;
class XiaMiPlayer_Action extends Typecho_Widget implements Widget_Interface_Do {
public function execute(){}
public function action(){
    $this->on($this->request->isGet())->player();
}

private function player(){
$dir = Typecho_Widget::widget('Widget_Options')->pluginUrl;
error_reporting(0);
header('content-type: application/javascript');
if(!isset($_GET['songs'])) $_GET['songs'] = '';
$gets = explode(",", $_GET['songs']);
$songs = array();
foreach($gets as $get) {
    $songs[] = strstr($get, '|') ? array_combine(array('url', 'name'), explode('|', $get)) : $get;
}

$cuz = '';
if (isset($_GET['setting'])) {
    $gets = explode(',', $_GET['setting']);
    $setting = array('background'=>'#'.$gets[0], 'border-bottom'=>'#'.$gets[1]);
    $cuz = '
    playbox.css('.json_encode($setting).');
    $(".box", playbox).css('.json_encode($setting).');
    $(".progress", playbox).css("background", "'.$setting['border-bottom'].'");';
}
$code = json_encode($songs);

echo
<<<EOF
(function($, songs) {
    api = '$dir/XiaMiPlayer/ajax.php?type=songs&id=';
    time = new Date().getTime();
    document.write('<div id="'+time+'" class="xiamiplayer"><div class="box"><div class="progress"></div><div class="play"></div><div class="btn-play"></div><span></span></div><ul class="list"></ul></div>');
    playbox = $('#'+time);
    (function(playbox) {
         songs.forEach(function(song, i) {
            var li = $('<li></li>');
            if(typeof song != 'object') {
                li.attr('data-xiami', '1'), 
                li.attr('data-id', song);
                $.getJSON(api+song, function(res) {
                    li.attr('data-url', res.listen_file),
                    li.attr('data-name', res.song_name),
                    li.attr('data-artist', res.artist_name),
                    li.attr('data-lyric', res.lyric),
                    li.text([res.song_name, res.artist_name].join(' - '));
                    if(i===0) {
                        console.log('Init music id '+song+' for playbox id #'+playbox.attr('id'));
                        XiaMiPlayerInit(li, playbox, false);
                    }
                });
            } else {
                li.attr('data-xiami', '0'),
                li.attr('data-url', song.url),
                li.text(song.name);
                if(i===0) XiaMiPlayerInit(li, playbox, false);
            }
            $('.list', playbox).append(li);
        })   
    })(playbox);

    if(songs.length <= 1) 
        $('.list', playbox).addClass('display');

    $cuz
})(jQuery, $code);
EOF;
}
}