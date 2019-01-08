<?php
/**
 * Typecho 评论通知、找回密码插件
 *
 * @package LoveKKComment
 * @author  康粑粑
 * @version 1.0.5
 * @link    https://www.usebsd.com
 */

if ( !defined('__TYPECHO_ROOT_DIR__') ) exit;

class LoveKKComment_Action extends Widget_Abstract_Users implements Widget_Interface_Do
{
    /**
     * 插件配置
     *
     * @access private
     * @var mixed
     */
    private $_plugin = NULL;
    
    /**
     * 构造函数
     *
     * @param mixed $request  请求对象
     * @param mixed $response 输出对象
     * @param null  $params   请求参数
     *
     * @throws Typecho_Plugin_Exception
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        // 获取插件配置信息
        $this->_plugin = $this->options->plugin('LoveKKComment');
    }
    
    /**
     * 找回密码页面输出
     *
     * @access private
     *
     * @param string $act  当前操作
     * @param mixed  $form 表单对象
     */
    private function html($act = 'forget', $form = NULL)
    {
        ?>
        <!DOCTYPE html>
        <html class="no-js">
        <head>
            <meta charset="<?php $this->options->charset(); ?>">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="renderer" content="webkit">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php _e('%s - %s - Powered by Typecho', 'reset' == $act ? _t('重置密码') : _t('找回密码'), $this->options->title); ?></title>
            <meta name="robots" content="noindex, nofollow">
            <link rel="stylesheet"
                  href="<?php $this->options->adminStaticUrl('css', 'normalize.css'); ?>">
            <link rel="stylesheet"
                  href="<?php $this->options->adminStaticUrl('css', 'grid.css'); ?>">
            <link rel="stylesheet"
                  href="<?php $this->options->adminStaticUrl('css', 'style.css'); ?>">
            <!--[if lt IE 9]>
            <script src="<?php $this->options->adminStaticUrl('js', 'html5shiv.js');?>"></script>
            <script src="<?php $this->options->adminStaticUrl('js', 'respond.js');?>"></script>
            <![endif]-->
        </head>
        <body class="body-100">
        <!--[if lt IE 9]>
        <div class="message error browsehappy" role="dialog"><?php _e('当前网页 <strong>不支持</strong> 你正在使用的浏览器. 为了正常的访问, 请
            <a href="http://browsehappy.com/">升级你的浏览器</a>'); ?>.
        </div>
        <![endif]-->
        <div class="typecho-login-wrap">
            <div class="typecho-login">
                <h1><a href="http://typecho.org" class="i-logo">Typecho</a></h1>
                <?php $form->render(); ?>
            </div>
        </div>
        <script src="<?php $this->options->adminStaticUrl('js', 'jquery.js'); ?>"></script>
        <script src="<?php $this->options->adminStaticUrl('js', 'jquery-ui.js'); ?>"></script>
        <script src="<?php $this->options->adminStaticUrl('js', 'typecho.js'); ?>"></script>
        <script>
            (function () {
                $(document).ready(function () {
                    (function () {
                        var prefix = '<?php echo Typecho_Cookie::getPrefix();?>',
                            cookies = {
                                notice: $.cookie(prefix + '__typecho_notice'),
                                noticeType: $.cookie(prefix + '__typecho_notice_type'),
                                highlight: $.cookie(prefix + '__typecho_notice_highlight')
                            },
                            path = '<?php echo Typecho_Cookie::getPath(); ?>';
                        if (!!cookies.notice && 'success|notice|error'.indexOf(cookies.noticeType) >= 0) {
                            var head = $('.typecho-head-nav'),
                                p = $('<div class="message popup ' + cookies.noticeType + '">'
                                    + '<ul><li>' + $.parseJSON(cookies.notice).join('</li><li>')
                                    + '</li></ul></div>'), offset = 0;
                            if (head.length > 0) {
                                p.insertAfter(head);
                                offset = head.outerHeight();
                            } else {
                                p.prependTo(document.body);
                            }

                            function checkScroll() {
                                if ($(window).scrollTop() >= offset) {
                                    p.css({
                                        'position': 'fixed',
                                        'top': 0
                                    });
                                } else {
                                    p.css({
                                        'position': 'absolute',
                                        'top': offset
                                    });
                                }
                            }

                            $(window).scroll(function () {
                                checkScroll();
                            });
                            checkScroll();
                            p.slideDown(function () {
                                var t = $(this), color = '#C6D880';
                                if (t.hasClass('error')) {
                                    color = '#FBC2C4';
                                } else if (t.hasClass('notice')) {
                                    color = '#FFD324';
                                }
                                t.effect('highlight', {color: color})
                                    .delay(5000).fadeOut(function () {
                                    $(this).remove();
                                });
                            });
                            $.cookie(prefix + '__typecho_notice', null, {path: path});
                            $.cookie(prefix + '__typecho_notice_type', null, {path: path});
                        }
                        if (cookies.highlight) {
                            $('#' + cookies.highlight).effect('highlight', 1000);
                            $.cookie(prefix + '__typecho_notice_highlight', null, {path: path});
                        }
                    })();
                    (function () {
                        $('#typecho-nav-list').find('.parent a').focus(function () {
                            $('#typecho-nav-list').find('.child').hide();
                            $(this).parents('.root').find('.child').show();
                        });
                        $('.operate').find('a').focus(function () {
                            $('#typecho-nav-list').find('.child').hide();
                        });
                    })();
                    if ($('.typecho-login').length == 0) {
                        $('a').each(function () {
                            var t = $(this), href = t.attr('href');
                            if ((href && href[0] == '#')
                                || /^<?php echo preg_quote($this->options->adminUrl, '/'); ?>.*$/.exec(href)
                                || /^<?php echo substr(preg_quote(Typecho_Common::url('s', $this->options->index), '/'), 0, -1); ?>action\/[_a-zA-Z0-9\/]+.*$/.exec(href)) {
                                return;
                            }
                            t.attr('target', '_blank');
                        });
                    }
                });
            })();
        </script>
        <?php if ( 'forget' == $act ) : ?>
            <script>$(document).ready(function () {
                    $('#mail').focus();
                });</script><?php endif; ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * 忘记密码表单
     *
     * @access private
     * @return Typecho_Widget_Helper_Form
     */
    private function forgetForm()
    {
        // 创建表单
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/lovekkcomment'), Typecho_Widget_Helper_Form::POST_METHOD);
        // 邮箱地址
        $mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL, _t('邮箱地址'), _t('请输入您注册时的邮箱地址'));
        // 添加class
        $mail->input->setAttribute('class', 'text-l w-100');
        // 添加规则
        $mail->addRule('required', _t('必须输入您的邮箱地址'));
        $mail->addRule('email', _t('请输入正确的邮箱格式'));
        $form->addInput($mail);
        // 动作
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'forget');
        $form->addItem($do);
        // 提交按钮
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('提交'));
        // 添加class
        $submit->input->setAttribute('class', 'btn btn-l w-100 primary');
        $form->addItem($submit);
        
