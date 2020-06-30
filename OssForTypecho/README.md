<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

OssForTypecho v1.0.2 - 社区维护版
======================
<h4 align="center">—— 附件使用阿里云储存OSS插件，支持流式上传/图片样式等。</h4>

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

###### OssForTypecho是基于Typecho附件功能的阿里云储存插件。除基本的文件附件上传、修改和删除功能，还支持自定义域名和HTTPS，可在Typecho 1.x版本下运行。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 OssForTypecho；
##### 2. 激活插件，填写AccessKeyId、AccessKeySecret、Bucket名称、域名等配置；
##### 3. 保存设置，使用附件上传功能即可看到效果。

**注意事项**：
* ##### 请先在阿里云[用户信息管理](https://usercenter.console.aliyun.com/#/manage/ak)处获取AccessKeyID与AccessKeySecret(如有必要可使用RAM子账户密钥)；
* ##### 插件会替换所有之前上传的文件的链接，若启用插件前存在已上传的数据，请自行将其上传至OSS相同目录中以保证正常显示；同时，禁用插件也会导致链接恢复，也请自行将数据下载至相同目录中；
* ##### 插件不会验证配置的正确性，请自行确认配置信息正确，否则不能正常使用。

</td>
</tr>
</table>

## 版本历史

 * v1.0.2 (20-6-26 [@jzwalk](https://github.com/jzwalk))
   * 增加附件url带**图片处理样式**后缀功能，合并1个衍生版改动：
     * 使用**2.3.1版SDK**，补全地域名称([@dragonflylee](https://github.com/dragonflylee))。
   * 增加**数据流上传**方式支持XMLRPC([@kraity](https://github.com/kraity))；
   * 修复原版无法同步删除文件bug，自动设置读写权限([@AaronHoEng](https://github.com/AaronHoEng))。
 * v1.0.1 (18-08-08 [@jqjiang819](https://github.com/jqjiang819))
   * 升级OSS-PHP-SDK，使用phar包。
 * v1.0.0 (18-04-05 [@jqjiang819](https://github.com/jqjiang819))
   * 首次发布并上传至GitHub的Typecho-Fans目录，产生社区维护版。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![dragonflylee](https://avatars1.githubusercontent.com/u/6219280?v=3&s=100)](https://github.com/dragonflylee) | [![kraity](https://avatars1.githubusercontent.com/u/29883656?v=3&s=100)](https://github.com/kraity) | [<img src="https://avatars1.githubusercontent.com/u/29192241?v=3" alt="AaronHoEng" height="100" />](https://github.com/AaronHoEng) | [![jqjiang819](https://avatars1.githubusercontent.com/u/9775943?v=3&s=100)](https://github.com/jqjiang819)
:---:|:---:|:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [dragonflylee](https://github.com/dragonflylee) (2019) | [kraity](https://github.com/kraity) (2019) | [AaronHoEng](https://github.com/AaronHoEng) (2019) | [jqjiang819](https://github.com/jqjiang819) (2018)

*为避免作者栏显示过长，插件信息仅选取登记2个署名，如有异议可协商修改。

## 附注/链接

本社区维护版已包含以下各版本功能并做优化调整：

* [Fork版(dragonflylee)](https://github.com/dragonflylee/typecho-plugin-ossfile) - 更新SDK和地域名称。
* [Issues代码段(kraity)](https://github.com/jqjiang819/typecho-plugin-ossfile/issues/6) - 加入流上传方式支持。
* [Issues代码段(AaronHoEng)](https://github.com/jqjiang819/typecho-plugin-ossfile/issues/3) - 解决删除问题，增加权限设置。
* [原版](https://github.com/jqjiang819/typecho-plugin-ossfile) - 实现附件功能整合。

欢迎社区成员继续贡献代码参与更新。

另有基于原版的二次开发版插件[OssImg](https://github.com/v03413/Typecho_Plugins/tree/master/OssImg)可供参考。

## 授权协议

沿用原作声明的[MIT](https://github.com/jqjiang819/typecho-plugin-ossfile/blob/master/LICENSE)开源协议。(要求提及出处。)

> OssForTypecho附带MIT许可证，原作者保留著作权及相关权利。 © [Charmeryl](https://github.com/jqjiang819)