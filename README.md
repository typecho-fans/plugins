# Typecho 后台IP白名单插件 AllowIp

## 插件简介

设置后台管理IP白名单，也就是说只允许IP白名单内的IP登陆访问后台。<br>
至于为什么要写这个插件是因为博客服务器用的是国外的vps，搭建了酸酸乳，在网站挂上了ssr来访问速度都一样的。

## 安装方法

1. 到[releases](https://github.com/fuzqing/AllowIp-Typecho-Plugin/releases)中下载最新版本插件；
2. 将下载的压缩包进行解压，文件夹重命名为`AllowIp`，上传至`Typecho`插件目录中；
3. 后台激活插件，设置IP地址。

## 注意

如果你的IP设置不对，或者访问网站时候没有使用酸酸乳之类的代理，<br>
请打开"Plugin.php"文件中105行的注释，也就是下面这一行的注释<br>
//$allow_ip[] = '0.0.0.0';<br>
打开保存之后就可以登陆了，不过这样子这个IP白名单插件也就没意义了。
