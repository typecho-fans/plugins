## 起步
组件来源[http://bh-lay.com/labs/github-widget-user][1]，并在其中做了一些修改。

## 安装
 1. 下载
 2. 解压后把 `GithubWidgetUser` 文件夹上传到插件目录。
 3. 启用插件，默认引入了jQuery，若已引入设置不引入可避免多次import jQuery。


<!--more-->


## 使用
#### 方式一、傻瓜式
在文章中创建一个class为`github-widget-user`的dom，并在data属性上增加用户参数即可，如下面代码所示。
```
<div class="github-widget-user" data-user="github上的用户名"></div>
```
#### 方式二、自定义式
若对应dom上有`data-user`参数，JS函数中可以省略用户名参数，两者有冲突时，以JS传入为优先。
```
$('.some_class').github_user_widget('github上的用户名');
```
## 效果

![github名片.png][3]


  [1]: http://bh-lay.com/labs/github-widget-user
  [3]: http://www.hongweipeng.com/usr/uploads/2015/12/3818505502.png