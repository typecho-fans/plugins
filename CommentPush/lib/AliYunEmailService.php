<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */

require_once 'Service.php';


class AliYunEmailService extends Service
{
    const HANGZHOU = 'hangzhou';
    const SINGAPORE = 'singapore';
    const SYDNEY = 'sydney';

    private static $regions = [
        self::HANGZHOU => [
            'regionId' => 'cn-hangzhou',
            'host' => 'https://dm.aliyuncs.com/',
            'version' => '2015-11-23'
        ],
        self::SINGAPORE => [
            'regionId' => 'ap-southeast-1',
            'host' => 'https://dm.ap-southeast-1.aliyuncs.com/',
            'version' => '2017-06-22'
        ],
        self::SYDNEY => [
            'regionId' => 'ap-southeast-2',
            'host' => 'https://dm.ap-southeast-2.aliyuncs.com/',
            'version' => '2017-06-22'
        ]
    ];

    public function __handler($active, $comment, $plugin)
    {
        try {
            $isPushBlogger = $plugin->isPushBlogger;
            if ($comment['authorId'] == 1 && $isPushBlogger == 1 && !$comment['parent']) return false;

            $isPushCommentReply = $plugin->isPushCommentReply;

            $options = Helper::options();
            $accountName = $plugin->accountName;
            $regionId = $plugin->regionId;
            $accessKeyId = $plugin->accessKeyId;
            $accessKeySecret = $plugin->accessKeySecret;
            $fromAlias = empty($plugin->fromAlias) ? $options->title : $plugin->fromAlias;
            $toAddress = $comment['mail'];

            $regionInfo = self::$regions[$regionId];


            if (empty($accountName) || empty($accessKeyId) || empty($accessKeySecret) || empty($regionInfo)) throw new \Exception('缺少阿里云邮件推送配置');


            $parentComment = NULL;

            if ($comment['authorId'] != $comment['ownerId']) {
                $author = self::getWidget('Users', 'uid', $comment['ownerId']);
                $toAddress = $author->mail;
                $parentComment = NULL;
            }


            if ($comment['parent'] && $comment['parent'] > 0) {
                $parentComment = self::getWidget('Comments', 'coid', $comment['parent']);
                if (isset($parentComment->coid) && $comment['mail'] != $parentComment->mail) {
                    $toAddress = $parentComment->mail;
                }
            }

            if (!is_null($parentComment) && $isPushCommentReply != 1) return false;

            list($subject, $body) = self::getSubjectAndBody($parentComment, $options, $comment, $active);


            $data = [
                'Action' => 'SingleSendMail',
                'AccountName' => $accountName,
                'ReplyToAddress' => "true",
                'AddressType' => 1,
                'ToAddress' => $toAddress,
                'FromAlias' => $fromAlias,
                'Subject' => $subject,
                'HtmlBody' => $body,
                'Format' => 'JSON',
                'Version' => $regionInfo['version'],
                'AccessKeyId' => $accessKeyId,
                'SignatureMethod' => 'HMAC-SHA1',
                'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                'SignatureVersion' => '1.0',
                'SignatureNonce' => md5(time()),
                'RegionId' => $regionInfo['regionId']
            ];
            $data['Signature'] = self::sign($data, $accessKeySecret);


            $content = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => self::getPostHttpBody($data)
                ]
            ]);
            $result = file_get_contents($regionInfo['host'], null, $content);
            self::logger(__CLASS__, $toAddress, $data, $result);
        } catch (\Exception $exception) {
            self::logger(__CLASS__, '', '', '', $exception->getMessage());
        }
    }


    private function getSubjectAndBody($parentComment, $options, $comment, $active)
    {
        $html = !is_null($parentComment) ?
            file_get_contents(dirname(__DIR__) . '/theme/reply.html') :
            file_get_contents(dirname(__DIR__) . '/theme/author.html');


        $subject = !is_null($parentComment) ?
            _t('您在 [' . trim($options->title) . '] 的评论有了新的回复！') :
            _t('您在 [' . trim($options->title) . ']  发表的文章有新评论！');


        $body = !is_null($parentComment) ? str_replace(
            [
                '{blogUrl}',
                '{blogName}',
                '{author}',
                '{permalink}',
                '{title}',
                '{text}',
                '{replyAuthor}',
                '{replyText}',
                '{commentUrl}'
            ],
            [
                trim($options->siteUrl),
                trim($options->title),
                trim($parentComment->author),
                trim($active->permalink . '#comment-' . $comment['coid']),
                trim($active->title),
                trim($parentComment->text),
                trim($comment['author']),
                trim($comment['text']),
                trim($active->permalink . '#comment-' . $comment['coid'])
            ], $html) : str_replace(
            [
                '{blogUrl}',
                '{blogName}',
                '{author}',
                '{permalink}',
                '{title}',
                '{text}'
            ],
            [
                trim($options->siteUrl),
                trim($options->title),
                trim($comment['author']),
                trim($active->permalink . '#comment-' . $comment['coid']),
                trim($active->title),
                trim($comment['text'])
            ], $html
        );

        return [$subject, $body];
    }

    private function getWidget($table, $key, $val)
    {
        $className = 'Widget_Abstract_' . $table;
        $db = Typecho_Db::get();
        $widget = new $className(Typecho_Request::getInstance(), Typecho_Widget_Helper_Empty::getInstance());
        $db->fetchRow($widget->select()->where($key . ' = ?', $val)->limit(1), array($widget, 'push'));

        return $widget;
    }

    private function sign($params, $accessKeySecret)
    {
        ksort($params);

        $stringToSign = 'POST&' . self::percentEncode('/') . '&';

        $tmp = '';

        foreach ($params as $k => $param) $tmp .= '&' . self::percentEncode($k) . '=' . self::percentEncode($param);

        $tmp = trim($tmp, '&');

        $stringToSign = $stringToSign . self::percentEncode($tmp);

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', TRUE));

        return $signature;
    }


    private function percentEncode($val)
    {
        $res = urlencode($val);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }

    private function getPostHttpBody($param)
    {
        $str = "";
        foreach ($param as $k => $v) $str .= $k . '=' . urlencode($v) . '&';
        return substr($str, 0, -1);
    }


}