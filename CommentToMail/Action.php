<?php
/**
 * CommentToMail Plugin
 * 异步发送提醒邮件到博主或访客的邮箱
 * 
 * @copyright  Copyright (c) 2014 Byends (http://www.byends.com)
 * @license    GNU General Public License 2.0
 */
class CommentToMail_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /** @var  数据操作对象 */
    private $_db;
    
    /** @var  插件根目录 */
    private $_dir;
    
    /** @var  插件配置信息 */
    private $_cfg;
    
    /** @var  系统配置信息 */
    private $_options;
    
    /** @var bool 是否记录日志 */
    private $_isMailLog = false;
    
    /** @var 当前登录用户 */
    private $_user;
    
    /** @var  邮件内容信息 */
    private  $_email;

    /**
     * 读取缓存文件内容
     */
    public function process($fileName)
    {
        ignore_user_abort(TRUE);
        $this->init();
        //获取评论内容
        $file = $this->_dir . '/cache/' . $fileName;
        if (file_exists($file)) {
            $this->_email = unserialize(file_get_contents($file));
            @unlink($file);

            if (!$this->_user->simpleLogin($this->_email->ownerId)) {
                $this->widget('Widget_Archive@404', 'type=404')->render();
                exit;
            }
        } else {
            $this->widget('Widget_Archive@404', 'type=404')->render();
            exit;
        }
        
        //如果本次评论设置了拒收邮件，把coid加入拒收列表
        if ($this->_email->banMail) {
            $this->ban($this->_email->coid, true);
        }

        //发件人邮箱
        $this->_email->from = $this->_cfg->user;

        //发件人名称
        $this->_email->fromName = $this->_cfg->fromName ? $this->_cfg->fromName : $this->_email->siteTitle;

        //向博主发邮件的标题格式
        $this->_email->titleForOwner = $this->_cfg->titleForOwner;

        //向访客发邮件的标题格式
        $this->_email->titleForGuest = $this->_cfg->titleForGuest;
        
        //验证博主是否接收自己的邮件
        $toMe = (in_array('to_me', $this->_cfg->other) && $this->_email->ownerId == $this->_email->authorId) ? true : false;

        //向博主发信
        if (in_array($this->_email->status, $this->_cfg->status) && in_array('to_owner', $this->_cfg->other)
            && ( $toMe || $this->_email->ownerId != $this->_email->authorId) && 0 == $this->_email->parent ) {
            if (empty($this->_cfg->mail)) {
                Typecho_Widget::widget('Widget_Users_Author@temp' . $this->_email->cid, array('uid' => $this->_email->ownerId))->to($user);
            	$this->_email->to = $user->mail;
            } else {
                $this->_email->to = $this->_cfg->mail;
            }

            $this->authorMail()->sendMail();
        }

        //向访客发信
        if (0 != $this->_email->parent 
            && 'approved' == $this->_email->status 
            && in_array('to_guest', $this->_cfg->other)
            && !$this->ban($this->_email->parent)) {
            //如果联系我的邮件地址为空，则使用文章作者的邮件地址
            if (empty($this->_email->contactme)) {
                if (!isset($user) || !$user) {
                    Typecho_Widget::widget('Widget_Users_Author@temp' . $this->_email->cid, array('uid' => $this->_email->ownerId))->to($user);
                }
                $this->_email->contactme = $user->mail;
            } else {
                $this->_email->contactme = $this->_cfg->contactme;
            }

            $original = $this->_db->fetchRow($this->_db->select('author', 'mail', 'text')
                                                       ->from('table.comments')
                                                       ->where('coid = ?', $this->_email->parent));

            if (in_array('to_me', $this->_cfg->other) 
                || $this->_email->mail != $original['mail']) {
                $this->_email->to             = $original['mail'];
                $this->_email->originalText   = $original['text'];
                $this->_email->originalAuthor = $original['author'];
                $this->guestMail()->sendMail();
            }
        }

        $date = new Typecho_Date(Typecho_Date::gmtTime());
        $time = $date->format('Y-m-d H:i:s');
        $this->mailLog(false, $time . " 邮件发送完毕!\r\n");
    }

    /**
     * 作者邮件信息
     * @return $this
     */
    public function authorMail()
    {
        $this->_email->toName = $this->_email->siteTitle;
        $date = new Typecho_Date($this->_email->created);
        $time = $date->format('Y-m-d H:i:s');
        $status = array(
            "approved" => '通过',
            "waiting"  => '待审',
            "spam"     => '垃圾'
        );
        $search  = array(
            '{siteTitle}',
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
        $replace = array(
            $this->_email->siteTitle,
            $this->_email->title,
            $this->_email->author,
            $this->_email->ip,
            $this->_email->mail,
            $this->_email->permalink,
            $this->_email->manage,
            $this->_email->text,
            $time,
            $status[$this->_email->status]
        );

        $this->_email->msgHtml = str_replace($search, $replace, $this->getTemplate('owner'));
        $this->_email->subject = str_replace($search, $replace, $this->_email->titleForOwner);
        $this->_email->altBody = "作者：".$this->_email->author."\r\n链接：".$this->_email->permalink."\r\n评论：\r\n".$this->_email->text;

        return $this;
    }

    /**
     * 访问邮件信息
     * @return $this
     */
    public function guestMail()
    {
        $this->_email->toName = $this->_email->originalAuthor ? $this->_email->originalAuthor : $this->_email->siteTitle;
        $date    = new Typecho_Date($this->_email->created);
        $time    = $date->format('Y-m-d H:i:s');
        $search  = array(
            '{siteTitle}',
            '{title}',
            '{author_p}',
            '{author}',
            '{permalink}',
            '{text}',
            '{contactme}',
            '{text_p}',
            '{time}'
        );
        $replace = array(
            $this->_email->siteTitle,
            $this->_email->title,
            $this->_email->originalAuthor,
            $this->_email->author,
            $this->_email->permalink,
            $this->_email->text,
            $this->_email->contactme,
            $this->_email->originalText,
            $time
        );

        $this->_email->msgHtml = str_replace($search, $replace, $this->getTemplate('guest'));
        $this->_email->subject = str_replace($search, $replace, $this->_email->titleForGuest);
        $this->_email->altBody = "作者：".$this->_email->author."\r\n链接：".$this->_email->permalink."\r\n评论：\r\n".$this->_email->text;

        return $this;
    }

    /*
     * 发送邮件
     */
    public function sendMail()
    {
        /** 载入邮件组件 */
        require_once $this->_dir . '/lib/class.phpmailer.php';
        $mailer = new PHPMailer();
        $mailer->CharSet = 'UTF-8';
        $mailer->Encoding = 'base64';

        //选择发信模式
        switch ($this->_cfg->mode)
        {
            case 'mail':
                break;
            case 'sendmail':
                $mailer->IsSendmail();
                break;
            case 'smtp':
                $mailer->IsSMTP();

                if (in_array('validate', $this->_cfg->validate)) {
                    $mailer->SMTPAuth = true;
                }

                if (in_array('ssl', $this->_cfg->validate)) {
                    $mailer->SMTPSecure = "ssl";
                }

                $mailer->Host     = $this->_cfg->host;
                $mailer->Port     = $this->_cfg->port;
                $mailer->Username = $this->_cfg->user;
                $mailer->Password = $this->_cfg->pass;

                break;
        }

        $mailer->SetFrom($this->_email->from, $this->_email->fromName);
        $mailer->AddReplyTo($this->_email->to, $this->_email->toName);
        $mailer->Subject = $this->_email->subject;
        $mailer->AltBody = $this->_email->altBody;
        $mailer->MsgHTML($this->_email->msgHtml);
        $mailer->AddAddress($this->_email->to, $this->_email->toName);

        if ($result = $mailer->Send()) {
            $this->mailLog();
        } else {
            $this->mailLog(false, $mailer->ErrorInfo . "\r\n");
            $result = $mailer->ErrorInfo;
        }
        
        $mailer->ClearAddresses();
        $mailer->ClearReplyTos();

        return $result;
    }

    /*
     * 记录邮件发送日志和错误信息
     */
    public function mailLog($type = true, $content = null)
    {
        if (!$this->_isMailLog) {
            return false;
        }

        $fileName = $this->_dir . '/log/mailer_log.txt';
        if ($type) {
            $guest = explode('@', $this->_email->to);
            $guest = substr($this->_email->to, 0, 1) . '***' . $guest[1];
            $content  = $content ? $content : "向 " . $guest . " 发送邮件成功！\r\n";
        }

        file_put_contents($fileName, $content, FILE_APPEND);
    }

    /*
     * 获取邮件正文模板
     * $author owner为博主 guest为访客
     */
    public function getTemplate($template = 'owner')
    {
        $template .= '.html';
        $filename = $this->_dir . '/' . $template;

        if (!file_exists($filename)) {
           throw new Typecho_Widget_Exception('模板文件' . $template . '不存在', 404);
        }

        return file_get_contents($this->_dir . '/' . $template);
    }

    /*
     * 验证原评论者是否接收评论
     */
    public function ban($parent, $isWrite = false)
    {
        if ($parent) {
            $index    = ceil($parent / 500);
            $filename = $this->_dir . '/log/ban_' . $index . '.list';

            if (!file_exists($filename)) {
                $list = array();
                file_put_contents($filename, serialize($list));
            } else {
                $list = unserialize(file_get_contents($filename));
            }

            //写入记录
            if ($isWrite) {
                $list[$parent] = 1;
                file_put_contents($filename, serialize($list));

                return true;
            } else if (isset($list[$parent]) && $list[$parent]) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 邮件发送测试
     */
    public function testMail()
    {
        if (Typecho_Widget::widget('CommentToMail_Console')->testMailForm()->validate()) {
            $this->response->goBack();
        }

        $this->init();
        $this->_isMailLog = true;
        $email = $this->request->from('toName', 'to', 'title', 'content');
        
        $this->_email->from = $this->_cfg->user;
        $this->_email->fromName = $this->_cfg->fromName ? $this->_cfg->fromName : $this->_options->title;
        $this->_email->to = $email['to'] ? $email['to'] : $this->_user->mail;
        $this->_email->toName = $email['toName'] ? $email['toName'] : $this->_user->screenName;
        $this->_email->subject = $email['title'];
        $this->_email->altBody = $email['content'];
        $this->_email->msgHtml = $email['content'];

        $result = $this->sendMail();

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(true === $result ? _t('邮件发送成功') : _t('邮件发送失败：' . $result),
            true === $result ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->goBack();
    }

    /**
     * 编辑模板文件
     * @param $file
     * @throws Typecho_Widget_Exception
     */
    public function editTheme($file)
    {
        $this->init();
        $path = $this->_dir . '/' . $file;

        if (file_exists($path) && is_writeable($path)) {
            $handle = fopen($path, 'wb');
            if ($handle && fwrite($handle, $this->request->content)) {
                fclose($handle);
                $this->widget('Widget_Notice')->set(_t("文件 %s 的更改已经保存", $file), 'success');
            } else {
                $this->widget('Widget_Notice')->set(_t("文件 %s 无法被写入", $file), 'error');
            }
            $this->response->goBack();
        } else {
            throw new Typecho_Widget_Exception(_t('您编辑的模板文件不存在'));
        }
    }

    /**
     * 初始化
     * @return $this
     */
    public function init()
    {
        $this->_dir = dirname(__FILE__);
        $this->_db = Typecho_Db::get();
        $this->_user = $this->widget('Widget_User');
        $this->_options = $this->widget('Widget_Options');
        $this->_cfg = Helper::options()->plugin('CommentToMail');
        $this->mailLog(false, "开始发送邮件Action：" . $this->request->send . "\r\n");
    }

    /**
     * action 入口
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->on($this->request->is('do=testMail'))->testMail();
        $this->on($this->request->is('do=editTheme'))->editTheme($this->request->edit);
        $this->on($this->request->is('send'))->process($this->request->send);
    }
}