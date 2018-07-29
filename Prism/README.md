### 轻量级代码高亮插件Prism v1.0.2

可在Markdown模式下用3个反引号+**语言名**格式高亮代码段落，支持八种样式。例如：
<pre>
```php
echo "Hello World!";
```
</pre>
非Markdown模式下可在code标签内添加class="lang-语言名"，如：
```
<pre>
    <code class="lang-php">
       echo "Hello World!";
    </code>
</pre>
```

- v1.0.2(18-7-23)：（[@羽中](https://github.com/jzwalk)）

使用1.15.0版核心，更新文档说明。

- v1.0.1(17-1-24)：

使用1.6.0版核心，修正样式前景色问题。

默认支持语言名：markup(html/xml类)、css、javascript、php和其他clike(C语言类)

###### 更多语言高亮详见：http://prismjs.com/download.html