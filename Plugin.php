<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();
/**
 * Typecho 应用商店
 *
 * @package AppStore
 * @author chekun
 * @version 1.0.2
 * @link http://typecho.dilicms.com
 */
class AppStore_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件下载临时目录
     *
     * @var string
     */
    public static $tempPath = '/.app_store/';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        //检查是否有curl扩展
        if (! extension_loaded('curl')) {
            throw new Typecho_Plugin_Exception('缺少curl扩展支持.');
        }
        //创建下载临时目录
        $tempDir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.self::$tempPath;
        if (! file_exists($tempDir) and ! @mkdir($tempDir)) {
            throw new Typecho_Plugin_Exception('无法创建插件下载临时目录.');
        }

        //创建菜单和路由
        Helper::addPanel(3, 'AppStore/market.php', '应用商店', '应用商店', 'administrator');
        Helper::addRoute('app.store.market', __TYPECHO_ADMIN_DIR__.'app-store/market', 'AppStore_Action', 'market');
        Helper::addRoute('app.store.install', __TYPECHO_ADMIN_DIR__.'app-store/install', 'AppStore_Action', 'install');
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
        include 'helpers/helpers.php';
        //删除下载临时目录
        $tempDir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.self::$tempPath;
        if (file_exists($tempDir) and (! delete_files($tempDir) or !@rmdir($tempDir))) {
            throw new Typecho_Plugin_Exception('无法删除插件下载临时目录.');
        }

        //移除菜单和路由
        Helper::removePanel(3, 'AppStore/market.php');
        Helper::removeRoute('app.store.market');
        Helper::removeRoute('app.store.install');

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
        /** 应用服务器地址 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('server', NULL, 'http://typecho.dilicms.com/', _t('应用服务器地址'));
        $form->addInput($name);
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
