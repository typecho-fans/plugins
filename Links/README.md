<a href="https://typecho-fans.github.io">
		<img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

Links v1.3.1 - 社区维护版
======================
<h4 align="center">—— 功能强大的友情链接管理插件，支持模板/内容调用/图文混合模式/自定义数据扩展等。</h4>

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

###### 本版本友情链接插件支持以下功能：
###### 1、自建独立数据表，干净无上限的添加友情链接信息。
###### 2、两种调用方式：函数方式，用于主题模板侧边栏等嵌入位置显示；HTML标签方式，用于独立页面等编辑内容显示。
###### 3、三种输出模式：文字友链、图片友链和图文混合友链，可自定义源码。（1.3.0之后添加模板，输出模式不限于默认的这三种）
###### 4、管理面板：支持友链的分类，拖拽排序及启用禁用等。
###### 5、可根据邮箱解析Gravatar头像作为友链图片。
###### 6、可添加自定义数据，方便用户做个性化扩展。
###### 7、文章重写功能，适配几乎所有主题。
###### 8、主题模板功能，丰富输出模式

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 Links；
##### 2. 激活插件，点击菜单-管理-友情链接，在面板右侧填写各项信息依次添加；
##### 3. 在主题sidebar.php文件种的适当位置写入`<?php $this->links(); ?>`即可显示。

**注意事项**：
* ##### 以上调用函数内支持用英文逗号隔开4个参数，即`$this->links( "模式", "数目", "分类", "尺寸" )`，赋值如下表：

|参数|可用值|缺省值|说明|
|---|:---:|:---:|:---:|
|模式|SHOW_TEXT<br/>SHOW_IMG<br/>SHOW_MIX|SHOW_TEXT|仅输出文字<br/>仅输出图片<br/>图片+文字|
|数目|整数|0<br/>(不限制)|输出链接条数|
|分类|分类名|ALL<br/>(所有)|输出单类链接|
|尺寸|整数|32<br/>(像素)|输出图片大小|

##### 模板函数带参数调用实例——
###### 图文混合链接 - `<?php $this->links("SHOW_MIX"); ?>`
###### 十个文字链接 - `<?php $this->links("SHOW_TEXT", 10); ?>`
###### 指定分类链接 - `<?php $this->links("SHOW_TEXT", 0, "testsort"); ?>`
###### 指定图片尺寸 - `<?php $this->links("SHOW_IMG", 0, "ALL", 64); ?>`

* ##### 想在文章或页面内容中展示友情链接则需在编辑器内写入HTML代码：`<links></links>`，同样支持上表参数。

##### HTML代码带参数调用实例——
###### 图文混合链接 - `<links>SHOW_MIX</links>`
###### 十个文字链接 - `<links 10>SHOW_TEXT</links>`
###### 指定分类链接 - `<links 0 testsort></links>`或`<links testsort></links>`
###### 指定图片尺寸 - `<links 0 ALL 64>SHOW_IMG</links>`或`<links ALL 64>SHOW_IMG</links>`

* ##### 新版插件可以直接在启用-禁用链接旁的原生设置面板中自定义各输出模式源码规则并指定默认图片尺寸。

</td>
</tr>
</table>

