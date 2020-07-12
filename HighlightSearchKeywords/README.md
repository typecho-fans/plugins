<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

HighlightSearchKeywords v0.1.3 - 社区维护版
======================
<h4 align="center">—— 高亮页面中来自搜索引擎或站内搜索的关键字，主要通过原生Js配合页面请求实现。</h4>

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

###### 默认为搜索结果页面中的关键字添加黄色背景(可在插件Plugin.php第74行自行修改)，支持从百度等搜索引擎到达的页面，方便访客迅速定位检索内容。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 HighlightSearchKeywords；
##### 2. 激活插件；
##### 3. 使用站内或外部搜索字词即可看到高亮效果。

**注意事项**：
* ##### 如果站内搜索使用Ajax可尝试为form添加class值`searchhi`自行调试，相关代码在src/highlight.js的第101行。

</td>
</tr>
</table>

## 版本历史

 * v0.1.3 (20-07-12 [@jzwalk](https://github.com/jzwalk))
   * 修正内部搜索无效问题，恢复自带样式。
 * v0.1.2 (不详 [@gouki](https://neatstudio.com))
   * 増加网站内部搜索关键字高亮。
 * v0.1.1 (不详 [@gouki](https://neatstudio.com))
   * 文件名hightlight.js写错，改为highlight.js。
 * v0.1.0 (10-06-16 [@gouki](https://neatstudio.com))
   * 高亮从google,yahoo,baidu过来的关键字。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![gouki](https://secure.gravatar.com/avatar/?d=mp&s=100)](https://neatstudio.com)
:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [gouki](https://neatstudio.com) (2010)

## 附注/链接

* [原版](https://neatstudio.com/show-1339-1.shtml) - 实现高亮搜索关键字功能。

插件使用核心年代久远，外部搜索高亮是否仍有效未严格测试，欢迎社区成员继续参与更新！

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> HighlightSearchKeywords原作未附协议声明，原作者保留所有权利。 © [gouki](https://neatstudio.com)
