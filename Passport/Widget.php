<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 找回密码类
 *
 * @package Passport
 * @copyright Copyright (c) 2016 小否先生 (https://github.com/mhcyong)
 * @license GNU General Public License 2.0
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Passport_Widget extends Typecho_Widget
{
    /**
     * 配置表
     *
     * @access private
     * @var Typecho_Options
     */
    private $options;

    /**
     * 插件配置
     *
     * @access private
     * @var Object
     */
    private $config;

    /**
     * 提示框组件
     *
     * @access private
     * @var Widget_Notice
     */
    private $notice;

    /**
     * 构造函数
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        $this->notice = parent::widget('Widget_Notice');
        $this->options = parent::widget('Widget_Options');
        $this->config = $this->options->plugin('Passport');
    }

    /**
     * execute function.
     *
     * @access public
     * @return void
     */
    public function execute(){}

    /**
     * 找回密码
     *
     * @access public
     * @return void
     */


    public function doForgot()
    {
        require_once 'theme/forgot.php';

        if ($this->request->isPost()) {
            /* 验证表单 */
            if ($error = $this->forgotForm()->validate()) {
                $this->notice->set($error, 'error');
                return false;
            }

            $db = Typecho_Db::get();
            $user = $db->fetchRow($db->select()->from('table.users')->where('mail = ?', $this->request->mail));

            if (empty($user)) {
                // 返回没有该用户
                $this->notice->set(_t('该邮箱还没有注册'), 'error');
                return false;
            }

            /* 生成重置密码地址 */
            $hashString = $user['name'] . $user['mail'] . $user['password'];
            $hashValidate = Typecho_Common::hash($hashString);
            $token = base64_encode($user['uid'] . '.' . $hashValidate . '.' . $this->options->gmtTime);
            $url = Typecho_Common::url('/passport/reset?token=' . $token, $this->options->index);

            /* 发送重置密码地址 */

            require 'PHPMailer/Exception.php';
            require 'PHPMailer/PHPMailer.php';
            require 'PHPMailer/SMTP.php';

            $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
            try {
                //服务器配置
                $mail->CharSet ="UTF-8";                     //设定邮件编码
                $mail->SMTPDebug = 0;                        // 调试模式输出
                $mail->isSMTP();                             // 使用SMTP
                $mail->Host = $this->config->host;               // SMTP服务器
                $mail->SMTPAuth = true;                      // 允许 SMTP 认证
                $mail->Username = $this->config->username;      // SMTP 用户名  即邮箱的用户名
                $mail->Password = $this->config->password;         // SMTP 密码  部分邮箱是授权码(例如163邮箱)
                $mail->Port = $this->config->port;                // 服务器端口 25 或者465 具体要看邮箱服务器支持
                if ('none' != $this->config->secure) {
                    $mail->SMTPSecure = $this->config->secure;    // 允许 TLS 或者ssl协议
                }

                $mail->setFrom($this->config->username, $this->options->title);
                $mail->addAddress($user['mail'], $user['name']);

                //$mail->addAddress('ellen@example.com');  // 可添加多个收件人
                //$mail->addReplyTo('mhcyong@163.com', 'info'); //回复的时候回复给哪个邮箱 建议和发件人一致
                //$mail->addCC('cc@example.com');                    //抄送
                //$mail->addBCC('bcc@example.com');                    //密送

                //发送附件
                // $mail->addAttachment('../xy.zip');         // 添加附件
                // $mail->addAttachment('../thumb-1.jpg', 'new.jpg');    // 发送附件并且重命名

                //Content
                $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
                $mail->Subject = '密码重置' . date('Y-m-d H:i:s');
                $mail->Body    = '<p>' . $user['name'] . ' 您好，您申请了重置登录密码。</p>'
                . '<p>请在 1 小时内点击此链接以完成重置 <a href="' . $url . '">' . $url . '</a>';                
                if(!$mail->send()) {
                    $this->notice->set(_t('邮件发送失败, 请重试或联系站长'), 'error');
                } else {
                    $this->notice->set(_t('邮件已成功发送, 请注意查收'), 'success');
                }
            } catch (Exception $e) {
                echo '邮件发送失败: ', $mail->ErrorInfo;
            }
        }
    }

    /**
     * 重置密码
     *
     * @access public
     * @return void
     */
    public function doReset()
    {
        /* 验证token */
        $token = $this->request->filter('strip_tags', 'trim', 'xss')->token;
        list($uid, $hashValidate, $timeStamp) = explode('.', base64_decode($token));
        $currentTimeStamp = $this->options->gmtTime;

        /* 检查链接时效 */
        if (($currentTimeStamp - $timeStamp) > 3600) {
            // 链接失效, 返回登录页
            $this->notice->set(_t('该链接已失效, 请重新获取'), 'notice');
            $this->response->redirect($this->options->loginUrl);
        }

        $db = Typecho_Db::get();
        $user = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', $uid));

        $hashString = $user['name'] . $user['mail'] . $user['password'];
        $hashValidate = Typecho_Common::hashValidate($hashString, $hashValidate);

        if (!$hashValidate) {
            // token错误, 返回登录页
            $this->notice->set(_t('该链接已失效, 请重新获取'), 'notice');
            $this->response->redirect($this->options->loginUrl);
        }

        require_once 'theme/reset.php';

        /* 重置密码 */
        if ($this->request->isPost()) {
            /* 验证表单 */
            if ($error = $this->resetForm()->validate()) {
                $this->notice->set($error, 'error');
                return false;
            }

            $hasher = new PasswordHash(8, true);
            $password = $hasher->HashPassword($this->request->password);

            $update = $db->query($db->update('table.users')
                ->rows(array('password' => $password))
                ->where('uid = ?', $user['uid']));

            if (!$update) {
                $this->notice->set(_t('重置密码失败'), 'error');
            }

            $this->notice->set(_t('重置密码成功'), 'success');
            $this->response->redirect($this->options->loginUrl);
        }
    }

    /**
     * 生成找回密码表单
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function forgotForm() {
        $form = new Typecho_Widget_Helper_Form(NULL, Typecho_Widget_Helper_Form::POST_METHOD);

        $mail = new Typecho_Widget_Helper_Form_Element_Text('mail',
            NULL,
            NULL,
            _t('邮箱'),
            _t('账号对应的邮箱地址'));
        $form->addInput($mail);

        /** 用户动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'mail');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('提交'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $mail->addRule('required', _t('必须填写电子邮箱'));
        $mail->addRule('email', _t('电子邮箱格式错误'));

        return $form;
    }

    /**
     * 生成重置密码表单
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function resetForm() {
        $form = new Typecho_Widget_Helper_Form(NULL, Typecho_Widget_Helper_Form::POST_METHOD);

        /** 新密码 */
        $password = new Typecho_Widget_Helper_Form_Element_Password('password',
            NULL,
            NULL,
            _t('新密码'),
            _t('建议使用特殊字符与字母、数字的混编样式,以增加系统安全性.'));
        $password->input->setAttribute('class', 'w-100');
        $form->addInput($password);

        /** 新密码确认 */
        $confirm = new Typecho_Widget_Helper_Form_Element_Password('confirm',
            NULL,
            NULL,
            _t('密码确认'),
            _t('请确认你的密码, 与上面输入的密码保持一致.'));
        $confirm->input->setAttribute('class', 'w-100');
        $form->addInput($confirm);

        /** 用户动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'password');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('更新密码'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $password->addRule('required', _t('必须填写密码'));
        $password->addRule('minLength', _t('为了保证账户安全, 请输入至少六位的密码'), 6);
        $confirm->addRule('confirm', _t('两次输入的密码不一致'), 'password');

        return $form;
    }
}
