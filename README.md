# AMP for Typecho

A Typecho plugin for Google AMP / Baidu MIP

## 总览

这是款一键生成符合 Google AMP/Baidu MIP 标准页面的插件，开启后可以进一步优化谷歌和百度的搜索结果。

最初本插件的功能是[直接通过暴力修改模板][1]实现的，结果发现有不少 TX 需要这个功能，所以就整理了一下做成插件，方便有需要的 TX 使用，随着不断的改进变成了现在的样子。

如果在使用过程中遇到问题，还请反馈。

点击下载[最新版][2]


2020.06.30 增加版本检查，修改sitemap分页数量。

2020.06.07 去除已下线的熊掌号相关功能，将自动提交和批量提交功能修改为 提交到快速收录接口和普通收录接口。 版本更新为0.7.6。


------

## 使用

### 安装

建议环境 PHP 5.6+

将文件夹重命名为 `AMP`，拷贝至 `usr/plugins/` 下，然后在后台->插件处安装。

### 升级

> 注意：已安装旧版本的 **请先禁用插件后再升级！**


> 非 MarkDown 编辑器书写的文章由于存在诸多不可预见的情况，生成的 AMP/MIP 页面可能不能完全符合标准，如果有遇到请及时反馈。

------

## 使用说明

- 在插件后台设置默认 LOGO 以及选择是否开启 SiteMap、AMP 首页、~~自动提交到熊掌号等功能（除自动提交到熊掌号外的功能都默认开启）~~。

- 从[百度站长][3]获取接口调用地址、~~熊掌号 APPID/TOKEN~~，填写到插件设置中（使用提交 URL功能时需要）。

- AMP/MIP 的页面缓存默认关闭，可在插件设置页面修改缓存时间。修改文章会自动更新页面缓存，重建缓存开关在插件设置页，设置缓存时间的下方。

- AMP/MIP 页面的模板已独立至 templates目录中，有个性化需要的 TX 可以自己进一步调整。

注意：

- 服务器 PHP 环境未启用 cURL 扩展时，后台批量提交至百度的功能不可用。
- **非 HTTPS 站点**受 [AMP-LIST 控件][4] 的 src 参数限制，AMP 首页无法换页，建议关闭生成 AMP 首页功能。

------

启用重写功能后：

AMP 首页为： http(s)://xxx/ampindex/

AMP 页面为： http(s)://xxx/amp/slug/

MIP 页面为： http(s)://xxx/mip/slug/


------

## 功能

- 生成符合 Google AMP/Baidu MIP 标准的 AMP/MIP 页面，并与标准页面建立关联。

- 生成 AMP/MIP 的 SiteMap，及所有 URL 的纯文本列表（支持分页）。

- 生成 AMP 版的首页。
 
- 后台批量提交 URL 到百度站长平台，可选手动或自动。

- MIP 页面完美支持百度熊掌号页面标准，~~新发表文章自动提交到熊掌号~~。

- （新增）用户决定是否只允许百度和谷歌的爬虫访问 MIP/AMP 页面。

- （新增）插件版本判断。

- （新增）自定义 MIP/AMP 页面样式。

- （新增）缓存功能，缓存访问过的 MIP/AMP 页面，可显著提高性能（默认关闭）。

- 自动解析自定义文章路径。


------

## 支持

如果本插件帮到了你，不妨给点赞赏鼓励一下作者 ^-^

<img width="150" height="150" src="https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/alipay.jpg">
<img width="150" height="150" src="https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/wechat.jpg">

------
## 效果预览

MIP内容页：

![MIP内容页](https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/screencapture-holmesian-org-mip-AMP-for-Typecho-2018-03-27-10_10_37.png)


AMP内容页：

![AMP内容页](https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/screencapture-holmesian-org-amp-AMP-for-Typecho-2018-03-27-10_11_27.png)


AMP首页：

![AMP首页](https://raw.githubusercontent.com/holmesian/Typecho-AMP/dev/screencapture-holmesian-org-ampindex-2018-03-27-10_12_54.png)


  [1]: https://holmesian.org/typecho-upgrade-AMP
  [2]: https://github.com/typecho-fans/plugins/releases/download/plugins-A_to_C/AMP.zip
  [3]: https://ziyuan.baidu.com/dailysubmit/index
  [4]: https://www.ampproject.org/docs/reference/components/amp-list
