<?php
//session_start();
//暂时先直接返回
exit;

include_once( 'config.php' );
include_once( 'saetv2.ex.class.php' );

$c = new SaeTClientV2( WB_AKEY , WB_SKEY , $_SESSION['token']['access_token'] );
$ms  = $c->home_timeline(); // done
$uid_get = $c->get_uid();
$uid = $uid_get['uid'];
$user_message = $c->show_user_by_id( $uid);//根据ID获取用户等基本信息

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>新浪微博V2接口演示程序-Powered by Sina App Engine</title>
</head>

<body>
	<?=$user_message['screen_name']?>,您好！ 
	<h2 align="left">发送新微博</h2>
	<form action="" >
		<input type="text" name="text" style="width:300px" />
		<input type="submit" />
	</form>
<?php
if( isset($_REQUEST['text']) ) {
	// 注意至少要带上一个链接。
	$ret = $c->share( $_REQUEST['text'] );	//发送微博
	if ( isset($ret['error_code']) && $ret['error_code'] > 0 ) {
		echo "<p>发送失败，错误：{$ret['error_code']}:{$ret['error']}</p>";
	} else {
		echo "<p>发送成功</p>";
	}
}
?>

<?php if( is_array( $ms['statuses'] ) ): ?>
<?php foreach( $ms['statuses'] as $item ): ?>
<div style="padding:10px;margin:5px;border:1px solid #ccc">
	<?=$item['text'];?>
</div>
<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
