# CommentPush

基于Typecho博客评论推送的一个小插件！

## 使用方法

- 插件目录名字必须为 `CommentPush` （切记不能修改目录名，否则失效！！）
- 把 `CommentPush` 整个目录拷贝到 `Typecho` 安装路径的 `/usr/plugins` 下面
- 登录 `Typecho` 后台，启用插件，进行插件设置。
- 目前支持推送功能：Server酱，Qmsg酱，阿里云邮件，SMTP邮件，企业微信机器人，钉钉机器人。
- 推送日志记录
- 自定义邮件推送模版

## 使用须知

当前插件需要使用`file_get_contents`函数，有些集成环境会关闭`allow_url_fopen`，需要把这个设置为`On`。

## 数据库适配

- 支持Pdo_Mysql
- 支持Pdo_SQLite

## 推送服务

[Server酱](http://sc.ftqq.com)

[Qmsg酱](https://qmsg.zendee.cn)

[阿里云邮件](https://www.aliyun.com/product/directmail)

SMTP

企业微信机器人

钉钉机器人

[微信公众号](https://mp.weixin.qq.com/debug/cgi-bin/sandbox?t=sandbox/login)

## 其它

有 `bug` 直接提 `issue`

[博客留言](https://blog.gaobinzhan.com/message.html)

感谢[LoveKKComment](https://github.com/ylqjgm/LoveKKComment)提供的邮件模版！

## License

[LICENSE](LICENSE)