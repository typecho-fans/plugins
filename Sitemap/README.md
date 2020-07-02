<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

Sitemap v1.0.4 - 社区维护版
======================
<h4 align="center">—— 动态生成符合搜索引擎收录标准的Xml格式站点地图插件，支持输出分类/标签页地址。</h4>

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

###### Sitemap 可方便站长通知搜索引擎网站上有哪些可供抓取的网页。最简单通行的就是Google制定的XML格式标准，其中可列出网址及其元数据（上次更新时间、更改频率和优先级权重等），便于搜索引擎更高效智能地抓取网站内容。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 Sitemap；
##### 2. 激活插件；
##### 3. 访问http://[example].com/sitemap.xml即可看到页面效果。

</td>
</tr>
</table>

## 版本历史

 * v1.0.4 (20-7-02 [@jzwalk](https://github.com/jzwalk))
   * 增加首页地址输出，合并4个衍生版改动：
   * 含魔改版Sitemap功能支持分类/标签地址输出([@kavico](https://minirizhi.com))；
   * 对调页面文章优先级使Google正常收录([@raymond9zhou](https://github.com/raymond9zhou))；
   * 简化样式说明，优化时间戳仅保留日期([@bayunjiang](https://github.com/bayunjiang))；
   * 页面路径带xml文件后缀，套用xsl美化样式([@Suming](https://inwao.com))。
 * v1.0.3 (17-03-28 [@Vicshs](https://github.com/Vicshs))
   * 增加标签页地址遍历；
   * 提升页面地址优先级。
 * v1.0.2 ([@Vicshs](https://github.com/Vicshs))
   * (不详)
 * v1.0.1 (10-01-02 [@Hanny](http://www.imhan.com))
   * 修改自定义静态链接时错误的Bug。
 * v1.0.0 (10-01-02 [@Hanny](http://www.imhan.com))
   * 实现生成含文章页面地址的Google标准sitemap页面。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![kavico](https://secure.gravatar.com/avatar/03b9df1a08dd482503a70e9339b4888b?s=100)](https://minirizhi.com) | [![raymond9zhou](https://avatars1.githubusercontent.com/u/28761293?v=3&s=100)](https://github.com/raymond9zhou) | [![bayunjiang](https://avatars1.githubusercontent.com/u/19381311?v=3&s=100)](https://github.com/bayunjiang) | [![Suming](https://secure.gravatar.com/avatar/433daae294c13cd6ca7246e84a721038?s=100)](https://inwao.com) | [![Hanny](https://secure.gravatar.com/avatar/?d=mp&s=100)](http://www.imhan.com)
:---:|:---:|:---:|:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [kavico](https://minirizhi.com) (2020) | [raymond9zhou](https://github.com/raymond9zhou) (2019) | [bayunjiang](https://github.com/bayunjiang) (2017) | [Suming](https://inwao.com) (2016) | [Hanny](http://www.imhan.com) (2010)

*为避免作者栏显示过长，插件信息仅选取登记2个署名，如有异议可协商修改。

## 附注/链接

本社区维护版已包含以下各版本功能并做优化调整：

* [合并版(kavico)](http://forum.typecho.org/viewtopic.php?f=6&t=12315&p=45414) - 含输出分类功能。
* [微调版(raymond9zhou)](https://github.com/raymond9zhou/typecho-auto-sitemap-plugin) - 调整优先级。
* [增强版(Vicshs)](https://github.com/Vicshs/Sitemap) - 输出tag，提升优先级。
* [简化版(bayunjiang)](https://github.com/bayunjiang/typecho-sitemap) - 简化样式及时间戳。
* [美化版(Suming)](https://inwao.com/Sitemap.html) - 改xml后缀，套用样式。
* [原版](http://www.imhan.com/typecho) - 实现基本功能。

欢迎社区成员继续贡献代码参与更新。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> Sitemap原作未附协议声明，原作者保留所有权利。 © [Hanny](http://www.imhan.com)
