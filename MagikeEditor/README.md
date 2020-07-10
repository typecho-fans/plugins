<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

MagikeEditor v1.1.1 - 社区维护版
======================
<h4 align="center">—— Magike博客(Typecho前身)移植版Html编辑器插件，支持自定义按钮，插入链接替换及快捷键等。</h4>

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

###### 原Magike博客自带简易编辑器，移植版解决mootool与jquery冲突还原了大部分功能，可定制按钮，插入图链并自动替换URL地址等。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 MagikeEditor；
##### 2. 激活插件，进入设置自定义按钮，修改链接插入方式等；
##### 3. 撰写文章或页面时即可看到编辑器效果。

**注意事项**：
* ##### 自定义按钮格式为每行英文逗号隔开4种值，即`按钮名,<前标签>,</后标签>,快捷键`。快捷键填写单字母，使用时用alt+字母组合键即可。

</td>
</tr>
</table>

## 版本历史

 * v1.1.1 (20-07-10 [@jzwalk](https://github.com/jzwalk))
   * 修正自定义按键默认值及引号转义问题；
   * 修正搭配Attachment插件获取cid问题。
 * v1.1.0 (14-01-15 [@Hanny](http://www.imhan.com))
   * 支持Typecho 0.9；
   * 新方法兼容Attachment插件；
   * 小小修改图片的插入方式。
 * v1.0.3 (10-10-13 [@Hanny](http://www.imhan.com))
   * 修正一个JS的Bug。
 * v1.0.2 (10-10-08 [@Hanny](http://www.imhan.com))
   * 与附件管理器插件相接；
   * 多动插入图片的方式选择；
   * 允许自定义一些简单按钮；
   * 自动转换 http https ftp 地址。
 * v1.0.1 (09-12-01 [@Hanny](http://www.imhan.com))
   * 修正一个插入图片的Bug。
 * v1.0.0 (09-11-27 [@Hanny](http://www.imhan.com))
   * 完成从Magike的移植。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![Hanny](https://secure.gravatar.com/avatar/?d=mp&s=100)](http://www.imhan.com)
:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [Hanny](http://www.imhan.com) (2009)

## 附注/链接

* [原版](http://www.imhan.com/archives/18) - 实现编辑器基本功能移植。

本插件最初为测试用未实现所有功能移植，可配合[Attachment](https://github.com/typecho-fans/plugins/tree/master/Attachment)自动插入cid附件标签。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> MagikeEditor原作未附协议声明，原作者保留所有权利。 © [Hanny](http://www.imhan.com)
