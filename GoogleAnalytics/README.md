# Typecho-GoogleAnalytics-Plugin
一个异步加载GA的Typecho插件
 
下载后复制`GoogleAnalytics`文件夹到`plugins`
在后台安装后，填入你的 跟踪 ID 即可

如果你使用了Pjax加载，在Pjax回调填入

```javascript
ga(window,document,navigator,location);
```
即可统计全站

----
> GA PHP代码来自 [Google-Analytics-Async](https://github.com/stneng/Google-Analytics-Async)

> 异步代码来自 [CommentToMail](https://github.com/visamz/CommentToMail)
