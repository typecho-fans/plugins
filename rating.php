<?php
if ('POST' != $_SERVER['REQUEST_METHOD']) {
    header('Allow: POST');
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type: text/plain');
    exit;
}

if(strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false) {
    exit('Access denied');
}

/** Sets up the Typecho Environment. */
require(dirname(__FILE__) .'/../../../config.inc.php');
Typecho_Widget::widget('Widget_Init');

$rating  = (isset($_POST['rating']))  ? (int)$_POST['rating'] : 0;
$cid     = (isset($_POST['cid']))     ? (int)$_POST['cid']    : 0;

$delete  = (isset($_POST['created'])) ? substr($_POST['created'], strpos($_POST['created'], '-') + 1) : '';
$hash    = substr(md5(Typecho_Widget::widget('Widget_User')->authCode), -10);

$db = Typecho_Db::get();

if ($rating && $cid) {

    $ip = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"]
     : (isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] : $_SERVER["REMOTE_ADDR"]);

    $user = Typecho_Widget::widget('Widget_User');
    $name = $user->hasLogin() ? $user->screenName : Typecho_Cookie::get('__typecho_remember_author', '');

    $created = time() + Typecho_Widget::widget('Widget_Options')->timezone;

    /* insert data */
    $db->query("INSERT INTO ". $db->getPrefix() ."postrating VALUES ('$cid', '$rating', '$ip', '$name', '$created')");

    // for debug
    //echo '$cid='.$cid.' $rating='.$rating.' $ip='.$ip.' $name='. $name.' $created='.$created;

} elseif ($delete && isset($_POST['hash']) && $_POST['hash'] == $hash) {

    /* delete data */
    $db->query($db->delete('table.postrating')->where('created = ?', $delete));

}

exit;
