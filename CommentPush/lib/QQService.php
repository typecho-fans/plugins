<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */

require_once 'Service.php';

class QQService extends Service
{
    public function __handler($active, $comment, $plugin)
    {
        try {
            $isPushBlogger = $plugin->isPushBlogger;
            if ($comment['authorId'] == 1 && $isPushBlogger == 1) return false;

            $qqApiUrl = $plugin->qqApiUrl;
            $receiveQq = $plugin->receiveQq;

            if (empty($qqApiUrl) || empty($receiveQq)) throw new \Exception('缺少Qmsg酱配置参数');

            $title = $active->title;
            $author = $comment['author'];
            $link = $active->permalink;
            $context = $comment['text'];

            $template = '标题：' . $title . PHP_EOL
                . '评论人：' . $author . PHP_EOL
                . '评论内容：' . $context . PHP_EOL
                . '链接：' . $link . '#comment-' . $comment['coid'];

            $params = [
                'qq' => $receiveQq,
                'msg' => $template
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($params)
                ]
            ]);

            $result = file_get_contents($qqApiUrl, false, $context);
            self::logger(__CLASS__, $receiveQq, $params, $result);
        } catch (\Exception $exception) {
            self::logger(__CLASS__, '', '', '', $exception->getMessage());
        }
    }


}