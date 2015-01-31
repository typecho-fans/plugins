GeeTest验证码插件
=============
评论框验证码插件，防止垃圾评论，作者「[啸傲居士](http://jiya.io)」。

2015年2月1日更新：

1. 更新[geetestlib](https://github.com/GeeTeam/gt-php-sdk/)到最新版本；
2. 增加样式选择选项；
3. 如果选择弹出样式，请将提交按钮id设为“submit-button”

说明：请先禁用后再更新本插件。

---

在[seccode代码](http://521-wf.com/archives/36.html)的基础上修改，可以到[官方体验页面](http://geetest.com/experience)体验。


### 使用说明

1. 在[GeeTest官网](http://my.geetest.com/)页面申请Captcha Key（即ID）和Private Key（即Key）；
2. 把插件文件夹上传到usr/plugins/目录下；
2. 进入后台，点击“激活”，并配置Key；
3. 在模板中加入显示验证码的代码，找到对应模板目录下的comments.php文件，然后在提交按钮前加入如下代码（这只是个方法，不是必须与下面代码一模一样，可以根据自己的需要做稍微的改动）：

```
<?php 
	if(!$this->user->hasLogin()) {
		GeeTest_Plugin::output();
	}
?>
<div style="clear: both;margin: 15px 0;zoom: 1;">
		<button id="submit-button" type="submit" class="submit"><?php _e('提交评论'); ?></button>
</div>
```

### 附：

为方便大家，特提供如下key：

```
Public Key: 6d1d522f9af8576c4287bde5d1963047
Private Key: 88aa2a14010d795a3d27d9f24fec4ba6
```