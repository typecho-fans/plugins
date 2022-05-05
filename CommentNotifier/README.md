# typecho-CommentNotifier

#### 项目介绍

Typecho博客评论邮件提醒，支持异步回调（异步回调优点就是不影响博客评论提交速度），支持编辑邮件模板，支持第三方开发邮件模板

#### 安装教程

- 下载后将压缩包解压到 `/usr/plugins` 目录
- 文件夹名改为`CommentNotifier`
- 登录管理后台，激活插件
- 配置插件 填写SMTP参数

### 插件升级
旧版本升级至1.2.5版本需要禁用删除旧版本的文件，然后传新的上去！

#### 软件架构

- `typecho`版本为`1.2.0`及以上
- `php: >=7.2.0`
- 如果启用SMTP加密模式`PHP`需要打开`openssl`扩展
- 邮件服务基于[`PHPMailer`](https://github.com/PHPMailer/PHPMailer/ )

#### 发信逻辑
文章收到新评论后，如果评论有父级，则发提醒给父级评论，否则发给提醒给文章作者；
如果文章作者邮箱为空，则发提醒给站长邮箱（需要在插件设置里设置）；

如果是待审核的评论则提提醒给站长邮箱，等站长在后台审核后再发提醒给评论的父级评论；
如果没有父级评论则发给文章作者；

同时自己评论自己文章，自己回复自己的情况默认不发邮件提醒。

### 邮件模板

template文件夹里存放的就是邮件发信模板，大家可以参考内置的两个模板来写属于自己的邮件模板，当然也可以在后台直接修改默认模板来达到邮件美化的作用！


#### 感谢

[https://gitee.com/HoeXhe/typecho-Comment2Mail](https://gitee.com/HoeXhe/typecho-Comment2Mail)

[https://github.com/typecho-fans/plugins/tree/master/CommentToMail](https://github.com/typecho-fans/plugins/tree/master/CommentToMail)