<?php
/**
 * 数据备份
 *
 * @package Export
 * @author ShingChi
 * @version 1.0.1
 * @link http://lcz.me
 */
class Export_Plugin implements Typecho_Plugin_Interface
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
        Helper::addAction('export', 'Export_Action');
        Helper::addPanel(1, 'Export/panel.php', _t('数据备份'), _t('数据备份'), 'administrator');

        return _t('插件已经激活，请设置插件以正常使用！');
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
        Helper::removeAction('export');
        Helper::removePanel(1, 'Export/panel.php');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $path = new Typecho_Widget_Helper_Form_Element_Text(
            'path', NULL, '/usr/plugins/Export/backup',
            _t('备份文件夹'),
            _t('备份文件夹默认在插件目录下的 backup，路径规则请以 Typecho 根目录为准，如：/usr/backup<br>请正确设置目录权限，以便正常插件正常运行')
        );
        $form->addInput($path->addRule('required', _t('备份文件夹不能为空')));
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
