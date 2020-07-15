<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

MenuTree v0.1.2 - 社区维护版
======================
<h4 align="center">—— 嵌入式文章内容目录树插件，可使用短代码标签或模板函数自定显示位置。</h4>

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

###### 可根据文章内容中的子标题结构生成锚点目录树，点击链接即可快速跳转至相应位置。支持在文内或模板中控制输出位置，新版添加了编辑器快捷插入按钮。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 MenuTree；
##### 2. 激活插件，设置内可开关“嵌入模式”即文章标签输出，“独立模式”即模板函数输出；
##### 3. “嵌入模式”勾选时，编辑文章用按钮插入或手写`<!-- index-menu -->`标签发布即可显示目录树；
##### 4. “独立模式”勾选时，修改模板文件如post.php中写入`<?php $this->treeMenu(); ?>`也可显示。

**注意事项**：
* ##### 插件仅输出html不带css，请根据以下class命名自行处理样式：
```
    .index-menu    容器 div
    .index-menu-list    列表 ul
    .index-menu-item    列表项 li
    .index-menu-link    列表项链接 a
    .menu-target-fix {display:block; position:relative; top:-60px; //偏移量}    锚点跳转定位
```

</td>
</tr>
</table>

## 版本历史

 * v0.1.2 (20-07-15 [@jzwalk](https://github.com/jzwalk))
   * 添加自适应编辑器按钮，合并1个衍生版本功能：
     * 增加模板函数输出及复选框设置([@xxyangyoulin](https://github.com/xxyangyoulin))。
 * v0.1.1 (18-05-24 [@wuruowuxin74](https://github.com/wuruowuxin74))
   * 增加摘要隐藏处理；
   * 增加锚点定位class。
 * v0.1.0 (15-06-20 @Melon)
   * 原作发布。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![xxyangyoulin](https://avatars1.githubusercontent.com/u/25523208?v=3&s=100)](https://github.com/xxyangyoulin) | [![wuruowuxin74](https://avatars1.githubusercontent.com/u/16893894?v=3&s=100)](https://github.com/wuruowuxin74) | ![Hanny](https://secure.gravatar.com/avatar/?d=mp&s=100)
:---:|:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [xxyangyoulin](https://github.com/xxyangyoulin) (2019) | [wuruowuxin74](https://github.com/wuruowuxin74) (2018) | Melon (2015)

## 附注/链接

本社区维护版已包含以下各版本功能并做优化调整：

* [Fork版(xxyangyoulin)](https://github.com/xxyangyoulin) - 添加模板输出与面板设置。
* [修正版(wuruowuxin74)](https://github.com/wuruowuxin74/MenuTree) - 添加摘要/锚点处理。
* [原版](http://forum.typecho.org/viewtopic.php?f=6&t=8201&p=33354) - 实现插入代码替换目录树功能。

欢迎社区成员继续贡献代码参与更新。

另有带样式目录树插件[ContentIndex](https://github.com/typecho-fans/plugins/blob/master/ContentIndex)和Js悬浮目录树插件[MenuTree](https://github.com/typecho-fans/plugins/blob/master/MenuTree)可供参考。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> MenuTree原作未附协议声明，原作者保留所有权利。 © Melon
