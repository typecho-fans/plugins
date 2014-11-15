<?php
/**
 * 让0.9之前的日志内容可用系统自带的Markdown编辑器编辑。
 * 
 * @category system
 * @package Html2Text
 * @author 冰剑
 * @version 0.1.0
 * @link http://www.binjoo.net
 */
class Html2Text_Plugin implements Typecho_Plugin_Interface
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
        Helper::addPanel(1, 'Html2Text/Panel.php', 'Html2Text', 'Html2Text设置', 'administrator');
        return('Html2Text已经成功激活，请进入控制台-Html2Text中进行操作!');
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
        Helper::removePanel(1, 'Html2Text/Panel.php');
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
