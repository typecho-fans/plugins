<?php

$fileInfo = Widget_Upload::uploadHandle($_FILES['upfile']);

$retInfo = array(
    "state" => "SUCCESS",
    "url" => Typecho_Request::getInstance()->getUrlPrefix() . $fileInfo['path'],
    "title" => $fileInfo['path'],
    "original" => $fileInfo['name'],
    "type" => $fileInfo['type'],
    "size" => $fileInfo['size'],
);
 
return json_encode($retInfo);
