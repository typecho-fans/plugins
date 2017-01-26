## 插件说明 ##
版本: v1.0.0
作者: [冰剑](https://github.com/binjoo)

使用自定义字段和jquery实现带动态计数的赞字链接。

 - 神补刀：添加jquery库引用~
 
## 使用方法 ##

 1. 下载插件
 2. 将插件上传到 /usr/plugins/ 这个目录下
 3. 启用当前插件
 4. 在模版post.php文件中你要插入`赞`的地方加入代码`<?php Typecho_Widget::widget('Zan_Action')->showZan($this->cid); ?>`

## 更新记录 ##
####v1.0.0
 - 基本的赞功能