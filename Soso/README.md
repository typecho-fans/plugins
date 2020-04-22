# soso
typecho程序的搜索增强插件

## 功能介绍
搜索模式：有常规搜索和仅搜索文章标题两种模式。

搜索过滤：可以设置一些分类，让其不被搜索到。

搜索高亮：搜索结果页面，文章标题和缩略内容中的关键字高亮显示。

注意：缩略内容使用`<?php $this->excerpt(140, '...'); ?>`来截取的并不会高亮，因为这个地方没有插件接口，可以将这个函数换成插件内置的方法`<?php $this->excerpts($this); ?>`，并且在插件设置里设置截取长度。也可以这样写个判断

```php
<?php $all = Typecho_Plugin::export(); if(array_key_exists('Soso', $all['activated'])): ?>
<?php $this->excerpts($this); ?>//插件启动就调用插件的这个方法
<?php else: ?>
<?php $this->excerpt(140, '...'); ?>//插件没启动就调用默认方法
<?php endif; ?>
```

## 使用说明
下载后将soso文件夹传到typecho目录下，然后启动插件，打开插件设置，根据文字提示设置即可。

## 插件升级操作说明
禁用旧版插件，删除旧版插件文件夹，然后上传最新版插件，启动插件设置插件即可

## 高级玩法
按分类搜索文章详见https://qqdie.com/archives/typecho-search-category.html
