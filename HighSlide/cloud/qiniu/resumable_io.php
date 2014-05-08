<?php

require_once("http.php");
require_once("auth_digest.php");

// ----------------------------------------------------------
// class Qiniu_Rio_PutExtra

class Qiniu_Rio_PutExtra
{
	public $Bucket = null;		// 必选（未来会没有这个字段）。
	public $Params = null;
	public $MimeType = null;
	public $ChunkSize = 0;		// 可选。每次上传的Chunk大小
	public $TryTimes = 0;		// 可选。尝试次数
	public $Progresses = null;	// 可选。上传进度：[]BlkputRet
	public $Notify = null;		// 进度通知：func(blkIdx int, blkSize int, ret *BlkputRet)
	public $NotifyErr = null;	// 错误通知：func(blkIdx int, blkSize int, err error)

	public function __construct($bucket = null) {
		$this->Bucket = $bucket;
	}
}

// ----------------------------------------------------------
// func Qiniu_Rio_BlockCount

define('QINIU_RIO_BLOCK_BITS', 22);
define('QINIU_RIO_BLOCK_SIZE', 1 << QINIU_RIO_BLOCK_BITS); // 4M

function Qiniu_Rio_BlockCount($fsize) // => $blockCnt
{
	return ($fsize + (QINIU_RIO_BLOCK_SIZE - 1)) >> QINIU_RIO_BLOCK_BITS;
}

// ----------------------------------------------------------
// internal func Qiniu_Rio_Mkblock/Mkfile

function Qiniu_Rio_Mkblock($self, $host, $reader, $size) // => ($blkputRet, $err)
{
	if (is_resource($reader)) {
		$body = fread($reader, $size);
		if ($body === false) {
			$err = Qiniu_NewError(0, 'fread failed');
			return array(null, $err);
		}
	} else {
		list($body, $err) = $reader->Read($size);
		if ($err !== null) {
			return array(null, $err);
		}
	}
	if (strlen($body) != $size) {
		$err = Qiniu_NewError(0, 'fread failed: unexpected eof');
		return array(null, $err);
	}

	$url = $host . '/mkblk/' . $size;
	return Qiniu_Client_CallWithForm($self, $url, $body, 'application/octet-stream');
}


function Qiniu_Rio_Mkfile($self, $host, $key, $fsize, $extra) // => ($putRet, $err)
{
	$url = $host . '/mkfile/' . $fsize;
	if ($key !== null) {
		$url .= '/key/' . Qiniu_Encode($key);
	}
	if (!empty($extra->MimeType)) {
		$url .= '/mimeType/' . Qiniu_Encode($extra->MimeType);
	}

	$ctxs = array();
	foreach ($extra->Progresses as $prog) {
		$ctxs []= $prog['ctx'];
	}
	$body = implode(',', $ctxs);

	return Qiniu_Client_CallWithForm($self, $url, $body, 'application/octet-stream');
}

// ----------------------------------------------------------
// class Qiniu_Rio_UploadClient

class Qiniu_Rio_UploadClient
{
	public $uptoken;

	public function __construct($uptoken)
	{
		$this->uptoken = $uptoken;
	}

	public function RoundTrip($req) // => ($resp, $error)
	{
		$token = $this->uptoken;
		$req->Header['Authorization'] = "UpToken $token";
		return Qiniu_Client_do($req);
	}
}

// ----------------------------------------------------------
// class Qiniu_Rio_Put/PutFile

function Qiniu_Rio_Put($upToken, $key, $body, $fsize, $putExtra) // => ($putRet, $err)
{
	global $QINIU_UP_HOST;

	$self = new Qiniu_Rio_UploadClient($upToken);

	$progresses = array();
	$uploaded = 0;
	while ($uploaded < $fsize) {
		if ($fsize < $uploaded + QINIU_RIO_BLOCK_SIZE) {
			$bsize = $fsize - $uploaded;
		} else {
			$bsize = QINIU_RIO_BLOCK_SIZE;
		}
		list($blkputRet, $err) = Qiniu_Rio_Mkblock($self, $QINIU_UP_HOST, $body, $bsize);
		$host = $blkputRet['host'];
		$uploaded += $bsize;
		$progresses []= $blkputRet;
	}

	$putExtra->Progresses = $progresses;
	return Qiniu_Rio_Mkfile($self, $QINIU_UP_HOST, $key, $fsize, $putExtra);
}

function Qiniu_Rio_PutFile($upToken, $key, $localFile, $putExtra) // => ($putRet, $err)
{
	$fp = fopen($localFile, 'rb');
	if ($fp === false) {
		$err = Qiniu_NewError(0, 'fopen failed');
		return array(null, $err);
	}

	$fi = fstat($fp);
	$result = Qiniu_Rio_Put($upToken, $key, $fp, $fi['size'], $putExtra);
	fclose($fp);
	return $result;
}

// ----------------------------------------------------------

