<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 密码找回插件
 *
 * @package Passport
 * @author 小否先生,ShingChi
 * @version 1.0.2
 * @link https://github.com/mhcyong
 * @dependence 14.5.26-*
 */
class Passport_Plugin implements Typecho_Plugin_Interface
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
        // 创建数据库字段
        Passport_Plugin::addTable();
        Helper::addRoute('passport_forgot', '/passport/forgot', 'Passport_Widget', 'doForgot');
        Helper::addRoute('passport_reset', '/passport/reset', 'Passport_Widget', 'doReset');
        Typecho_Plugin::factory('admin/footer.php')->end = array('Passport_Plugin', 'addFooter');

        return _t('请配置此插件的SMTP信息, 以使您的插件生效');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute('passport_reset');
        Helper::removeRoute('passport_forgot');
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
        $host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL, '', _t('服务器(SMTP)'), _t('如: smtp.exmail.qq.com'));
        $port = new Typecho_Widget_Helper_Form_Element_Text('port', NULL, '465', _t('端口'), _t('如: 25、465(SSL)、587(SSL)'));

        $username = new Typecho_Widget_Helper_Form_Element_Text('username', NULL, '', _t('帐号'), _t('如: hello@example.com'));
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL, _t('密码'));

        $secure = new Typecho_Widget_Helper_Form_Element_Select('secure',array(
            'ssl' => _t('SSL'),
            'tls' => _t('TLS'),
            'none' => _t('无')
        ), 'ssl', _t('安全类型'));

        $repeat = new Typecho_Widget_Helper_Form_Element_Radio('repeat', array('0' => _t('不允许'), '1' => _t('允许')), 0, _t('是否允许失效时间内重复发送邮件'),
        _t('建议关闭，减少邮箱短时间重复发送.'));

        $form->addInput($host);
        $form->addInput($port);
        $form->addInput($username);
        $form->addInput($password);
        $form->addInput($secure);
        $form->addInput($repeat);
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
     * 创建数据库字段
     *
     * @throws Typecho_Db_Exception
     */
    private static function addTable()
    {
        $db = Typecho_Db::get();
        try {
            $db->query($db->select('table.users.passport_token')->from('table.users'));
        } catch (Typecho_Db_Exception $e) {
            $sql = "ALTER TABLE `" . $db->getPrefix() . "users` ADD `passport_token` VARCHAR(255)  DEFAULT '';";
            $db->query($sql);
        }
    }

    /**
     * 登录界面添加js
     */
    public static function addFooter()
    {
        if (!Typecho_Widget::widget('Widget_User')->hasLogin()) {
            echo "<script>var link = $('.typecho-login-wrap .typecho-login .more-link');if (link.length) {link.append('&bull;<a href=\"/passport/forgot\">忘记密码</a>');}</script>";
        }
    }
}
