Typecho新浪微博同步插件
======================================================


## 1 描述
#### 1.1 weibo
用于搭建一个基于新浪微博开放平台`OAuth2`协议的应用服务的程序，它为`WeiboSync`插件提供博客作者当前新浪微博对应的`access_token`（30天有效）和`uid`数据。
	
#### 1.2 WeiboSync
基于Typecho的插件服务程序，可提取Typecho文章图片作配图并同步到新浪微博。同步效果如下图所示：

![Typecho同步新浪微博的插件（可提取文章图片作配图）](https://o3cex9zsl.qnssl.com/2015/08/blog_synchronize_weibo.png "Typecho同步新浪微博的插件（可提取文章图片作配图）")


## 2 安装方法
详见`weibo`和`WeiboSync`这两个目录下的`README`文件。


## 3 版本更新说明
#### 3.1 版本v1.0.0 (2015.12.11)
    1、初始化版本。

本版本详细说明：[升级博客文章同步微博的插件：PHP正则提取Markdown的图片地址](https://typecodes.com/mix/synweibophpmarkdownimgurl.html '升级博客文章同步微博的插件：PHP正则提取Markdown的图片地址')。

#### 3.2 版本1.0.0 (2017.07.04)
    1、完善所有的程序和相关文档。

本版本详细说明：[Typecho同步新浪微博的插件（可提取文章图片作配图）](https://typecodes.com/mix/typechosynweibo.html 'Typecho同步新浪微博的插件（可提取文章图片作配图）')。

##### 3.3 版本1.0.0 (2017.08.23)
    1、同步新浪微博开放平台官方php sdk，修改调用接口。

注意：新浪微博最新官方要求share接口至少要带上一个【安全域名】下的链接，也就是在`WeiboSync\README.md`文件中的2.3小节填写`微博内容`时，必须包含`{link}`参数。

