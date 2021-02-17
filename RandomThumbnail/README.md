## Typecho随机图片挂件
[![Apache2](https://camo.githubusercontent.com/64d506383be67decddf8968e3b0072c3e9ba4a84/68747470733a2f2f696d672e736869656c64732e696f2f686578706d2f6c2f706c75672e737667)](LICENSE)
[![HitCount](http://hits.dwyl.io/LittleJake/Typecho-RandomThumbnail.svg)](http://hits.dwyl.io/LittleJake/Typecho-RandomThumbnail)


根据后台设置链接随机生成图片。

支持自定义模板。

## 安装方法

1. `git clone`或zip下载，将 Typecho-RandomThumbnail 中`RandomThumbnail`文件夹放入`网站目录/usr/plugins`文件夹内，文件夹权限0755，插件文件0644。
2. 打开Typecho后台激活插件
3. 添加图片链接
4. 在挂件位置插入
```php
<?php echo RandomThumbnail_Plugin::getThumbnail($seed); ?>
```
## 参数说明
### 调用函数参数
| 参数    | 参数名      | 参数类型 | 备注 |
| ------- | ----------- | -------- | ---- |
| $seed   | 随机数      | int      | 可选 |

### 设置模板参数
| 参数    | 参数名      | 参数类型  | 备注 |
| ------- | ---------- | -------- | ---- |
| img_url | 图片地址    | String   | - |


## Demo
![preview](https://cdn.jsdelivr.net/gh/LittleJake/blog-static-files@imgs/imgs/20200711112058.png)



[Demo](https://blog.littlejake.net/)

## 鸣谢
Typecho

## 开源协议
Apache2.0