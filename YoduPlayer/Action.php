<?php
use Typecho\widget;
class YoduPlayer_Action extends Typecho_Widget implements Widget_Interface_Do {
     public function execute() {
        //Do
    }
  
    public function action()
    {
$rewrite='';if(Helper::options()->rewrite==0){$rewrite='index.php/';}
$apiurl=Helper::options()->rootUrl.'/'.$rewrite.'yoduapi';
// 设置API路径
define('API_URI', $apiurl);
// 设置中文歌词
define('TLYRIC', true);
// 设置歌单文件缓存及时间
define('CACHE', true);
define('CACHE_TIME', 86400);
// 设置短期缓存-需要安装apcu
define('APCU_CACHE', false);
// 设置AUTH密钥-更改'meting-secret'
define('AUTH', true);
define('AUTH_SECRET', Helper::options()->Plugin('YoduPlayer')->auth);
include('jx.php');
    }
}

?>