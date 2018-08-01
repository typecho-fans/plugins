Gravatar 头像缓存插件 For Typecho
=============
支持curl方式下载Gravatar头像到本地，可指定目录和保存时间。

### 使用说明
- 将GravatarCache.php文件直接上传至`/usr/plugins/`目录(:warning:不需要文件夹)；
- 登陆后台，在“控制台”下拉菜单中进入“插件管理”
- 启用插件即可

###升级日志

####2.0.2 at 2012-04-07
- 修复初次激活插件，初次调用 getGravatarCache() 不会自动创建缓存文件夹的BUG
- getGravatarCache() 增加第五个参数 $default
  - getGravatarCache($mail, $isSecure = false, $size = 32, $rating = 'G', $default = 'mm')
  - $mail     ->  邮件地址；
  - $isSecure ->  是否使用 https 安全协议，默认 false；
  - $size     ->  头像大小，这个只用于当评论头像不存在时重新获取头像时的大小，若头像已存在则无效，默认 32
  - $rating   ->  头像等级，这个只用于当评论头像不存在时重新获取头像时的等级，若头像已存在则无效，默认 G
  - $default  ->  默认头像 地址，默认值 为 mm（此值对应 gravatar官方比较美观的默认头像）
- 整理简化代码，提高可读性

####1.2.1 at 2012-04-06

- 修复由于没有声明方法类型 为 静态类型 而导致插件初次使用时 出现错误警告的BUG （download 方法）
- 删除一些无用的代码行

####1.2.0  at 2011-04-14
 
- 修复程序逻辑BUG
- 修复域名后面多出一个斜杠的BUG
- 优化了代码效率

