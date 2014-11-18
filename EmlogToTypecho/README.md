## 插件说明 ##

 - 版本: v1.0.0
 - 作者: [ShingChi](https://github.com/shingchi)
 - 主页: <https://github.com/typecho-fans/plugins/tree/master/EmlogToTypecho>
 - 适用: [Typecho 0.9 (14.3.14)](https://github.com/typecho/typecho/releases/tag/v0.9-14.5.25-beta)

此插件涉及数据库操作，有潜在的未知风险，请慎用！

EmlogToTypecho 插件会自动转换 emlog 博客中的评论、文章、分类和标签。在转换过程中，保留新装 typecho 中的默认分类来存储原来 emlog 中的未分类文章。支持多级分类转换，不过要要求 typecho 版本也支持多级分类，所以这个插件只支持有多级分类后的 typecho 版本。转换后，会自动更新内容中的附件地址。

## 插件结构 ##

```
EmlogToTypecho
|
|-- Action.php
|-- panel.php
|-- Plugin.php
|-- README.md
```

## 使用方法 ##

 1. 下载安装**支持多级分类**的 typecho 版本，如 Typecho 0.9 (14.3.14)。
 2. 下载插件，并解压上传到插件目录下。
 3. 启用插件，并设置插件，配置数据库信息。
 4. 打开 `控制台 => 从 Emlog 导入数据` 面板，点击 `开始数据转换 »`。
 5. 复制原 emlog 的附件目录 `content/uploadfile`，黏贴到 typecho 的 `usr/uploads` 目录下，并更名为 `emlog`。
