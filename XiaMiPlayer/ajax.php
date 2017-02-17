<?php
require dirname(__FILE__) . '/XiamiMusicAPI.php';

if(!isset($api)){
	$api = new XiamiMusicAPI();
}

$type = $_GET['type'];
$id = $_GET['id'];
$page = isset($_GET['page']) ? ($_GET['page'])-1 : 0;

switch ($type) {
	case 'songs' :
	$data=json_decode($api->detail($id));
	$data = $data ? $data->data->song : null;
	break;

	case 'album':
	$data=json_decode($api->album($id));
	$data = $data ? $data->data : null;
	break;

	case 'collect':
	$data=json_decode($api->playlist($id));
	$data = $data ? $data->data : null;
	break;

	case 'search':
	$data=json_decode($api->search($id,5,$page));
	$data = $data ? $data->data : null;
	break;
}

header('Content-type: application/json; charset=UTF-8');
echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
exit;