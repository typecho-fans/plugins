
# MyTagCloud - Typecho标签云插件


## 插件说明
- 插件名称：MyTagCloud
- 开发作者：[马燕龙](http://www.mayanlong.com)
- 功能描述：Typecho标签云插件，后台控制前台标签智能显示。
    - 可以后台设置前台是否显示标签栏目
    - 可以后台设置是否显示没使用的标签
    - 可以后台设置前台显示标签栏目标题
    - 可以后台设置前台标签最多显示数量


## 使用帮助
1. 下载插件
2. 将插件上传到/usr/plugins/目录
3. 在需要使用标签云的模板中放入如下PHP代码 Typecho_Plugin::factory('usr/themes/sidebar.php')->tagCloud();
4. 登陆后台，在菜单“控制台->插件”中启用插件，并根据自己需求进行配置即可轻松使用
5. 如果标签云模板结构不同，重写插件中render()方法即可


## 详细使用说明和效果演示地址:
- 马燕龙个人博客:[http://www.mayanlong.com](http://www.mayanlong.com/archives/2016/53.html)
- GitHub主页:[https://github.com/yanlongma](https://github.com/YanlongMa/TypechoPlugins)

