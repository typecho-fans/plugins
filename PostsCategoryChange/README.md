# Typecho 批量更改文章分类状态插件 PostsCategoryChange

## 插件简介

批量更新文章分类、状态（公开|隐藏|私密）

## 注意（灰常重要）

* 在批量更新文章分类的时候，请先确认**被操作的文章是否都只有一个分类。**
* 如果有个别文章存在多个分类的请手工修改分类，不要使用本插件。

## 安装方法

* 至[releases](https://github.com/fuzqing/PostsCategoryChange/releases)中下载最新版本插件；
* 将下载的压缩包进行解压，文件夹重命名为`PostsCategoryChange`，上传至`Typecho`插件目录中；
* 后台激活插件。

## 使用方法

* 到文章管理界面选择你要修改分类的文章 -> 选中项 -> 移动 -> 选择一个分类。
![makeChange](http://p7dh1laws.bkt.clouddn.com/makeChange.gif)
* 到文章管理界面选择你要修改分类的文章 -> 选中项 -> 设置状态
![makeChange](http://p7dh1laws.bkt.clouddn.com/changeStaus.gif)

## 更新日志

### 2018.7.6

* 增加批量修改文章状态为私密（蜜汁尴尬，之前不知道有这个状态，没注意）

### 2018.7.3

* 修复了在后台首页waring的报错

### 2018.6.22

* 更新了批量修改文章状态（公开|隐藏|私密）
* 不用增加钩子也能用了 By benzBrake 

