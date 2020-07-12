<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

SplitArchivePage v0.1.7 - 社区维护版
======================
<h4 align="center">—— 在文章内部插入自定义分页符输出分页效果插件，可定制上下页导航文字，自带美化样式。</h4>

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

###### 当你的文章内容很长时可用本插件进行简单分页。使用原生Get请求生成标准盒装分页，可自动显示编辑器按钮点击插入分页符。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 SplitArchivePage；
##### 2. 激活插件，在设置中指定分页符与导航文字；
##### 3. 编辑文章或页面用编辑器按钮插入分页符发布即可看到分页效果。

##### 演示地址：https://ffis.me/experience/1815.html

</td>
</tr>
</table>

## 版本历史

 * v0.1.7 (20-07-12 [@jzwalk](https://github.com/jzwalk))
   * 修正插入分页符按钮为默认Markdown编辑器或通用自判断型。
 * v0.1.6 (20-02-26 [@noisky](https://github.com/noisky))
   * 修复了 Typecho1.1 后无法识别分页标记问题，优化了显示样式。
 * v0.1.5 (不详 [@gouki](https://neatstudio.com))
   * 原有的程序只支持一个 GET 变量，现在已修正，只要是 GET 变量都支持。
 * v0.1.4 (不详 [@gouki](https://neatstudio.com))
   * 修正了Rewrite规则下，还会自动加上index.php的BUG，目前在Rewrite规则下去除了index.php。
 * v0.1.3 (不详 [@gouki](https://neatstudio.com))
   * 修正了内容页中如果没有插入分页符内容不能显示的BUG（疏忽）。
 * v0.1.2 (不详 [@gouki](https://neatstudio.com))
   * 基本功能实现。
 * v0.1.0~v0.1.1 (10-06-13 [@gouki](https://neatstudio.com))
   * 不详。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![noisky](https://avatars1.githubusercontent.com/u/7553053?v=3&s=100)](https://github.com/noisky) | [![gouki](https://secure.gravatar.com/avatar/?d=mp&s=100)](https://neatstudio.com)
:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [noisky](https://github.com/noisky) (2020) | [gouki](https://neatstudio.com) (2010)

## 附注/链接

* [修正版](https://github.com/noisky/SplitArchivePage) - 适配1.1版并美化样式。
* [原版](https://neatstudio.com/show-1333-1.shtml) - 实现文章内容分页功能。

分页底部的盒装导航样式可通过修改pagebar.css文件自行定制。

## 授权协议

沿用修正版声明的[Apache2](https://github.com/noisky/SplitArchivePage/blob/master/LICENSE)开源协议。(要求提及出处，不得用于广告)

> SplitArchivePage原作未附协议声明，原作者保留所有权利。 © [gouki](https://neatstudio.com)
