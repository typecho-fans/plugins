<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

JSON v1.1 - 社区维护版
======================
<h4 align="center">—— 以“api/接口名”路径按参数输出json格式博客数据插件，可用于各类映射扩展。</h4>

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

###### 一款基于Typecho的开放式API插件，支持按接口参数输出文章，首页，评论，分类等数据用于生成微信小程序。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 JSON；
##### 2. 激活插件，试试访问 http://[example].com/api/[action] 吧！

**注意事项**：
* ##### 以上地址中的[action]请替换下表列出的接口名，其后可再接?[参数名]=[值]。
* ##### 未开启地址重写功能的博客需要在域名后接/index.php再/api/[action]同上。

|接口名|可用参数|默认值|说明|
|---|:---:|:---:|---|
|posts|pageSize<br/>page<br/>authorId<br/>created<br/>cid<br/>category<br/>commentsNumMax<br/>commentsNumMin<br/>allowComment|1000<br/>1<br/>0<br/>-<br/>-<br/>-<br/>-<br/>-<br/>-|文章综合数据|
|pageList|content|false|页面综合数据|
|single|cid<br/>slug|-<br/>-|指定单页数据|
|post|同上|同上|指定文章数据|
|page|同上|同上|指定页面数据|
|relatedPosts|authorId<br/>limit<br/>type<br/>cid|-<br/>5<br/>-<br/>-|关联文章数据|
|recentPost|pageSize|10|最新文章数据|
|recentComments|pageSize<br/>parentId<br/>ignoreAuthor<br/>showCommentOnly|10<br/>0<br/>false<br/>false|最近评论数据|
|categoryList|ignore<br/>childMode|-<br/>false|分类列表数据|
|tagCloud|sort<br/>ignoreZeroCount<br/>desc<br/>limit|count<br/>false<br/>true<br/>0|标签列表数据|
|archive|format<br/>type<br/>limit|Y-m<br/>month<br/>0|归档列表数据|
|info|user|0|用户配置数据<br/>(安全考虑已注释)|
|count|-|-|博客文章总数|
|upgrade|-|-|系统检测升级<br/>(安全考虑已注释)|

##### 演示地址：[https://sangsir.com/api/posts](https://sangsir.com/api/posts)

</td>
</tr>
</table>

## 版本历史

 * v1.1 (20-6-27 [@jzwalk](https://github.com/jzwalk))
   * 合并2个衍生版本改动：
     * 文章接口增加作者数据输出[@hkq15](https://gitee.com/hkq15)；
     * 文章默认输出页数改为1000[@insoxin](https://github.com/insoxin)。
 * v1.0 (17-11-06 [@SangSir](https://github.com/szj1006))
   * 简化部分接口，调整代码格式，文章数据增加thumb字段。
 * v0.1 (17-01-08 [@jzwalk](https://github.com/jzwalk))
   * 随公子GitHub仓库作品引入Typecho-Fans目录，产生社区维护版；
   * upgrade接口改注释提高安全性，未改动版本号。
 * v0.1 (14-10-12 [@lizheming](https://github.com/lizheming))
   * 原作在GitHub发布。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![hkq15](https://portrait.gitee.com/uploads/avatars/user/526/1579006_hkq15_1578955047.png!avatar100)](https://github.com/hkq15) | [![insoxin](https://avatars1.githubusercontent.com/u/19371836?v=3&s=100)](https://github.com/insoxin) | [![szj1006](https://avatars1.githubusercontent.com/u/9147062?v=3&s=100)](https://github.com/szj1006) | [![lizheming](https://avatars1.githubusercontent.com/u/424491?v=3&s=100)](https://github.com/lizheming)
:---:|:---:|:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [hkq15](https://gitee.com/hkq15) (2019) | [insoxin](https://github.com/insoxin) (2018) | [szj1006](https://github.com/szj1006) (2017) | [lizheming](https://github.com/lizheming) (2014)

*为避免作者栏显示过长，插件信息仅选取登记3个署名，如有异议可协商修改。

## 附注/链接

本社区维护版已包含以下各版本的可用增量功能：

* [精简版(hkq15)](https://gitee.com/hkq15/Typecho-api/tree/master/JSON) - 文章接口增加作者数据输出。
* [小程序版(insoxin)](https://github.com/insoxin/typecho-json-miniprogram) - 进一步简化接口字段，增加面板设置(效用不明)。
* [精简版(szj1006)](https://github.com/szj1006/typecho-api) - 去除部分接口，增加缩略图字段。
* [原版](https://github.com/lizheming/JSON) - 实现各接口数据输出功能。

欢迎社区成员继续贡献代码参与更新。

本插件最初仅为测试用功能实现较简易，安全性等考虑更加周密的同类插件推荐[Restful](https://github.com/moefront/typecho-plugin-Restful)(支持写入接口)。

## 授权协议

沿用小程序版声明的[AGPL](https://github.com/insoxin/typecho-json-miniprogram/blob/master/LICENSE)开源协议。(要求提及出处，保持开源并注明修改。)

> JSON原作未附协议声明，原作者保留所有权利。 © [公子](https://github.com/lizheming)
