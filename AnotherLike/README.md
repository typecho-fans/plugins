
> 又一款typecho点赞插件AnotherLike
>
> Another typecho like plugin AnotherLike

## 效果展示

[demo](https://me.idealclover.top/archives/171/)
![](img/Screenshot.gif)

## 插件启用

Clone这个仓库到 {typecho目录}/usr/plugins

Clone this repository to {typecho}/usr/plugins

重命名为```AnotherLike```（区分大小写）

rename it to ```AnotherLike```(case sensitive)

在admin平台中启用插件

Launch the plugin in admin.

## 调用接口

单个文章点赞

```<?php AnotherLike_Plugin::theLike(); ?>```

输出点赞数最多的文章

```<?php AnotherLike_Plugin::theMostLiked(); ?>```
