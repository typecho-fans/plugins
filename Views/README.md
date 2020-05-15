### Typecho文章浏览数排行插件Views v1.0.1

通过在文章数据表中自增字段views实现统计浏览次数效果并按排行输出热门文章列表。

输出访问次数：

```<?php Views_Plugin::theViews(); ?>```

定制文字：

```<?php Views_Plugin::theViews('有 ', ' 次点击'); ?>```

输出最受欢迎文章：

```<?php Views_Plugin::theMostViewed(); ?>```
