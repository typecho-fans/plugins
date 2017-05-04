<?php !defined('__TYPECHO_ROOT_DIR__') and exit();

$options = Helper::options();
$siteUrl = $options->siteUrl;
$isRewrite = $options->rewrite;
$absUrl = ($isRewrite ? rtrim($siteUrl, '/') : $siteUrl."index.php");

Typecho_Response::getInstance()->redirect($absUrl.__TYPECHO_ADMIN_DIR__.'app-store/market');
