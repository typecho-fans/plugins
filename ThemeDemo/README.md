<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

ThemeDemo v1.2.2 - 社区维护版
======================
<h4 align="center">—— 多主题外观切换演示插件，支持Cookie参数或子路径双模式，可显示导航条。</h4>

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

###### 适用多款主题预览或模板演示站等场景，整合客户端Cookie参数读取和服务端子路径生成两种模式，支持前台导航条点击即可切换主题显示。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 ThemeDemo；
##### 2. 激活插件，设置可选演示模式，开关导航条；
##### 3. 访问网站前台点击导航条或在地址栏输入主题名参数/子路径即可预览。

**注意事项**：
* ##### Cookie模式只对当前访问用户的浏览器有效，在地址栏输入`域名?theme`即可清除数据恢复默认；子路径模式则为永久目录地址，在内容相同情况下或影响网站SEO收录。

</td>
</tr>
</table>

## 版本历史
 * v1.2.2 (25-07-23) [@hongweipeng](https://github.com/hongweipeng)
   * 修复子路径模式无法使用问题；
   * 修复 cookie 模式取值问题；
 * v1.2.1 (20-07-19 [@jzwalk](https://github.com/jzwalk))
   * 综合之前所有衍生版本功能：
     * 集成Cookie与子路径双模式，增加切换选项；
     * 导航条支持子路径，调整至底部增强兼容性；
     * 子路径模式修正除文章页面外无法显示问题。
 * v1.0.1 (15-04-02 [@doudoutime](https://github.com/doudoutime))
   * 社区维护版未更新版本号但修正了使用魔术方法等问题。
 * v1.2.0 (14-11-22 [@shingchi](https://github.com/shingchi))
   * 修改Cookie模式版，减少文件读取次数直接从 cookie 取值；
   * 修正直接使用魔术方法设置配置值的方式；
   * 精简模版检测方法。
 * v1.0.1 (14-01-06 [@doudoutime](https://github.com/doudoutime))
   * Cookie模式版上传至GitHub的Typecho-Fans目录，产生社区维护版。
 * v1.0.2 (12-06-17 [@shingchi](https://github.com/shingchi))
   * 修改Cookie模式版，增加前台顶部导航条切换效果。
 * v1.0.0 (12-02-19 [@doudoutime](https://github.com/doudoutime))
   * 原作者以ThemeDemo2为名发布一款使用子路径模式的同功能插件。
 * v1.0.1 (12-02-08 [@doudoutime](https://github.com/doudoutime))
   * 原作发布，使用Cookie参数模式。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/7546325?v=3&s=100)](https://github.com/hongweipeng) | [![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![shingchi](https://avatars1.githubusercontent.com/u/1904614?v=3&s=100)](https://github.com/shingchi) | [![doudoutime](https://avatars1.githubusercontent.com/u/1299098?v=3&s=100)](https://github.com/doudoutime)
:---:|:---:|:---:|:---:
[hongweipeng](https://github.com/hongweipeng) (2025) | [jzwalk](https://github.com/jzwalk) (2020) | [shingchi](https://github.com/shingchi) (2014) | [doudoutime](https://github.com/doudoutime) (2012)

## 附注/链接

本社区维护版已包含以下各版本功能并做优化调整：

* [优化版(shingchi)](https://github.com/typechor/ThemeDemo) - 优化Cookie存取和配置检测方法效率。
* [美化版(shingchi)](#) - 增加前台导航条切换效果。
* [二代版(doudoutime)](https://plugins.typecho.me/plugins/theme-demo-2.html) - 使用子路径模式预览主题。
* [原版](http://forum.typecho.org/viewtopic.php?f=6&t=2313) - 实现Cookie参数模式预览功能。

欢迎社区成员继续贡献代码参与更新。

## 授权协议

沿用优化版声明的[GPLv2](https://github.com/typechor/ThemeDemo/blob/master/LICENSE)开源协议。(要求提及出处，保持开源并注明修改。)

> ThemeDemo原作未附协议声明，原作者保留所有权利。 © doudou
