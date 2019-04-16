*NewPost，可通过get或post请求发送文章的typecho插件*

**使用说明：** 
上传插件并启用，添加一位用户。用户组为编辑（为了安全）

设置验证密钥key

设置发表分类mid，例如某分类设置页url为 https://wei.bz/admin/category.php?mid=2 ，mid就等于2

**通过post发表文章：** 

**请求URL：** 
- ` 你的网址/action/import `
  
**请求方式：**
- POST|GET 

**参数：** 

|参数名|必选|类型|说明|
|:----    |:---|:----- |-----   |
|title |  是  |    string   |    标题   |
|text |  是  |    string   |    正文   |
|key |  是  |    string   |    验证密钥   |


下载地址：[NewPost.zip][1]


  [1]: https://github.com/iLay1678/NewPost/releases/download/v0.1/NewPost.zip