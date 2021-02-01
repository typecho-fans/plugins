<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 * @modify 小码农 <chengshongguo@qq.com> 增加实例化方法
 */

require_once 'Service.php';

class OfficialAccountService extends Service
{
    public static function create(){

		static $instance ;
		if (!$instance){ 
		    $instance = new OfficialAccountService();
		}
		return $instance;
	}
    public function __handler($active, $comment, $plugin)
    {
        try {
            $token = $plugin->officialAccountToken;
            $appId = $plugin->officialAccountAppId;
            $appSecret = $plugin->officialAccountAppSecret;
            $openid = $plugin->officialAccountOpenid;
            $templateId = $plugin->officialAccountTemplateId;
            if (empty($token) || empty($appId) || empty($appSecret) || empty($openid) || empty($templateId)) throw new \Exception('缺少微信公众号配置参数');

            $accessTokenResult = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}");
            $accessTokenResult = json_decode($accessTokenResult, true);
            if ($accessTokenResult == false || isset($r['errcode']) || isset($r['errmsg'])) {
                self::logger(__CLASS__, '', '', $accessTokenResult);
                return false;
            }

            $accessToken = $accessTokenResult['access_token'];


            $title = $active->title;
            $author = $comment['author'];
            $link = $active->permalink;
            $context = $comment['text'];

            $params = json_encode([
                'touser' => $openid,
                'template_id' => $templateId,
                'url' => $link . '#comment-' . $comment['coid'],
                'appid' => $appId,
                'data' => [
                    'title' => [
                        'value' => $title,
                        'color' => "#173177"
                    ],
                    'user' => [
                        'value' => $author,
                        'color' => "#F00"
                    ],
                    'ip' => [
                        'value' => $comment['ip'],
                        'color' => "#173177"
                    ],
                    'content' => [
                        'value' => $context,
                        'color' => "#3D3D3D"
                    ],
                ]
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => urldecode($params)
                ]
            ]);
            $result = file_get_contents('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $accessToken, false, $context);
            self::logger(__CLASS__, $openid, $params, $result);
        } catch (\Exception $exception) {
            self::logger(__CLASS__, '', '', '', $exception->getMessage());
        }
    }
}