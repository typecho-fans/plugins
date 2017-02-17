### 自动生成文章缩略名插件AutoSlug v2.1.1

输入文章标题后在下方永久链接自动生成对应英文slug，支持百度、有道和谷歌翻译及拼音模式。

**版本: v2.1.0**

升级时，请先禁用旧版本后，覆盖文件再启用。

 > 2.1.1谷歌修复版(17-2-11)更新：使用[Google Translator API for free](https://github.com/statickidz/php-google-translate-free)改国内节点。（[@羽中](https://github.com/jzwalk)）

== 更新 2014-12-10 ==

 1. 增加有道、谷歌等翻译 API

== 更新 ==

 1. 把插件原来后台静默翻译，转为标题输入栏失去焦点时，实时使用 ajax 获取翻译结果显示在缩略名输入栏中。
 2. 去除原来英文翻译类，直接把功能集成到 action 中，并使用 typecho 内置的 client 类来代替原生的 curl 书写方法。
 3. 精简插件目录结构。

###### 更多详见论坛原帖：http://forum.typecho.org/viewtopic.php?f=6&t=4420