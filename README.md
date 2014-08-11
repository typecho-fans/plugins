MyPlayer
========

Typecho外链播放器转换插件



## 简介
这是一个[Typecho][5]插件，将指向音乐或者视频页面的链接转换为相应外站播放器的插件。

目前支持虾米、优酷、土豆、爱奇艺、音悦台、乐视、56、哔哩哔哩、新浪的播放页面链接。

特别提供了一个简单的小型音频播放器，以供MP3文件链接和虾米音乐链接使用。

另简单支持flash和html5音频。

## 特征
### 无侵入性
本插件具有“无侵入性”。由于文档中只是使用普通链接，在关闭、移除了插件后，文档内容不受影响，没有数据丢失或冗余，仅仅由点击链接展开播放器降级为打开播放页面，是一种可接受的降级方式。使用后不喜欢本插件的可以随意关闭并删除之。
### 高扩展性
本插件使用比较简单的接口，扩展性强，可以轻松添加其他的外链播放器。

## 需求
最低Typecho0.9版本。
注：未在Typecho0.8版本中测试。

## 安装
将MyPlayer文件夹置入Typecho的usr/plugins/目录下，并进入后台插件管理界面中激活。

建议使用[Typecho 应用商店 插件][4]，可以在其中找到本插件的稳定版，后台下载安装。

## 用法
在文章中只需要使用指向音乐或者视频播放页面的普通链接即可。例如：

```
[断点](http://www.xiami.com/song/72299)

[欧派之歌·抖抖抖腿根本停不下来](http://www.bilibili.com/video/av1192762/)
```

以上为Markdown代码，html代码同理，自行脑补即可。

## 实现
插件通过js脚本检查指定范围内的链接，当匹配某个插件可识别的页面时，在前台转换为对应的官方外站播放器。

具体的方法主要是通过地址中的特征字符串匹配，自行拼接出外链播放器地址。

对于特定的网站，可能无法通过简单的页面链接获取足够信息时，则需要通过后台抓取页面内容以获得相关数据。

## 其它
稳定版发布地址：[typecho-fan插件集][3]

开发版发布地址：[MyPlayer On Github][2]

更多介绍：[TE插件——MyPlayer][1]



[1]:http://perichr.org/plugin/2014/04/14/myplayer
[2]:https://github.com/perichr/MyPlayer/tree/develop
[3]:https://github.com/typecho-fans/plugins/tree/master/MyPlayer
[4]:https://github.com/typecho-app-store/AppStore
[5]:http://typecho.org


