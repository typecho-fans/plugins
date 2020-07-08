<?php
/**
 * @author stars_kim <stars_kim@163.com>
 */

require_once 'Service.php';

class EnterpriseWeChatService extends Service
{
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
                . '评论人：' . $author . PHP_EOL
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