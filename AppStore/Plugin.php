<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();
/**
 * Typecho 应用商店
 *
 * @package AppStore
 * @author chekun
 * @version 2.0.0
 * @link https://typecho.chekun.me
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

        ! file_exists($tempDir) and ! @mkdir($tempDir);

        //创建菜单和路由
        Helper::addPanel(1, 'AppStore/market.php', '应用商店', '应用商店', 'administrator');
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
        Helper::removePanel(1, 'AppStore/market.php');
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
        $name = new Typecho_Widget_Helper_Form_Element_Text(
            'server', 
            NULL, 
            'https://typecho.chekun.me/', 
            _t('应用服务器地址'),
            '参与服务端开发的小伙伴可以通过设置此处调试，普通的小伙伴默认就好，😄'
        );
        $form->addInput($name);
        /** 下载插件方法 */
        $http = new Typecho_Widget_Helper_Form_Element_Select(
            'http',
            ['curl' => 'curl', 'file_get_contents' => 'file_get_contents'],
            'curl',
            _t('下载插件方法'),
            '不能正常显示插件列表/下载插件的小伙伴可以设置为file_get_contents方式'
        );
        $form->addInput($http);
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
