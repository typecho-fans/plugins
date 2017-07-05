Typecho新浪微博同步插件
======================================================


## 1 描述
基于Typecho的插件服务程序，可提取Typecho文章图片作配图并同步到新浪微博。

插件通过新浪微博开放平台最新的`OAuth2`认证方式登录并调用相关的API接口同步Typecho文章。效果如下图所示：

![Typecho同步新浪微博的插件（可提取文章图片作配图）](https://o3cex9zsl.qnssl.com/2015/08/blog_synchronize_weibo.png "Typecho同步新浪微博的插件（可提取文章图片作配图）")


## 2 安装方法
##### 2.1 修改配置文件
根据新浪微博开放平台的应用的信息修改`config.php`文件中的`WB_AKEY`、`WB_SKEY`和`WB_CALLBACK_URL`这三个变量，它们分别代表应用的`App Key`、`App Secret`和回调地址。

##### 2.2 上传插件程序
把本目录（`WeiboSync`）上传至Typecho插件目录（默认为`/usr/plugins`）。

##### 2.3 插件设置
进入typecho的后台并启用`WeiboSync`插件，再点击`设置`按钮，进入到`WeiboSync`插件信息的设置页面。

![Typecho同步新浪微博的插件的设置](https://o3cex9zsl.qnssl.com/2015/08/update_weibo_plugin.png "Typecho同步新浪微博的插件的设置")

##### 2.4 注意
为了能正常使用本插件，请根据`weibo`目录下的`README`说明文件搭建一个基于新浪微博开放平台的应用服务程序。该程序可以为本插件提供所需的`access_token`（30天有效）和`uid`数据。


## 3 版本更新说明
##### 3.1 版本v1.0.0 (2015.12.11)
    1、初始化版本。

本版本详细说明：[升级博客文章同步微博的插件：PHP正则提取Markdown的图片地址](https://typecodes.com/mix/synweibophpmarkdownimgurl.html '升级博客文章同步微博的插件：PHP正则提取Markdown的图片地址')。

##### 3.2 版本1.0.0 (2017.07.04)
    1、完善所有的程序和相关文档。

本版本详细说明：[Typecho同步新浪微博的插件（可提取文章图片作配图）](https://typecodes.com/mix/typechosynweibo.html 'Typecho同步新浪微博的插件（可提取文章图片作配图）')。

