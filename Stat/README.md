<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

Stat v1.0.4 - 社区维护版
======================
<h4 align="center">—— 老牌文章浏览计数器插件，首创单页句柄机制支持附件，现已加入模板输出钩子。</h4>

<p align="center">
  <a href="#使用说明">使用说明</a> •
  <a href="#版本历史">版本历史</a> •
  <a href="#贡献作者">贡献作者</a> •
  <a href="#附注链接">附注/链接</a> •
  <a href="#授权协议">授权协议</a>
</p>

---

## 使用说明

<table>
<tr>
<td>

###### 通过挂载单页句柄实现不依赖模板输出的计数功能，除文章外独立页面甚至附件页面均可作用。新版可忽略1小时内重复访问，增加输出函数并支持参数定制。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 Stat；
##### 2. 激活插件，即可自动添加计数字段并按访问自增；
##### 3. 需输出当前文章计数数字可在模板文件如post.php中添加`<?php $this->stat(); ?>`；
##### 4. 需输出文章排行列表可在模板文件如sidebar.php中添加`<?php $this->rank(); ?>`。

**注意事项**：
* ##### 以上排行列表输出函数内支持用英文逗号隔开4种参数，即`$this->rank( "源码规则", "页面类型", "分类或标签mid", "数目" )`，赋值如下表：

|参数|可用值|缺省值|说明|
|---|:---:|:---:|:---:|
|源码规则|任意Html代码<br/>{title}即文章标题<br/>{link}为文章地址|`<li><a href="{link}">{title}</a></li>`|每条输出源码格式|
|页面类型|post<br/>page<br/>attachment|post<br/>(文章)|可排行页面或附件|
|分类或标签mid|整数<br/>(多个可用,隔开)|ALL<br/>(所有)|仅对文章类型有效|
|数目|整数|10|总共输出文章条数|

* ##### 查看分类标签mid方法：后台进入管理-分类或标签页面，点击分类名称或编辑标签，地址栏?后即会显示mid等于的值。

</td>
</tr>
</table>

## 版本历史

 * v1.0.4 (20-07-08 [@jzwalk](https://github.com/jzwalk))
   * 增加计数输出和忽略重复访问功能；
   * 增加排行输出可指定分类ID与类型。
 * v1.0.3 (18-08-24 [@jozhn](https://github.com/jozhn))
   * 修复PDO下数据表检测失败的错误。
 * v1.0.2 (10-07-03 [@Hanny](http://www.imhan.com))
   * 终于支持前台调用了；
   * 接口支持Typecho 0.8的计数；
   * 增加SQLite的支持。
 * v1.0.1 (10-01-02 [@Hanny](http://www.imhan.com))
   * 修改安装出错处理；
   * 修改安装时默认值错误。
 * v1.0.0 (09-12-12 [@Hanny](http://www.imhan.com))
   * 实现浏览次数统计的基本功能。

## 贡献作者

[![jzwalk](https://avatars1.githubusercontent.com/u/252331?v=3&s=100)](https://github.com/jzwalk) | [![jozhn](https://avatars1.githubusercontent.com/u/19300336?v=3&s=100)](https://github.com/jozhn) | [![Hanny](https://secure.gravatar.com/avatar/?d=mp&s=100)](http://www.imhan.com)
:---:|:---:|:---:
[jzwalk](https://github.com/jzwalk) (2020) | [jozhn](https://github.com/jozhn) (2018) | [Hanny](http://www.imhan.com) (2009)

## 附注/链接

* [微调版(jozhn)](https://github.com/jozhn/Stat-for-Typecho) - 修复激活报错。
* [原版](http://www.imhan.com/typecho) - 实现浏览计数功能。

本插件最初仅为测试用未实现任何输出，可与[Attachment](https://github.com/typecho-fans/plugins/tree/master/Attachment)搭配统计附件下载。
另有基于本作句柄机制的二次开发版[TeStat](https://github.com/jiangmuzi/TeStat)可供参考。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> Stat原作未附协议声明，原作者保留所有权利。 © [Hanny](http://www.imhan.com)
