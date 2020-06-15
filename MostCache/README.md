# typecho-MostCache再次修改增进版

原作者：[typecho缓存插件MostCache](http://www.phoneshuo.com/PHP/typecho-mostcache-plugin.html)  
修改支持memcache作者：[为typecho增加文件缓存及memcached缓存功能-MostCache修改增进版](http://www.lvtao.net/dev/mostcache_memcached.html)  
  
由于作者表示存在会缓存用户状态的问题，所以  
现在我再次修改一下  
  
###插件特性
基于MostCache的缓存插件  
支持mysql缓存及Memcached缓存  
支持首页、目录、内容页、独立页面缓存  
支持内容修改之后自动更新内容、目录、首页缓存  
支持评论生效更新缓存  
支持缓存在线管理(仅在mysql模式支持详细列表)  
支持自定义缓存规则  
支持postviews阅读次数更新(在默认路由规则下生效，如archives/cid)  
  
###新增特性
支持设置memcached服务器地址  
**支持不缓存用户登录状态,登录状态下插件不缓存任何页面**  
  
  
####插件使用方法
  
下载插件：https://github.com/weicno/typecho-cache/archive/master.zip
  
解压后，修改目录名为`MostCache`放到`typecho`的`plugins`进后台修改相关信息即可~
  
