<?php
if( isset($_REQUEST['code']) ) {
	include 'config.php';
	include 'saetv2.ex.class.php';

	$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );

	$keys = array();
	$keys['code'] = $_REQUEST['code'];
	$keys['redirect_uri'] = WB_CALLBACK_URL;
	try {
		$token = $o->getAccessToken( 'code', $keys ) ;
	} catch (OAuthException $e) {
	}
	
	if ($token) {
		$c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token['access_token'] );
		$uid_get = $c->get_uid();
		echo 'Sina_weibo_Access_token = ['. $token['access_token'] . "]<p/>Sina_weibo_Uid = [" . $uid_get['uid'] . ']';
	}
}
exit;
