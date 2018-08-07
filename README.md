## 插件简介

SendCloud评论邮件通知插件（LoveKKComment）是一款针对Typecho博客系统的评论邮件通知插件。

插件通过后台配置后，可在用户回复评论时对被回复者进行邮件通知，摒弃传统stmp等邮件发送方式，使用SendCloud进行邮件通知。

## 安装方法

> 1. 至[releases](https://github.com/typecho-fans/plugins/releases/tag/plugins-H_to_L)中下载最新版本插件；
> 2. 将下载的压缩包进行解压并上传至`Typecho`插件目录中；
> 3. 后台激活插件；
> 4. 插件配置中将所有信息配置完成；
> 5. 再次返回插件配置中，点击刚出现的`一键创建通知模板`按钮创建通知模板。

## 自定义模板说明

若不想使用插件自带的通知模板，请自行登录`SendCloud`后台创建自己的模板。

回复通知模板调用名称：**LoveKKComment_Reply_Template**

评论审核模板调用名称：**LoveKKComment_Approved_Template**

### `LoveKKComment_Reply_Template`模板变量

> 1. 博客网站名称：**%blogname%**
> 2. 博客网站地址：**%blogurl%**
> 3. 被回复者名称：**%author%**
> 4. 评论文章名称：**%title%**
> 5. 评论文章地址：**%permalink%**
> 6. 被回复者评论：**%text%**
> 7. 回复用户名称：**%author2%**
> 8. 回复用户内容：**%text2%**
> 9. 回复楼层地址：**%commenturl%**

### `LoveKKComment_Approved_Template`模板变量

> 1. 博客网站名称：**%blogname%**
> 2. 博客网站地址：**%blogurl%**
> 3. 评论作者名称：**%author%**
> 4. 评论文章地址：**%permalink%**
> 5. 评论文章标题：**%title%**
> 6. 评论内容文本：**%text%**

## 更新日志

### 2018.8.8

> 1. 修正版本检测地址
> 2. 符合TeStore插件
> 3. 仓库迁移

### 2018.3.28

> 增加评论作者通知功能（用户评论后自动发送邮件通知文章作者）