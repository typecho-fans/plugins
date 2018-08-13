[typecho][1]评论验证码插件，使用Google的[reCAPTCHA][2]接口。
======

#### 使用方法：
1. 到「[页面][3]」申请一个API key；
2. 激活该插件，并配置Public Key和Private Key；
3. 在适当地方添加如下代码：

```
<?php reCAPTCHA_Plugin::output(); ?>
```
**注意：若未配置Public Key和Private Key而在页面中先添加上述代码，该页面会报错**

[1]: http://typecho.org/about
[2]: https://www.google.com/recaptcha/
[3]: https://www.google.com/recaptcha/admin/create
