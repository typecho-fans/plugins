## 本版特点
相对于0.0.1版，0.0.2版的验证逻辑**全部更新**，推荐升级！
支持验证态保持，一次登录后，在session或cookie有效期内无需再次验证
废弃0.0.1使用的登录接口，采用插件内注册的Route来处理otp，无需等待tp返回的2s后验证
废弃0.0.1使用的插入点`header`，直接采用`common`插入

#### 兼容所有符合 [RFC6238](https://tools.ietf.org/html/rfc6238 "rfc6238") 规范的AuthOTP软件
- Microsoft Authenticator
- Google Authenticator
- 1Password
- Authy
- KeePass
- LastPass
- ...

#### 后台截图
![后台截图.png](https://static.cuojue.org/usr/uploads/2018/11/2777331120.png)

#### 验证界面截图
![验证界面截图.png](https://static.cuojue.org/usr/uploads/2018/11/86736268.png)

## 更新说明
#### 0.0.4
- [add] 支持后台直接显示二维码
- [fix] 修改为使用联图API显示二维码
- [fix] 修复博客名称为中文时扫描二维码提示错误
- [fix] 修复卸载的时候没有删除路由
- [fix] 登录成功后主动访问路由地址会显示一条msg 验证失败

#### 0.0.3
- [add] 更新支持记住本机

#### 0.0.2
+ 支持typecho最新版
+ 流程优化,符合大多数网站逻辑
 + 先验证登录信息
 + 然后再验证otp
+ 修复插入header导致的新版css错乱
+ 支持密码管理软件自动填充 (1password等)


## 食用方法
[下载插件](https://github.com/weicno/typecho-Authenticator/releases)，修改文件名为`GAuthenticator`放到`/usr/plugins`目录，然后到后台启用

插件默认关闭，首次开启需要**扫描二维码绑定**之后**填写手机上显示的代码**，验证成功之后才可以启用


------------


# 以下是 0.0.1 版的说明 已废弃

看到好多网站都支持Google Authenticator的两步验证，所以写了这个小插件，参考了很多前辈写的插件。

Google Authenticator的PHP实现来自：http://www.phpgangsta.de

#### 实现接口  
`Widget_Login->loginFail`  
`Widget_Login->loginSucceed`  
并没有用更高级的  
`Widget_User->login`  
其实按道理来说后者使用更好，但是函数内判断了如果被插件~~插~~(注册)了，就直接返回插件返回的结果……  
  
#### 插件原理说明  
插入了`('admin/header.php')->header`来重新处理整个后台页面，实现自定义登录页面，隐藏了用户名输入，固定为`_Authenticator`，修改密码输入为两步验证的代码输入。  
  
用cookies来判断验证是否成功，显示系统的登录页面，因为未登录状态typecho并没有开启PHP SESSION支持，(为了系统性能)  
  
调用`loginFail`接口，也就是登录失败的接口，实现了用系统登录接口验证两步验证的代码。  
  
调用`loginSucceed`来清理保存验证的cookies  
  
#### 已知问题  
后台没法显示图片，或者是我不知道如何显示，反正就是没显示二维码，只给了一个二维码的网址，自己打开吧  
  
如果手机丢了，没法找回的哟，但是SecretKey保存在数据库`typecho_options/plugin:GAuthenticator`里，可以手动查询再次绑定  
  
#### 使用说明  
下载插件，修改文件名为`GAuthenticator`放到`/usr/plugins`目录，然后到后台启用  

插件默认关闭，首次开启需要扫描二维码绑定之后填写手机上显示的代码，验证成功之后才可以启用  
