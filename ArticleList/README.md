### 随机/热门文章列表插件ArticleList v1.1.1

支持设置列表数量，选择分类并指定缓存文件位置等。

 > 修正php5.6报错问题，可设置热评收录时限(代码注释形式)。（[@羽中](https://github.com/jzwalk)）

#### 使用说明
1. 将ArticleList.php文件直接上传至`/usr/plugins/`目录(:dart:不需要文件夹)；
2. 登陆后台，在“插件管理”中启用并进行设置；
3. 在模版中写入`<?php ArticleList::random(); ?>`输出随机文章列表，`<?php ArticleList::hot(); ?>`输出热门文章列表。

###### 更多详见作者博客：http://defe.me/prg/395.html