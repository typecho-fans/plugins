<?php
/**
 * @author stars_kim <stars_kim@163.com>
 */

require_once 'Service.php';

class DingTalkBotService extends Service
{
    public function __handler($active, $comment, $plugin)
    {
        try {
            $isPushBlogger = $plugin->isPushBlogger;
            if ($comment['authorId'] == 1 && $isPushBlogger == 1) return false;

            $DingTalkWebhook = $plugin->DingTalkWebhook;
            $DingTalkSecret = $plugin->DingTalkSecret;

            if (empty($DingTalkWebhook) || empty($DingTalkSecret)) throw new \Exception('缺少钉钉机器人配置参数');

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

            list($s1, $s2) = explode(' ', microtime());
            $timestamp = (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
            $stringToSign = $timestamp . "\n" . $DingTalkSecret;
            $signature = base64_encode(hash_hmac('sha256', $stringToSign, $DingTalkSecret, true));
            $signature = utf8_encode(urlencode($signature));
            $DingTalkWebhook .="&timestamp=$timestamp&sign=$signature";
            $result = file_get_contents($DingTalkWebhook, null, $context);
            self::logger(__CLASS__, '', $params, $result);
        } catch (\Exception $exception) {
            self::logger(__CLASS__, '', '', '', $exception->getMessage());
        }
    }
}