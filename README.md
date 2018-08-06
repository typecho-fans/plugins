## 插件描述

> 不修改系统代码给login.php页面添加一个找回密码链接.
> 可对插件进行相关设置
> 邮件发送使用SendCloud进行发送, 需设置相应模板

## 使用帮助

 1. 下载插件压缩包
 2. 解压并将插件文件夹命名为`LoveKKForget`
 3. 上传`LoveKKForget`文件夹至插件目录下
 4. 后台激活插件
 5. 注册SendCloud账号并进行相应的设置、获取API
 6. 在SendCloud中新建一个模板，内容为下方邮件模板内容
 7. 插件中进行相关设置

## 邮件模板
 ```html
 <div style="background:#ececec;width: 100%;padding: 50px 0;text-align:center;">
<div style="background:#fff;width:750px;text-align:left;position:relative;margin:0 auto;font-size:14px;line-height:1.5;">
<div style="zoom:1;padding:25px 40px;background:#518bcb; border-bottom:1px solid #467ec3;">
<h1 style="color:#fff; font-size:25px;line-height:30px; margin:0;"><a href="%home_url%" style="text-decoration: none;color: #FFF;">您在 [%blogname%] 的密码找回申请!</a></h1>
</div>

<div style="padding:35px 40px 30px;">
<h2 style="font-size:18px;margin:5px 0;"><span style="color: rgb(186, 76, 50); font-family:微软雅黑, verdana, arial; line-height: 23.3999996185303px;">%mail%</span>, 您好!</h2>

<p style="color:#313131;line-height:20px;font-size:15px;margin:20px 0;">您在 [%blogname%] 提交了找回密码申请, 请核对下方表内信息并点击链接修改密码：</p>

<table cellspacing="0" style="font-size:14px;text-align:center;border:1px solid #ccc;table-layout:fixed;width:500px;">
    <thead>
        <tr>
            <th style="padding:5px 0;text-indent:8px;border:1px solid #eee;border-width:0 1px 1px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:normal;color:#a0a0a0;background:#eee;border-color:#dfdfdf;" width="280px;">邮箱地址</th>
            <th style="padding:5px 0;text-indent:8px;border:1px solid #eee;border-width:0 1px 1px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:normal;color:#a0a0a0;background:#eee;border-color:#dfdfdf;" width="270px;">申请时间</th>
            <th style="padding:5px 0;text-indent:8px;border:1px solid #eee;border-width:0 1px 1px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:normal;color:#a0a0a0;background:#eee;border-color:#dfdfdf;" width="110px;">操作</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding:5px 0;text-indent:8px;border:1px solid #eee;border-width:0 1px 1px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">%mail%</td>
            <td style="padding:5px 0;text-indent:8px;border:1px solid #eee;border-width:0 1px 1px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">%sendtime%</td>
            <td style="padding:5px 0;text-indent:8px;border:1px solid #eee;border-width:0 1px 1px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><a href="%resetlink%" style="color:#1E5494;text-decoration:none;vertical-align:middle;" target="_blank">%resetlink%</a></td>
        </tr>
        <tr>
            <td colspan="3" style="padding:5px 0;text-indent:8px;border:1px solid #eee;border-width:0 1px 1px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#FF0000;font-weight:bold">重置密码链接有效时间为 &quot;%expire%&quot; 分钟, 请在此时间内进行密码重置</td>
        </tr>
    </tbody>
</table>
&nbsp;

<div style="font-size:13px;color:#a0a0a0;padding-top:10px">请注意：此邮件由&nbsp;<a href="%blogurl%" target="_blank" title="%blogname%">%blogname%</a>&nbsp;自动发送，请勿直接回复。<br />
若此邮件不是您请求的，请忽略并删除！</div>

<div class="qmSysSign" style="padding-top:20px;font-size:12px;color:#a0a0a0;">
<p><a href="%%user_defined_unsubscribe_link%%" style="background: #1ABC9C;border:1px solid #13A386;padding:8px 20px;color: #fff;text-decoration:none;border-radius:4px">不想再收到此类邮件</a></p>
</div>
</div>
</div>
</div>
```