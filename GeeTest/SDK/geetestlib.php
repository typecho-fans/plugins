<?php

/**
 * Gets the challenge HTML (javascript and non-javascript version).
 * This is called from the browser, and the resulting GeeTest HTML widget
 * is embedded within the HTML form it was called from.
 * @param string $pubkey A public key for GeeTest
 */
function geetest_get_html ($pubkey) {
	if ($pubkey == null || $pubkey == '') {
		die ("To use GeeTest you must get an API key from <a href='http://www.geetest.com/'>http://www.geetest.com/</a>");
	}

  return '<script type="text/javascript" src="http://api.geetest.com/get.php?gt='.$pubkey.'"></script>';
}



/**
  * Calls an HTTP POST function to verify if the user's guess was correct
  * @param string $privkey
  * @param string $remoteip
  * @param string $challenge
  * @param string $response
  * @param array $extra_params an array of extra variables to post to the server
  * @return ReCaptchaResponse
  */
function geetest_check_answer ($privkey, $challenge, $validate, $seccode) {
    if ($privkey == null || $privkey == '') {
        die ("To use GeeTest you must get an API key from <a href='http://www.geetest.com/'>http://www.geetest.com/</a>");
    }

    return geetest_validate($privkey, $challenge, $validate, $seccode);
}

function geetest_validate($privkey, $challenge, $validate, $seccode) {
    $apiserver = 'api.geetest.com';
    if (strlen($validate) > 0 && $validate == md5($privkey.'geetest'.$challenge)) {
        $query = 'seccode='.$seccode;
        $servervalidate = _http_post($apiserver, '/validate.php', $query);
        if (strlen($servervalidate) > 0 && $servervalidate == md5($seccode)) {
            return TRUE;
        }
    }

    return FALSE;
}

function _http_post($host, $path, $data, $port = 80) {
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