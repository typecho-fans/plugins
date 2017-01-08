## 简介 ##

一个简洁清新的 Typecho 播放器插件，支持 Memcache 和文件式缓存服务。

## 使用 ##

后台支持编辑器按钮插入音乐，由于技术不够，所以多部分写入要靠手写。格式为单独一行，上下要空行：

```markdown
[Remix serve=服务 auto=自动 loop=循环 type=类型 songs=ID]
```

### 参数 ###

 - serve: 服务，值有 nets 或 xiami, 留空默认为 xiami
 - auto: 自动播放，值有 1 或 0
 - loop: 循环播放，值有 1 或 0
 - type: 列表类型，值有 song、list、album、collect
 - songs: 单曲、专辑、精选集的 id，类型列表时为列表中歌曲的 id

### 示例 ###

 - 单曲: `[Remix serve=xiami auto=0 loop=1 type=song songs=2086679]`
 - 列表: `[Remix serve=nets auto=0 loop=1 type=list songs=2039856,2086679,1298289]`
 - 专辑: `[Remix serve=xiami auto=0 loop=1 type=album songs=12019827]`
 - 精选集: `[Remix serve=nets auto=0 loop=1 type=collect songs=4406118]`

### 缓存 ###

缓存服务默认为 Memcache，选项中还有个 Redis，暂时有问题，切忌选择，下面缓存服务配置是这些类型缓存服务的设置，文件缓存不需要设置。

## 注意 ##

在启用前，请确认自己的主机支持 Curl 和插件需要的缓存服务，文件缓存需要支持文件写入权限。

## 感谢 ##

 - [SoundManager2](https://github.com/scottschiller/SoundManager2) 播放器内核
 - [Hermit](https://github.com/iMuFeng/Hermit) 提供音乐API和许多参考
