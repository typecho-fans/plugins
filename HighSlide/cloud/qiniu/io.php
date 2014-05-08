<?php

require_once("http.php");
require_once("auth_digest.php");

// ----------------------------------------------------------
// class Qiniu_PutExtra

class Qiniu_PutExtra
{
	public $Params = null;
	public $MimeType = null;
	public $Crc32 = 0;
	public $CheckCrc = 0;
}

function Qiniu_Put($upToken, $key, $body, $putExtra) // => ($putRet, $err)
{
	global $QINIU_UP_HOST;

	if ($putExtra === null) {
		$putExtra = new Qiniu_PutExtra;
	}

	$fields = array('token' => $upToken);
	if ($key === null) {
		$fname = '?';
	} else {
		$fname = $key;
		$fields['key'] = $key;
	}
	if ($putExtra->CheckCrc) {
		$fields['crc32'] = $putExtra->Crc32;
	}

	$files = array(array('file', $fname, $body, $putExtra->MimeType));

	$client = new Qiniu_HttpClient;
	return Qiniu_Client_CallWithMultipartForm($client, $QINIU_UP_HOST, $fields, $files);
}

function Qiniu_PutFile($upToken, $key, $localFile, $putExtra) // => ($putRet, $err)
{
	global $QINIU_UP_HOST;

	if ($putExtra === null) {
		$putExtra = new Qiniu_PutExtra;
	}

	if (!empty($putExtra->MimeType)) {
		$localFile .= ';type=' . $putExtra->MimeType;
	}

	$fields = array('token' => $upToken, 'file' => '@' . $localFile);
	if ($key === null) {
		$fname = '?';
	} else {
		$fname = $key;
		$fields['key'] = $key;
	}
	if ($putExtra->CheckCrc) {
		if ($putExtra->CheckCrc === 1) {
			$hash = hash_file('crc32b', $localFile);
			$array = unpack('N', pack('H*', $hash));
			$putExtra->Crc32 = $array[1];
		}
		$fields['crc32'] = sprintf('%u', $putExtra->Crc32);
	}

	$client = new Qiniu_HttpClient;
	return Qiniu_Client_CallWithForm($client, $QINIU_UP_HOST, $fields, 'multipart/form-data');
}

// ----------------------------------------------------------

