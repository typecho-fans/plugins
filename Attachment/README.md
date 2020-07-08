<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

Attachment v1.0.2 - 社区维护版
======================
<h4 align="center">—— 附件下载链接美化及跳转计数插件，计数功能需搭配Stat插件。</h4>

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

###### 支持文章内短代码形式输出附件下载链接，链接带传统别针图标，通过跳转页面实现下载次数统计，新版还可设置域名支持云储存地址。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 Attachment；
##### 2. 激活插件，如使用云储存可在设置中填写自定义域名保存，否则忽略即可；
##### 3. 编辑文章时写入`<attach>附件cid</attach>`发布即可显示下载链接。

**注意事项**：
* ##### 查看附件文件cid方法：后台进入管理-文件页面，点击文件名，地址栏?后即会显示cid等于的值。

</td>
</tr>
</table>

## 版本历史

 * v1.0.2 (20-07-08 [@jzwalk](https://github.com/jzwalk))
   * 修正输出源码符合W3C标准并本地化文本；
   * 增加附件地址域名设置兼容云储存等情况。
 * v1.0.1 (10-01-02 [@Hanny](http://www.imhan.com))
   * 修改下载地址路由。
 * v1.0.0 (09-12-12 [@Hanny](http://www.imhan.com))
   * 实现用<attach>aid</attach>添加附件的功能；
   * 与统计插件结合来实现下载次数的统计功能。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![Hanny](https://secure.gravatar.com/avatar/?d=mp&s=100)](http://www.imhan.com)
:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [Hanny](http://www.imhan.com) (2009)

## 附注/链接

* [原版](http://www.imhan.com/archives/45) - 实现附件管理器功能。

未安装并启用[Stat](https://github.com/typecho-fans/plugins/tree/master/Stat)插件时将不显示下载计数。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> Attachment原作未附协议声明，原作者保留所有权利。 © [Hanny](http://www.imhan.com)
