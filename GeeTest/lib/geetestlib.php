<?php
/**
 * 极验行为式验证安全平台，php 网站主后台包含的库文件
 */

// define("PRIVATE_KEY","0f1a37e33c9ed10dd2e133fe2ae9c459");

class GeetestLib {
  function __construct($PRIVATE_KEY){
    $this->PRIVATE_KEY = $PRIVATE_KEY;
  }

  function geetest_validate($challenge, $validate, $seccode) {	
    $apiserver = 'api.geetest.com';
    if (strlen($validate) > 0 && $this->_check_result_by_private($challenge, $validate)) {		
      $query = 'seccode='.$seccode;
      $servervalidate = $this->_http_post($apiserver, '/validate.php', $query);			
      if (strlen($servervalidate) > 0 && $servervalidate == md5($seccode)) {
	return TRUE;
      }else if($servervalidate == "false"){
	return FALSE;
      }else{ 
	return $servervalidate;
      }
    }
    
    return FALSE;		
  }
  function challenge(){
    $str = str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ");
    $time = strval(time());
    $rand = strval(rand(0,99999));
    $test = $time.$str.$rand;
    $challenge = md5($test);
    return $challenge;
  }


  function _check_result_by_private($origin, $validate) {
    return $validate == md5($this->PRIVATE_KEY.'geetest'.$origin) ? TRUE : FALSE;
  }

  function _http_post($host, $path, $data, $port = 80) {
    // $data = _fix_encoding($data);
    
    $http_request  = "POST $path HTTP/1.0\r\n";
    $http_request .= "Host: $host\r\n";
    $http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $http_request .= "Content-Length: " . strlen($data) . "\r\n";
    $http_request .= "\r\n";
    $http_request .= $data;

    $response = '';
    if (($fs = @fsockopen($host, $port, $errno, $errstr, 10)) == false) {
      die ('Could not open socket! ' . $errstr);
    }
    
    fwrite($fs, $http_request);

    while (!feof($fs))
      $response .= fgets($fs, 1160);
    fclose($fs);
    
    $response = explode("\r\n\r\n", $response, 2);
    return $response[1];
  }

  function _fix_encoding($str) { 	
    $curr_encoding = mb_detect_encoding($str) ; 
    
    if($curr_encoding == "UTF-8" && mb_check_encoding($str,"UTF-8")) {
      return $str; 
    } else {
      return utf8_encode($str); 
    }
  }
}
?>
