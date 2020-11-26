<?php
/**
 * Typecho 评论通知、找回密码插件
 *
 * @package LoveKKComment
 * @author  康粑粑
 * @version 1.0.6
 * @link    https://www.usebsd.com
 */

if ( !defined('__TYPECHO_ROOT_DIR__') ) exit;
// 时间区域
date_default_timezone_set('Asia/Shanghai');

class LoveKKComment_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     *
     * @static
     * @access public
     * @throws Typecho_Plugin_Exception
     */
    static public function activate()
    {
        // 检查CURL
        if ( !function_exists('curl_init') ) {
            throw new Typecho_Plugin_Exception(_t('对不起，使用本插件必须要支持CURL'));
        }
        
        // 评论通知添加绑定
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array(__CLASS__, 'doComment');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array(__CLASS__, 'doComment');
        Typecho_Plugin::factory('Widget_Comments_Edit')->mark = array(__CLASS__, 'doApproved');
        // 检测当前版本
        if ( version_compare(str_replace('/', '.', Typecho_Common::VERSION), '1.1.17.10.30') ) {
            // 注册异步回调
            Typecho_Plugin::factory('Widget_Service')->sendMail = array(__CLASS__, 'sendMail');
            Typecho_Plugin::factory('Widget_Service')->asyncApproved = array(__CLASS__, 'asyncApproved');
        }
        
        // 找回密码绑定
        Typecho_Plugin::factory('admin/footer.php')->end = array(__CLASS__, 'forgetLink');
        // 找回密码动作添加
        Helper::addAction('lovekkcomment', 'LoveKKComment_Action');
        // 找回密码路由添加
        Helper::addRoute('lovekkcomment_forget', '/lovekkcomment/forget', 'LoveKKComment_Action', 'forget');
        Helper::addRoute('lovekkcomment_reset', '/lovekkcomment/reset', 'LoveKKCOmment_Action', 'reset');
    }
    
    /**
     * 禁用插件
     *
     * @static
     * @access public
     */
    static public function deactivate()
    {
        // 删除动作
        Helper::removeAction('lovekkcomment');
        // 删除路由
        Helper::removeRoute('lovekkcomment_forget');
        Helper::removeRoute('lovekkcomment_reset');
    }
    
    /**
     * 插件配置
     *
     * @static
     * @access public
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    static public function config(Typecho_Widget_Helper_Form $form)
    {
        // 公共区域
        $public_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        // 区块标题
        $public_section->html('<h2>公共信息配置</h2>');
        $form->addItem($public_section);
        // 找回密码开启
        $public_forget = new Typecho_Widget_Helper_Form_Element_Checkbox('public_forget', array('enable' => _t('启用找回密码')), array('enable'), _t('是否启用找回密码功能'), _t('启用后，将在登录界面出现找回密码链接，并发送邮件至账号邮箱找回密码'));
        $form->addInput($public_forget);
        // 找回密码过期时间
        $public_expire = new Typecho_Widget_Helper_Form_Element_Text('public_expire', NULL, '10', _t('验证过期时间'), _t('当发起找回密码申请后，会有一封链接邮件，此处定义链接过期时间，单位为分钟'));
        $form->addInput($public_expire);
        // Debug模式
        $public_debug = new Typecho_Widget_Helper_Form_Element_Checkbox('public_debug', array('enable' => _t('启用Debug')), array('enable'), _t('是否启用Debug模式'), _t('启用后将在插件目录生成debug.txt文件，可记录邮件发送详细错误'));
        $form->addInput($public_debug);
        // 参数验证
        $public_verify = new Typecho_Widget_Helper_Form_Element_Checkbox('public_verify', array('enable' => _t('启用配置验证')), array('enable'), _t('是否启用参数配置验证'), _t('启用配置验证后，将会在提交配置时进行验证，检测配置是否正确，启用后可能导致配置保存速度缓慢，注意，若使用SSL连接465端口模式，可能导致验证失败，建议使用TLS连接587端口发送邮件'));
        $form->addInput($public_verify);
        // 接口选择
        $public_interface = new Typecho_Widget_Helper_Form_Element_Radio('public_interface', array('smtp' => _t('SMTP'), 'sendcloud' => _t('Send Cloud'), 'aliyun' => _t('阿里云推送')), NULL, _t('发信接口'));
        // 添加验证器并加入表单
        $form->addInput($public_interface->addRule('required', _t('请选择发件接口')));
        // 发件人名称
        $public_name = new Typecho_Widget_Helper_Form_Element_Text('public_name', NULL, NULL, _t('发件人名称'), _t('邮件中显示的发信人名称，留空为博客名称'));
        $form->addInput($public_name);
        // 发件邮箱
        $public_mail = new Typecho_Widget_Helper_Form_Element_Text('public_mail', NULL, NULL, _t('发件邮箱地址'), _t('邮件中显示的发信地址'));
        $form->addInput($public_mail->addRule('required', _t('请输入发件邮箱地址'))->addRule('email', _t('请输入正确的邮箱地址')));
        // 回信地址
        $public_replyto = new Typecho_Widget_Helper_Form_Element_Text('public_replyto', NULL, NULL, _t('邮件回复地址'), _t('附带在邮件中的默认回信地址'));
        $form->addInput($public_replyto->addRule('required', _t('请输入回信邮箱地址'))->addRule('email', _t('请输入正确的邮箱地址')));
        
        // SMTP区块
        $smtp_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $smtp_section->html('<h2>SMTP邮件发送设置</h2>');
        $form->addItem($smtp_section);
        // SMTP地址
        $smtp_host = new Typecho_Widget_Helper_Form_Element_Text('smtp_host', NULL, NULL, _t('SMTP地址'), _t('SMTP服务器连接地址'));
        $form->addInput($smtp_host);
        // SMTP端口
        $smtp_port = new Typecho_Widget_Helper_Form_Element_Text('smtp_port', NULL, NULL, _t('SMTP端口'), _t('SMTP服务器连接端口'));
        $form->addInput($smtp_port);
        // SMTP用户名
        $smtp_user = new Typecho_Widget_Helper_Form_Element_Text('smtp_user', NULL, NULL, _t('SMTP登录用户'), _t('SMTP登录用户名，一般为邮箱地址'));
        $form->addInput($smtp_user);
        // SMTP密码
        $smtp_pass = new Typecho_Widget_Helper_Form_Element_Text('smtp_pass', NULL, NULL, _t('SMTP登录密码'), _t('一般为邮箱密码，但某些服务商需要生成特定密码'));
        $form->addInput($smtp_pass);
        // 是否需要验证
        $smtp_auth = new Typecho_Widget_Helper_Form_Element_Checkbox('smtp_auth', array('enable' => _t('服务器需要验证')), array('enable'), _t('SMTP验证模式'));
        $form->addInput($smtp_auth);
        // 服务器安全模式
        $smtp_secure = new Typecho_Widget_Helper_Form_Element_Radio('smtp_secure', array('none' => _t('无安全加密'), 'ssl' => _t('SSL加密'), 'tls' => _t('TLS加密')), 'none', _t('SMTP加密模式'));
        $form->addInput($smtp_secure);
        
        // SendCloud区块
        $sendcloud_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        // 区块标题
        $sendcloud_section->html('<h2>Send Cloud邮件发送设置</h2>');
        $form->addItem($sendcloud_section);
        // API USER
        $sendcloud_api_user = new Typecho_Widget_Helper_Form_Element_Text('sendcloud_api_user', NULL, NULL, _t('API USER'), _t('请填入在SendCloud生成的API_USER'));
        $form->addInput($sendcloud_api_user);
        // API KEY
        $sendcloud_api_key = new Typecho_Widget_Helper_Form_Element_Text('sendcloud_api_key', NULL, NULL, _t('API KEY'), _t('请填入在SendCloud生成的API_KEY'));
        $form->addInput($sendcloud_api_key);
        
        // 阿里云推送区块
        $ali_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        // 区块标题
        $ali_section->html('<h2>阿里云推送邮件发送设置</h2>');
        $form->addItem($ali_section);
        // 地域选择
        $ali_region = new Typecho_Widget_Helper_Form_Element_Select('ali_region', array('hangzhou' => _t('华东1(杭州)'), 'singapore' => _t('亚太东南1(新加坡)'), 'sydney' => _t('亚太东南2(悉尼)')), NULL, _t('DM接入区域'), _t('请选择您的邮件推送所在服务器区域，请务必选择正确'));
        $form->addInput($ali_region);
        // AccessKey ID
        $ali_accesskey_id = new Typecho_Widget_Helper_Form_Element_Text('ali_accesskey_id', NULL, NULL, _t('AccessKey ID'), _t('请填入在阿里云生成的AccessKey ID'));
        $form->addInput($ali_accesskey_id);
        // Access Key Secret
        $ali_accesskey_secret = new Typecho_Widget_Helper_Form_Element_Text('ali_accesskey_secret', NULL, NULL, _t('Access Key Secret'), _t('请填入在阿里云生成的Access Key Secret'));
        $form->addInput($ali_accesskey_secret);
    }
    
    static public function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
    
    /**
     * 检查配置信息
     *
     * @static
     * @access public
     *
     * @param array $settings 配置数据
     *
     * @return string
     * @throws Typecho_Plugin_Exception
     */
    static public function configCheck(array $settings)
    {
        // 获取系统配置
        $options = Helper::options();
        // 获取插件配置
        $plugin = $options->plugin('LoveKKComment');
        // 是否启用配置验证
        if ( in_array('enable', $plugin->public_verify) ) {
            // 根据不同的接口选择来进行验证
            switch ( $settings['public_interface'] ) {
                case 'sendcloud': // Send Cloud验证
                    if ( !isset($settings['sendcloud_api_user']) || empty($settings['sendcloud_api_user']) || !isset($settings['sendcloud_api_key']) || empty($settings['sendcloud_api_key']) ) {
                        return _t('Send Cloud API USER与API KEY必须填写');
                    }
                    $url = 'http://api.sendcloud.net/apiv2/apiuser/list?apiUser=' . $settings['sendcloud_api_user'] . '&apiKey=' . $settings['sendcloud_api_key'];
                    // 使用curl连接send cloud并使用API_USER验证
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $result = curl_exec($ch);
                    curl_close($ch);
                    $result = json_decode($result);
                    if ( 200 != $result->statusCode ) {
                        return _t($result->message);
                    }
                    break;
                case 'aliyun':
                    if ( !isset($settings['ali_region']) || empty($settings['ali_region']) || !isset($settings['ali_accesskey_id']) || empty($settings['ali_accesskey_id']) || !isset($settings['ali_accesskey_secret']) || empty($settings['ali_accesskey_secret']) ) {
                        return _t('阿里云接入区域、AccessKey ID、Access Key Secret必须填写');
                    }
                    break;
                default: // SMTP验证
                    // SMTP地址
                    if ( !isset($settings['smtp_host']) || empty($settings['smtp_host']) ) {
                        return _t('SMTP地址必须填写');
                    }
                    // SMTP端口
                    if ( !isset($settings['smtp_port']) || empty($settings['smtp_port']) ) {
                        return _t('SMTP端口必须填写');
                    }
                    // 载入SMTP
                    if ( !class_exists('PHPMailer\PHPMailer\SMTP') ) {
                        require dirname(__FILE__) . '/lib/SMTP.php';
                    }
                    // 初始化SMTP类
                    $smtp = new \PHPMailer\PHPMailer\SMTP;
                    // hostname
                    $hostname = 'localhost.localdomain';
                    // 使用$_SERVER
                    if ( isset($_SERVER) and array_key_exists('SERVER_NAME', $_SERVER) and !empty($_SERVER['SERVER_NAME']) ) {
                        $hostname = $_SERVER['SERVER_NAME'];
                    } else if ( function_exists('gethostname') && gethostname() !== FALSE ) { // 使用gethostname
                        $hostname = gethostname();
                    } else if ( php_uname('n') !== FALSE ) { // 使用php_uname
                        $hostname = php_uname('n');
                    }
                    // 连接
                    if ( !$smtp->connect($settings['smtp_host'], $settings['smtp_port'], 5) ) {
                        return _t('SMTP连接失败，请检查SMTP地址及端口');
                    }
                    // 发送hello
                    if ( !$smtp->hello($hostname) ) {
                        return _t('SMTP发送EHLO指令失败，错误信息：' . $smtp->getError()['error'] . '，若您使用的SMTP端口是465，加密方式为SSL，可能导致此错误，建议更换为TLS加密587端口后重试');
                    }
                    // 获取服务器信息
                    $e = $smtp->getServerExtList();
                    // 是否需要StartTls
                    if ( is_array($e) && array_key_exists('STARTTLS', $e) ) {
                        // 验证模式是否为tls
                        if ( 'tls' != $settings['smtp_secure'] ) {
                            return _t('SMTP服务器要求tls加密');
                        }
                        // starttls加密
                        $tls = $smtp->startTLS();
                        // 失败
                        if ( !$tls ) {
                            return _t('tls加密失败，错误信息：' . $smtp->getError()['error']);
                        }
                        // 发送hello
                        if ( !$smtp->hello($hostname) ) {
                            return _t('SMTP发送EHLO指令失败，错误信息：' . $smtp->getError()['error'] . '，若您使用的SMTP端口是465，加密方式为SSL，可能导致此错误，建议更换为TLS加密587端口后重试');
                        }
                        // 获取服务器信息
                        $e = $smtp->getServerExtList();
                    }
                    
                    // 登录验证
                    if ( is_array($e) && array_key_exists('AUTH', $e) || in_array('enable', $settings['smtp_auth']) ) {
                        // 用户、密码
                        if ( !isset($settings['smtp_user']) || !isset($settings['smtp_pass']) || empty($settings['smtp_user']) || empty($settings['smtp_pass']) ) {
                            return _t('SMTP登录账号及密码不能为空');
                        }
                        // 没有启用验证
                        if ( !in_array('enable', $settings['smtp_auth']) ) {
                            return _t('SMTP服务器要求身份验证');
                        }
                        // 验证账号
                        if ( !$smtp->authenticate($settings['smtp_user'], $settings['smtp_pass']) ) {
                            return _t('SMTP登录失败，错误信息：' . $smtp->getError()['error']);
                        }
                    }
                    // 退出登录
                    $smtp->quit(TRUE);
            }
        }
    }
    
    /**
     * 评论通知
     *
     * @static
     * @access public
     *
     * @param mixed $comment 评论对象
     *
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static public function doComment($comment)
    {
        // 检测当前版本是否大于1.1/17.10.30
        if ( version_compare(str_replace('/', '.', Typecho_Common::VERSION), '1.1.17.10.30') ) {
            // 调用异步回调模式
            Helper::requestService('sendMail', $comment->coid);
        } else {
            self::sendMail($comment->coid);
        }
    }
    
    /**
     * 评论审核
     *
     * @static
     * @access public
     *
     * @param mixed  $comment 评论对象
     * @param mixed  $edit    编辑对象
     * @param string $status  评论状态
     *
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static public function doApproved($comment, $edit, $status)
    {
        // 仅审核通过才发送邮件
        if ( 'approved' == $status ) {
            // 检测当前版本是否大于1.1/17.10.30
            if ( version_compare(str_replace('/', '.', Typecho_Common::VERSION), '1.1.17.10.30') ) {
                // 调用异步回调模式
                Helper::requestService('asyncApproved', $comment);
            } else {
                self::sendMail($comment->coid ? $comment->coid : $comment['coid'], TRUE);
            }
        }
    }
    
    /**
     * 异步审核回调
     *
     * @static
     * @access public
     *
     * @param mixed $comment 评论对象
     *
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static public function asyncApproved($comment)
    {
        // 调用异步邮件发送
        self::sendMail($comment->coid, TRUE);
    }
    
    /**
     * 邮件发送操作
     *
     * @static
     * @access public
     *
     * @param int  $commentId  评论编号
     * @param bool $isApproved 是否为审核操作
     *
     * @return bool|string
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static public function sendMail($commentId, $isApproved = FALSE)
    {
        // 重新获取评论数据
        $comment = self::getWidget('Comments', 'coid', $commentId);
        // 收件人地址
        $address = $comment->mail;
        // 上级评论对象
        $parentComment = NULL;
        // 不是帖子发表者
        if ( $comment->authorId != $comment->ownerId ) {
            // 获取作者信息
            $author = self::getWidget('Users', 'uid', $comment->ownerId);
            // 收件地址
            $address = $author->mail;
            // 上级评论
            $parentComment = NULL;
        }
        
        // 评论回复
        if ( 0 < $comment->parent ) {
            // 获取上级对象
            $parentComment = self::getWidget('Comments', 'coid', $comment->parent);
            // 是否获取到且用户ID不同或邮件地址不同
            if ( isset($parentComment->coid) && $comment->mail != $parentComment->mail ) {
                // 收件地址
                $address = $parentComment->mail;
            }
        }
        
        // 获取系统配置
        $options = Helper::options();
        // 获取插件配置
        $plugin = $options->plugin('LoveKKComment');
        // 请求参数
        $data = array(
            'fromName' => ( !isset($plugin->public_name) || empty($plugin->public_name) ) ? trim($options->title) : $plugin->public_name, // 发件人名称
            'from' => $plugin->public_mail, // 发件地址
            'to' => $address, // 收件地址
            'replyTo' => $plugin->public_replyto // 回信地址
        );
        
        // 如果是通过审核
        if ( $isApproved ) {
            // 邮件标题
            $data['subject'] = _t('您在 [' . trim($options->title) . ']  发表的评论已通过审核！');
            // 读取模板
            $html = file_get_contents(dirname(__FILE__) . '/theme/approved.html');
            // 替换内容
            $data['html'] = str_replace(
                array(
                    '{blogUrl}', // 博客地址
                    '{blogName}', // 博客名称
                    '{author}', // 作者名称
                    '{permalink}', // 文章链接
                    '{title}', // 文章标题
                    '{text}' // 评论内容
                ),
                array(
                    trim($options->siteUrl),
                    trim($options->title),
                    trim($comment->author),
                    trim($comment->permalink),
                    trim($comment->title),
                    trim($comment->text)
                ),
                $html
            );
        } else {
            // 有上级评论
            if ( !is_null($parentComment) ) {
                // 标题
                $data['subject'] = _t('您在 [' . trim($options->title) . '] 的评论有了新的回复！');
                // 读取模板
                $html = file_get_contents(dirname(__FILE__) . '/theme/reply.html');
                // 获取文章
                $post = self::getWidget('Contents', 'cid', $parentComment->cid);
                // 替换模板
                $data['html'] = str_replace(
                    array(
                        '{blogUrl}', // 博客地址
                        '{blogName}', // 博客名称
                        '{author}', // 作者名称
                        '{permalink}', // 文章链接
                        '{title}', // 文章标题
                        '{text}', // 评论内容
                        '{replyAuthor}', // 回复者名称
                        '{replyText}', // 回复内容
                        '{commentUrl}' // 评论地址
                    ), array(
                        trim($options->siteUrl),
                        trim($options->title),
                        trim($parentComment->author),
                        trim($post->permalink),
                        trim($post->title),
                        trim($parentComment->text),
                        trim($comment->author),
                        trim($comment->text),
                        trim($comment->permalink)
                    ), $html
                );
            } else {
                // 标题
                $data['subject'] = _t('您在 [' . trim($options->title) . ']  发表的文章有新评论！');
                // 读取模板
                $html = file_get_contents(dirname(__FILE__) . '/theme/author.html');
                // 替换模板内容
                $data['html'] = str_replace(
                    array(
                        '{blogUrl}', // 博客地址
                        '{blogName}', // 博客名称
                        '{author}', // 作者名称
                        '{permalink}', // 文章地址
                        '{title}', // 文章标题
                        '{text}' // 评论内容
                    ), array(
                        trim($options->siteUrl),
                        trim($options->title),
                        trim($comment->author),
                        trim($comment->permalink),
                        trim($comment->title),
                        trim($comment->text)
                    ), $html
                );
            }
        }
        // 根据接口进行操作
        switch ( $plugin->public_interface ) {
            case 'sendcloud': // Send Cloud
                // API User
                $data['apiUser'] = $plugin->sendcloud_api_user;
                // API Key
                $data['apiKey'] = $plugin->sendcloud_api_key;
                return self::sendCloud($data);
            case 'aliyun': // 阿里云推送
                // 判断当前请求区域
                switch ( $plugin->ali_region ) {
                    case 'hangzhou': // 杭州
                        // API地址
                        $data['api'] = 'https://dm.aliyuncs.com/';
                        // API版本号
                        $data['version'] = '2015-11-23';
                        // 机房信息
                        $data['region'] = 'cn-hangzhou';
                        break;
                    case 'singapore': // 新加坡
                        // API地址
                        $data['api'] = 'https://dm.ap-southeast-1.aliyuncs.com/';
                        // API版本号
                        $data['version'] = '2017-06-22';
                        // 机房信息
                        $data['region'] = 'ap-southeast-1';
                        break;
                    case 'sydney': // 悉尼
                        // API地址
                        $data['api'] = 'https://dm.ap-southeast-2.aliyuncs.com/';
                        // API版本号
                        $data['version'] = '2017-06-22';
                        // 机房信息
                        $data['region'] = 'ap-southeast-2';
                        break;
                }
                // AccessKeyId
                $data['accessid'] = $plugin->ali_accesskey_id;
                // AccessKeySecret
                $data['accesssecret'] = $plugin->ali_accesskey_secret;
                return self::aliyun($data);
            default: // SMTP
                // SMTP地址
                $data['smtp_host'] = $plugin->smtp_host;
                // SMTP端口
                $data['smtp_port'] = $plugin->smtp_port;
                // SMTP用户
                $data['smtp_user'] = $plugin->smtp_user;
                // SMTP密码
                $data['smtp_pass'] = $plugin->smtp_pass;
                // 验证模式
                $data['smtp_auth'] = $plugin->smtp_auth;
                // 加密模式
                $data['smtp_secure'] = $plugin->smtp_secure;
                return self::smtp($data);
        }
    }
    
    /**
     * Send Cloud 邮件发送
     *
     * @static
     * @access public
     *
     * @param array $data 公共参数
     *
     * @return bool|string
     * @throws Typecho_Plugin_Exception
     */
    static public function sendCloud($data)
    {
        // 初始化Curl
        $ch = curl_init();
        // 请求地址
        curl_setopt($ch, CURLOPT_URL, 'http://api.sendcloud.net/apiv2/mail/send');
        // 基础验证
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // 返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // POST请求
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        // 请求参数
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // 执行请求
        $result = curl_exec($ch);
        // 获取错误代码
        $errno = curl_errno($ch);
        // 获取错误信息
        $error = curl_error($ch);
        // 关闭请求
        curl_close($ch);
        // 成功标识
        $flag = TRUE;
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKComment');
        // 如果开启了Debug
        if ( in_array('enable', $plugin->public_debug) ) {
            // 记录时间
            $log = '[Send Cloud] ' . date('Y-m-d H:i:s') . ': ' . PHP_EOL;
            // 如果失败
            if ( $errno ) {
                // 设置为失败
                $flag = FALSE;
                $log .= _t('邮件发送失败, 错误代码：' . $errno . '，错误提示: ' . $error . PHP_EOL);
            }
            // 转换为json
            if ( $json = json_decode($result) ) {
                // 失败
                if ( 200 != $json->statusCode ) {
                    // 设置为失败
                    $flag = FALSE;
                    $log .= _t('邮件发送失败，错误提示：' . $json->message . PHP_EOL);
                }
            }
            // 记录返回值
            $log .= _t('邮件发送返回数据：' . serialize($result) . PHP_EOL);
            // 输出分隔
            $log .= '-------------------------------------------' . PHP_EOL . PHP_EOL . PHP_EOL;
            // 写入文件
            file_put_contents(__DIR__ . '/debug.txt', $log, FILE_APPEND);
        }
        // 返回结果
        return $flag;
    }
    
    /**
     * 阿里云邮件发送
     *
     * @static
     * @access public
     *
     * @param array $param 公共参数
     *
     * @return bool|string
     * @throws Typecho_Plugin_Exception
     */
    static public function aliyun($param)
    {
        // 重新组合为阿里云所使用的参数
        $data = array(
            'Action' => 'SingleSendMail', // 操作接口名
            'AccountName' => $param['from'], // 发件地址
            'ReplyToAddress' => "true", // 回信地址
            'AddressType' => 1, // 地址类型
            'ToAddress' => $param['to'], // 收件地址
            'FromAlias' => $param['fromName'], // 发件人名称
            'Subject' => $param['subject'], // 邮件标题
            'HtmlBody' => $param['html'], // 邮件内容
            'Format' => 'JSON', // 返回JSON
            'Version' => $param['version'], // API版本号
            'AccessKeyId' => $param['accessid'], // Access Key ID
            'SignatureMethod' => 'HMAC-SHA1', // 签名方式
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'), // 请求时间
            'SignatureVersion' => '1.0', // 签名算法版本
            'SignatureNonce' => md5(time()), // 唯一随机数
            'RegionId' => $param['region'] // 机房信息
        );
        // 请求签名
        $data['Signature'] = self::sign($data, $param['accesssecret']);
        // 初始化Curl
        $ch = curl_init();
        // 设置为POST请求
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        // 请求地址
        curl_setopt($ch, CURLOPT_URL, $param['api']);
        // 返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // 提交参数
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::getPostHttpBody($data));
        // 关闭ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 执行请求
        $result = curl_exec($ch);
        // 获取错误代码
        $errno = curl_errno($ch);
        // 获取错误信息
        $error = curl_error($ch);
        // 获取返回状态码
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // 关闭请求
        curl_close($ch);
        // 成功标识
        $flag = TRUE;
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKComment');
        // 如果开启了Debug
        if ( in_array('enable', $plugin->public_debug) ) {
            // 记录时间
            $log = '[Aliyun] ' . date('Y-m-d H:i:s') . ': ' . PHP_EOL;
            // 如果失败
            if ( $errno ) {
                // 设置失败
                $flag = FALSE;
                $log .= _t('邮件发送失败, 错误代码：' . $errno . '，错误提示: ' . $error . PHP_EOL);
            }
            // 如果失败
            if ( 400 <= $httpCode ) {
                // 设置失败
                $flag = FALSE;
                // 尝试转换json
                if ( $json = json_decode($result) ) {
                    $log .= _t('邮件发送失败，错误代码：' . $json->Code . '，错误提示：' . $json->Message . PHP_EOL);
                } else {
                    $log .= _t('邮件发送失败, 请求返回HTTP Code：' . $httpCode . PHP_EOL);
                }
            }
            // 记录返回值
            $log .= _t('邮件发送返回数据：' . serialize($result) . PHP_EOL);
            // 输出分隔
            $log .= '-------------------------------------------' . PHP_EOL . PHP_EOL . PHP_EOL;
            // 写入文件
            file_put_contents(__DIR__ . '/debug.txt', $log, FILE_APPEND);
        }
        // 返回结果
        return $flag;
    }
    
    /**
     * SMTP邮件发送
     *
     * @static
     * @access public
     *
     * @param array $param 公共参数
     *
     * @return bool
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static public function smtp($param)
    {
        // 载入PHPMailer
        if ( !class_exists('PHPMailer\PHPMailer\PHPMailer') ) {
            require dirname(__FILE__) . '/lib/PHPMailer.php';
        }
        // 载入SMTP
        if ( !class_exists('PHPMailer\PHPMailer\SMTP') ) {
            require dirname(__FILE__) . '/lib/SMTP.php';
        }
        // 载入Exception
        if ( !class_exists('PHPMaile\PHPMailer\Exception') ) {
            require dirname(__FILE__) . '/lib/Exception.php';
        }
        
        // 初始化PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(FALSE);
        // 设置编码
        $mail->CharSet = 'UTF-8';
        // 启用SMTP
        $mail->isSMTP();
        // SMTP地址
        $mail->Host = $param['smtp_host'];
        // SMTP端口
        $mail->Port = $param['smtp_port'] ?: 25;
        // SMTP用户名
        $mail->Username = $param['smtp_user'];
        // SMTP密码
        $mail->Password = $param['smtp_pass'];
        // 是否需要验证
        if ( in_array('enable', $param['smtp_auth']) ) {
            // 开启验证
            $mail->SMTPAuth = TRUE;
        }
        // 加密模式
        if ( 'none' != $param['smtp_secure'] ) {
            // 设置加密模式
            $mail->SMTPSecure = $param['smtp_secure'];
        }
        // 发件人信息
        $mail->setFrom($param['from'], $param['fromName']);
        // 回信信息
        $mail->addReplyTo($param['replyTo'], $param['fromName']);
        // 收件地址
        $mail->addAddress($param['to']);
        // HTML格式
        $mail->isHTML(TRUE);
        // Debug级别
        $mail->SMTPDebug = 4;
        // 邮件标题
        $mail->Subject = $param['subject'];
        // 邮件内容
        $mail->msgHTML($param['html']);
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKComment');
        // 发送邮件
        $result = $mail->send();
        // 如果开启了Debug
        if ( in_array('enable', $plugin->public_debug) ) {
            // 记录时间
            $log = '[SMTP] ' . date('Y-m-d H:i:s') . ': ' . PHP_EOL;
            $log .= 'data: ' . serialize($param) . PHP_EOL . PHP_EOL;
            // 记录返回值及PHPMailer错误
            $log .= _t('邮件发送返回数据：' . serialize($result) . '; 错误信息: ' . $mail->ErrorInfo . PHP_EOL);
            // 输出分隔
            $log .= '-------------------------------------------' . PHP_EOL . PHP_EOL . PHP_EOL;
            // 写入文件
            file_put_contents(__DIR__ . '/debug.txt', $log, FILE_APPEND);
        }
        // 结果
        return $result;
    }
    
    /**
     * 添加找回密码链接
     */
    static public function forgetLink()
    {
        // 获取系统配置选项
        $options = Helper::options();
        // 获取插件配置
        $plugin = $options->plugin('LoveKKComment');
        // 如果开启了密码找回
        if ( in_array('enable', $plugin->public_forget) ) {
            // 初始化request对象
            $request = Typecho_Request::getInstance();
            // 获取当前请求
            $pathinfo = $request->getRequestUrl();
            // 如果是登录页面则添加忘记密码链接
            if ( preg_match('/\/login\.php/i', $pathinfo) ) {
                ?>
                <script>
                    var forget = document.createElement('a');
                    forget.href = '<?php echo Typecho_Common::url('/action/lovekkcomment?forget', Helper::options()->index);?>';
                    var text = document.createTextNode('<?php _e('忘记密码');?>');
                    forget.appendChild(text);
                    document.getElementsByClassName('more-link')[0].appendChild(forget);
                </script>
                <?php
            }
        }
    }
    
    /**
     * 获取Widget对象
     *
     * @static
     * @access private
     *
     * @param string $table 数据表名
     * @param string $key   查询关键字
     * @param mixed  $val   数据数据
     *
     * @return mixed
     * @throws Typecho_Db_Exception
     */
    static private function getWidget($table, $key, $val)
    {
        // 类名称
        $className = 'Widget_Abstract_' . $table;
        // 初始化数据库
        $db = Typecho_Db::get();
        // 初始化类
        $widget = new $className(Typecho_Request::getInstance(), Typecho_Widget_Helper_Empty::getInstance());
        // 查询数据
        $db->fetchRow($widget->select()->where($key . ' = ?', $val)->limit(1), array($widget, 'push'));
        
        return $widget;
    }
    
    /**
     * 阿里云签名
     *
     * @static
     * @access private
     *
     * @param array  $param        签名参数
     * @param string $accesssecret 秘钥
     *
     * @return string
     */
    static private function sign($param, $accesssecret)
    {
        // 参数排序
        ksort($param);
        // 组合基础
        $stringToSign = 'POST&' . self::percentEncode('/') . '&';
        // 临时变量
        $tmp = '';
        // 循环参数列表
        foreach ( $param as $k => $v ) {
            // 组合参数
            $tmp .= '&' . self::percentEncode($k) . '=' . self::percentEncode($v);
        }
        // 去除最后一个&
        $tmp = trim($tmp, '&');
        // 组合签名参数
        $stringToSign = $stringToSign . self::percentEncode($tmp);
        // 数据签名
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $accesssecret . '&', TRUE));
        // 返回签名
        return $signature;
    }
    
    /**
     * 阿里云签名编码转换
     *
     * @static
     * @access private
     *
     * @param string $val 要转换的编码
     *
     * @return string|string[]|null
     */
    static private function percentEncode($val)
    {
        // URL编码
        $res = urlencode($val);
        // 加号转换为%20
        $res = preg_replace('/\+/', '%20', $res);
        // 星号转换为%2A
        $res = preg_replace('/\*/', '%2A', $res);
        // %7E转换为~
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }
    
    /**
     * 阿里云请求参数组合
     *
     * @static
     * @access private
     *
     * @param array $param 发送参数
     *
     * @return bool|string
     */
    static private function getPostHttpBody($param)
    {
        // 空字符串
        $str = "";
        // 循环参数
        foreach ( $param as $k => $v ) {
            // 组合参数
            $str .= $k . '=' . urlencode($v) . '&';
        }
        // 去除第一个&
        return substr($str, 0, -1);
    }
}
