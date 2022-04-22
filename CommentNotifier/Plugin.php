<?php
namespace TypechoPlugin\CommentNotifier;
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Checkbox;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Layout;
use Widget\Options;
use Widget\Base\Comments;
use Typecho\Db;
use Typecho\Date;

/**
 * typecho 评论通过时发送邮件提醒,要求typecho1.2.o及以上,项目地址<a href="https://github.com/jrotty/CommentNotifier" target="_blank">https://github.com/jrotty/CommentNotifier</a>
 * @package CommentNotifier
 * @author 泽泽社长
 * @version 1.0.0
 * @link http://blog.zezeshe.com
 */

require dirname(__FILE__) . '/PHPMailer/src/PHPMailer.php';
require dirname(__FILE__) . '/PHPMailer/src/SMTP.php';
require dirname(__FILE__) . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Plugin implements PluginInterface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return string
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget_Feedback')->finishComment = __CLASS__. '::finishComment'; // 前台提交评论完成接口
        \Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment = __CLASS__. '::finishComment'; // 后台操作评论完成接口
        \Typecho\Plugin::factory('Widget_Comments_Edit')->mark = __CLASS__. '::mark'; // 后台标记评论状态完成接口
        return _t('请配置邮箱SMTP选项!');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate()
    {
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Form $form)
    {
        // 记录log
        $log = new Checkbox('log', ['log' => _t('记录日志')], 'log', _t('记录日志'), _t('启用后将当前目录生成一个log.txt 注:目录需有写入权限'));
        $form->addInput($log);

        $layout = new Layout();
        $layout->html(_t('<h3>邮件服务配置:</h3>'));
        $form->addItem($layout);

        // SMTP服务地址
        $STMPHost = new Text('STMPHost', NULL, 'smtp.qq.com', _t('SMTP服务器地址'), _t('如:smtp.163.com,smtp.gmail.com,smtp.exmail.qq.com,smtp.sohu.com,smtp.sina.com'));
        $form->addInput($STMPHost->addRule('required', _t('SMTP服务器地址必填!')));

        // SMTP用户名
        $SMTPUserName = new Text('SMTPUserName', NULL, NULL, _t('SMTP登录用户'), _t('SMTP登录用户名，一般为邮箱地址'));
        $form->addInput($SMTPUserName->addRule('required', _t('SMTP登录用户必填!')));

        // 发件邮箱
        $from = new Text('from', NULL, NULL, _t('SMTP邮箱地址'), _t('请填写用于发送邮件的邮箱，一般与SMTP登录用户名一致'));
        $form->addInput($from->addRule('required', _t('发件邮箱必填!')));
        
        // SMTP密码
        $description = _t('一般为邮箱登录密码, 有特殊如: QQ邮箱有独立的SMTP密码. 可参考: ');
        $description .= '<a href="https://service.mail.qq.com/cgi-bin/help?subtype=1&&no=1001256&&id=28" target="_blank">QQ邮箱</a> ';
        $description .= '<a href="https://mailhelp.aliyun.com/freemail/detail.vm?knoId=6521875" target="_blank">阿里邮箱</a> ';
        $description .= '<a href="https://support.office.com/zh-cn/article/outlook-com-%E7%9A%84-pop%E3%80%81imap-%E5%92%8C-smtp-%E8%AE%BE%E7%BD%AE-d088b986-291d-42b8-9564-9c414e2aa040?ui=zh-CN&rs=zh-CN&ad=CN" target="_blank">Outlook邮箱</a> ';
        $description .= '<a href="http://help.sina.com.cn/comquestiondetail/view/160/" target="_blank">新浪邮箱</a> ';
        $SMTPPassword = new Text('SMTPPassword', NULL, NULL, _t('SMTP登录密码'), $description);
        $form->addInput($SMTPPassword->addRule('required', _t('SMTP登录密码必填!')));

        // 服务器安全模式
        $SMTPSecure = new Radio('SMTPSecure', array('' => _t('无安全加密'), 'ssl' => _t('SSL加密'), 'tls' => _t('TLS加密')), 'none', _t('SMTP加密模式'));
        $form->addInput($SMTPSecure);

        // SMTP server port
        $SMTPPort = new Text('SMTPPort', NULL, '25', _t('SMTP服务端口'), _t('默认25 SSL为465 TLS为587'));
        $form->addInput($SMTPPort);

        $layout = new Layout();
        $layout->html(_t('<h3>邮件信息配置:</h3>'));
        $form->addItem($layout);

        // 发件人姓名
        $fromName = new Text('fromName', NULL, NULL, _t('发件人姓名'), _t('发件人姓名'));
        $form->addInput($fromName->addRule('required', _t('发件人姓名必填!')));

        // 收件邮箱
        $adminfrom = new Text('adminfrom', NULL, NULL, _t('站长收件邮箱'), _t('遇到待审核评论或文章作者邮箱为空时，评论提醒会发送到此邮箱地址！'));
        $form->addInput($adminfrom->addRule('required', _t('收件邮箱必填!')));
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
    }


    /**
     * @param $comment
     * @return array
     * @throws Typecho_Db_Exception
     * 获取上级评论人
     */
    public static function getParent($comment)
    {
        $recipients = [];
        $db = Db::get();
        $widget = Comments::alloc();
        // 查询
        $select = $widget->select()->where('coid' . ' = ?', $comment->parent)->limit(1);
        $parent = $db->fetchRow($select, [$widget, 'push']); // 获取上级评论对象
        if ($parent && $parent['mail']) {
            $recipients = [
                'name' => $parent['author'],
                'mail' => $parent['mail'],
            ];
        }
        return $recipients;
    }
    /**
     * @param $comment
     * @return array
     * @throws Typecho_Db_Exception
     * 获取文章作者邮箱
     */
    public static function getzuozhe($comment)
    {
        $CommentNotifier = Options::alloc()->plugin('CommentNotifier');
        $recipients = [];
        $db = Db::get();
$ae=$db->fetchRow($db->select()->from ('table.users')->where ('table.users.uid=?',$comment->ownerId));
        if(empty($ae['screenName'])){
            $ae['screenName']=$CommentNotifier->adminfrom;
        }
        $recipients = [
                'name' => $ae['screenName'],
                'mail' => $ae['mail'],
        ];
        // 查询
        return $recipients;
    }
    /**
     * @param $comment
     * @param Widget_Comments_Edit $edit
     * @param $status
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * 在后台标记评论状态时的回调
     */
    public static function mark($comment, $edit, $status)
    {
        // 在后台标记评论状态为[approved 审核通过]时, 发信给上级评论人
        if ($status == 'approved' && 0 < $edit->parent) {
            $parent = self::getParent($edit);
            // 如果自己回复自己的评论, 不做任何操作
            if ($parent['mail'] == $edit->mail) {
                return;
            }
            $CommentNotifier = Options::alloc()->plugin('CommentNotifier');
            $from = $CommentNotifier->adminfrom; // 站长邮箱
            // 如果上级是博主, 不做任何操作
            if ($parent['mail'] == $from) {
                return;
            }
            
            $recipients[] = $parent;
            self::sendMail($edit, $recipients, '您有一条新的回复');
        }
    }


    /**
     * @param Widget_Comments_Edit|Widget_Feedback $comment
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * 评论/回复时的回调
     */
    public static function finishComment($comment)
    {
        $CommentNotifier = Options::alloc()->plugin('CommentNotifier');
        $from = $CommentNotifier->adminfrom; // 站长邮箱
        $fromName = $CommentNotifier->fromName; // 发件人
        $recipients = [];
        // 审核通过
        if ($comment->status == 'approved') {
            // 不需要发信给博主
            if ($comment->authorId != $comment->ownerId && $comment->mail != $CommentNotifier->from) {
                $recipients[] = self::getzuozhe($comment);//收到新评论后发送给文章作者
            }
            // 如果有上级
            if ($comment->parent > 0) {
                // 查询上级评论人
                $parent = self::getParent($comment);//获取上级评论者邮箱
                // 如果上级是博主和自己回复自己, 不需要发信
                if ($parent['mail'] != $xfrom && $parent['mail'] != $comment->mail) {
                    $recipients[] = $parent;
                }
            }
            self::sendMail($comment, $recipients, '您有一条新的回复');
        } else {
            // 如果所有评论必须经过审核, 通知博主审核评论
            $recipients[] = ['name' => $fromName, 'mail' => $from];
            self::sendMail($comment, $recipients, '您有一条新的待审核回复');
        }
    }

    /**
     * @param Widget_Comments_Edit|Widget_Feedback $comment
     * @param array $recipients
     * @param $desc
     * @throws Typecho_Plugin_Exception
     */
    private static function sendMail($comment, $recipients, $desc)
    {
        if (empty($recipients)) return; // 没有收信人
        try {
            // 获取系统配置选项
            $options = Options::alloc();
            // 获取插件配置
            $CommentNotifier = $options->plugin('CommentNotifier');
            $from = $CommentNotifier->from; // 发件邮箱
            $fromName = $CommentNotifier->fromName; // 发件人
            // Server settings
            $mail = new PHPMailer(true);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;
            $mail->isSMTP();
            $mail->Host = $CommentNotifier->STMPHost; // SMTP 服务地址
            $mail->SMTPAuth = true; // 开启认证
            $mail->Username = $CommentNotifier->SMTPUserName; // SMTP 用户名
            $mail->Password = $CommentNotifier->SMTPPassword; // SMTP 密码
            $mail->SMTPSecure = $CommentNotifier->SMTPSecure; // SMTP 加密类型 'ssl' or 'tls'.
            $mail->Port = $CommentNotifier->SMTPPort; // SMTP 端口

            $mail->setFrom($from, $fromName);
            foreach ($recipients as $recipient) {
                $mail->addAddress($recipient['mail'], $recipient['name']); // 发件人
            }
            $mail->Subject = '你有来自《' . $comment->title . '》文章的新回复';

            $mail->isHTML(); // 邮件为HTML格式
            // 邮件内容
            $content = self::mailBody($comment, $options, $desc);
            $mail->Body = $content;
            $mail->send();

            // 记录日志
            if ($CommentNotifier->log) {
                $at = date('Y-m-d H:i:s');
                if ($mail->isError()) {
                    $data = $at . ' ' . $mail->ErrorInfo; // 记录发信失败的日志
                } else { // 记录发信成功的日志
                    $recipientNames = $recipientMails = '';
                    foreach ($recipients as $recipient) {
                        $recipientNames .= $recipient['name'] . ', ';
                        $recipientMails .= $recipient['mail'] . ', ';
                    }
                    $data  = PHP_EOL . $at .' 发送成功! ';
                    $data .= ' 发件人:'   . $fromName;
                    $data .= ' 发件邮箱:' . $from;
                    $data .= ' 接收人:'   . $recipientNames;
                    $data .= ' 接收邮箱:' . $recipientMails . PHP_EOL;
                }
                $fileName = dirname(__FILE__) . '/log.txt';
                file_put_contents($fileName, $data, FILE_APPEND);
            }

        } catch (Exception $e) {
            $fileName = dirname(__FILE__) . '/log.txt';
            $str = "\nerror time: ".date('Y-m-d H:i:s') . "\n";
            file_put_contents($fileName, $str, FILE_APPEND);
            file_put_contents($fileName, $e, FILE_APPEND);
        }
    }
    /**
     * @param $comment
     * @param $options
     * @param $desc
     * @return string
     * 很朴素的邮件风格
     */
    private static function mailBody($comment, $options, $desc)
    {
        $commentAt = new Date($comment->created);
        $commentAt = $commentAt->format('Y-m-d H:i:s');
      $commentText = htmlspecialchars($comment->text);
        $content = <<<HTML
<style type="text/css">.qmbox style, .qmbox script, .qmbox head, .qmbox link, .qmbox meta {display: none !important;}.emailz{background-color:white;border-top:2px solid #12ADDB;box-shadow:0 1px 3px #AAAAAA;line-height:180%;padding:0 15px 12px;width:500px;margin:35px auto;color:#555555;font-family:'Century Gothic','Trebuchet MS','Hiragino Sans GB',微软雅黑,'Microsoft Yahei',Tahoma,Helvetica,Arial,'SimSun',sans-serif;font-size:14px;}@media(max-width:767px){.emailz{width: 88%;}}</style>
<div class="emailz">  
<h2 style="border-bottom:1px solid #DDD;font-size:14px;font-weight:normal;padding:13px 0 10px 8px;"><span style="color: #12ADDB;font-weight: bold;">&gt; </span>在<a style="text-decoration:none;color: #12ADDB;" href="{$comment->permalink}" target="_blank" rel="noopener">《{$comment->title}》</a>中，$desc</h2>
        <div style="padding:0 12px 0 12px;margin-top:18px">  
            <p>时间：<span style="border-bottom:1px dashed #ccc;" t="5" times=" 20:42">{$commentAt}</p>  
            <p style="background-color: #f5f5f5;border: 0px solid #DDD;padding: 10px 15px;margin:18px 0">{$commentText}</p>  
            <p>评论者:<span style="color: #12ADDB;">{$comment->author}</span></p>  
            <p style="background-color: #f5f5f5;border: 0px solid #DDD;padding: 10px 15px;margin:18px 0"> <a href="{$comment->mail}" target="_blank" rel="noopener">{$comment->mail}</a></p> 
            <p>您可以点击 <a style="text-decoration:none; color:#12addb" href="{$comment->permalink}" target="_blank" rel="noopener">查看回复的完整內容 </a>，欢迎再次光临 <a style="text-decoration:none; color:#12addb" href="{$options->siteUrl}" target="_blank" rel="noopener">{$options->title}</a>。</p>  
        </div>  
</div>
HTML;
        return $content;
    }

}
