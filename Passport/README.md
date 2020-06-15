# Passport
Typecho 密码找回插件

# 插件信息
1、在此基础上进行的修改：https://github.com/typecho-fans/plugins/tree/master/Passport  
2、主要更新了一下PHPMailer到最新版本  

# 使用帮助
1、上传插件包  
2、打开 admin/login.php 文件，做以下修改：
```
// 找到这里
<?php if($options->allowRegister): ?>
&bull;
<a href="<?php $options->registerUrl(); ?>"><?php _e('用户注册'); ?></a>
<?php endif; ?>

// 在它下面插入以下代码
<?php
   $activates = array_keys(Typecho_Plugin::export()['activated']);
   if (in_array('Passport', $activates)) {
       echo '<a href="' . Typecho_Common::url('passport/forgot', $options->index) . '">' . '忘记密码' . '</a>';
   }
?>
```
提示：其他地方也可以，自己根据需要进行调整吧。