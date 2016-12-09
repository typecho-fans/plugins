<?php
global $SDK_VER;

global $QINIU_UP_HOST;
global $QINIU_RS_HOST;
global $QINIU_RSF_HOST;
global $QINIU_API_HOST;
global $QINIU_IOVIP_HOST;

global $QINIU_ACCESS_KEY;
global $QINIU_SECRET_KEY;

$SDK_VER = "6.1.13";


$QINIU_RS_HOST  = 'http://rs.qbox.me';
$QINIU_RSF_HOST = 'http://rsf.qbox.me';
$QINIU_API_HOST = 'http://api.qiniu.com';

//华东
if($server == 0){
    $QINIU_UP_HOST  = 'http://up.qiniu.com';
    $QINIU_IOVIP_HOST = 'http://iovip.qbox.me';  
}
//华北
elseif($server == 1){
    $QINIU_UP_HOST  = 'up-z1.qiniu.com';
    $QINIU_IOVIP_HOST = 'http://iovip-z1.qbox.me';  
}
//华南
elseif($server == 2){
    $QINIU_UP_HOST  = 'up-z2.qiniu.com';
    $QINIU_IOVIP_HOST = 'http://iovip-z2.qbox.me';  
}
//北美
elseif($server == 3){
    $QINIU_UP_HOST  = 'up-na0.qiniu.com';
    $QINIU_IOVIP_HOST = 'http://iovip-na0.qbox.me';
}

$QINIU_ACCESS_KEY	= '<Please apply your access key>';
$QINIU_SECRET_KEY	= '<Dont send your secret key to anyone>';
