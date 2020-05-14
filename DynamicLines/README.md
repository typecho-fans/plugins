## 简介 ##
**一个基于 html5 canvas 绘制的网页背景效果的typecho插件。**
可以在网页上绘制出蛛网般的动态线条，随鼠标移动聚合
## 效果预览 ##
![筑巢特效][2]
##使用方法##

 - 将该插件上传到typecho目录下的usr/plugins目录下
 - 启用插件
 - 自定义选项（如下图）
![插件设置界面][4]
 - 线条颜色是RGB值。红色是255,0,0 绿色是0,255,0 蓝色是0,0,255 默认是蓝色
 - 线条数量即为屏幕上的线条数，不宜过大。
 - 线条透明度建议为0.5~1
 - zindex 是显示优先级，如果值较低，则有可能被其他元素遮盖住。所以建议填写100，这样即可在所有元素上显示。
## 特性 ##
 - 不依赖 jQuery，使用原生的 javascript。
 - 非常小，只有 2 Kb。
 - 非常容易实现，配置简单，即使你不是 web 开发者，也能简单搞定。
## 插件下载 ##
下载完成后请将文件夹重命名为**DynamicLines**



  [1]: http://www.changjiangblog.top
  [2]: http://ww1.sinaimg.cn/large/0079MVdAly1ft8lqah3tmg31gi0q34ne.gif
  [3]: http://www.changjiangblog.top/canvas-nest.html
  [4]: http://ww1.sinaimg.cn/large/0079MVdAly1ft8lvqp7lmj31hc0pg76l.jpg
  [5]: https://github.com/changjiangblog/DynamicLines-typecho-plugin/archive/master.zip
  [6]: https://github.com/changjiangblog/DynamicLines-typecho-plugin
  [7]: http://ww1.sinaimg.cn/large/0079MVdAly1ft8mgddx8wj313a0gj40l.jpg
  [8]: https://creativecommons.org/licenses/by-nc/4.0/
