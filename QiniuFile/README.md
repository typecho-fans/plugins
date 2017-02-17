### 七牛云储存支持插件QiniuFile v1.3.1

将Typecho的文件功能接入七牛云存储，支持上传、修改和删除附件，获取文件在七牛的绝对网址或缩略图url。目录结构与默认`/year/month/`保持一致，也可自定义配置。

- :dart:sdk目录仅存放旧版，php版本高于5.2可放心删除。

 > v1.3.1更新：使用新版SDK(php5.3-7.0可用)，保留旧版目录及注释(php5.2可用)。（[@羽中](https://github.com/binjoo)）
 > v1.3.0更新：使用七牛图片处理API新增六种缩略图设置。（[@冰剑](https://github.com/binjoo)）

#### 使用方法：
第一步：下载本插件，放在 `usr/plugins/` 目录中；  
第二步：激活插件；  
第三步：填写空间名称、Access Key、Secret Key、域名 等配置；  
第四步：完成。

#### 特别说明：
这个插件是为满足个人需求而编写，兼容性方面多多少少会有不完善的地方，如有需求，可根据源代码自行修改，或者与我联系。

#### 与我联系：
作者：abelyao    
主页：[www.abelyao.com](http://www.abelyao.com/)  
或者通过 Typecho 官方QQ群 `8110782` 找到我
