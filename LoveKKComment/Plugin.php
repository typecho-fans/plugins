<?php
/**
 * Typecho 评论SMTP、SendCloud、阿里云邮件通知插件
 *
 * @package LoveKKComment
 * @author  康粑粑
 * @version 1.0.4
 * @link    https://www.lovekk.org
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
// 时间区域
date_default_timezone_set('Asia/Shanghai');
// 当前版本号
define('VERSION', '1.0.4');

class LoveKKComment_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件激活方法
     *
     * @static
     * @access public
     * @throws Typecho_Plugin_Exception
     */
    static public function activate()
    {
        // 检查CURL
        if (!function_exists('curl_init')) {
            // 报错
            throw new Typecho_Plugin_Exception(_t('对不起，使用此插件必须支持CURL'));
        }
        // 添加绑定
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array(__CLASS__, 'sendMail');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array(__CLASS__, 'sendMail');
        Typecho_Plugin::factory('Widget_Comments_Edit')->mark = array(__CLASS__, 'approvedMail');
    }

    /**
     * 插件禁用方法
     *
     * @static
     * @access public
     */
    static public function deactivate()
    {
    }

    /**
     * 插件配置方法
     *
     * @static
     * @access public
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    static public function config(Typecho_Widget_Helper_Form $form)
    {
        ?>
        <style>.message {
                padding: 10px;
                background-color: #fff;
                box-shadow: 2px 2px 5px #888;
                font-size: 1pc;
                line-height: 1.875rem
            }

            .message span {
                display: block;
                color: #1abc9c
            }

            .message span pre {
                margin: 0;
                padding: 0;
                color: #ee5c42
            }

            .message li, .message p {
                margin: 0;
                padding: 0;
                line-height: 1.5rem
            }</style>
        <div class="message">
            <div id="update_txt">当前版本: <?php _e(VERSION); ?>, 正在检测版本更新...</div>
            <span id="update_notice"></span>
            <span id="update_body"></span>
        </div>
        <script src="//cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
        <script src="//cdn.bootcss.com/marked/0.3.12/marked.min.js"></script>
        <script>$(function () {
                $.getJSON('https://git.wskehao.com/api/v1/repos/ylqjgm/LoveKKComment/releases', function (data) {
                    if (checkUpdater('<?php _e(VERSION);?>', data[0].tag_name)) {
                        $('#update_notice').html('有新版本可用, <a href="' + data[0].zipball_url + '" target="_blank">点此下载 ' + data[0].tag_name + ' 版本</a>');
                        $('#update_body').html('版本说明: ' + marked(data[0].body))
                    } else {
                        $('#update_txt').html('当前版本: <?php _e(VERSION);?>, 当前没有新版本')
                    }
                })
            });

            function checkUpdater(currVer, remoteVer) {
                currVer = currVer || '0.0.0';
                remoteVer = remoteVer || '0.0.0';
                if (currVer == remoteVer) return false;
                var currVerAry = currVer.split('.');
                var remoteVerAry = remoteVer.split('.');
                var len = Math.max(currVerAry.length, remoteVerAry.length);
                for (var i = 0; i < len; i++) {
                    if (~~remoteVerAry[i] > ~~currVerAry[i]) return true
                }
                return false
            }</script>
        <?php
        // 公共区块
        $public_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        // 区块标题
        $public_section->html('<h2>公共信息配置</h2>');
        $form->addItem($public_section);
        // Debug
        $public_debug = new Typecho_Widget_Helper_Form_Element_Checkbox('public_debug', array('enable' => _t('启用Debug')), array('enable'), _t('是否启用Debug模式'), _t('启用后将在插件目录下生成一个debug.txt文件，若出现错误请将此文件发送作者解决'));
        $form->addInput($public_debug);
        // 接口选择
        $public_interface = new Typecho_Widget_Helper_Form_Element_Radio('public_interface', array('smtp' => _t('SMTP'), 'sendcloud' => _t('Send Cloud'), 'aliyun' => _t('阿里云推送')), NULL, _t('发信接口'));
        // 添加验证器并加入表单
        $form->addInput($public_interface->addRule('required', _t('请选择发件接口')));
        // 发件人名称
        $public_name = new Typecho_Widget_Helper_Form_Element_Text('public_name', NULL, NULL, _t('发件人名称'), _t('邮件中显示的发信人名称，留空为博客名称'));
        $form->addInput($public_name);
        // 发件邮箱
        $public_mail = new Typecho_Widget_Helper_Form_Element_Text('public_mail', NULL, NULL, _t('发件邮箱地址'), _t('邮件中显示的发信地址'));
        // 增加验证器并加入表单
        $form->addInput($public_mail->addRule('required', _t('请输入发件邮箱地址'))->addRule('email', _t('请输入正确的邮箱地址')));
        // 回信地址
        $public_replyto = new Typecho_Widget_Helper_Form_Element_Text('public_replyto', NULL, NULL, _t('邮件回复地址'), _t('附带在邮件中的默认回信地址'));
        // 增加验证器并加入表单
        $form->addInput($public_replyto->addRule('required', _t('请输入回信邮箱地址'))->addRule('email', _t('请输入正确的邮箱地址')));
        // SMTP区块
        $smtp_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        // 区块标题
        $smtp_section->html('<h2>SMTP邮件发送设置</h2>');
        $form->addItem($smtp_section);
        // SMTP地址
        $smtp_host = new Typecho_Widget_Helper_Form_Element_Text('smtp_host', NULL, NULL, _t('SMTP地址'), _t('SMTP服务器地址'));
        $form->addInput($smtp_host);
        // SMTP端口
        $smtp_port = new Typecho_Widget_Helper_Form_Element_Text('smtp_port', NULL, NULL, _t('SMTP端口'), _t('SMTP服务器连接端口，一般为25'));
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

    /**
     * 个人配置
     *
     * @static
     * @access public
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    static public function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 发送回复邮件初始方法
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
    static public function sendMail($comment)
    {
        // 不是帖子发表者
        if ($comment->authorId != $comment->ownerId) {
            // 获取作者信息
            $author = self::getWidget('Users', 'uid', $comment->ownerId);
            // 发送邮件
            self::send($author->mail, $comment, NULL);
        }
        // 如果是评论回复
        if (0 < $comment->parent) {
            // 获取上级评论对象
            $parentComment = self::getWidget('Comments', 'coid', $comment->parent);
            // 检测数据是否获取到且回复用户不是本用户
            if (isset($parentComment->coid) && $comment->authorId != $parentComment->authorId) {
                // 发送邮件
                self::send($parentComment->mail, $comment, $parentComment);
            }
        }
    }

    /**
     * 评论审核邮件通知
     *
     * @static
     * @access public
     *
     * @param mixed $comment 评论对象
     * @param mixed $edit    编辑对象
     * @param mixed $status  评论状态
     *
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static public function approvedMail($comment, $edit, $status)
    {
        // 只有在标记为展现的时候才发送邮件
        if ('approved' === $status) {
            // 发送邮件
            self::send($edit->mail, $edit, NULL, TRUE);
        }
    }

    /**
     * 邮件发送选择操作
     *
     * @static
     * @access private
     *
     * @param      string $mail          收件地址
     * @param      mixed  $comment       评论对象
     * @param      mixed  $parentComment 上级评论对象
     * @param bool        $isApproved
     *
     * @return bool|mixed
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static private function send($mail, $comment, $parentComment, $isApproved = FALSE)
    {
        // 获取系统配置选项
        $options = Helper::options();
        // 获取插件配置
        $plugin = $options->plugin('LoveKKComment');
        // 请求参数
        $data = array(
            'fromName' => (!isset($plugin->public_name) || is_null($plugin->public_name) || empty($plugin->public_name)) ? trim($options->title) : $plugin->public_name, // 发件人名称
            'from' => $plugin->public_mail, // 发件地址
            'to' => $mail, // 收件地址
            'replyTo' => $plugin->public_replyto, // 回信地址
        );
        // 是否为通过审核
        if ($isApproved) {
            // 设置邮件标题
            $data['subject'] = '您在 [' . trim($options->title) . ']  发表的文章有新评论！';
            // 读取审核通过模板
            $html = file_get_contents(dirname(__FILE__) . '/theme/approved.html');
            // 替换模板内容
            $data['html'] = str_replace(array(
                '{blogUrl}', // 博客地址
                '{blogName}', // 博客名称
                '{author}', // 作者名称
                '{permalink}', // 文章链接
                '{title}', // 文章标题
                '{text}' // 评论内容
            ), array(
                trim($options->siteUrl),
                trim($options->title),
                trim($comment->author),
                trim($comment->permalink),
                trim($comment->title),
                trim($comment->text)
            ), $html);
        } else {
            // 如果传入了parentComment
            if (!is_null($parentComment)) {
                // 设置邮件标题
                $data['subject'] = '您在 [' . $options->title . '] 的评论有了新的回复！';
                // 读取回复通知模板
                $html = file_get_contents(dirname(__FILE__) . '/theme/reply.html');
                // 获取文章对象
                $post = self::getWidget('Contents', 'cid', $parentComment->cid);
                // 替换模板内容
                $data['html'] = str_replace(array(
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
                ), $html);
            } else {
                // 设置邮件标题
                $data['subject'] = '您在 [' . $options->title . ']  发表的文章有新评论！';
                // 读取作者评论通知模板
                $html = file_get_contents(dirname(__FILE__) . '/theme/author.html');
                // 替换模板内容
                $data['html'] = str_replace(array(
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
                ), $html);
            }
        }
        // 根据当前选择接口进行不同操作
        switch ($plugin->public_interface) {
            case 'sendcloud': // Send Cloud
                // API User
                $data['apiUser'] = $plugin->sendcloud_api_user;
                // API Key
                $data['apiKey'] = $plugin->sendcloud_api_key;
                // 发送邮件
                return self::sendCloud($data);
            case 'aliyun': // 阿里云推送
                // 判断当前请求区域
                switch ($plugin->ali_region) {
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
     * 获取Widget对象
     *
     * @static
     * @access private
     *
     * @param string $name Widget名称
     * @param string $key  查询关键字
     * @param mixed  $val  查询值
     *
     * @return mixed
     * @throws Typecho_Db_Exception
     */
    static private function getWidget($name, $key, $val)
    {
        // Widget类名
        $className = 'Widget_Abstract_' . $name;
        // 初始化Widget
        $widget = new $className(new Typecho_Request(), new Typecho_Response(), NULL);
        // 初始化Typecho_Db
        $db = Typecho_Db::get();
        // 定义查询
        $select = $widget->select()->where($key . ' = ?', $val)->limit(1);
        // 查询并过滤
        $db->fetchRow($select, array($widget, 'push'));
        // 返回Widget
        return $widget;
    }

    /**
     * 发送SendCloud邮件
     *
     * @static
     * @access private
     *
     * @param array $param 请求参数
     *
     * @return mixed
     * @throws Typecho_Plugin_Exception
     */
    static private function sendCloud($param)
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        // 执行请求
        $result = curl_exec($ch);
        // 关闭请求
        curl_close($ch);
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKComment');
        // 如果开启了Debug
        if (in_array('enable', $plugin->public_debug)) {
            // 记录时间
            $log = '[Send Cloud] ' . date('Y-m-d H:i:s') . ': ' . PHP_EOL;
            // 记录返回值
            $log .= serialize($result) . PHP_EOL;
            // 输出分隔
            $log .= '-------------------------------------------' . PHP_EOL . PHP_EOL . PHP_EOL;
            // 写入文件
            file_put_contents(__DIR__ . '/debug.txt', $log, FILE_APPEND);
        }
        // 返回结果
        return $result;
    }

    /**
     * 发送阿里云推送邮件
     *
     * @static
     * @access private
     *
     * @param $param
     *
     * @return mixed
     * @throws Typecho_Plugin_Exception
     */
    static private function aliyun($param)
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
        // 关闭请求
        curl_close($ch);
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKComment');
        // 如果开启了Debug
        if (in_array('enable', $plugin->public_debug)) {
            // 记录时间
            $log = '[Aliyun] ' . date('Y-m-d H:i:s') . ': ' . PHP_EOL;
            // 记录返回值
            $log .= serialize($result) . PHP_EOL;
            // 输出分隔
            $log .= '-------------------------------------------' . PHP_EOL . PHP_EOL . PHP_EOL;
            // 写入文件
            file_put_contents(__DIR__ . '/debug.txt', $log, FILE_APPEND);
        }
        // 返回结果
        return $result;
    }

    /**
     * 发送SMTP邮件
     *
     * @static
     * @access private
     *
     * @param $param
     *
     * @return bool
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    static private function smtp($param)
    {
        // 载入PHPMailer
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            require dirname(__FILE__) . '/lib/PHPMailer.php';
        }
        // 载入SMTP
        if (!class_exists('PHPMailer\PHPMailer\SMTP')) {
            require dirname(__FILE__) . '/lib/SMTP.php';
        }
        // 载入Exception
        if (!class_exists('PHPMaile\PHPMailer\Exception')) {
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
        if (in_array('enable', $param['smtp_auth'])) {
            // 开启验证
            $mail->SMTPAuth = TRUE;
        }
        // 加密模式
        if ('none' != $param['smtp_secure']) {
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
        if (in_array('enable', $plugin->public_debug)) {
            // 记录时间
            $log = '[SMTP] ' . date('Y-m-d H:i:s') . ': ' . PHP_EOL;
            // 记录返回值及PHPMailer错误
            $log .= serialize($result) . '; PHPMailer error: ' . $mail->ErrorInfo . PHP_EOL;
            // 输出分隔
            $log .= '-------------------------------------------' . PHP_EOL . PHP_EOL . PHP_EOL;
            // 写入文件
            file_put_contents(__DIR__ . '/debug.txt', $log, FILE_APPEND);
        }
        // 结果
        return $result;
    }

    /**
     * 阿里云推送签名
     *
     * @static
     * @access private
     *
     * @param array  $param        请求参数
     * @param string $accesssecret Access Key Secret
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
        foreach ($param as $k => $v) {
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
     * 符合阿里云签名的编码转换
     *
     * @static
     * @access private
     *
     * @param $val
     *
     * @return null|string|string[]
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
     * 组合阿里云推送请求参数
     *
     * @static
     * @access private
     *
     * @param array $param 请求参数
     *
     * @return bool|string
     */
    static private function getPostHttpBody($param)
    {
        // 空字符串
        $str = "";
        // 循环参数
        foreach ($param as $k => $v) {
            // 组合参数
            $str .= $k . '=' . urlencode($v) . '&';
        }
        // 去除第一个&
        return substr($str, 0, -1);
    }
}