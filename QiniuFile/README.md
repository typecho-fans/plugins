<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

QiniuFile v1.3.3 - 社区维护版
======================
<h4 align="center">—— 新附件使用七牛云储存插件，支持自定路径/本地备份/缩略图/图片样式等。</h4>

<p align="center">
  <a href="#使用说明">使用说明</a> •
  <a href="#版本历史">版本历史</a> •
  <a href="#贡献作者">贡献作者</a> •
  <a href="#附注链接">附注/链接</a> •
  <a href="#授权协议">授权协议</a>
</p>

---

## 使用说明

<table>
<tr>
<td>

###### Qiniu File 是一款 Typecho 的七牛云存储插件，可将 Typecho 的文件功能接入到七牛云存储中，包括上传附件、修改附件、删除附件，以及获取文件在七牛的绝对网址或缩略图url。文件目录结构默认与 Typecho 的 `year/month/` 保持一致，也可自定义配置，方便迁移。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 QiniuFile；
##### 2. 激活插件，填写空间名称、Access Key、Secret Key、域名等配置；
##### 3. 保存设置，使用附件上传功能即可看到效果。

**注意事项**：
* ##### 开启在本服务器备份时本地文件保存在usr/uploads/下与插件设置相同路径内。
* ##### 缩略图模式和图片样式只可选其一，即图片样式有值时缩略图模式将不起效。

</td>
</tr>
</table>

## 版本历史

 * v1.3.3 (20-6-24 [@jzwalk](https://github.com/jzwalk))
   * 使用**7.2.10版SDK**，合并2个衍生版本功能：
     * **本地备份文件**，同步上传/删除([@lscho](https://github.com/lscho))；
     * 附件url套用**图片处理样式**后缀([@gxuzf](https://github.com/gxuzf))。
   * 增加**数据流上传**方式支持XMLRPC([@kraity](https://github.com/kraity))；
   * 去除旧版SDK和相关代码注释(PHP5.2用户可使用[QNUpload](https://github.com/typecho-fans/plugins/tree/master/QNUpload)插件)。
 * v1.3.2 (18-7-23 [@jzwalk](https://github.com/jzwalk))
   * 使用7.2.6版SDK，修正重复引用和注释问题。
 * v1.3.1 (17-2-17 [@jzwalk](https://github.com/jzwalk))
   * 随冰剑的GitHub仓库作品引入Typecho-Fans目录，产生社区维护版；
   * 使用7.1.3版SDK(PHP5.3-7.0可用)，保留旧版SDK代码及注释。
 * v1.3.0 (14-5-9 [@binjoo](https://github.com/binjoo))
   * 使用七牛图片处理API支持六种常用缩略图规格自动生成。
 * v1.2.0 (14-2-24 [@yb](https://github.com/yb))
   * 原作发布。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![gxuzf](https://avatars1.githubusercontent.com/u/61103266?v=3&s=100)](https://github.com/gxuzf) | [![kraity](https://avatars1.githubusercontent.com/u/29883656?v=3&s=100)](https://github.com/kraity) | [![lscho](https://avatars1.githubusercontent.com/u/11583677?v=3&s=100)](https://github.com/lscho) | [![JokerQyou](https://avatars1.githubusercontent.com/u/1465267?v=3&s=100)](https://github.com/JokerQyou) | [![binjoo](https://avatars1.githubusercontent.com/u/219092?v=3&s=100)](https://github.com/binjoo) |[![yb](https://avatars1.githubusercontent.com/u/25887822?v=3&s=100)](https://github.com/yb)
:---:|:---:|:---:|:---:|:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [gxuzf](https://github.com/gxuzf) (2020) | [kraity](https://github.com/kraity) (2019) | [lscho](https://github.com/lscho) (2017) | [JokerQyou](https://github.com/JokerQyou) (2016) | [binjoo](https://github.com/binjoo) (2014) | [yb](https://github.com/yb) (2014)

*为避免作者栏显示过长，插件信息仅选取登记3个署名，如有异议可协商修改。

## 附注/链接

本社区维护版已包含以下各版本功能并做优化调整：

* [衍生版(gxuzf)](https://github.com/gxuzf/QiniuFile) - 加入图片处理样式支持。
* [Issues代码段(kraity)](https://github.com/yb/qiniu-file-for-typecho/issues/4) - 加入流上传方式支持。
* [衍生版(lscho)](https://github.com/lscho/QiniuFile_For_Typecho) - 加入本地文件备份功能。
* [Fork版(JokerQyou)](https://github.com/JokerQyou/Typecho-QiniuFile) - 修正拼写，更新SDK。
* [Fork版(binjoo)](https://github.com/binjoo/QiniuFile) - 加入六种缩略图模式支持。
* [原版](https://github.com/yb/qiniu-file-for-typecho) - 实现附件功能整合，支持自定义路径。

欢迎社区成员继续贡献代码参与更新。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> QiniuFile原作未附协议声明，仅在文档中许可修改，原作者保留所有权利。 © [abelyao](https://github.com/yaobo)
