# AMP for Typecho
 A typecho plugin for Google AMP/ Baidu MIP

这是款一键生成符合Google AMP/Baidu MIP标准相关页面的插件，开启后可以进一步优化Google、Baidu的搜索结果。

最初本插件的功能是[直接通过暴力修改模板][1]实现的，结果发现有不少TX需要这个功能，所以就整理了一下做成插件，方便有需要的TX使用，随着不断的改进变成了现在的样子。

如果在使用过程中遇到问题，还请反馈。

点击下载[最新版][2]

---
## 功能

- 生成符合Google AMP/Baidu MIP标准的AMP/MIP页面，并与标准页面建立关联。

- 生成AMP/MIP的SiteMap，及所有ULR的纯文本列表。

- 生成AMP版的首页。
 
- 后台批量提交URL到Baidu，可选手动或自动。

- MIP页面完美支持百度熊掌号页面标准，新发表文章自动提交到熊掌号。

- 新增开关：用户决定是否只允许Baidu和Google的爬虫访问MIP/AMP页面。

- 新增插件版本判断。

- 新增自定义MIP/AMP页面样式。

- 新增缓存功能，缓存访问过的MIP/AMP页面，可显著提高性能（默认关闭）。

- 自动解析自定义文章路径

---
## 安装

建议PHP 5.6+

将文件夹重命名为`AMP`，拷贝至`usr/plugins/`下，然后在后台->插件处安装。

---
## 升级方法

**请先禁用插件后再升级**

PS:非Markdown编辑器书写的文章由于存在诸多不可预见的情况，生成的AMP/MIP页面可能不能完全符合标准，如果有遇到请及时反馈。


---
## 使用说明

- 在插件后台设置默认LOGO和图片，以及选择是否开启SiteMap、AMP首页、自动提交到熊掌号等功能（除自动提交到熊掌号外的功能都默认开启）。

- 从[百度站长][3]获取接口调用地址、熊掌号APPID/TOKEN，填写到插件设置中（使用提交URL功能时需要）。

- AMP/MIP的页面缓存默认为24小时，可在插件设置页面修改缓存时间。修改文章会自动更新页面缓存，重建缓存开关在插件设置页，设置缓存时间的下方。

- AMP/MIP页面的模板已独立至templates目录中，有个性化需要的TX可以自己进一步调整：



注：
- 服务器未启用php-curl扩展时，后台批量提交URL到Baidu的功能不可用。
- **非HTTPS站点**受 [amp-list 控件][4] 的src参数限制，AMP首页无法换页，建议关闭生成AMP首页功能。

---

启用Rewrite之后：

AMP首页为 http(s)://xxx/ampindex/

AMP页面为 http(s)://xxx/amp/slug/

MIP页面为 http(s)://xxx/mip/slug/




---
## 效果预览

MIP内容页：

![MIP内容页](https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/screencapture-holmesian-org-mip-AMP-for-Typecho-2018-03-27-10_10_37.png)


AMP内容页：

![AMP内容页](https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/screencapture-holmesian-org-amp-AMP-for-Typecho-2018-03-27-10_11_27.png)


AMP首页：

![AMP首页](https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/screencapture-holmesian-org-ampindex-2018-03-27-10_12_54.png)


  [1]: https://holmesian.org/typecho-upgrade-AMP
  [2]: https://github.com/typecho-fans/plugins/releases/download/plugins-A_to_C/AMP.zip
  [3]: http://ziyuan.baidu.com/mip/index
  [4]: https://www.ampproject.org/docs/reference/components/amp-list