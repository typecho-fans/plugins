# ShortLinks

有时候我们为了减少权重的流失，或者是为了隐藏某些推荐链接（比如：淘宝客、主机推荐），因此需要将外链转化为内链（淘宝客、主机推荐都是隐藏 AFF）。

Typecho 外链转内链插件，支持正文和评论者链接。

从比 1.0.9 更老的版本升级上来建议先禁用再启用。

## 获取 Download

[最稳定版下载地址](https://github.com/benzBrake/ShortLinks/releases/latest)

## 简介 Introduction

1. 把外部链接转换为 your_blog_path/go/key/，撰写链接页面支持修改
2. 通过菜单“创建->短链接”设置；
3. 自定义短链功能来自[golinks](http://defe.me/prg/429.html "golinks")；
4. 支持 referer 白名单和外链转换白名单；
5. 支持跳转页面，可以自行制作模板放到 templates 目录下，插件设置里可选择，目前自带 5 个模板；
6. 支持自定义字段转换（实验性功能）；
7. 支持关闭指定页面的链接转换功能。添加自定义字段 `noshort` 即可；

## 使用方法 Usage

- 使用 Git 命令直接克隆至插件目录即可，例如： `/var/www/html/usr/plugins/` 下
- 然后启用插件即可

## 其他 Others

### 模板使用 Template Usage

模板功能自 1.1.0 b1 开始支持更多的字段替换。

支持 Typecho 选项和主题选项字段替换。

就是平常用 `$this->options->logoUrl` 这样的形式调用的字段，可以直接在模板里使用 `{{logoUrl}}` 定义，ShortLinks 插件会自动替换。

如果发现有不支持的字段，别尝试了，就是 ShortLinks 没适配。

### 计划功能 Todo

- 自定义短链接增加密码功能

### 感谢 Thanks

- [小咪兔](http://forum.typecho.org/viewtopic.php?t=5576 "小咪兔")

- [DEFE](http://defe.me/prg/429.html "GoLinks")

- [左岸](https://www.zrahh.com/archives/451.html "左岸")

### 预览 Preview

暂无
