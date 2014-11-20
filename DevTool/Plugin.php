<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 开发者工具
 * 
 * @category system
 * @package DevTool 
 * @author zhulin3141
 * @version 1.0.0
 * @link http://zhulin31410.blog.163.com/
 */
class DevTool_Plugin implements Typecho_Plugin_Interface
{

    const NAME = '开发者工具';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/menu.php')->navBar = array('DevTool_Plugin', 'render');
        Helper::addPanel(1, 'DevTool/index.php', self::NAME, self::NAME, 'administrator');
        Helper::addRoute('dev-tool_index', __TYPECHO_ADMIN_DIR__ . 'dev-tool/index', 'DevTool_Action', 'index');
        Helper::addRoute('dev-tool_options', __TYPECHO_ADMIN_DIR__ . 'dev-tool/options', 'DevTool_Action', 'options');
        Helper::addRoute('dev-tool_post', __TYPECHO_ADMIN_DIR__ . 'dev-tool/post', 'DevTool_Action', 'post');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Helper::removePanel(1, 'DevTool/index.php');
        Helper::removeRoute('dev-tool_index');
        Helper::removeRoute('dev-tool_options');
        Helper::removeRoute('dev-tool_post');
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {}

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render()
    {
        $options = Helper::options();
        echo '<a href="';
        $options->adminUrl('extending.php?panel=DevTool%2Findex.php');
        echo '">' .self::NAME .'</a>';
    }
}
