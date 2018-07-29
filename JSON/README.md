### Json格式数据输出插件JSON v0.1

支持使用“api/接口名”路径按参数获取json格式的博客数据。

 > upgrade接口改注释。

|接口名|可用参数|默认值|说明|
|---|:---:|:---:|---|
|posts|pageSize<br/>page<br/>authorId<br/>created<br/>cid<br/>category<br/>commentsNumMax<br/>commentsNumMin<br/>allowComment|5<br/>1<br/>0<br/>-<br/>-<br/>-<br/>-<br/>-<br/>-|文章综合数据|
|pageList|content|false|页面综合数据|
|single|cid<br/>slug|-<br/>-|指定单页数据|
|post|同上|同上|指定文章数据|
|page|同上|同上|指定页面数据|
|relatedPosts|authorId<br/>limit<br/>type<br/>cid|-<br/>5<br/>-<br/>-|关联文章数据|
|recentPost|pageSize|10|最新文章数据|
|recentComments|pageSize<br/>parentId<br/>ignoreAuthor<br/>showCommentOnly|10<br/>0<br/>false<br/>false|最近评论数据|
|categoryList|ignore<br/>childMode|-<br/>false|分类列表数据|
|tagCloud|sort<br/>ignoreZeroCount<br/>desc<br/>limit|count<br/>false<br/>true<br/>0|标签列表数据|
|archive|format<br/>type<br/>limit|Y-m<br/>month<br/>0|归档列表数据|
|info|user|0|配置信息数据|
|count|-|-|发表文章总数|

###### 示例可参考作者博客：https://imnerd.org/api/posts