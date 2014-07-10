# RewriteRule for Typecho

一个能让你的 Typecho 在 404 显示之前重定向到其他地方的插件。

Blog: http://note.laobubu.net/archives/typecho-rewriterule

## 安装

将东西丢到 `usr/plugins/RewriteRule` 下即可，文件夹自建。


## 配置

访问后台-插件管理，启用 RewriteRule 后方可设置。

在输入框内输入跳转的规则，一行一个。每一行格式如

    用来匹配的Pattern      新地址[       可选的flags]

例如 `^\/(\d+)\/?$ /archives/$1 T` 会把诸如 `/27` 或者 `/27/` 的地址跳转到 `/archivers/27` 上（而且因为这里有 T 这个flag，所以采用的是302临时重定向）




## 一些有用的信息
### 可用的 flags
flags 的字母代号不分大小写，不需要间隔符。

| flag | 功能 | 注   |
| ---: | :--- | :--- |
|   T | 这是一个302临时重定向。|若无此flag，则默认301 |
|   I | 为正则表达式打开i选项（即不分大小写） | … |
|   C | 按照条件查找文章。 | 这个有点复杂，注意看下面的 [应用举例1 之 分析那句规则](#分析那句规则)  |

##应用举例

###让不同的永久链接共存

####一个故事

Yang 用 Typecho 搭建了一个 Blog，写了一些文章，但是由于一直没有设置永久链接，因此他的文章地址一直是 `/archives/123/` 这样的。

有一天 Yang 为了提高网址易读性，他决定将永久链接改变为 `/archives/a-romantic-story/` 这样的格式，但是苦于过去的链接会失效，一直纠结怎么使得旧地址能跳到新地址上。

后来，他安装了 RewriteRule 这个插件，并添加了如下一句跳转规则：
    
    ^\/archives\/(?P<cid>\d+)\/$     /archives/$slug/    C

于是当访客访问 `/archives/123/` 这样的地址时， RewriteRule 便会查找对应文章的 slug 并引导访客到诸如 `/archives/a-romantic-story/` 这样的地址上。

####分析那句规则
1. 首先正则表达式 `^\/archives\/(?P<cid>\d+)\/` 会匹配先前提到的那种地址，并把其中的数字存为 `$cid` 变量。
2. 由于末尾加上了flag `C`，而且存在变量`$cid`，因此插件会搜索数据库查找对应文章。
    1. 如果找到了，则扩展会补齐 `$cid, $slug, $category` 三个变量，规则指定的新地址 `/archives/$slug/`里面的变量名自然会被替换。
    2. 如果没有查找到，则这句规则无效。要是后续的规则还是无效，则显示 404。
    
####关于 C 这个flag
这个 flag 会补全 `$cid, $slug, $category` 三个变量，只要 Pattern 里面搞到了至少一个上述变量就能工作。

要让正则给捕获组命名，写法有点难看，就像 `(?P<名字>正则表达式)` 这种。