## 插件说明 ##

通过引入两个抽象类扩展实现八种常用侧边栏文章或评论数据输出。

 - 版本: v2.0.0
 - 作者: [冰剑](https://github.com/binjoo)
 - 主页: <https://github.com/typecho-fans/plugins/tree/master/TeKit>

- 使用方法：将插件放入\usr\plugins目录并在主题文件的适当位置插入输出代码，具体见以下文档：

#### 日志类 TeKit_Contents

###### 随机日志 Random

> 输出代码示例：
> ```php
> <?php $this->widget('TeKit_Contents')->Random(10)
>     ->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|number|否|10|显示数量|

###### 最多评论日志 MostCommented

> 输出代码示例：
> ```php
> <?php $this->widget('TeKit_Contents')->MostCommented(10)
>     ->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|number|否|10|显示数量|

###### 历史上当天日志 HistoryToday

> 输出代码示例：
> ```php
> <?php $this->widget('TeKit_Contents')->HistoryToday(10)
>     ->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|number|否|10|显示数量|

###### 历史上当月日志 HistoryTomonth

> 输出代码示例：
> ```php
> <?php $this->widget('TeKit_Contents')->HistoryTomonth(10)
>     ->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|number|否|10|显示数量|

#### 评论类 TeKit_Comments
###### 最多评论的人 MostCommentors

> 输出代码示例：
> ```php
> <?php $this->widget('TeKit_Comments')->MostCommentors(365,10,true)
>     ->parse('<li><a href="{url}">{author} ({cnt})</a></li>'); ?>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|days|否|NULL|多少天内|
|number|否|10|显示数量|
|ignore|否|true|不包含作者|

###### 最多沙发的人 MostSofaCommentors

> 输出代码示例：
> ```php
> <?php $this->widget('TeKit_Comments')->MostSofaCommentors(365,10,true)
>     ->parse('<li><a href="{url}">{author} ({cnt})</a></li>'); ?>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|days|否|NULL|多少天内|
|number|否|10|显示数量|
|ignore|否|true|不包含作者|

###### 评论人评论数量 CommentorNumber

> 输出代码示例：
> ```php
> <?php echo $this->widget('TeKit_Comments')->CommentorNumber('admin','test@test.com',365); ?>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|author|是||昵称|
|mail|是||Email|
|days|否|30|多少天内|

###### 评论人评论 CommentorComments

> 输出代码示例：
> ```php
> <ul class="widget-list">
>     <?php $this->widget('TeKit_Comments')->CommentorComments('admin','test@test.com',365)->to($tekit); ?>
>     <?php while($tekit->next()): ?>
>         <li>
>         <a href="<?php $tekit->permalink(); ?>"><?php $tekit->author(); ?></a>:
>         <?php $tekit->excerpt(35, '...'); ?>
>         </li>
>     <?php endwhile; ?>
> </ul>
> ```

|参数名称|是否必须|默认值|说明|
|---|---|---|---|
|author|是||昵称|
|mail|是||Email|
|days|否|30|多少天内|
