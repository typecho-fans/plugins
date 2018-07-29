## 插件说明 ##

 - 版本: v1.0.7
 - 作者: [冰剑](https://github.com/binjoo)
 - 主页: <https://github.com/binjoo/SlimBox2-for-Typecho>

## 使用方法 ##

 1. 下载插件
 2. 如果安装有老版本，请先卸载老版本，再删除插件文件
 3. 将插件上传到 /usr/plugins/ 这个目录下
 4. 登陆后台，在“控制台”下拉菜单中进入“插件管理”
 5. 启用当前插件

## 更新记录 ##

#### v1.0.7(18-5-24)（[@Ryan](https://github.com/ryanfwy)）
 - 修改选择器图片链接为 `img` 的 `src` 属性，无需再为图片嵌套链接；
 - 修改灯箱自适应逻辑，更合理地缩放图片；
 - 修改部分显示样式；
 - 去掉对移动端不友好的 `hover` 样式；
 - 还原标题栏（前面的版本无法输出标题和页码），并修改为单行显示；
 - 灯箱展示时禁止滑动，屏幕旋转时关闭展示；
 - 增加`灯箱大小变化速度`选项；
 - 初始化 `script` 增加 `id="slimbox"` 属性，便于 `pjax` 初始化。
   
#### v1.0.6(17-2-2)（[@羽中](https://github.com/jzwalk)）
 - jquery库从google改为staticfile.org，默认选择.post-content适应1.0主题。

#### v1.0.5
 - 使用[林木木的代码](http://immmmm.com/slimbox2-js-picture-box-adaptive.html)修复`图片灯箱自适应`功能；

#### v1.0.4
 - 加入标题栏显示隐藏；

#### v1.0.3
 - 加入图片循环；

#### v1.0.2
 - 加入Google CDN jQuery库；

#### v1.0.1
 - 加入插件后台；
 - 加入自定义计数器提示；

#### v1.0.0
 - 实现灯箱展示效果；