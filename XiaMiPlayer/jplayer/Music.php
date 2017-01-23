<?php 
error_reporting(0);
header('content-type: application/javascript');
if(!isset($_GET['songs'])) $_GET['songs'] = '';
$gets = explode(",", $_GET['songs']);
$songs = array();
foreach($gets as $get) {
    $songs[] = strstr($get, '|') ? array_combine(array('url', 'name'), explode('|', $get)) : $get;
}
?>
(function($, songs) {
    api = 'http://songs.sinaapp.com/apiv2.php?callback=?&id=';
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
                    li.attr('data-url', res.location),
                    li.attr('data-name', res.title),
                    li.attr('data-artist', res.artist),
                    li.attr('data-lyric', res.lyric),
                    li.text([res.title, res.artist].join(' - '));
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

    <?php if($_GET['setting']): ?>
    <?php   $gets = explode(',', $_GET['setting']);$setting = array('background'=>'#'.$gets[0], 'border-bottom'=>'#'.$gets[1]); ?>
    playbox.css(<?php echo json_encode($setting); ?>);
    $('.box', playbox).css(<?php echo json_encode($setting); ?>);
    $('.progress', playbox).css('background', '<?php echo $setting['border-bottom']; ?>');
    <?php endif; ?>
})(jQuery, <?php echo json_encode($songs); ?>);