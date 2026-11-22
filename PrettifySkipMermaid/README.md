### PrettifySkipMermaid 代码高亮插件 v2.0.2

基于[公子](https://github.com/lizheming)的 [GoogleCodePrettify v2.0.0](https://github.com/typecho-fans/plugins/tree/master/GoogleCodePrettify) 修改，普通代码块继续高亮和显示行号，Mermaid 图表代码块自动跳过。

维护者：[heky12356](https://github.com/heky12356)

修改版源码：[heky12356/plugins — PrettifySkipMermaid](https://github.com/heky12356/plugins/tree/master/PrettifySkipMermaid)

本仓库包含多个 Typecho 插件，安装时只需其中的 `PrettifySkipMermaid` 目录。

安装步骤：

1. 将整个 `PrettifySkipMermaid` 文件夹上传至 Typecho 的 `usr/plugins/`，保持文件夹名称不变。
2. 在后台禁用原来的 `GoogleCodePrettify` 和此前的 `GoogleCodePrettifySkipMermaid`（如果已启用），然后启用 `PrettifySkipMermaid`。不要同时启用多个版本，原版仍会处理 Mermaid。
3. 在新插件的设置中选择配色和自定义 CSS；这份副本使用独立配置，不会自动继承原插件设置。
4. 刷新文章页面；如有缓存，请清理缓存或强制刷新。

识别 `pre` 自身或其中元素上的 `mermaid`、`language-mermaid`、`lang-mermaid` 类名，兼容 MarkdownParse 插件生成的 `<pre><code class="mermaid">`。本插件只负责跳过图表，Mermaid 的加载和渲染仍由 MarkdownParse 等图表插件负责。

初始化使用 `addEventListener`，保留页面已有的 `window.onload` 处理函数。原有配色文件和高亮库保持原样。

2.0.2 将目录、类名和配置名统一缩短为 `PrettifySkipMermaid`，配置键 `plugin:PrettifySkipMermaid` 长度为 26，兼容 Typecho 默认 MySQL 配置表的 `name varchar(32)`。压缩包内部路径使用 `/`，便于直接在 Linux 服务器解压。请保持目录名称不变。

以下为原版使用说明：

为Markdown模式下的代码标记段落(3反引号包围或4空格开头)自动渲染高亮效果，支持多种样式自定义。例如：
<pre>
```
echo "Hello World!";
```
</pre>
非Markdown模式下请使用pre+code双标签包围代码，如：
```
<pre>
    <code>
        echo "Hello World!";
    </code>
</pre>
```

 > 修正默认样式生效bug和部分样式行号显示问题。

###### 更多详见作者博客：http://imnerd.org/google-code-prettify-plugin-for-typecho-0-9.html
