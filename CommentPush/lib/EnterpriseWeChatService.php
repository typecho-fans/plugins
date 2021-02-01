<?php
/**
 * @author stars_kim <stars_kim@163.com>
 * @modify 小码农 <chengshongguo@qq.com> 增加实例化方法
 */

require_once 'Service.php';

class EnterpriseWeChatService extends Service
{
    public static function create(){

		static $instance ;
		if (!$instance){ 
		    $instance = new EnterpriseWeChatService();
		}
		return $instance;
	}
    public function __handler($active, $comment, $plugin)
    {
        try {
            $isPushBlogger = $plugin->isPushBlogger;
            if ($comment['authorId'] == 1 && $isPushBlogger == 1) return false;


            $EnterpriseWeChatWebhook = $plugin->EnterpriseWeChatWebhook;
            if (empty($EnterpriseWeChatWebhook)) throw new \Exception('缺少企业微信机器人配置参数');

            $title = $active->title;
            $author = $comment['author'];
            $link = $active->permalink;
            $context = $comment['text'];

            $template = '标题：' . $title . PHP_EOL
                . '评论人：' . $author . " [{$comment['ip']}]" . PHP_EOL
                . '评论内容：' . $context . PHP_EOL
                . '链接：' . $link . '#comment-' . $comment['coid'];

            $params = [
                'msgtype' => 'text',
                'text' => [
                    'content' => $template
                ]
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json;charset=utf-8',
                    'content' => json_encode($params)
                ]
            ]);

            $result = file_get_contents($EnterpriseWeChatWebhook, false, $context);
            self::logger(__CLASS__, '', $params, $result);
        } catch (\Exception $exception) {
            self::logger(__CLASS__, '', '', '', $exception->getMessage());
        }

    }
}