<?php
/**
 * 评论回复邮件提醒插件
 *
 * @package CommentToMail
 * @author Byends Upgrade
 * @version 1.3.2
 * @link http://www.byends.com
 * @oriAuthor DEFE (http://defe.me)
 * 
 * 原作者是  DEFE (http://defe.me),请尊重版权
 * 
 */
class CommentToMail_Plugin implements Typecho_Plugin_Interface
{
    private static $_actionName = 'comment-to-mail';
    private static $_isMailLog  = false;

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        if (false == Typecho_Http_Client::get()) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持 php-curl 扩展而且没有打开 allow_url_fopen 功能, 无法正常使用此功能'));
        }

        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('CommentToMail_Plugin', 'parseComment');
        Helper::addAction(self::$_actionName, 'CommentToMail_Action');

        return _t('请对插件进行正确设置，以使插件顺利工作！');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction(self::$_actionName);
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $mode= new Typecho_Widget_Helper_Form_Element_Radio('mode',
                array( 'smtp' => 'smtp',
                       'mail' => 'mail()',
                       'sendmail' => 'sendmail()'),
                'smtp', '发信方式');
        $form->addInput($mode);

        $host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL, 'smtp.',
                _t('SMTP地址'), _t('请填写 SMTP 服务器地址'));
        $form->addInput($host->addRule('required', _t('必须填写一个SMTP服务器地址')));

        $port = new Typecho_Widget_Helper_Form_Element_Text('port', NULL, '25',
                _t('SMTP端口'), _t('SMTP服务端口,一般为25。'));
        $port->input->setAttribute('class', 'mini');
        $form->addInput($port->addRule('required', _t('必须填写SMTP服务端口'))
                ->addRule('isInteger', _t('端口号必须是纯数字')));

        $user = new Typecho_Widget_Helper_Form_Element_Text('user', NULL, NULL,
                _t('SMTP用户'),_t('SMTP服务验证用户名,一般为邮箱名如：youname@domain.com'));
        $form->addInput($user->addRule('required', _t('SMTP服务验证用户名')));

        $pass = new Typecho_Widget_Helper_Form_Element_Password('pass', NULL, NULL,
                _t('SMTP密码'));
        $form->addInput($pass->addRule('required', _t('SMTP服务验证密码')));

        $validate = new Typecho_Widget_Helper_Form_Element_Checkbox('validate',
                array('validate'=>'服务器需要验证',
                    'ssl'=>'ssl加密'),
                array('validate'),'SMTP验证');
        $form->addInput($validate);

        $mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL,
                _t('接收邮件的地址'),_t('接收邮件的地址,如为空则使用文章作者个人设置中的邮件地址！'));
        $form->addInput($mail->addRule('email', _t('请填写正确的邮件地址！')));

        $contactme = new Typecho_Widget_Helper_Form_Element_Text('contactme', NULL, NULL,
                _t('联系我的邮件地址'),_t('联系我用的邮件地址,如为空则使用文章作者个人设置中的邮件地址！'));
        $form->addInput($contactme->addRule('email', _t('请填写正确的邮件地址！')));

        $status = new Typecho_Widget_Helper_Form_Element_Checkbox('status',
                array('approved' => '提醒已通过评论',
                        'waiting' => '提醒待审核评论',
                        'spam' => '提醒垃圾评论'),
                array('approved', 'waiting'), '提醒设置',_t('该选项仅针对博主，访客只发送已通过的评论。'));
        $form->addInput($status);

        $other = new Typecho_Widget_Helper_Form_Element_Checkbox('other',
                array('to_owner' => '有评论及回复时，发邮件通知博主。',
                    'to_guest' => '评论被回复时，发邮件通知评论者。',
                    'to_me'=>'自己回复自己的评论时，发邮件通知。(同时针对博主和访客)',
                    'to_log' => '记录邮件发送日志。'),
                array('to_owner','to_guest'), '其他设置',_t('如果选上"记录邮件发送日志"选项，则会在./CommentToMail/log/mailer_log.txt 文件中记录发送信息。'));
        $form->addInput($other->multiMode());

        $titleForOwner = new Typecho_Widget_Helper_Form_Element_Text('titleForOwner',null,"[{title}] 一文有新的评论",
                _t('博主接收邮件标题'));
        $form->addInput($titleForOwner->addRule('required', _t('博主接收邮件标题 不能为空')));

        $titleForGuest = new Typecho_Widget_Helper_Form_Element_Text('titleForGuest',null,"您在 [{title}] 的评论有了回复",
                _t('访客接收邮件标题'));
        $form->addInput($titleForGuest->addRule('required', _t('访客接收邮件标题 不能为空')));
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {}

    /**
     * 获取邮件内容
     *
     * @access public
     * @param $comment 调用参数
     * @return void
     */
    public static function parseComment($comment)
    {
        $options           = Typecho_Widget::widget('Widget_Options');
        $cfg = array(
            'site'      => $options->title,
            'timezone'  => $options->timezone,
            'cid'       => $comment->cid,
            'coid'      => $comment->coid,
            'created'   => $comment->created,
            'author'    => $comment->author,
            'authorId'  => $comment->authorId,
            'ownerId'   => $comment->ownerId,
            'mail'      => $comment->mail,
            'ip'        => $comment->ip,
            'title'     => $comment->title,
            'text'      => $comment->text,
            'permalink' => $comment->permalink,
            'status'    => $comment->status,
            'parent'    => $comment->parent,
            'manage'    => $options->siteUrl . "admin/manage-comments.php"
        );

        self::$_isMailLog = in_array('to_log', Helper::options()->plugin('CommentToMail')->other) ? true : false;

        //是否接收邮件
        if (isset($_POST['banmail']) && 'stop' == $_POST['banmail']) {
            $cfg['banMail'] = 1;
        } else {
            $cfg['banMail'] = 0;
        }

        $fileName = Typecho_Common::randString(7);
        $cfg      = (object)$cfg;
        file_put_contents(dirname(__FILE__) . '/cache/' . $fileName, serialize($cfg));
        $url = ($options->rewrite) ? $options->siteUrl : $options->siteUrl . 'index.php';
        $url = rtrim($url, '/') . '/action/' . self::$_actionName . '?send=' . $fileName;


        $client = Typecho_Http_Client::get('Socket', 'Curl');
        if (false == $client) {
            self::saveLog("'主机不支持 php-curl 扩展而且没有打开 allow_url_fopen 功能, 无法正常使用此功能'\n");
            return false;
        }

//        $client->send($url);
        
        self::saveLog("开始发送请求：{$url}\n");
        self::asyncRequest($url);
    }

    /**
     * 发送异步请求
     * @param $url
     * @return bool
     */
    public static function asyncRequest($url)
    {
        $params = parse_url($url);
        $path = $params['path'] . '?' . $params['query'];
        $host = $params['host'];
        $port = 80;
        $http = '';

        if ('https' == $params['scheme']) {
            $port = 443;
            $http = 'ssl://';
        }

        $fp = @fsockopen($http . $host, $port, $errno, $errstr, 30);

        if ($fp === false) {
            self::saveLog("SOCKET错误," . $errno . ':' . $errstr);
            return false;
        }

        $out = "GET " . $path . " HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n\r\n";

        self::saveLog(var_export($params, true)."\n");
        self::saveLog($out."\n");

        fwrite($fp, $out);
        sleep(1);
        fclose($fp);
        self::saveLog("请求结束\n");
    }

    /**
     * 写入记录
     * @param $content
     * @return bool
     */
    public static function saveLog($content)
    {
        if (!self::$_isMailLog) {
            return false;
        }

        file_put_contents(dirname(__FILE__) . '/log/mailer_log.txt', $content, FILE_APPEND);
    }
}
