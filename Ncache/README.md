本插件需要 WEB 服务器为 Nginx 并且需要 ngx_cache_purge 模块支持

### 插件特性

支持所有页面缓存
支持内容修改之后自动更新内容、分类、首页缓存
支持评论生效更新缓存
支持登录状态下不缓存
支持搜索等动态页面不缓存

### 更新日志

v1.0
初始版本
v1.1
修复评论分页刷新
增加Tag页面刷新
增加自定义刷新后缀

### 已知问题

非js方式的访问统计插件会失效

### 使用方法

需要修改nginx配置文件，添加
```nginx
#下面2行的中的wpcache路径请自行提前创建，否则可能会路径不存在而无法启动nginx，max_size请根据分区大小自行设置
fastcgi_cache_path /www/server/nginx/fastcgi_cache_dir levels=1:2 keys_zone=fcache:250m inactive=1d max_size=1G;
fastcgi_temp_path /www/server/nginx/fastcgi_cache_dir/temp;
fastcgi_cache_key "$scheme$request_method$host$request_uri";
fastcgi_cache_use_stale error timeout invalid_header http_500;
#忽略一切nocache申明，避免不缓存伪静态等
fastcgi_ignore_headers Cache-Control Expires Set-Cookie;
#Ps：如果是多个站点，以上内容不要重复添加，否则会冲突，可以考虑将以上内容添加到nginx.conf里面，避免加了多次。
```

```nginx
server
{
	***略***
	set $skip_cache 0;
	#post访问不缓存
	if ($request_method = POST) {
		set $skip_cache 1;
	}
	#动态查询不缓存
	if ($query_string != "") {
		set $skip_cache 1;
	}
	#pjax查询缓存
	if ($query_string ~ "_pjax=(.*)") {
		set $skip_cache 0;
	}
	#后台等特定页面不缓存（其他需求请自行添加即可）
	if ($request_uri ~* "/admin/|/action/|/search/|/feed/|baidu_sitemap.xml|sitemap.xml") {
		set $skip_cache 1;
	}
	#对登录的用户不展示缓存
	if ($http_cookie ~* "typecho_authCode") {
		set $skip_cache 1;
	}
	location ~ [^/]\.php(/|$)
	{
		try_files $uri =404;
		fastcgi_pass  unix:/tmp/php-cgi-74.sock;
		fastcgi_index index.php;
		include fastcgi.conf;
		include pathinfo.conf;
		#新增的缓存规则
		fastcgi_cache_bypass $skip_cache;
		fastcgi_no_cache $skip_cache;
		add_header X-Cuojue-Cache "$upstream_cache_status From $host";
		fastcgi_cache fcache;
		fastcgi_cache_valid 200 1d;
	}

	location ~* /{后台设置的token}/_clean_cache(/.*) {
		fastcgi_cache_purge fcache "$scheme$request_method$host$1$is_args$args";
	}
	***略***
}
```
以上的
```nginx
	location ~* /{后台设置的token}/_clean_cache(/.*) {
		fastcgi_cache_purge fcache "$scheme$request_method$host$1$is_args$args";
	}
```
需要和后台设置的token一致，例如后台设置`1150AE6A4F7938AE754D`则这里设置为
```nginx
	location ~* /1150AE6A4F7938AE754D/_clean_cache(/.*) {
		fastcgi_cache_purge fcache "$scheme$request_method$host$1$is_args$args";
	}
```

### 缓存效果

替换新的配置，并且重载Nginx之后，访问前台页面，查看header，会多出一个 X-Cuojue-Cache 标志。

X-Cuojue-Cache 一般会有3个状态：MISS、HIT、BYPASS。

- **MISS表示未命中**
即这个页面还没被缓存，新发布或刚被删除的页面，首次访问将出现这个状态（图略）。

- **HIT表示缓存命中**
打开一个会缓存的页面，比如文章内容html页面，F5刷新几次即可在F12开发者模式当中的Header头部信息中看到如图缓存命中状态：
![HIT](https://cuojue.org/usr/uploads/2020/02/2906163519.png)

- **BYPASS表示缓存黑名单**
即页面路径在Nginx规则中被设置成不缓存（set $skip_cache 1;），比如typecho后台和搜索：
![BYPASS](https://cuojue.org/usr/uploads/2020/02/4256031367.png)

*如果你发现想要缓存的页面却是这个状态，就可以去检查排除规则中是不是包含了这个路径！反之，如果你发现后台登录不了，或者各种登陆态丢失问题，则应该到排除规则中加上该页面路径的关键字。*


### 进阶操作
#### 评论者信息被缓存修复
typecho主题一般使用php的函数获取cookies来填充评论者信息，导致了如果用户评论了文章，就会缓存评论者的信息，如何修复参考下面的文章
https://cuojue.org/read/typecho_comments_author_javascript.html

#### Set-Cookie头处理
使用了fastcgi_cache来缓存所有页面，导致了一个问题，那就是set-cookie也被缓存了，其他用户再次访问会导致被设置缓存的cookie，解决方法见下文。
https://cuojue.org/read/fastcgi_cache_fix_cookies.html

### 下载地址

链接：https://disk.cuojue.org/cloud/Typecho/Plugins/Ncache.zip



参考：
[Nginx开启fastcgi_cache缓存加速，支持html伪静态页面 | 张戈博客](https://zhangge.net/5042.html "Nginx开启fastcgi_cache缓存加速，支持html伪静态页面 | 张戈博客")

[为typecho增加缓存功能,支持memcached缓存](https://cuojue.org/read/typecho-cache-memcache.html "为typecho增加缓存功能,支持memcached缓存")
