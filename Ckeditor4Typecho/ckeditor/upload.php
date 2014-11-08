<?php
header('Content-Type: text/html; charset=UTF-8');

$rootDir = strstr( dirname(__FILE__), 'usr', TRUE );
require_once $rootDir . 'config.inc.php';
require_once $rootDir . 'var/Typecho/Common.php';
require_once $rootDir . 'var/Typecho/Request.php';
require_once $rootDir . 'var/Widget/Upload.php';

$fileInfo = Widget_Upload::uploadHandle($_FILES['upload']);
if( false === $fileInfo ){
    echo '上传失败!';
}else{
    echo sprintf("<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction(1, '%s', '');</script>", 
        Typecho_Request::getInstance()->getUrlPrefix() . $fileInfo['path']);
}
