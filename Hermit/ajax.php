<?php
require dirname(__FILE__) . '/class.json.php';

if(!isset($HMTJSON)){
	$HMTJSON = new HermitJson();
}

$scope = $_GET['scope'];
$id = $_GET['id'];

switch ($scope) {
	case 'songs' :
		$result = array(
			'status' => 200,
			'msg' => $HMTJSON->song_list($id)
		);
	break;

	case 'album':
		$result = array(
			'status' =>  200,
			'msg' => $HMTJSON->album($id)
		);
	break;

	case 'collect':
		$result = array(
			'status' =>  200,
			'msg' =>  $HMTJSON->collect($id)
		);
	break;						
				
	default:
		$result = array(
			'status' =>  400,
			'msg' =>  null
		);
}

header('Content-type: application/json');
echo json_encode($result);
exit;