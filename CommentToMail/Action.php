<?php
/**
 * CommentToMail Plugin
 * 异步发送提醒邮件到博主或访客的邮箱
 * 
 * @copyright  Copyright (c) 2012 DEFE (http://defe.me)
 * @license    GNU General Public License 2.0
 *
 */
class CommentToMail_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $_db;
    private $_dir;
    private $_cfg;
    private $_isMailLog = false;
    public  $mailer;
    public  $smtp;

    /**
     * 读取缓存文件内容，并根据条件组合邮件内容发送。
     */
    public function process($fileName)
    {
        /** 载入邮件组件 */
        require_once $this->_dir . '/lib/class.phpmailer.php';

        $this->mailer = new PHPMailer();
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Encoding = 'base64';

        //获取评论内容
        $file = $this->_dir . '/cache/' . $fileName;
        if (file_exists($file)) {
            $this->smtp = unserialize(file_get_contents($file));
            if (!$this->widget('Widget_User')->simpleLogin($this->smtp->ownerId)) {
                @unlink($file);
                $this->widget('Widget_Archive@404', 'type=404')->render();
                exit;
            }
        } else {
            $this->widget('Widget_Archive@404', 'type=404')->render();
            exit;
        }
        
        //如果本次评论设置了拒收邮件，把coid加入拒收列表
        if ($this->smtp->banMail) {
            $this->proveParent($this->smtp->coid, 1);
        }

        //选择发信模式
        switch ($this->_cfg->mode)
        {
            case 'mail':
                break;
            case 'sendmail':
                $this->mailer->IsSendmail();
                break;
            case 'smtp':
                $this->mailer->IsSMTP();
                if (in_array('validate', $this->_cfg->validate)) {
                    $this->mailer->SMTPAuth = true;
                }
                if (in_array('ssl', $this->_cfg->validate)) {
                    $this->mailer->SMTPSecure = "ssl";
                }
                $this->mailer->Host     = $this->_cfg->host;
                $this->mailer->Port     = $this->_cfg->port;
                $this->mailer->Username = $this->_cfg->user;
                $this->mailer->Password = $this->_cfg->pass;
                $this->smtp->from     = $this->_cfg->user;
                break;
        }

        //是否记录邮件错误日志
        $this->_isMailLog = in_array('to_log', $this->_cfg->other) ? true : false;

        //向博主发邮件的标题格式
        $this->smtp->titleForOwner = $this->_cfg->titleForOwner;
        
        //向访客发邮件的标题格式
        $this->smtp->titleForGuest = $this->_cfg->titleForGuest;

        //验证博主是否接收自己的邮件
        $toMe = (in_array('to_me', $this->_cfg->other) && $this->smtp->ownerId == $this->smtp->authorId) ? true : false;

        //向博主发信
        if (in_array($this->smtp->status, $this->_cfg->status) && in_array('to_owner', $this->_cfg->other)
            && ( $toMe || $this->smtp->ownerId != $this->smtp->authorId) && 0 == $this->smtp->parent ) {
            if (empty($this->_cfg->mail)) {
            	Typecho_Widget::widget('Widget_Users_Author@' . $this->smtp->cid, array('uid' => $this->smtp->ownerId))->to($user);
            	$this->smtp->to = $user->mail;
            } else {
                $this->smtp->to = $this->_cfg->mail;
            }

            $this->sendMail('owner');
        }

        //向访客发信
        if (0 != $this->smtp->parent && 'approved' == $this->smtp->status && in_array('to_guest', $this->_cfg->other)
            && $this->proveParent($this->smtp->parent)
        ) {
            //如果联系我的邮件地址为空，则使用文章作者的邮件地址
            if (empty($this->smtp->contactme)) {
                Typecho_Widget::widget('Widget_Users_Author@' . $this->smtp->cid, array('uid' => $this->smtp->ownerId))->to($user);
                $this->smtp->contactme = $user->mail;
            } else {
                $this->smtp->contactme = $this->_cfg->contactme;
            }

            $original = $this->_db->fetchRow($this->_db->select('author', 'mail', 'text')
                                                       ->from('table.comments')
                                                       ->where('coid = ?', $this->smtp->parent));

            $toGuest = (!in_array('to_me',$this->_cfg->other) && $this->smtp->mail == $original['mail'] || $this->smtp->to == $original['mail']) ? false : true;

            if ($toGuest) {
                $this->smtp->to             = $original['mail'];
                $this->smtp->originalText   = $original['text'];
                $this->smtp->originalAuthor = $original['author'];
                $this->sendMail('guest');
            }
        }

        $this->mailLog(false, "邮件发送完毕！\n");

        @unlink($file);

    }
    
    /*
     * 生成邮件内容并发送
     * $sendTo 为  0 发向博主;  1 发向访客
     */
    public function sendMail($author = 'owner')
    {
    	$date = new Typecho_Date($this->smtp->created);
        $time = date('Y-m-d H:i:s', $date->timeStamp);

        if ('owner' == $author) {
            $status = array(
                "approved" => '通过',
                "waiting"  => '待审',
                "spam"     => '垃圾'
            );
            $subject = $this->_cfg->titleForOwner;
            $body =  $this->getTemplate();
            $search = array(
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
            $replace = array(
                $this->smtp->site,
                $this->smtp->title,
                $this->smtp->author,
                $this->smtp->ip,
                $this->smtp->mail,
                $this->smtp->permalink,
                $this->smtp->manage,
                $this->smtp->text,
                $time,
                $status[$this->smtp->status]
            );
        } else {
            $subject = $this->_cfg->titleForGuest;
            $body    = $this->getTemplate('guest');
            $search  = array(
                '{site}',
                '{title}',
                '{author_p}',
                '{author}',
                '{mail}',
                '{permalink}',
                '{text}',
                '{contactme}',
                '{text_p}',
                '{time}'
            );
            $replace = array(
                $this->smtp->site,
                $this->smtp->title,
                $this->smtp->originalAuthor,
                $this->smtp->author,
                $this->smtp->mail,
                $this->smtp->permalink,
                $this->smtp->text,
                $this->smtp->contactme,
                $this->smtp->originalText,
                $time
            );
        }

        $this->smtp->body = str_replace($search, $replace, $body);
        $this->smtp->subject = str_replace($search, $replace, $subject);
        $this->smtp->AltBody = "作者：".$this->smtp->author."\r\n链接：".$this->smtp->permalink."\r\n评论：\r\n".$this->smtp->text;

        $this->mailer->SetFrom($this->smtp->from, $this->smtp->site);
        $this->mailer->AddReplyTo($this->smtp->to, $this->smtp->site);
        $this->mailer->Subject = $this->smtp->subject;
        $this->mailer->AltBody = $this->smtp->AltBody;
        $this->mailer->MsgHTML($this->smtp->body);

        $name = $this->smtp->originalAuthor ? $this->smtp->originalAuthor : $this->smtp->site;

        $this->mailer->AddAddress($this->smtp->to,$name);

        if ($this->mailer->Send()) {
            $this->mailLog();
        } else {
            $this->mailLog(false);
        }
        $this->mailer->ClearAddresses();
        $this->mailer->ClearReplyTos();
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
            $content  = $content ? $content : date("Y-m-d H:i:s",
                            $this->smtp->created + $this->smtp->timezone) . " 向 " . $this->smtp->to . " 发送邮件成功！\r\n";
        } else {
            $content  = $content ? $content : $this->mailer->ErrorInfo;
        }

        file_put_contents($fileName, $content, FILE_APPEND);
    }

    /*
     * 获取邮件正文模板
     * $author owner为博主 guest为访客
     */
    public function getTemplate($author = 'owner')
    {
        $template = 'owner' == $author ? 'owner' : 'guest';

        return file_get_contents($this->_dir . '/' . $template . '.html');
    }

    /*
     * 验证原评论者是否接收评论
     */
    public function proveParent($parent, $write = false)
    {
        if ($parent) {
            $index    = ceil($parent / 500);
            $filename = $this->_dir . '/log/ban_' . $index . '.list';

            if (!file_exists($filename)) {
                file_put_contents($filename, "a:0:{}");
            }

            $list = unserialize(file_get_contents($filename));

            //写入记录
            if ($write) {
                $list[$parent] = 1;
                file_put_contents($filename, serialize($list));

                return true;
            }

            //判读记录是否存在，存在则返回false，不存在返回true表示接收邮件
            if (!$write && 1 == $list[$parent]) {
                return false;
            } else {
                return true;
            }

        } else {
            return false;
        }
    }

    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->_db  = Typecho_Db::get();
        $this->_dir = dirname(__FILE__);
        $this->_cfg = Helper::options()->plugin('CommentToMail');
        $this->mailLog(false, "开始发送邮件Action：" . $this->request->send . "\n");

        $this->on($this->request->is('send'))->process($this->request->send);
    }
}