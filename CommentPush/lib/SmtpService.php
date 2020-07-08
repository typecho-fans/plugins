<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */

require_once 'Service.php';
require_once 'Extend/SMTP.php';
require_once 'Extend/PHPMailer.php';

class SmtpService extends Service
{
    public function __handler($active, $comment, $plugin)
    {
        try {
            $isPushBlogger = $plugin->isPushBlogger;
            if ($comment['authorId'] == 1 && $isPushBlogger == 1 && !$comment['parent']) return false;

            $isPushCommentReply = $plugin->isPushCommentReply;

            $options = Helper::options();

            $smtpHost = $plugin->smtpHost;
            $smtpPort = $plugin->smtpPort;
            $smtpUser = $plugin->smtpUser;
            $smtpPass = $plugin->smtpPass;
            $smtpAuth = $plugin->smtpAuth;
            $smtpSecure = $plugin->smtpSecure;

            $authorTemplate = $plugin->authorTemplate;
            $replyTemplate = $plugin->replyTemplate;

            $smtpFromAlias = empty($plugin->smtpFromAlias) ? $options->title : $plugin->smtpFromAlias;
            $toAddress = $comment['mail'];


            if (empty($smtpHost) || empty($smtpPort) || empty($smtpUser) || empty($smtpPass) || empty($smtpSecure)) throw new \Exception('缺少SMTP邮件推送配置');


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

            list($subject, $body) = self::getSubjectAndBody($parentComment, $options, $comment, $active, $authorTemplate, $replyTemplate);


            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = empty($smtpAuth) ? false : $smtpAuth;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = $smtpPort;
            $mail->setFrom($smtpUser, $smtpFromAlias);
            $mail->addAddress($toAddress);
            $mail->addReplyTo($smtpUser, $smtpFromAlias);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = '如果邮件客户端不支持HTML则显示此内容';
            $result = $mail->send();
            self::logger(__CLASS__, $toAddress, json_encode($mail), $result);
        } catch (\Exception $exception) {
            self::logger(__CLASS__, '', '', '', $exception->getMessage());
        }
    }

    private function getSubjectAndBody($parentComment, $options, $comment, $active, $authorTemplate, $replyTemplate)
    {

        if (!is_null($parentComment)) {
            if (is_null($replyTemplate) || empty($replyTemplate)) {
                $html = file_get_contents(dirname(__DIR__) . '/theme/reply.html');
            } else {
                $html = $replyTemplate;
            }
        } else {
            if (is_null($authorTemplate) || empty($authorTemplate)) {
                $html = file_get_contents(dirname(__DIR__) . '/theme/author.html');
            } else {
                $html = $authorTemplate;
            }
        }


        /*$html = !is_null($parentComment) ?
            file_get_contents(dirname(__DIR__) . '/theme/reply.html') :
            file_get_contents(dirname(__DIR__) . '/theme/author.html');*/


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
}