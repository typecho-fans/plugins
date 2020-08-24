# AbbrSlug

对标 `hexo-abbrlink` 插件  

以下参照了 `hexo-abbrlink` 的文档

## 主题 Mirages 不生效问题

将 21 22 行 代码替换如下
```
// Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('AbbrSlug_Plugin', 'render');
// Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('AbbrSlug_Plugin', 'render');
Typecho_Plugin::factory('Mirages_Plugin')->writePost = array('AbbrSlug_Plugin', 'render');
Typecho_Plugin::factory('Mirages_Plugin')->writePage = array('AbbrSlug_Plugin', 'render');
```

再将插件 禁用 重启即可 

## 设置:

```
alg -- 算法 (目前支持 crc16 和 crc32, 默认crc2)
rep -- 进制显示 (sulg 会显示10进制和16进制)
```

## 例子

> 生成的 slug 会像下面显示:

crc16 & hex
https://tmp.com/posts/66c8.html

crc16 & dec
https://tmp.com/posts/65535.html
crc32 & hex
https://tmp.com/posts/8ddf18fb.html

crc32 & dec
https://tmp.com/posts/1690090958.html

## 限制

`crc16` 算法最大支持 65535 个文章 （个人博客来说已经完全够了）

## 感谢

[NoahDragon](https://github.com/NoahDragon)
[Rozbo](https://github.com/Rozbo)