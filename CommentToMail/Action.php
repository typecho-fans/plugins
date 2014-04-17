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
    public  $email;

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
            $this->email = unserialize(file_get_contents($file));
            @unlink($file);
            if (!$this->widget('Widget_User')->simpleLogin($this->email->ownerId)) {
                $this->widget('Widget_Archive@404', 'type=404')->render();
                exit;
            }
        } else {
            $this->widget('Widget_Archive@404', 'type=404')->render();
            exit;
        }
        
        //如果本次评论设置了拒收邮件，把coid加入拒收列表
        if ($this->email->banMail) {
            $this->proveParent($this->email->coid, 1);
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
                $this->email->from      = $this->_cfg->user;
                break;
        }

        //向博主发邮件的标题格式
        $this->email->titleForOwner = $this->_cfg->titleForOwner;
        
        //向访客发邮件的标题格式
        $this->email->titleForGuest = $this->_cfg->titleForGuest;

        //验证博主是否接收自己的邮件
        $toMe = (in_array('to_me', $this->_cfg->other) && $this->email->ownerId == $this->email->authorId) ? true : false;

        //向博主发信
        if (in_array($this->email->status, $this->_cfg->status) && in_array('to_owner', $this->_cfg->other)
            && ( $toMe || $this->email->ownerId != $this->email->authorId) && 0 == $this->email->parent ) {
            if (empty($this->_cfg->mail)) {
                Typecho_Widget::widget('Widget_Users_Author@temp' . $this->email->cid, array('uid' => $this->email->ownerId))->to($user);
            	$this->email->to = $user->mail;
            } else {
                $this->email->to = $this->_cfg->mail;
            }

            $this->sendMail('owner');
        }

        //向访客发信
        if (0 != $this->email->parent 
            && 'approved' == $this->email->status 
            && in_array('to_guest', $this->_cfg->other)
            && $this->proveParent($this->email->parent)) {
            
            //如果联系我的邮件地址为空，则使用文章作者的邮件地址
            if (empty($this->email->contactme)) {
                if (!isset($user) || !$user) {
                    Typecho_Widget::widget('Widget_Users_Author@temp' . $this->email->cid, array('uid' => $this->email->ownerId))->to($user);
                }
                $this->email->contactme = $user->mail;
            } else {
                $this->email->contactme = $this->_cfg->contactme;
            }

            $original = $this->_db->fetchRow($this->_db->select('author', 'mail', 'text')
                                                       ->from('table.comments')
                                                       ->where('coid = ?', $this->email->parent));

            if (in_array('to_me', $this->_cfg->other) 
                || $this->email->mail != $original['mail']) {
                $this->email->to             = $original['mail'];
                $this->email->originalText   = $original['text'];
                $this->email->originalAuthor = $original['author'];
                $this->sendMail('guest');
            }
        }

        $this->mailLog(false, "邮件发送完毕！\n");
    }
    
    /*
     * 生成邮件内容并发送
     * $sendTo 为  owner 发向博主;  其它发向访客
     */
    public function sendMail($author = 'owner')
    {
    	$date = new Typecho_Date($this->email->created);
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
                $this->email->site,
                $this->email->title,
                $this->email->author,
                $this->email->ip,
                $this->email->mail,
                $this->email->permalink,
                $this->email->manage,
                $this->email->text,
                $time,
                $status[$this->email->status]
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
                $this->email->site,
                $this->email->title,
                $this->email->originalAuthor,
                $this->email->author,
                $this->email->mail,
                $this->email->permalink,
                $this->email->text,
                $this->email->contactme,
                $this->email->originalText,
                $time
            );
        }

        $this->email->body = str_replace($search, $replace, $body);
        $this->email->subject = str_replace($search, $replace, $subject);
        $this->email->AltBody = "作者：".$this->email->author."\r\n链接：".$this->email->permalink."\r\n评论：\r\n".$this->email->text;

        $this->mailer->SetFrom($this->email->from, $this->email->site);
        $this->mailer->AddReplyTo($this->email->to, $this->email->site);
        $this->mailer->Subject = $this->email->subject;
        $this->mailer->AltBody = $this->email->AltBody;
        $this->mailer->MsgHTML($this->email->body);

        $name = $this->email->originalAuthor ? $this->email->originalAuthor : $this->email->site;

        $this->mailer->AddAddress($this->email->to,$name);

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
                            $this->email->created + $this->email->timezone) . " 向 " . $this->email->to . " 发送邮件成功！\r\n";
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

        //是否记录邮件错误日志
        $this->_isMailLog = in_array('to_log', $this->_cfg->other) ? true : false;
        $this->mailLog(false, "开始发送邮件Action：" . $this->request->send . "\n");

        $this->on($this->request->is('send'))->process($this->request->send);
    }
}
