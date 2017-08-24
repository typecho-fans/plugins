基于新浪微博开放平台OAuth2协议的应用服务的程序
======================================================


## 1 描述
用于搭建一个基于新浪微博开放平台`OAuth2`协议的应用服务的程序，它为`WeiboSync`插件提供博客作者当前新浪微博对应的`access_token`（30天有效）和`uid`数据。


## 2 使用方法
##### 2.1 新浪微博开放平台的应用
用户必须有一个已经审核通过的新浪微博开放平台的应用。申请方法这里不再赘述，详见[官方说明](http://open.weibo.com/authentication/ '微博登录介绍')。

##### 2.2 修改配置文件
根据新浪微博开放平台的应用的信息修改`config.php`文件中的`WB_AKEY`、`WB_SKEY`和`WB_CALLBACK_URL`这三个变量，它们分别代表应用的`App Key`、`App Secret`和回调地址。

![新浪微博开放平台OAuth2.0授权回调地址](https://o3cex9zsl.qnssl.com/2017/07/sinaweiboauthcallback_1.png "新浪微博开放平台OAuth2.0授权回调地址")

##### 2.3 博客搭建回调服务
把本目录（`weibo`）上传至对应的新浪微博应用服务器，如果能通过访问该回调地址（例如`https://typecodes.com/weibo`）则表示服务搭建完毕。


## 3 版本更新说明
##### 3.1 版本v1.0.0 (2015.12.11)
    1、初始化版本。

本版本详细说明：[升级博客文章同步微博的插件：PHP正则提取Markdown的图片地址](https://typecodes.com/mix/synweibophpmarkdownimgurl.html '升级博客文章同步微博的插件：PHP正则提取Markdown的图片地址')。

##### 3.2 版本1.0.0 (2017.07.04)
    1、完善所有的程序和相关文档。

本版本详细说明：[Typecho同步新浪微博的插件（可提取文章图片作配图）](https://typecodes.com/mix/typechosynweibo.html 'Typecho同步新浪微博的插件（可提取文章图片作配图）')。

##### 3.3 版本1.0.0 (2017.08.23)
    1、同步新浪微博开放平台官方php sdk，修改调用接口。


## 4 鸣谢
感谢新浪微博开放平台官方提供的`V2版PHP SDK`。
