# [EditorMD 1.5.0](https://dt27.cn/php/editormd-for-typecho/)

![EditorMD](https://dt27.cn/usr/uploads/2016/03/2477390697.png)

**Markdown 编辑器 [Editor.md](https://pandao.github.io/editor.md/) for Typecho**

## 环境要求

- Typecho 1.2 或 1.3
- PHP 7.4 或 PHP 8.x

## Features
- 支持实时预览、代码/表格插入、代码折叠等功能；
- 支持 [Emoji 表情](http://www.emoji-cheat-sheet.com/)
- 支持 [ToC（Table of Contents）](https://pandao.github.io/editor.md/examples/toc.html)、[Github Task lists](https://pandao.github.io/editor.md/examples/task-lists.html) 等 Markdown 扩展语法 <sup>*</sup>
- 支持 TeX 科学公式（基于 [KaTeX](http://khan.github.io/KaTeX/)）、流程图 [Flowchart](https://pandao.github.io/editor.md/examples/flowchart.html) 和 时序图 [Sequence Diagram](https://pandao.github.io/editor.md/examples/sequence-diagram.html) <sup>**</sup>
- 发布非 Markdown 文章时可禁用该文章 Markdown 解析，以免出现兼容问题。比如使用[APlayer插件](https://github.com/zgq354/APlayer-Typecho-Plugin)的[纯音乐页面](https://dt27.cn/meiju/chen_yi_xun_zhou_jie_lun_-_jian_dan_ai_live/)。

_<sup>*</sup>需要在插件设置中手动启用，启用后将使用 [marked.js](https://github.com/chjj/marked) 接管前台 Markdown 解析，但接管前台解析后，会导致与文章内容有关的插件失效。_

_<sup>**</sup>语法完整示例：[https://pandao.github.io/editor.md/examples/full.html](https://pandao.github.io/editor.md/examples/full.html)_

为避免 XSS，1.5.0 的后台预览及插件接管的前台解析不再解析 Markdown 中的原始 HTML；关闭前台接管后仍遵循 Typecho 自身的 Markdown 行为。需要嵌入页面结构时，建议通过主题模板或经过安全过滤的插件实现。

## 1.5.0 变更

- 兼容 Typecho 1.2/1.3 的正文和摘要解析流程。
- 使用 DOMPurify 3.4.13 净化最终 HTML，修复后台预览及前台渲染的 XSS 风险。
- 修复现代 Chrome、Safari 的粘贴上传兼容问题。
- 修复同时粘贴多张图片时上传结果错位。
- 新文章粘贴上传后自动关联附件，避免文件长期处于未归档状态。

## Typecho 插件安装及使用
0. **插件更新升级时，请先禁用插件后再上传**
1. [点此](https://github.com/DT27/EditorMD/archive/master.zip)下载插件
2. 将下载的文件解压，文件夹重命名为`EditorMD`，上传到Typecho`usr/plugins/`目录下
3. 登陆后台，在`控制台`下拉菜单中点击`插件`进入`插件管理`
4. 找到`EditorMD`，点击`启用`
5. 根据需要更新设置

## License
> Copyright © 2016 [DT27](https://dt27.cn)  
> License: [The MIT License.](https://github.com/DT27/EditorMD/blob/master/LICENSE)
