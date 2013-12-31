<?php
/**
 * 上传、删除插件和模板 For Typecho 0.9
 *
 * @package Upload Plugin
 * @author DEFE
 * @version 1.1.1
 * @link http://defe.me
 */
class UploadPlugin_Plugin implements Typecho_Plugin_Interface
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
        if (!class_exists('ZipArchive')) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的服务器不支持 ZipArchive 类, 无法正常使用此插件'));
        }
        Helper::addPanel(1, 'UploadPlugin/panel.php', _t('上传'), _t('在线插件管理'), 'administrator');
        Helper::addAction('upload-plugin', 'UploadPlugin_Action');
        //return _t('请设置插件仓库的服务地址，以便能在线安装插件！');
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
        Helper::removeAction('upload-plugin');
        Helper::removePanel(1, 'UploadPlugin/panel.php');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){}

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

}

