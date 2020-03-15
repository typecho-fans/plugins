<?php
	$siteurl = file_get_contents('siteurl.json');
	$site = json_decode($siteurl, true);
	fopen($site['siteurl'],'r');
	$json_string = file_get_contents('settings.json'); //获取设置信息
	$data = json_decode($json_string, true); //json文件数据读取到PHP变量
	$id = $data['id']; //定义id
	$lrc_out = $data['lrc']; //定义lrc
	$autoplay_out = $data['autoplay']; //定义autoplay
	$theme = $data['theme']; //定义theme
    $volume = $data['volume']; //定义volume
    $order_out = $data['order']; //定义order
	$apiget = file_get_contents('https://api.ohmyga.cn/netease/?use=1&type=playlist&id='.$id.''); //OhmygaAPI获取音乐歌单
	$total = 'const ap = new APlayer({container: document.getElementById(\'downplayer\'),lrcType:'.$lrc_out.',autoplay:'.$autoplay_out.',fixed:true,theme:\''.$theme.'\',volume:'.$volume.',order:\''.$order_out.'\',audio:'.$apiget.'});';
    file_put_contents('downplayer.js',$total); //将js写入downplayer.js
	echo('更新成功！请您刷新浏览器缓存查看音乐列表更新~'); //提示更新成功！
?>
