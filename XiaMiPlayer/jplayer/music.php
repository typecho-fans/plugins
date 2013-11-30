<?php
header('Content-type: audio/mpeg');
if(!isset($_GET['id']) && !isset($_GET['location'])) die();
$id = $_GET['id'];
$url = str_replace('|', '%', $_GET['location']);
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_REFERER, 'http://img.xiami.com/res/player/widget/singlePlayer.swf?dataUrl=http://www.xiami.com/widget/xml-single/uid/0/sid/'.$id);
curl_exec($curl);
curl_close($curl);
?>