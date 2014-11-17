<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Emlog 转换到 Typecho
 *
 * @category data
 * @package EmlogToTypecho
 * @author ShingChi
 * @version 1.0.0
 * @link http://lcz.me
 */
class EmlogToTypecho_Plugin implements Typecho_Plugin_Interface
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
        if (!Typecho_Db_Adapter_Mysql::isAvailable()
            && !Typecho_Db_Adapter_Pdo_Mysql::isAvailable()
        ) {
            throw new Typecho_Plugin_Exception(_t('没有找到任何可用的 Mysql 适配器'));
        }

        Helper::addPanel(1, 'EmlogToTypecho/panel.php',
            _t('从 Emlog 导入数据'),
            _t('从 Emlog 导入数据'), 'administrator');
        Helper::addAction('emlog-to-typecho', 'EmlogToTypecho_Action');
        return _t('请在插件设置里设置 Emlog 所在的数据库参数') . $error;
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
        Helper::removeAction('emlog-to-typecho');
        Helper::removePanel(1, 'EmlogToTypecho/panel.php');
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
        $host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL,
            'localhost', _t('数据库地址'), _t('Emlog 所在的数据库地址'));
        $form->addInput($host->addRule('required', _t('必须填写一个数据库地址')));

        $port = new Typecho_Widget_Helper_Form_Element_Text('port', NULL,
            '3306', _t('数据库端口'), _t('Emlog 所在的数据库服务器端口'));
        $port->input->setAttribute('class', 'w-20');
        $form->addInput($port->addRule('required', _t('必须填写数据库端口'))
            ->addRule('isInteger', _t('端口号必须是纯数字')));

        $user = new Typecho_Widget_Helper_Form_Element_Text('user', NULL,
            'root', _t('数据库用户名'));
        $form->addInput($user->addRule('required', _t('必须填写数据库用户名')));

        $password = new Typecho_Widget_Helper_Form_Element_Password('password',
            NULL, NULL, _t('数据库密码'));
        $password->input->setAttribute('class', 'w-40');
        $form->addInput($password);

        $database = new Typecho_Widget_Helper_Form_Element_Text('database',
            NULL, 'emlog', _t('数据库名称'), _t('Emlog 所在的数据库名称'));
        $form->addInput($database->addRule('required', _t('您必须填写数据库名称')));

        $prefix = new Typecho_Widget_Helper_Form_Element_Text('prefix', NULL,
            'emlog_', _t('表前缀'), _t('所有 Emlog 数据表的前缀'));
        $form->addInput($prefix->addRule('required', _t('您必须填写表前缀')));
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
