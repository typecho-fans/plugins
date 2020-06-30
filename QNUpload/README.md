<a href="https://typecho-fans.github.io">
    <img src="https://typecho-fans.github.io/text-logo.svg" alt="TF Logo" title="Typecho Fans开源作品社区" align="right" height="100" />
</a>

QNUpload v1.3.1 - 社区维护版
======================
<h4 align="center">—— 经典版七牛云附件上传插件，适用于PHP 5.2-5.6版本环境。</h4>

<p align="center">
  <a href="#使用说明">使用说明</a> •
  <a href="#版本历史">版本历史</a> •
  <a href="#贡献作者">贡献作者</a> •
  <a href="#附注链接">附注/链接</a> •
  <a href="#授权协议">授权协议</a>
</p>

---

## 使用说明

<table>
<tr>
<td>

###### 七牛新版SDK已不支持低版本PHP环境，在有[QiniuFile社区版](https://github.com/typecho-fans/plugins/tree/master/QiniuFile)更新情况下本插件仅作归档备用，激活及设置方法与后者基本相同。

**使用方法**：
##### 1. 下载本插件，放在 `usr/plugins/` 目录中，确保文件夹名为 QNUpload；
##### 2. 激活插件，填写Access Key、Secret Key、Bucket名称、域名等配置；
##### 3. 保存设置，使用附件上传功能即可看到效果。

**注意事项**：
* ##### 由于SDK版本较老，地域节点只有4处选项，坚持在低版本PHP下使用的童鞋或可自行尝试添加。

</td>
</tr>
</table>

## 版本历史

 * v1.3.1 (16-12-12 [@rakiy](https://github.com/rakiy))
   * 修正后台域名正则判定，增加对ssl域名的支持。
 * v1.3.0 (16-12-09 [@rakiy](https://github.com/rakiy))
   * 后台添加选择bucket的位置选项，影响上传接口；
   * 后台添加域名必填的提示(感谢@雨伤反馈)；
   * 修复后台返回文件不带路径的BUG(感谢@雨伤反馈)。
 * v1.2.0 (16-11-28 [@rakiy](https://github.com/rakiy))
   * 上传至GitHub的Typecho-Fans目录，产生社区维护版；
   * 将所含七牛SDK升级至6.1.3，同时简化了上传方法。
   * 增加TE1.0的兼容性。
 * v1.1.0 (14-5-9 [@rakiy](https://github.com/rakiy))
   * (不详)
 * v1.0.0 (13-12-26 [@rakiy](https://github.com/rakiy))
   * 第1版本完成，改自[@doudou](https://github.com/doudoutime)的BAE插件。

## 贡献作者

[![rakiy](https://avatars1.githubusercontent.com/u/2987195?v=3&s=100)](https://github.com/rakiy) | [![doudoutime](https://avatars1.githubusercontent.com/u/1299098?v=3&s=100)](https://github.com/doudoutime)
:---:|:---:
[rakiy](https://github.com/rakiy) (2013) | [doudoutime](https://github.com/doudoutime) (2013)

## 附注/链接

作者鸣谢：

[@兜兜](https://github.com/doudoutime)，[@刘世杰](http://t.qq.com/youtubefans)，[@雨伤](http://t.qq.com/yushanggj)，@超人欧巴。

本社区版暂未产生更新分支，与原作v1.3.1版代码基本一致。

## 授权协议

TF目录下的社区维护版作品如果没有明确声明，默认采用与Typecho相同的[GPLv2](https://github.com/typecho/typecho/blob/master/LICENSE.txt)开源协议。(要求提及出处，保持开源并注明修改。)

> QNUpload原作未附协议声明，原作者保留所有权利。 © [rakiy](https://del.pub)
