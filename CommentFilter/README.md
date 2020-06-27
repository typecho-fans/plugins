<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

CommentFilter v1.2.1 - 社区维护版
======================
<h4 align="center">—— 全能评论过滤器插件，支持按内容/IP/昵称/链接等拦截垃圾评论。</h4>

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

###### Comment Filter 是一款给力的 Typecho 评论过滤插件，从IP/关键词黑名单到无中文屏蔽，再到隐藏域陷阱拦截机器人等，现在加入魔改的昵称/链接和首次评论过滤后功能更为全面。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 CommentFilter；
##### 2. 激活插件，选择并填写各项过滤规则配置；
##### 3. 保存设置，即可自动监管全站新发评论。

**注意**：
* 已通过session增加评论内容临时变量，可在模板输出让评论者看到自己的未审核评论。
* 此插件历史悠久，如果被spammer针对发现效果不佳可尝试另一款插件[SmartSpam](http://www.yovisun.com/archive/typecho-plugin-smartspam.html)。

</td>
</tr>
</table>

## 版本历史

 * v1.2.1 (20-6-27 [@jzwalk](https://github.com/jzwalk))
   * 上传至GitHub的Typecho-Fans目录，产生社区维护版；
   * 合并1个衍生版本功能：
     * 增加新用户判断，**过滤首次评论**[@ghostry](https://blog.ghostry.cn)；
     * 增加session输出，让评论者**可看到未审核评论**[@ghostry](https://blog.ghostry.cn)。
 * v1.2.0 (17-10-10 [@jrotty](https://github.com/jrotty))
   * 增加评论者**昵称/超链接过滤**功能。
 * v1.1.0 (14-01-04 [@Hanny](http://www.imhan.com))
   * 增加机器评论过滤。
 * v1.0.2 (10-05-16 [@Hanny](http://www.imhan.com))
   * 修正发表评论成功后，评论内容Cookie不清空的Bug。
 * v1.0.1 (09-11-29 [@Hanny](http://www.imhan.com))
   * 增加IP段过滤功能。
 * v1.0.0 (09-11-14 [@Hanny](http://www.imhan.com))
   * 实现评论内容按屏蔽词过滤功能；
   * 实现过滤非中文评论功能。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![jrotty](https://avatars1.githubusercontent.com/u/16165576?v=3&s=100)](https://github.com/jrotty) | [![ghostry](https://secure.gravatar.com/avatar/1623d5f14ef33ea40a084416f59ee93e?s=100)](https://blog.ghostry.cn) | [![Hanny](https://secure.gravatar.com/avatar/?d=mp&s=100)](http://www.imhan.com)
:---:|:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [jrotty](https://github.com/jrotty) (2017) | [ghostry](https://blog.ghostry.cn) (2012) | [Hanny](http://www.imhan.com) (2009)

## 附注/链接

本社区维护版已包含以下各版本功能：

* [魔改版备份(ClayMoreBoy)](https://github.com/ClayMoreBoy/CommentFilter-typecho) - GitHub上的源码备份。
* [魔改版(jrotty)](https://qqdie.com/archives/typecho-CommentFilter.html) - 加入昵称链接过滤。
* [衍生版(ghostry)](https://blog.ghostry.cn/program/huan-zhe-teng-cha-jian---zhe-ci-shi-commentfilter.html) - 加入首次评论过滤和待审核输出。
* [原版](http://www.imhan.com/archives/typecho_commentfilter_110) - 实现各项过滤拦截功能。

欢迎社区成员继续贡献代码参与更新。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> CommentFilter原作未附协议声明，原作者保留所有权利。 © [Hanny](http://www.imhan.com)
