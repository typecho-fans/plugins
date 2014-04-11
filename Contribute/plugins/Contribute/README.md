## 插件说明 ##

 - 版本: v1.0.0
 - 作者: [ShingChi](https://github.com/shingchi)
 - 主页: <https://github.com/typecho-fans/plugins/blob/master/contribute>

此插件涉及数据库操作，有潜在的未知风险，请慎用！

这个插件本来是朋友要帮忙写的，囿于鄙人没有什么技术，插件还存在很多不足，请见谅。同时，也希望各位基友共同去完善它。

因为投稿页面使用和后台一样的效果，所以加载的 `js` 文件较多，本来想直接用后台的，但觉得那样不是很好，因为会暴露后台路径。所以就直接复制出来独立放在插件包里，在引用这些文件时，使用了 `LABjs` 来加载。当然，你也可以自定义投稿页面模板，可以去掉这些 `js` 文件。Markdown 的启用状态和后台设置同步。

目前插件暂不支持附件上传，留待日后再看。

## 插件特点 ##

 - 自动创建投稿页面，禁用插件只隐藏页面而不删除
 - 创建新数据表存储投稿，以免破坏程序原有数据表
 - 后台支持投稿管理，目前功能只有审核、删除和预览
 - 采用内置的过滤方法过滤XSS输入

## 插件结构 ##

```txt
Contribute_v1.0.0
|-- plugins/
|   |-- Contribute/
|       |-- css/
|       |   |-- img/
|       |-- js/
|       |   |-- panel/
|       |-- Action.php
|       |-- Mysql.sql
|       |-- panel.php
|       |-- Plugin.php
|       |-- preview-ajax.php
|       |-- README.md
|-- themes/
    |-- contribute.php
```

## 使用方法 ##

 1. 解压插件包
 2. 把 `themes/contribute.php` 文件上传到当前使用的模板文件夹下
 3. 把 `plugins/Contribute` 文件夹上传到插件目录
 4. 启用插件，并设置使用插件
