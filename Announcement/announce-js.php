<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$settings = Helper::options()->plugin('Announcement');
if($settings->showArea=='2'){
    $request = new Typecho_Request;
    $currentUrl = $request->getRequestUrl();
    $blogUrl = Helper::options()->stack[0]['siteUrl'];
    if($currentUrl!=$blogUrl || $currentUrl==$blogUrl.'index.php'){
       return; 
    }   
}
$jqUrl = Helper::options()->pluginUrl . '/Announcement/js/jquery.min.js';
$styleSheetUrl = Helper::options()->pluginUrl . '/Announcement/css/style.css';
$jqueryScriptUrl = Helper::options()->pluginUrl . '/Announcement/js/script.js';
if($settings->jquery){
   echo '<script src="'.$jqUrl.'"></script>'; 
}
echo '<link href="'.$styleSheetUrl.'" type="text/css" rel="stylesheet" />';
echo '<script src="'.$jqueryScriptUrl.'" type="text/javascript"></script>';
echo '<span id="announcement_plug" data-type="'.$settings->annMode.'" data-content="'.$settings->content.'"></span>';
?>