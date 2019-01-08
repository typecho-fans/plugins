## 插件简介

LoveKKComment是一款Typecho邮件通知类插件，支持SMTP、Send Cloud、阿里云邮件推送三种邮件通知方式。

在评论审核通过、用户评论文章、用户评论被回复时对不同场景进行不同的邮件通知。

## 安装方法

> 1. 至[releases](https://github.com/typecho-fans/plugins/releases/tag/plugins-H_to_L)中下载最新版本插件；
> 2. 将下载的压缩包进行解压并上传至`Typecho`插件目录中，注意目录名称更改为`LoveKKComment`；
> 3. 后台激活插件；
> 4. 根据自己的实际情况选择邮件发送接口方式；
> 5. 根据所选的邮件发送接口，配置相应接口参数。

## 自定义模板说明

插件共有三个模板，保存在插件`theme`目录下，分别为：

> 1. approved.html：邮件审核通过通知模板。
> 2. author.html：文章评论通知作者模板。
> 3. reply.html：评论回复通知被回复者模板。

三个模板使用变量作为内容替换，您只需在自己的模板中增加相应的模板变量即可，模板变量列表如下：

### approved.html

> 1. {blogUrl}：博客地址
> 2. {blogName}：博客名称
> 3. {author}：评论者名称
> 4. {permalink}：文章链接
> 5. {title}：文章标题
> 6. {text}：评论内容

### author.html

author.html内变量与approved.html内变量一致。

### reply.html

> 1. {blogUrl}：博客地址
> 2. {blogName}：博客名称
> 3. {author}：被回复者名称
> 4. {permalink}：文章链接
> 5. {title}：文章标题
> 6. {text}：被回复者评论内容
> 7. {replyAuthor}：回复者名称
> 8. {replyText}：回复内容

## 更新日志

### 2019.01.08

> 1. 新增异步回调邮件发送模式，仅在Typecho版本大于1.1/17.10.30时使用
> 2. 新增配置验证模式，Send Cloud验证API USER及API KEY正确性，SMTP验证登录正确性，阿里云仅验证是否填写
> 3. 与LoveKKForget插件合并，可自由开启
> 4. 去除新版本检测功能，请使用[TeStore](http://www.yzmb.me/archives/net/testore-for-typecho)进行版本检测

**修复**

> Typecho的当前稳定版1.1和开发版1.2的Helper中，都有一个widgetById方法，是直接根据表名和主键获取数据对象，由于稳定版在初始化数据类时没有传入参数，导致初始化错误，引起程序报错，开发版已经修复了这个问题。
> 而今日更新的1.0.5插件，在编写时未注意，将原本自己编写的获取数据对象方法弃用，使用官方的widgetById方法，导致了在1.1正式版中评论报错问题，2019.01.08 23:53修复此问题，若存在此问题的朋友，请下载修复后的插件，覆盖`Plugin.php`文件即可。

### 2018.09.03

> 由于今年备案规则，限制部分后缀域名备案，担心后续会影响网站数据，将仓库再次迁移回github.com

### 2018.08.19

> 1. 新增Debug模式
> 2. 修复PHPMailer发信时的小错误

### 2018.08.14

> 1. 增加SMTP邮件发送方式
> 2. 增加阿里云邮件推送发送方式
> 3. 更改SendCloud发送方式为普通发送，不再使用模板发送
> 4. 邮件模板更改为本地HTML模板
> 5. 自由选择邮件发送方式
> 6. 去除Action.php文件

### 2018.08.08

> 1. 修正版本检测地址
> 2. 符合TeStore插件
> 3. 仓库迁移

### 2018.03.28

> 增加评论作者通知功能（用户评论后自动发送邮件通知文章作者）