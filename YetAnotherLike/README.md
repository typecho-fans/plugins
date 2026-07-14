# YetAnotherLike

Typecho 点赞插件，适用于 1.3.0 及以上的版本。

对自定义 CSS 友好。

可在设置中自定义 IP 用户是否能点赞。

## 使用方式

在主题文件中引用 `like()`：

```
<?php \TypechoPlugin\YetAnotherLike\Plugin::like() ?>
```

效果：

![点赞框效果图，包含点赞按钮和当前赞的数量](screenshots/like.png)


引用 `likeCount()` 可以输出当前文章的点赞数，只有一个数字。

```
<?php \TypechoPlugin\YetAnotherLike\Plugin::likeCount() ?>
```

效果：
```
1
```
