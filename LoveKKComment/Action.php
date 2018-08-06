<?php
/**
 * Notes: 操作动作文件
 *
 * @Date  : 03/25/2018 21:19:18
 * @Author: 康康
 * @Link  : https://www.lovekk.org
 * @File  : Action.php
 */
if ( !defined('__TYPECHO_ROOT_DIR__') )
    exit;
// API请求地址
define('API', 'http://api.sendcloud.net/apiv2/');

class LoveKKComment_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * LoveKKComment_Action constructor.
     * @param $request
     * @param $response
     * @param null $params
     */
    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);
    }

    /**
     * Notes: 创建模板方法
     *
     * @access public
     * @date   03/25/2018 22:12:50
     * @return void
     * @throws Exception
     * @throws Typecho_Plugin_Exception
     */
    public function createTemplate()
    {
        // 错误代码
        $errno = 0;
        // 错误信息
        $error = '';
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKComment');
        // 回复通知模板请求参数
        $reply = array(
            'apiUser'      => $plugin->api_user,
            'apiKey'       => $plugin->api_key,
            'invokeName'   => 'LoveKKComment_Reply_Template', // 调用名称
            'name'         => '评论回复通知', // 模板名称
            'html'         => $this->replyTemplate(), // Html内容
            'subject'      => '您在 [%blogname%] 的评论有了新的回复！', // 邮件标题
            'templateType' => 0 // 设置为触发类型
        );
        // 获取请求结果
        $result = $this->send(API . 'template/add', $reply);
        // 解析json
        $json = json_decode($result);
        // 如果不成功
        if ( 200 != $json->statusCode || FALSE == $json->result ) {
            // 设置错误代码
            $errno = 1;
            // 设置错误信息
            $error .= '回复通知模板创建失败，错误信息：' . $json->message . '<br>';
        }
        // 审核通过模板请求参数
        $approved = array(
            'apiUser'      => $plugin->api_user,
            'apiKey'       => $plugin->api_key,
            'invokeName'   => 'LoveKKComment_Approved_Template', // 调用名称
            'name'         => '评论审核通过通知', // 模板名称
            'html'         => $this->approvedTemplate(), // Html内容
            'subject'      => '您在 [%blogname%] 的评论已通过审核！', // 邮件标题
            'templateType' => 0 // 设置为触发类型
        );
        $result   = $this->send(API . 'template/add', $approved);
        // 解析json
        $json = json_decode($result);
        // 如果不成功
        if ( 200 != $json->statusCode || FALSE == $json->result ) {
            // 设置错误代码
            $errno = 1;
            // 设置错误信息
            $error .= '审核通过模板创建失败，错误信息：' . $json->message . '<br>';
        }
        // 作者通知模板请求参数
        $author = array(
            'apiUser'      => $plugin->api_user,
            'apiKey'       => $plugin->api_key,
            'invokeName'   => 'LoveKKComment_Author_Template', // 调用名称
            'name'         => '作者评论通知', // 模板名称
            'html'         => $this->authorTemplate(), // Html内容
            'subject'      => '您在 [%blogname%] 发表的文章有新评论！', // 邮件标题
            'templateType' => 0 // 设置为触发类型
        );
        $result = $this->send(API . 'template/add', $author);
        // 解析json
        $json = json_decode($result);
        // 如果不成功
        if ( 200 != $json->statusCode || FALSE == $json->result ) {
            // 设置错误代码
            $errno = 1;
            // 设置错误信息
            $error .= '作者评论通知模板创建失败，错误信息：' . $json->message . '<br>';
        }
        // 输出Json
        $this->response->throwJson(array('errno' => $errno, 'message' => $error));
    }

    /**
     * Notes: 获取回复通知模板代码
     *
     * @access private
     * @date   03/25/2018 21:26:03
     * @return string
     */
    private function replyTemplate()
    {
        return '<table style="width:99.8%;height:99.8%"><tbody><tr><td style="background:#fafafa url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAy0lEQVQY0x2PsQtAYBDFP1keKZfBKIqNycCERUkMKLuSgZnRarIpJX8s3zfcDe9+794du+8bRVHQOI4wDAOmaULTNDDGYFkWMVVVQUTQdZ3iOMZxHCjLElVV0TRNYHVdC7ptW6RpSn3f4wdJkiTs+w6WJAl4DcOAbdugKAq974umaRAEARgXn+cRW3zfFxuiKCJZloXGHMeBbdv4Beq6Duu6Issy7iYB8Jbnucg8zxPLsggnj/zvIxaGIXmeB9d1wSE+nOeZf4HruvABUtou5ypjMF4AAAAASUVORK5CYII=)"><div style="border-radius:10px;font-size:13px;color:#555;width:666px;font-family:\'Century Gothic\',\'Trebuchet MS\',\'Hiragino Sans GB\',\'微软雅黑\',\'Microsoft Yahei\',Tahoma,Helvetica,Arial,SimSun,sans-serif;margin:50px auto;border:1px solid #eee;max-width:100%;background:#fff repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow:0 1px 5px rgba(0,0,0,.15)"><div style="width:100%;background:#49BDAD;color:#fff;border-radius:10px 10px 0 0;background-image:-moz-linear-gradient(0deg,#43c6b8,#ffd1f4);background-image:-webkit-linear-gradient(0deg,#43c6b8,#ffd1f4);height:66px"><p style="font-size:15px;word-break:break-all;padding:23px 32px;margin:0;background-color:hsla(0,0%,100%,.4);border-radius:10px 10px 0 0">您在 [<a href="%blogurl%" style="text-decoration:none;color:#fff" target="_blank">%blogname%</a>] 的评论有了新的回复！</p></div><div style="margin:40px auto;width:90%"><p>%author%，您曾在文章《<a href="%permalink%" style="text-decoration:none;color:#12addb" target="_blank">%title%</a>》上发表评论:</p><p style="background:#fafafa repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow:0 2px 5px rgba(0,0,0,.15);margin:20px 0;padding:15px;border-radius:5px;font-size:14px;color:#555">%text%</p><p>%author2% 给您的回复如下：</p><p style="background:#fafafa repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow:0 2px 5px rgba(0,0,0,.15);margin:20px 0;padding:15px;border-radius:5px;font-size:14px;color:#555">%text2%</p><p>您可以 <a href="%commenturl%" style="text-decoration:none;color:#12addb" target="_blank">查看回复完整内容</a>，欢迎再次光临 <a href="%blogurl%" style="text-decoration:none;color:#12addb" target="_blank">%blogname%</a>。</p><p>请注意：此邮件由 <a href="%blogurl%" style="color:#12addb" target="_blank">%blogname%</a> 自动发送，请勿直接回复。</p><p>若此邮件不是您请求的，请忽略并删除！</p><p class="qmSysSign" style="padding-top:20px;font-size:12px;color:#a0a0a0"><a href="%%user_defined_unsubscribe_link%%" style="background:#1ABC9C;border:1px solid #13A386;padding:8px 20px;color:#fff;text-decoration:none;border-radius:4px">不想再收到此类邮件</a></p></div></div></td></tr></tbody></table>';
    }

    /**
     * Notes: 远程请求方法
     *
     * @access private
     * @date   03/25/2018 21:36:17
     * @param string $uri
     * @param array $data
     * @return mixed
     */
    private function send($uri, $data)
    {
        // 初始化curl
        $ch = curl_init();
        // 请求地址
        curl_setopt($ch, CURLOPT_URL, $uri);
        // 返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 提交方式
        curl_setopt($ch, CURLOPT_POST, 1);
        // 提交数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // 执行提交
        $result = curl_exec($ch);
        // 关闭curl
        curl_close($ch);
        return $result;
    }

    /**
     * Notes: 获取评论审核通过通知模板代码
     *
     * @access private
     * @date   03/25/2018 21:26:52
     * @return string
     */
    private function approvedTemplate()
    {
        return '<table style="width:99.8%;height:99.8%"><tbody><tr><td style="background:#fafafa url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAy0lEQVQY0x2PsQtAYBDFP1keKZfBKIqNycCERUkMKLuSgZnRarIpJX8s3zfcDe9+794du+8bRVHQOI4wDAOmaULTNDDGYFkWMVVVQUTQdZ3iOMZxHCjLElVV0TRNYHVdC7ptW6RpSn3f4wdJkiTs+w6WJAl4DcOAbdugKAq974umaRAEARgXn+cRW3zfFxuiKCJZloXGHMeBbdv4Beq6Duu6Issy7iYB8Jbnucg8zxPLsggnj/zvIxaGIXmeB9d1wSE+nOeZf4HruvABUtou5ypjMF4AAAAASUVORK5CYII=)"><div style="border-radius:10px;font-size:13px;color:#555;width:666px;font-family:\'Century Gothic\',\'Trebuchet MS\',\'Hiragino Sans GB\',\'微软雅黑\',\'Microsoft Yahei\',Tahoma,Helvetica,Arial,SimSun,sans-serif;margin:50px auto;border:1px solid #eee;max-width:100%;background:#fff repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow:0 1px 5px rgba(0,0,0,.15)"><div style="width:100%;background:#49BDAD;color:#fff;border-radius:10px 10px 0 0;background-image:-moz-linear-gradient(0deg,#43c6b8,#ffd1f4);background-image:-webkit-linear-gradient(0deg,#43c6b8,#ffd1f4);height:66px"><p style="font-size:15px;word-break:break-all;padding:23px 32px;margin:0;background-color:hsla(0,0%,100%,.4);border-radius:10px 10px 0 0">您在 [<a href="%blogurl%" style="text-decoration:none;color:#fff" target="_blank">%blogname%</a>] 的评论已通过审核！</p></div><div style="margin:40px auto;width:90%"><p>%author%，您曾在文章《<a href="%permalink%" style="text-decoration:none;color:#12addb" target="_blank">%title%</a>》上发表评论:</p><p style="background:#fafafa repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow:0 2px 5px rgba(0,0,0,.15);margin:20px 0;padding:15px;border-radius:5px;font-size:14px;color:#555">%text%</p><p>目前您的评论已通过审核！</p><p>请注意：此邮件由 <a href="%blogurl%" style="color:#12addb" target="_blank">%blogname%</a> 自动发送，请勿直接回复。</p><p>若此邮件不是您请求的，请忽略并删除！</p><p class="qmSysSign" style="padding-top:20px;font-size:12px;color:#a0a0a0"><a href="%%user_defined_unsubscribe_link%%" style="background:#1ABC9C;border:1px solid #13A386;padding:8px 20px;color:#fff;text-decoration:none;border-radius:4px">不想再收到此类邮件</a></p></div></div></td></tr></tbody></table>';
    }

    /**
     * Notes: 获取作者通知模板代码
     *
     * @access private
     * @date   03/28/2018 21:05:43
     * @return string
     */
    private function authorTemplate()
    {
        return '<table style="width:99.8%;height:99.8%"><tbody><tr><td style="background:#fafafa url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAy0lEQVQY0x2PsQtAYBDFP1keKZfBKIqNycCERUkMKLuSgZnRarIpJX8s3zfcDe9+794du+8bRVHQOI4wDAOmaULTNDDGYFkWMVVVQUTQdZ3iOMZxHCjLElVV0TRNYHVdC7ptW6RpSn3f4wdJkiTs+w6WJAl4DcOAbdugKAq974umaRAEARgXn+cRW3zfFxuiKCJZloXGHMeBbdv4Beq6Duu6Issy7iYB8Jbnucg8zxPLsggnj/zvIxaGIXmeB9d1wSE+nOeZf4HruvABUtou5ypjMF4AAAAASUVORK5CYII=)"><div style="border-radius:10px;font-size:13px;color:#555;width:666px;font-family:\'Century Gothic\',\'Trebuchet MS\',\'Hiragino Sans GB\',\'微软雅黑\',\'Microsoft Yahei\',Tahoma,Helvetica,Arial,SimSun,sans-serif;margin:50px auto;border:1px solid #eee;max-width:100%;background:#fff repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow:0 1px 5px rgba(0,0,0,.15)"><div style="width:100%;background:#49BDAD;color:#fff;border-radius:10px 10px 0 0;background-image:-moz-linear-gradient(0deg,#43c6b8,#ffd1f4);background-image:-webkit-linear-gradient(0deg,#43c6b8,#ffd1f4);height:66px"><p style="font-size:15px;word-break:break-all;padding:23px 32px;margin:0;background-color:hsla(0,0%,100%,.4);border-radius:10px 10px 0 0">您在 [<a href="%blogurl%" style="text-decoration:none;color:#fff" target="_blank">%blogname%</a>] 发表的文章有新评论！</p></div><div style="margin:40px auto;width:90%"><p>%author% 在您的《<a href="%permalink%" style="text-decoration:none;color:#12addb" target="_blank">%title%</a>》上发表评论:</p><p style="background:#fafafa repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow:0 2px 5px rgba(0,0,0,.15);margin:20px 0;padding:15px;border-radius:5px;font-size:14px;color:#555">%text%</p><p>请注意：此邮件由 <a href="%blogurl%" style="color:#12addb" target="_blank">%blogname%</a> 自动发送，请勿直接回复。</p><p>若此邮件不是您请求的，请忽略并删除！</p><p class="qmSysSign" style="padding-top:20px;font-size:12px;color:#a0a0a0"><a href="%%user_defined_unsubscribe_link%%" style="background:#1ABC9C;border:1px solid #13A386;padding:8px 20px;color:#fff;text-decoration:none;border-radius:4px">不想再收到此类邮件</a></p></div></div></td></tr></tbody></table>';
    }

    public function action()
    {
    }
}