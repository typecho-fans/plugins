<?php

require_once("auth_digest.php");

// --------------------------------------------------------------------------------
// class Qiniu_Error

class Qiniu_Error
{
	public $Err;	 // string
	public $Reqid;	 // string
	public $Details; // []string
	public $Code;	 // int

	public function __construct($code, $err)
	{
		$this->Code = $code;
		$this->Err = $err;
	}
}

// --------------------------------------------------------------------------------
// class Qiniu_Request

class Qiniu_Request
{
	public $URL;
	public $Header;
	public $Body;

	public function __construct($url, $body)
	{
		$this->URL = $url;
		$this->Header = array();
		$this->Body = $body;
	}
}

// --------------------------------------------------------------------------------
// class Qiniu_Response

class Qiniu_Response
{
	public $StatusCode;
	public $Header;
	public $ContentLength;
	public $Body;

	public function __construct($code, $body)
	{
		$this->StatusCode = $code;
		$this->Header = array();
		$this->Body = $body;
		$this->ContentLength = strlen($body);
	}
}

// --------------------------------------------------------------------------------
// class Qiniu_Header

function Qiniu_Header_Get($header, $key) // => $val
{
	$val = @$header[$key];
	if (isset($val)) {
		if (is_array($val)) {
			return $val[0];
		}
		return $val;
	} else {
		return '';
	}
}

function Qiniu_ResponseError($resp) // => $error
{
	$header = $resp->Header;
	$details = Qiniu_Header_Get($header, 'X-Log');
	$reqId = Qiniu_Header_Get($header, 'X-Reqid');
	$err = new Qiniu_Error($resp->StatusCode, null);

	if ($err->Code > 299) {
		if ($resp->ContentLength !== 0) {
			if (Qiniu_Header_Get($header, 'Content-Type') === 'application/json') {
				$ret = json_decode($resp->Body, true);
				$err->Err = $ret['error'];
			}
		}
	}
	return $err;
}

// --------------------------------------------------------------------------------
// class Qiniu_Client

function Qiniu_Client_incBody($req) // => $incbody
{
	$body = $req->Body;
	if (!isset($body)) {
		return false;
	}

	$ct = Qiniu_Header_Get($req->Header, 'Content-Type');
	if ($ct === 'application/x-www-form-urlencoded') {
		return true;
	}
	return false;
}

function Qiniu_Client_do($req) // => ($resp, $error)
{
	$ch = curl_init();
	$url = $req->URL;
	$options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_CUSTOMREQUEST  => 'POST',
		CURLOPT_URL => $url['path']
	);
	$httpHeader = $req->Header;
	if (!empty($httpHeader))
	{
		$header = array();
		foreach($httpHeader as $key => $parsedUrlValue) {
			$header[] = "$key: $parsedUrlValue";
		}
		$options[CURLOPT_HTTPHEADER] = $header;
	}
	$body = $req->Body;
	if (!empty($body)) {
		$options[CURLOPT_POSTFIELDS] = $body;
	}
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	$ret = curl_errno($ch);
	if ($ret !== 0) {
		$err = new Qiniu_Error(0, curl_error($ch));
		curl_close($ch);
		return array(null, $err);
	}
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);
	$resp = new Qiniu_Response($code, $result);
	$resp->Header['Content-Type'] = $contentType;
	return array($resp, null);
}

class Qiniu_HttpClient
{
	public function RoundTrip($req) // => ($resp, $error)
	{
		return Qiniu_Client_do($req);
	}
}

class Qiniu_MacHttpClient
{
	public $Mac;

	public function __construct($mac)
	{
		$this->Mac = Qiniu_RequireMac($mac);
	}

	public function RoundTrip($req) // => ($resp, $error)
	{
		$incbody = Qiniu_Client_incBody($req);
		$token = $this->Mac->SignRequest($req, $incbody);
		$req->Header['Authorization'] = "QBox $token";
		return Qiniu_Client_do($req);
	}
}

// --------------------------------------------------------------------------------

function Qiniu_Client_ret($resp) // => ($data, $error)
{
	$code = $resp->StatusCode;
	$data = null;
	if ($code >= 200 && $code <= 299) {
		if ($resp->ContentLength !== 0) {
			$data = json_decode($resp->Body, true);
			if ($data === null) {
				$err_msg = function_exists('json_last_error_msg') ? json_last_error_msg() : "error with content:" . $resp->Body;
				$err = new Qiniu_Error(0, $err_msg);
				return array(null, $err);
			}
		}
		if ($code === 200) {
			return array($data, null);
		}
	}
	return array($data, Qiniu_ResponseError($resp));
}

function Qiniu_Client_Call($self, $url) // => ($data, $error)
{
	$u = array('path' => $url);
	$req = new Qiniu_Request($u, null);
	list($resp, $err) = $self->RoundTrip($req);
	if ($err !== null) {
		return array(null, $err);
	}
	return Qiniu_Client_ret($resp);
}

function Qiniu_Client_CallNoRet($self, $url) // => $error
{
	$u = array('path' => $url);
	$req = new Qiniu_Request($u, null);
	list($resp, $err) = $self->RoundTrip($req);
	if ($err !== null) {
		return array(null, $err);
	}
	if ($resp->StatusCode === 200) {
		return null;
	}
	return Qiniu_ResponseError($resp);
}

function Qiniu_Client_CallWithForm(
	$self, $url, $params, $contentType = 'application/x-www-form-urlencoded') // => ($data, $error)
{
	$u = array('path' => $url);
	if ($contentType === 'application/x-www-form-urlencoded') {
		if (is_array($params)) {
			$params = http_build_query($params);
		}
	}
	$req = new Qiniu_Request($u, $params);
	if ($contentType !== 'multipart/form-data') {
		$req->Header['Content-Type'] = $contentType;
	}
	list($resp, $err) = $self->RoundTrip($req);
	if ($err !== null) {
		return array(null, $err);
	}
	return Qiniu_Client_ret($resp);
}

// --------------------------------------------------------------------------------

function Qiniu_Client_CallWithMultipartForm($self, $url, $fields, $files)
{
	list($contentType, $body) = Qiniu_Build_MultipartForm($fields, $files);
	return Qiniu_Client_CallWithForm($self, $url, $body, $contentType);
}

function Qiniu_Build_MultipartForm($fields, $files) // => ($contentType, $body)
{
	$data = array();
	$mimeBoundary = md5(microtime());

	foreach ($fields as $name => $val) {
		array_push($data, '--' . $mimeBoundary);
		array_push($data, "Content-Disposition: form-data; name=\"$name\"");
		array_push($data, '');
		array_push($data, $val);
	}

	foreach ($files as $file) {
		array_push($data, '--' . $mimeBoundary);
		list($name, $fileName, $fileBody, $mimeType) = $file;
		$mimeType = empty($mimeType) ? 'application/octet-stream' : $mimeType;
		$fileName = Qiniu_escapeQuotes($fileName);
		array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
		array_push($data, "Content-Type: $mimeType");
		array_push($data, '');
		array_push($data, $fileBody);
	}

	array_push($data, '--' . $mimeBoundary . '--');
	array_push($data, '');

	$body = implode("\r\n", $data);
	$contentType = 'multipart/form-data; boundary=' . $mimeBoundary;
	return array($contentType, $body);
}

function Qiniu_escapeQuotes($str)
{
	$find = array("\\", "\"");
	$replace = array("\\\\", "\\\"");
	return str_replace($find, $replace, $str);
}

// --------------------------------------------------------------------------------

