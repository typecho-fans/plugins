<?php
if (!defined('__TYPECHO_ADMIN__')) {
    exit;
}
include 'common.php';

$bodyClass = 'body-100';

$url = ($options->rewrite) ? $options->siteUrl : $options->siteUrl . 'index.php';
$url = rtrim($url, '/') . '/GAuthenticator';

include 'header.php';
?>
<div class="typecho-login-wrap">
    <div class="typecho-login">
        <h1><a href="http://typecho.org" class="i-logo">Typecho</a></h1>
        <form action="<?=$url?>" method="post" name="login" role="form">
            <p>
                <label for="otp" class="sr-only"><?php _e('两步验证密码'); ?></label>
                <input type="text" autofocus="autofocus" id="otp" name="otp" class="text-l w-100" placeholder="<?php _e('两步验证密码'); ?>" />
            </p>
            <p class="submit">
                <button type="submit" class="btn btn-l w-100 primary"><?php _e('登录'); ?></button>
                <input type="hidden" name="referer" value="<?php echo htmlspecialchars($request->get('referer')); ?>" />
            </p>
            <p>
                <label for="remember"><input type="checkbox" name="remember" class="checkbox" value="1" id="remember" checked /> <?php _e('记住本机 (一个月内免验证)'); ?></label>
            </p>
        </form>
        
        <p class="more-link">
            <a href="<?php $options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
            <?php if($options->allowRegister): ?>
            &bull;
            <a href="<?php $options->registerUrl(); ?>"><?php _e('用户注册'); ?></a>
            <?php endif; ?>
            &bull;
            <a href="<?php $options->logoutUrl(); ?>" title="Logout"><?php _e('退出'); ?></a>
        </p>
    </div>
</div>
<?php 
include 'common-js.php';
include 'footer.php';
exit;
?>
