## 插件信息 ##

 - 描述: 找回密码
 - version: 1.0.0
 - 依赖: 14.5.26-*

## 使用帮助 ##

 1. 上传插件包
 2. 打开 `admin/login.php` 文件，做以下修改：

 ```php
 // 找到这里
 <?php if($options->allowRegister): ?>
&bull;
<a href="<?php $options->registerUrl(); ?>"><?php _e('用户注册'); ?></a>
<?php endif; ?>

// 在它下面插入以下代码
<?php
    $activates = array_keys(Typecho_Plugin::export()['activated']);
    if (in_array('Passport', $activates)) {
        echo '<a href="' . Typecho_Common::url('Passport/forgot', $options->index) . '">' . '忘记密码' . '</a>';
    }
?>
 ```


