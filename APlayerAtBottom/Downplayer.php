<?php
	header('Content-Type: application/javascript');
	$content = json_decode(file_get_contents(__DIR__ .'/settings.json'),true);
	$set = $content['settings'];
	echo 'const ap = new APlayer({container: document.getElementById(\'downplayer\'),lrcType:'.$set['lrc'].',autoplay:'.$set['autoplay'].',fixed:true,theme:\''.$set['theme'].'\',volume:'.$set['volume'].',order:\''.$set['order'].'\',audio:'.$content['data'].'});';
?>