## 版本历史

 * v1.3.1 at 2025-02-09 by [@lhl77](https://github.com/lhl77)
	 * 优化 一些细节 [@lhl77](https://github.com/lhl77)

 * v1.3.0 at 2025-02-09 by [@lhl77](https://github.com/lhl77)
	 * 优化 UI - Material Design 3 [@lhl77](https://github.com/lhl77)
	 * 添加 Links Plus到菜单，更加方便操作 [@lhl77](https://github.com/lhl77)
	 * 添加 模板，能够更灵活地自定义输出结构，支持 CSS/JS 注入 [@lhl77](https://github.com/lhl77)
	 * 添加 正文重写，免去修改/重新开发主题的步骤 [@lhl77](https://github.com/lhl77)

 * v1.2.7 (24-6-21 [@jrotty](https://github.com/jrotty))
	 * 解决php8.2一处报错问题

 * v1.2.6 (23-5-15 [@jrotty](https://github.com/jrotty))
	 * 支持主题作者自定义友链 html 结构

 * v1.2.5 (23-3-27 [@Mejituu](https://github.com/Mejituu))
	 * 友链添加 noopener 外链属性
	 * 内置友链邮箱解析头像链接 api 接口调整为仅内部调用
	 * Action 和内置友链邮箱解析头像链接 api 接口使用加盐地址
	 * 文本字段入库过滤 XSS
	 * 增加图片尺寸参数支持
	 * 增加规则和默认图片尺寸设置选项
	 * 修复历史遗留问题更新 lid 导致报错


 * v1.2.3 (20-6-30 [@jzwalk](https://github.com/jzwalk))
	 * 在[@Mejituu](https://github.com/Mejituu)维护版基础上修复优化，合并1个微调版改动：
		 * 友链添加rel=noopener标签加强安全性([@he0119](https://github.com/he0119/typecho-links))。
	 * Action使用加盐地址，文本字段入库过滤XSS加强安全性([@jzwalk](https://github.com/jzwalk))；
	 * 修复自动获取邮箱头像Api失效问题，增加图片尺寸参数支持([@jzwalk](https://github.com/jzwalk))；
	 * 增加原生设置选项，方便修改源码规则和默认图片尺寸等([@jzwalk](https://github.com/jzwalk))。
 * v1.2.2 (20-03-11 [@Mejituu](https://github.com/Mejituu))
	 * 修复一个小BUG。
 * v1.2.1 (20-02-16 [@Mejituu](https://github.com/Mejituu))
	 * 修复邮箱头像解析问题；
	 * 优化逻辑问题。
 * v1.2.0 (20-02-16 [@Mejituu](https://github.com/Mejituu))
	 * 增加友链禁用功能；
	 * 增加友链邮箱功能；
	 * 增加友链邮箱解析头像链接功能；
	 * 修正数据表占用大小问题。
 * v1.1.3 (20-02-08 [@Mejituu](https://github.com/Mejituu))
	 * 修复已存在表激活失败、表检测失败。
 * v1.1.2 (19-08-26 [@jrotty](https://github.com/jrotty))
	 * 修复Action越权漏洞。
 * v1.1.1 (14-12-14 [@Hanny](http://www.imhan.com))
	 * 修正兼容Typecho 1.0。
 * v1.1.0 (13-12-08 [@Hanny](http://www.imhan.com))
	 * 修正兼容Typecho 0.9。
 * v1.0.4 (10-06-30 [@Hanny](http://www.imhan.com))
	 * 修正数据表前缀问题；
	 * 源码规则支持所有数据表字段。
 * v1.0.3 (10-06-20 [@Hanny](http://www.imhan.com))
	 * 修改图片链接支持方式；
	 * 增加链接分类功能；
	 * 增加自定义数据；
	 * 增加多种输出模式；
	 * 增加较详细的帮助文档；
	 * 增加页面标签调用方式。
 * v1.0.2 (10-05-16 [@Hanny](http://www.imhan.com))
	 * 增加SQLite支持。
 * v1.0.1 (09-12-27 [@Hanny](http://www.imhan.com))
	 * 增加链接描述显示；
	 * 增加首页链接数量限制功能；
	 * 增加图片链接功能。
 * v1.0.0 (09-12-12 [@Hanny](http://www.imhan.com))
	 * 实现基本功能，包括添加，删除，修改和排序等。

## 贡献作者

[![lhl77](https://avatars1.githubusercontent.com/u/70808385?s=100&v=3)](https://github.com/lhl77)|[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![he0119](https://avatars1.githubusercontent.com/u/5219550?v=3&s=100)](https://github.com/he0119) | [![Mejituu](https://avatars1.githubusercontent.com/u/36153418?v=3&s=100)](https://github.com/Mejituu) | [![jrotty](https://avatars1.githubusercontent.com/u/16165576?v=3&s=100)](https://github.com/jrotty) | [![Hanny](https://secure.gravatar.com/avatar/?d=mp&s=100)](http://www.imhan.com)
:---:|:---:|:---:|:---:|:---:
[lhl77](https://github.com/lhl77) (2026)|[jzwalk](https://github.com/jzwalk) (2020) | [he0119](https://github.com/he0119) (2020) | [Mejituu](https://github.com/Mejituu) (2020) | [jrotty](https://github.com/jrotty) (2019) | [Hanny](http://www.imhan.com) (2009)

*为避免作者栏显示过长，插件信息仅选取登记2个署名，如有异议可协商修改。

## 附注/链接

本社区维护版已包含以下各版本功能并做优化调整：

* [微调版(he0119)](https://github.com/he0119/typecho-links) - 添加noopener外链标记。
* [维护版(Mejituu)](https://github.com/Mejituu/Links) - 修复激活报错，新增邮箱配置及多项优化。
* [修复版(jrotty)](https://qqdie.com/archives/links-typecho-plugin.html) - 修复Action越权漏洞。
* [原版](http://www.imhan.com/archives/typecho-links) - 实现多种格式友链数据输出。

欢迎社区成员继续贡献代码参与更新。

## 授权协议

沿用维护版声明的[MIT](https://github.com/Mejituu/Links/blob/master/LICENSE)开源协议。(要求提及出处。)

> Links原作未附协议声明，原作者保留所有权利。 © [Hanny](http://www.imhan.com) 按照这个模板修改

## 目录

- [功能](#增强版功能清单)
- [环境要求](#环境要求)
- [安装](#安装)
- [说明](#配置说明)
	- [模板选择](#模板选择)
	- [正文重写](#正文重写)
	- [高级自定义](#高级自定义)
    - [后台管理](#后台管理)
- [主题](#主题)
- [常见问题](#常见问题)
- [仓库与帮助](#仓库与帮助)
- [许可](#许可)

---

## 增强版功能清单

- 多模板输出（内置主题，支持自定义模板目录 `templates/`）
- 模板可携带 CSS/JS 注入，支持模板级别交互
- 正文重写（支持按 cid 重写、块标记、可多次重写）

## 原版功能保留
- 支持 `<links>...</links>` 标签与参数（向后兼容）
- 后台友链管理：添加/编辑/分类/拖拽排序/启用禁用

---

## 环境要求

- Typecho（1.2.x+）
- PHP 7.2+

---

## 安装

1. 下载本插件并解压到：
	- `usr/plugins/Links/`
2. 确认目录结构包含：
	- `usr/plugins/Links/Plugin.php`
	- `usr/plugins/Links/manage-links.php`
	- `usr/plugins/Links/templates/`（内含模板）
3. 后台 → 控制台 → 插件，启用 **Links Plus**。

---

## 配置说明

后台 → 插件 → Links Plus：

### 模板选择
- 支持文件模板（`templates/<name>/`），`manifest.json` 控制 CSS/JS 注入。

### 正文重写
- 当主题不走 `contentEx` 导致 `<links>...</links>` 无法解析时，可使用“正文重写”将占位符替换为友链 HTML。
- 支持按 `cid` 重写、块标记 `<!-- LINKS_PLUS_START -->...<!-- LINKS_PLUS_END -->`，并可选择输出模板。

### 高级自定义
- 保留旧版源码规则（SHOW_TEXT/SHOW_IMG/SHOW_MIX）用于兼容；优先推荐使用文件模板管理输出。

---

## 后台管理

- 后台 → 扩展 → 友情链接（管理界面为 MD3 风格卡片与表格管理）
- 支持批量导入/导出、按分类过滤、图片尺寸设置、默认图片配置等

---

## 主题

- 模板目录为 `templates/<name>/`。
- 必要文件：`manifest.json`、`template.html`。
- 可选文件：`style.css`、`script.js`（`manifest.json` 中 `inject` 决定是否注入）。
- 模板占位符：`{name}` `{url}` `{image}` `{description}` `{sort}` `{lid}` 等。

### 主题开发请查阅：https://blog.lhl.one/artical/902.html

---

## 常见问题

1. 样式被主题覆盖
- 尽力避免使用 `<a>` 标签直接输出，模板采用 `role="link"` + `data-href` 的跳转方案；如仍被覆盖可在自定义模板中引入更强选择器或 `!important`。

2. 模板资源未注入
- 确认模板下 `manifest.json` 中 `inject.css`/`inject.js` 设置为 `true` 并且前端没有被 CSP 等策略阻止。

---

## 仓库与帮助

- 插件仓库： https://github.com/lhl77/Typecho-Plugin-LinksPlus
- 使用帮助： https://blog.lhl.one/artical/902.html 

如果你需要更详细的开发/模板示例，可以在仓库 Issues 或 PR 提问。

---

## 许可

MIT（以仓库 `LICENSE` 为准）
