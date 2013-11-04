<?php
/**
 * 评论回复邮件提醒插件BAE版，基于<a href="http://defe.me" target="_blank">@defe</a>版本
 * 
 * @package CommentToMail 
 * @author ShingChi
 * @version 1.0.0
 * @link http://lcz.me
 */
class CommentToMail_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('CommentToMail_Plugin', 'composeMail');
        return _t('请对插件进行正确设置，以使插件顺利工作！') . $error;
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** Access Key */
        $accessKey = new Typecho_Widget_Helper_Form_Element_Text('aKey',
            NULL, '',
            _t('Access Key'),
            _t('<a href="http://developer.baidu.com/bae/ref/key/" target="_blank">获取Access Key</a> 运行在BAE环境下时可不填'));
        $form->addInput($accessKey);

        /** Secret Key */
        $secretKey = new Typecho_Widget_Helper_Form_Element_Text('sKey',
            NULL, '',
            _t('Secret Key'),
            _t('<a href="http://developer.baidu.com/bae/ref/key/" target="_blank">获取Secure Key</a> 运行在BAE环境下时可不填'));
        $form->addInput($secretKey);

        /** 要发送邮件的队列 */
        $queueName = new Typecho_Widget_Helper_Form_Element_Text('queue',
            NULL, '',
            _t('消息队列名称'),
            _t('必填，<a href="http://developer.baidu.com/bae/bms/list/" target="_blank">获取消息队列名称</a>'));
        $form->addInput($queueName);

        /** 作者接收邮箱 */
        $ownMail = new Typecho_Widget_Helper_Form_Element_Text('ownMail',
            NULL, '',
            _t('接收邮箱'),
            _t('接收邮件用的信箱，如为空则使用博客创建者个人设置中的邮箱！'));
        $form->addInput($ownMail);

        /** 提醒设置 */
        $status = new Typecho_Widget_Helper_Form_Element_Checkbox('status',
            array(
                'approved' => '提醒已通过评论',
                'waiting' => '提醒待审核评论',
                'spam' => '提醒垃圾评论'
            ),
            array('approved', 'waiting'),
            _t('提醒设置'));
        $form->addInput($status);

        /** 其他设置 */
        $goal = new Typecho_Widget_Helper_Form_Element_Checkbox('goal',
            array(
                'to_me' => '有评论及回复时，发邮件通知博主。',
                'to_other' => '评论被回复时，发邮件通知评论者。'
            ),
            array('to_me', 'to_other'),
            _t('其他设置'),
            _t('暂时没有日志功能。'));
        $form->addInput($goal->multiMode());

        /** 个性标题 */
        $ownSub = new Typecho_Widget_Helper_Form_Element_Text('ownSub',
            null,
            "[{site}]:《{title}》一文有新的评论",
            _t('邮件标题'));
        $form->addInput($ownSub);

        $guestSub = new Typecho_Widget_Helper_Form_Element_Text('guestSub',
            null,
            "[{site}]:您在《{title}》一文的评论有了回复",
            _t('回复评论者邮件标题'));
        $form->addInput($guestSub);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 组合邮件内容
     * 
     * @access public
     * @param object $comment 评论数据
     * @return void
     */
    public static function composeMail($comment)
    {
        /** 设置时区 */
        date_default_timezone_set('Asia/Shanghai');

        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $siteName = $options->title;
        $settings = Helper::options()->plugin('CommentToMail');
        $mail = array();

        /** 获取接收邮箱 $mail['from'] */
        if ($settings->ownMail != '') {
            $mail['from'] = $settings->ownMail;
        } else {
            $select = Typecho_Widget::widget('Widget_Abstract_Users')->select()->where('uid', 1);
            $result = $db->query($select);
            $row = $db->fetchRow($result);
            $mail['from'] = $row['mail'];
        }

        /** 评论被回复时，发信通知被回复者 */
        if (0 != $comment->parent 
        && in_array($comment->status, $settings->status) 
        && in_array('to_other', $settings->goal)) {
            $select = $db->select('author', 'mail', 'text')
                         ->from('table.comments')
                         ->where('coid = ?', $comment->parent);
            $result = $db->query($select);
            $row = $db->fetchRow($result);
            $mail['to'] = array($row['mail']); //发送地址

            if ($row['mail'] != $comment->mail) {
                /** 取得标题格式 */
                if ($settings->guestSub) {
                    $mail['subject'] = $settings->guestSub; //邮件标题
                } else {
                    $mail['subject'] = '[' . $siteName . ']:您在《' . $comment->title . '》的评论有了回复';
                }

                /**取得邮件主体格式*/
                $guestFormat = file_get_contents('./usr/plugins/CommentToMail/guest.html');

                $guestSearch = array(
                    '{site}',
                    '{title}',
                    '{author_p}',
                    '{author}',
                    '{ip}',
                    '{mail}',
                    '{permalink}',
                    '{text}',
                    '{text_p}'
                );
                $guestReplace = array(
                    $siteName, 
                    $comment->title,
                    $row['author'],
                    $comment->author,
                    $comment->ip,
                    $comment->mail,
                    $comment->permalink,
                    $comment->text,
                    $row['text']
                );
                $mail['text'] = '<!--HTML-->' . str_replace($guestSearch, $guestReplace, $guestFormat);
                $mail['subject'] = str_replace($guestSearch, $guestReplace, $mail['subject']);
                $optional = array(
                    'from'    => $mail['from'], //发信人地址
                    'subject' => $mail['subject'], //邮件标题
                );
                self::sendMail($settings->queue, $mail['text'], $mail['to'], $optional);
            }
        }

        /** 发信到博主信箱 */
        if (in_array($comment->status, $settings->status)
        && $comment->ownerId != $comment->authorId
        && in_array('to_me', $settings->goal)) {
            /** 格式化评论发布时间 */
            $date = getdate($comment->created);
            $time = $date['year'] . '年' . $date['mon'] . '月' . $date['mday'] . '日' 
                   . $date['hours'] . ':' . $date['minutes'] . ':' . $date['seconds'];

            /** 评论状态 */
            switch ($comment->status) {
                case 'approved':
                    $status = '<font color="#008040">已通过</font>';
                    break;
                case 'waiting':
                    $status = '<font color="#FF8000">待审核</font>';
                    break;
                case 'spam':
                    $status = '<font color="#FF0000">垃圾</font>';
                    break;
            }

            /** 管理评论链接 */
            $manage = $options->siteUrl . 'admin/manage-comments.php';

            /** 获取邮件正文格式 */
            $ownFormat = file_get_contents('./usr/plugins/CommentToMail/owner.html');

            /** 处理邮件正文 */
            $ownSearch = array(
                '{site}',
                '{title}',
                '{author}',
                '{ip}',
                '{mail}',
                '{permalink}',
                '{manage}',
                '{text}',
                '{time}',
                '{status}'
            );
            $ownReplace = array(
                $siteName,
                $comment->title,
                $comment->author,
                $comment->ip,
                $comment->mail,
                $comment->permalink,
                $manage,
                $comment->text,
                $time,
                $status
            );
            $mail['text'] = '<!--HTML-->' . str_replace($ownSearch, $ownReplace, $ownFormat);

            /**邮件标题*/
            if ($settings->ownSub) {
                $mail['subject'] = $settings->ownSub;
            } else {
                $mail['subject'] = '[{site}]:《{title}》一文有新的评论';
            }

            $mail['subject'] = str_replace($ownSearch, $ownReplace, $mail['subject']);
            $mail['to'] = array($mail['from']); //接收邮箱
            $optional = array(
                'from'    => $mail['from'], //发信人地址
                'subject' => $mail['subject'], //邮件标题
            );
        }
        self::sendMail($settings->queue, $mail['text'], $mail['to'], $optional);
    }
    
    public static function sendMail($queueName, $message, $address, $optional = array()) {
        /** 载入BCMS SDK */
        require_once('Bcms.class.php');

        $settings = Helper::options()->plugin('CommentToMail');
        $accessKey = $settings->aKey;
        $secretKey = $settings->sKey;
        $host = 'bcms.api.duapp.com';

        if ($accessKey != '' && $secretKey != '') {
            $bcms = new Bcms($accessKey, $secretKey, $host);
        } else {
            $bcms = new Bcms();
        }

        $opt = array(
            Bcms::FROM => $optional['from'],
            Bcms::MAIL_SUBJECT => $optional['subject']
        );
        $ret = $bcms->mail($queueName, $message, $address, $opt);

        if (false === $ret) {
            error_output('ERROR NUMBER: ' . $bcms->errno());
            error_output('ERROR MESSAGE: ' . $bcms->errmsg());
        }
    }
}
