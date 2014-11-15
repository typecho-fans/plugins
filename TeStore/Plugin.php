<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 用来管理插件，可以下载，安装，卸载插件
 * 
 * @category system
 * @package TeStore 
 * @author zhulin3141
 * @version 1.0.0
 * @link http://zhulin31410.blog.163.com/
 */
class TeStore_Plugin implements Typecho_Plugin_Interface
{

    //默认应用数据来源URL
    private static $defaultServer = 'http://teck.vipsinaapp.com/';

    //临时目录
    private static $tempPath = '/.tmp/';

    //数据目录
    private static $dataPath = '/data/';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        $tempDir = dirname(__FILE__) . self::$tempPath;
        $dataDir = dirname(__FILE__) . self::$dataPath;

        if ( ! file_exists($tempDir) and ! @mkdir($tempDir) ) {
            throw new Typecho_Plugin_Exception('无法创建临时目录.');
        }

        if ( ! file_exists($dataDir) and ! @mkdir($dataDir) ) {
            throw new Typecho_Plugin_Exception('无法创建数据目录.');
        }

        Helper::addPanel(1, 'TeStore/market.php', 'TE应用商店', 'TE应用商店', 'administrator');
        Helper::addRoute('te-store_market', __TYPECHO_ADMIN_DIR__ . 'te-store/market', 'TeStore_Action', 'market');
        Helper::addRoute('te-store_install', __TYPECHO_ADMIN_DIR__ . 'te-store/install', 'TeStore_Action', 'install');
        Helper::addRoute('te-store_uninstall', __TYPECHO_ADMIN_DIR__ . 'te-store/uninstall', 'TeStore_Action', 'uninstall');
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
        Helper::removePanel(1, 'TeStore/market.php');
        Helper::removeRoute('te-store_market');
        Helper::removeRoute('te-store_install');
        Helper::removeRoute('te-store_uninstall');
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
        //应用服务器地址
        $server = new Typecho_Widget_Helper_Form_Element_Text('server', NULL, self::$defaultServer, _t('应用服务器地址'));
        $server->addRule('required',_t('应用服务器地址不能为空'));
        $form->addInput($server);

        //缓存设置
        $cache = new Typecho_Widget_Helper_Form_Element_Select('cache_time',
            array(
                '0'=>_t('不缓存'),
                '0.5'=>_t('半小时'),
                '1'=>_t('1小时'),
                '12'=>_t('12小时'),
                '24'=>_t('24小时'),
            ),
            '1',
            _t('缓存时间'),
            '应用数据的缓存时间'
        );
        $form->addInput($cache);
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
