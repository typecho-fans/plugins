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
use Utils\Helper;

/**
 * typecho 评论通过时发送邮件提醒,要求typecho1.2.0及以上,项目地址<a href="https://github.com/jrotty/CommentNotifier" target="_blank">https://github.com/jrotty/CommentNotifier</a>
 * @package CommentNotifier
 * @author 泽泽社长
 * @version 1.2.9
 * @link http://blog.zezeshe.com
 */

require dirname(__FILE__) . '/PHPMailer/PHPMailer.php';
require dirname(__FILE__) . '/PHPMailer/SMTP.php';
require dirname(__FILE__) . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Plugin implements PluginInterface
{
    /** @var string 控制菜单链接 */
    public static $panel = 'CommentNotifier/console.php';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return string
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget_Feedback')->finishComment = __CLASS__ . '::resendMail'; // 前台提交评论完成接口
        \Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment = __CLASS__ . '::resendMail'; // 后台操作评论完成接口
        \Typecho\Plugin::factory('Widget_Comments_Edit')->mark = __CLASS__ . '::mark'; // 后台标记评论状态完成接口
        \Typecho\Plugin::factory('Widget_Service')->refinishComment = __CLASS__ . '::refinishComment';//异步接口
        Helper::addPanel(1, self::$panel, '评论邮件提醒', '评论邮件提醒控制台', 'administrator');
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
        Helper::removePanel(1, self::$panel);
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Form $form 配置面板
     * @return void
     */
    public static function config(Form $form)
    {
        // 记录log
        $log = new Checkbox('log', ['log' => _t('记录日志')], 'log', _t('记录日志'), _t('启用后将当前目录生成一个log.txt 注:目录需有写入权限'));
        $form->addInput($log);
        
        $yibu = new Radio('yibu', array('0' => _t('不启用'), '1' => _t('启用'),), '0', _t('异步提交'), _t('注意：如你博客使用ajax提交评论请不要开启此项否则可能导致邮件无法发送！'));
        $form->addInput($yibu);

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
        $SMTPSecure = new Radio('SMTPSecure', array('' => _t('无安全加密'), 'ssl' => _t('SSL加密'), 'tls' => _t('TLS加密')), '', _t('SMTP加密模式'));
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

        // 模板
        $template = new Text('template', NULL, 'default', _t('邮件模板选择'), _t('该项请不要在插件设置里填写，请到邮件模板列表页面选择模板启动！'));
        $template->setAttribute('class', 'hidden');
        $form->addInput($template);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Form $form
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
    public static function getParent($comment): array
    {
        $recipients = [];
        $parent = Helper::widgetById('comments', $comment->parent);
        $recipients = [
                'name' => $parent->author,
                'mail' => $parent->mail,
                ];
        return $recipients;
    }

    /**
     * @param $comment
     * @return array
     * @throws Typecho_Db_Exception
     * 获取文章作者邮箱
     */
    public static function getAuthor($comment): array
    {
        $CommentNotifier = Options::alloc()->plugin('CommentNotifier');
        $recipients = [];
        $db = Db::get();
        $ae = $db->fetchRow($db->select()->from('table.users')->where('table.users.uid=?', $comment->ownerId));
        if (empty($ae['mail'])) {
            $ae['screenName'] = $CommentNotifier->fromName;
            $ae['mail'] = $CommentNotifier->adminfrom;
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
        $recipients = [];
        $CommentNotifier = Options::alloc()->plugin('CommentNotifier');
        $from = $CommentNotifier->adminfrom; // 站长邮箱
        // 在后台标记评论状态为[approved 审核通过]时, 发信给上级评论人或作者
        if ($status == 'approved') {
            $type = 0;
            // 如果有上级
            if ($edit->parent > 0) {
                $recipients[] = self::getParent($edit);//获取上级评论信息
                $type = 1;
            } else {
                $recipients[] = self::getAuthor($edit);//获取作者信息
            }

            // 如果自己回复自己的评论, 不做任何操作
            if ($recipients[0]['mail'] == $edit->mail) {
                return;
            }
            // 如果上级是博主, 不做任何操作
            if ($recipients[0]['mail'] == $from) {
                return;
            }
            //邮箱为空时就不发邮件
            if (empty($recipients[0]['mail'])) {
                return;
            }

            self::sendMail($edit, $recipients, $type);
        }
    }


    /**
     * @param Widget_Comments_Edit|Widget_Feedback $comment
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * 评论/回复时的回调
     */
    public static function refinishComment($comment)
    {
        $CommentNotifier = Options::alloc()->plugin('CommentNotifier');
        $from = $CommentNotifier->adminfrom; // 站长邮箱
        $fromName = $CommentNotifier->fromName; // 发件人
        $recipients = [];
        // 审核通过
        if ($comment->status == 'approved') {
            $type = 0;//0为无父级评论
            // 不需要发信给博主
            if ($comment->authorId != $comment->ownerId && $comment->mail != $from) {
                $recipients[] = self::getAuthor($comment);//收到新评论后发送给文章作者
            }
            // 如果有上级
            if ($comment->parent) {
                $type = 1;//1为有父级评论
                // 查询上级评论人
                $parent = self::getParent($comment);//获取上级评论者邮箱
                // 如果上级是博主和自己回复自己, 不需要发信
                if ($parent['mail'] != $from && $parent['mail'] != $comment->mail) {
                    $recipients[] = $parent;
                }
            }
            self::sendMail($comment, $recipients, $type);
        } else {
            // 如果所有评论必须经过审核, 通知博主审核评论
            $recipients[] = ['name' => $fromName, 'mail' => $from];
            self::sendMail($comment, $recipients, 2);//2为待审核评论
        }
    }

    /**
     * @param Widget_Comments_Edit|Widget_Feedback $comment
     * @param array $recipients
     * @param $type
     */
    private static function sendMail($comment, array $recipients, $type)
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
            $mail = new PHPMailer(false);
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
            if ($type == 1) {
                $mail->Subject = '你在[' . $comment->title . ']的评论有了新的回复';
            } elseif ($type == 2) {
                $mail->Subject = '文章《' . $comment->title . '》有条待审评论';
            } else {
                $mail->Subject = '你的《' . $comment->title . '》文章有了新的评论';
            }

            $mail->isHTML(); // 邮件为HTML格式
            // 邮件内容
            $content = self::mailBody($comment, $options, $type);
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
                    $data = PHP_EOL . $at . ' 发送成功! ';
                    $data .= ' 发件人:' . $fromName;
                    $data .= ' 发件邮箱:' . $from;
                    $data .= ' 接收人:' . $recipientNames;
                    $data .= ' 接收邮箱:' . $recipientMails . PHP_EOL;
                }
                $fileName = dirname(__FILE__) . '/log.txt';
                file_put_contents($fileName, $data, FILE_APPEND);
            }

        } catch (Exception $e) {
            $fileName = dirname(__FILE__) . '/log.txt';
            $str = "\nerror time: " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($fileName, $str, FILE_APPEND);
            file_put_contents($fileName, $e, FILE_APPEND);
        }
    }

    /**
     * @param $comment
     * @param $options
     * @param $type
     * @return string
     * 很朴素的邮件风格
     */
    private static function mailBody($comment, $options, $type): string
    {
        $commentAt = new Date($comment->created);
        $commentAt = $commentAt->format('Y-m-d H:i:s');
        $commentText = htmlspecialchars($comment->text);
        $html = 'owner';
        if ($type == 1) {
            $html = 'guest';
        } elseif ($type == 2) {
            $html = 'notice';
        }
        $Pmail = '';
        $Pname = '';
        $Ptext = '';
        if ($comment->parent) {
            $parent = Helper::widgetById('comments', $comment->parent);
            $Pmail = $parent->mail;
            $Pname = $parent->author;
            $Ptext = $parent->text;
        }

        $content = self::getTemplate($html);
        $template = Options::alloc()->plugin('CommentNotifier')->template;
        $search = array(
            '{title}',//文章标题
            '{time}',//评论发出时间
            '{commentText}',//评论内容
            '{author}',//评论人昵称
            '{mail}',//评论者邮箱
            '{permalink}',//评论楼层链接
            '{siteUrl}',//网站地址
            '{siteTitle}',//网站标题
            '{Pname}',//父级评论昵称
            '{Ptext}',//父级评论内容
            '{Pmail}',//父级评论邮箱
            '{url}',//当前模板文件夹路径
        );
        $replace = array(
            $comment->title,
            $commentAt,
            $commentText,
            $comment->author,
            $comment->mail,
            $comment->permalink,
            $options->siteUrl,
            $options->title,
            $Pname,
            $Ptext,
            $Pmail,
            Options::alloc()->pluginUrl . '/CommentNotifier/template/' . $template,
        );

        return str_replace($search, $replace, $content);
    }

    /**
     * 获取评论模板
     *
     * @param template owner 为博主 guest 为访客
     * @return false|string
     */
    private static function getTemplate($template = 'owner')
    {
        $template .= '.html';
        $templateDir = self::configStr('template', 'default');
        $filePath = dirname(__FILE__) . '/template/' . $templateDir . '/' . $template;

        if (!file_exists($filePath)) {//如果模板文件缺失就调用根目录下的default文件夹中用于垫底的模板
            $filePath = dirname(__FILE__) . 'template/default/' . $template;
        }

        return file_get_contents($filePath);
    }

    public static function resendMail($comment)
    {
        if(Options::alloc()->plugin('CommentNotifier')->yibu==1){
        Helper::requestService('refinishComment', $comment);
        }else{
        self::refinishComment($comment);
        }
    }

    /**
     * 从 Widget_Options 对象获取 Typecho 选项值（文本型）
     * @param string $key 选项 Key
     * @param mixed $default 默认值
     * @param string $method 测空值方法
     * @return string
     */
    public static function configStr(string $key, $default = '', string $method = 'empty'): string
    {
        $value = Helper::options()->plugin('CommentNotifier')->$key;
        if ($method === 'empty') {
            return empty($value) ? $default : $value;
        } else {
            return call_user_func($method, $value) ? $default : $value;
        }

    }
}