        return $form;
    }
    
    /**
     * 重置密码表单
     *
     * @access private
     *
     * @param int $uid 用户编号
     *
     * @return Typecho_Widget_Helper_Form
     */
    private function resetForm($uid = 0)
    {
        // 创建表单
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/lovekkcomment'), Typecho_Widget_Helper_Form::POST_METHOD);
        // 登录密码
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL, _t('用户密码'), _t('建议使用特殊字符与字母、数字的混编样式,以增加系统安全性.'));
        // 设置class
        $password->input->setAttribute('class', 'text-l w-100');
        // 添加规则
        $password->addRule('required', _t('必须输入您的邮箱地址'));
        $password->addRule('minLength', _t('为了保证账户安全, 请设置最少8位数的密码'), 8);
        $form->addInput($password);
        // 密码确认
        $confirm = new Typecho_Widget_Helper_Form_Element_Password('confirm', NULL, NULL, _t('用户密码确认'), _t('请确认您的密码, 与上面输入的密码保持一致.'));
        // 设置class
        $confirm->input->setAttribute('class', 'text-l w-100');
        // 添加规则
        $confirm->addRule('confirm', _t('您两次输入的密码不一致, 请重新输入'), 'password');
        $form->addInput($confirm);
        // 动作
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'reset');
        $form->addItem($do);
        // uid
        $uid = new Typecho_Widget_Helper_Form_Element_Hidden('uid', NULL, $uid);
        $form->addItem($uid);
        // 提交按钮
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('提交'));
        // 添加class
        $submit->input->setAttribute('class', 'btn btn-l w-100 primary');
        $form->addItem($submit);
        
        return $form;
    }
    
    /**
     * 找回密码提交
     *
     * @access private
     * @throws Typecho_Exception
     * @throws Typecho_Plugin_Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function doForget()
    {
        // 验证表单
        if ( $error = $this->forgetForm()->validate() ) {
            // 显示错误信息
            $this->widget('Widget_Notice')->set($error, 'error');
            // 返回上一页
            $this->response->goBack();
        }
        // 查询用户数据
        $user = $this->db->fetchRow($this->select()->where('mail = ?', $this->request->mail));
        // 没有用户
        if ( !$user ) {
            // 输出错误
            $this->widget('Widget_Notice')->set(_t('邮箱地址错误, 请核对后重新输入'), 'error');
            // 返回上一页
            $this->response->goBack();
        }
        // 过期时间
        $expire = $this->_plugin->public_expire ? $this->_plugin->public_expire : 10;
        // 转换为秒数
        $time = time() + $expire * 60;
        // 构造参数
        $query = array(
            'reset' => 'true',
            't' => md5($user['uid'] . $user['name'] . $user['mail'] . $time),
            'm' => $user['mail'],
            'e' => $time
        );
        // 生成链接地址
        $uri = Typecho_Common::url('/action/lovekkcomment?' . http_build_query($query), $this->options->index);
        // 请求参数
        $data = array(
            'fromName' => ( !isset($this->_plugin->public_name) || is_null($this->_plugin->public_name) || empty($this->_plugin->public_name) ) ? trim($this->options->title) : $this->_plugin->public_name, // 发件人名称
            'from' => $this->_plugin->public_mail, // 发件地址
            'to' => $user['mail'], // 收件地址
            'replyTo' => $this->_plugin->public_replyto // 回信地址
        );
        // 标题
        $data['subject'] = _t('您在 [' . trim($this->options->title) . '] 提交的密码找回申请!');
        // 读取模板
        $html = file_get_contents(dirname(__FILE__) . '/theme/forget.html');
        // 替换内容
        $data['html'] = str_replace(
            array(
                '{blogname}',
                '{blogurl}',
                '{mail}',
                '{sendtime}',
                '{resetlink}',
                '{expire}'
            ),
            array(
                trim($this->options->title),
                trim($this->options->siteUrl),
                trim($user['mail']),
                trim(date('Y-m-d H:i:s', time())),
                trim($uri),
                trim($expire)
            ),
            $html
        );
        // 根据接口选择
        switch ( $this->_plugin->public_interface ) {
            case 'sendcloud': // Send Cloud
                // API User
                $data['apiUser'] = $this->_plugin->sendcloud_api_user;
                // API Key
                $data['apiKey'] = $this->_plugin->sendcloud_api_key;
                // 是否成功
                if ( !LoveKKComment_Plugin::sendCloud($data) ) {
                    // 输出错误信息
                    $this->widget('Widget_Notice')->set(_t('邮件发送失败, 请联系管理员解决!'), 'error');
                    // 跳转回去
                    $this->response->goBack();
                }
                // 输出提示
                $this->widget('Widget_Notice')->set(_t('已将重置密码信息发送至您的注册邮箱中, 请注意查收!'), 'success');
                // 跳转回去
                $this->response->goBack();
            case 'aliyun': // 阿里云
                // 判断当前请求区域
                switch ( $this->_plugin->ali_region ) {
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
                $data['accessid'] = $this->_plugin->ali_accesskey_id;
                // AccessKeySecret
                $data['accesssecret'] = $this->_plugin->ali_accesskey_secret;
                // 是否成功
                if ( !LoveKKComment_Plugin::aliyun($data) ) {
                    // 输出错误信息
                    $this->widget('Widget_Notice')->set(_t('邮件发送失败, 请联系管理员解决!'), 'error');
                    // 跳转回去
                    $this->response->goBack();
                }
                // 输出提示
                $this->widget('Widget_Notice')->set(_t('已将重置密码信息发送至您的注册邮箱中, 请注意查收!'), 'success');
                // 跳转回去
                $this->response->goBack();
            default: // SMTP
                // SMTP地址
                $data['smtp_host'] = $this->_plugin->smtp_host;
                // SMTP端口
                $data['smtp_port'] = $this->_plugin->smtp_port;
                // SMTP用户
                $data['smtp_user'] = $this->_plugin->smtp_user;
                // SMTP密码
                $data['smtp_pass'] = $this->_plugin->smtp_pass;
                // 验证模式
                $data['smtp_auth'] = $this->_plugin->smtp_auth;
                // 加密模式
                $data['smtp_secure'] = $this->_plugin->smtp_secure;
                // 是否成功
                if ( !LoveKKComment_Plugin::smtp($data) ) {
                    // 输出错误信息
                    $this->widget('Widget_Notice')->set(_t('邮件发送失败, 请联系管理员解决!'), 'error');
                    // 跳转回去
                    $this->response->goBack();
                }
                // 输出提示
                $this->widget('Widget_Notice')->set(_t('已将重置密码信息发送至您的注册邮箱中, 请注意查收!'), 'success');
                // 跳转回去
                $this->response->goBack();
        }
    }
    
    /**
     * 重置密码界面
     *
     * @access private
     * @throws Typecho_Exception
     */
    private function reset()
    {
        // 获取过期时间
        $expire = $this->request->filter('int')->e;
        // 如果链接过期则输出错误
        if ( time() > $expire ) {
            // 输出错误
            $this->widget('Widget_Notice')->set(_t('抱歉, 您所提交的重置密码链接已过期, 请重新获取'), 'notice');
            // 跳转到找回密码界面
            $this->response->redirect(Typecho_Common::url('/action/lovekkcomment?forget', $this->options->index));
        }
        // 查询用户数据
        $user = $this->db->fetchRow($this->select()->where('mail = ?', $this->request->m));
        // 没有用户
        if ( !$user ) {
            // 输出错误
            $this->widget('Widget_Notice')->set(_t('抱歉, 您的请求有误'), 'error');
            // 返回登录界面
            $this->resopnse->redirect($this->options->loginUrl);
        }
        // 取出数据
        $token = $this->request->filter('strip_tags', 'trim', 'xss')->t;
        // 如果验证不通过
        if ( $token != md5($user['uid'] . $user['name'] . $user['mail'] . $expire) ) {
            // 输出错误
            $this->widget('Widget_Notice')->set(_t('抱歉, 您的请求验证错误'), 'error');
            // 返回登录界面
            $this->resopnse->redirect($this->options->loginUrl);
        }
        // 显示重置界面
        $this->html('reset', $this->resetForm($user['uid']));
    }
    
    /**
     * 重置密码提交
     *
     * @access private
     * @throws Typecho_Exception
     */
    private function doReset()
    {
        // 验证表单
        if ( $error = $this->resetForm()->validate() ) {
            // 显示错误信息
            $this->widget('Widget_Notice')->set($error, 'error');
            // 返回上一页
            $this->response->goBack();
        }
        // 获取用户uid
        $uid = $this->request->filter('integer')->uid;
        // 验证uid
        if ( !$uid ) {
            // 显示错误信息
            $this->widget('Widget_Notice')->set(_t('抱歉, 您的请求验证失败'), 'error');
            // 返回上一页
            $this->response->goBack();
        }
        echo '2';
        // 初始化passwordhash
        $hasher = new PasswordHash(8, TRUE);
        // 密码加密
        $password = $hasher->HashPassword($this->request->password);
        // 更新密码
        if ( $this->update(array('password' => $password), $this->db->sql()->where('uid = ?', $uid)) ) {
            // 显示成功信息
            $this->widget('Widget_Notice')->set(_t('密码重置成功'), 'success');
            // 跳转登录页面
            $this->response->redirect($this->options->loginUrl);
        }
        echo '3';
        // 显示错误信息
        $this->widget('Widget_Notice')->set(_t('密码重置失败, 请联系管理员'), 'error');
        // 跳转登录页面
        $this->response->redirect($this->options->loginUrl);
    }
    
    /**
     * 操作方法
     *
     * @access public
     * @throws Typecho_Exception
     */
    public function action()
    {
        // 如果用户是登录状态则直接跳转至个人信息界面
        if ( $this->user->hasLogin() ) $this->response->redirect($this->options->profileUrl);
        // 动作必须是POST提交
        if ( $this->request->isPost() ) {
            // 忘记密码请求
            $this->on($this->request->is('do=forget'))->doForget();
            // 重置密码请求
            $this->on($this->request->is('do=reset'))->doReset();
        }
        // 忘记密码界面
        if ( $this->request->is('forget') ) $this->html('forget', $this->forgetForm());
        // 重置密码界面
        if ( $this->request->is('reset') ) $this->reset();
    }
}