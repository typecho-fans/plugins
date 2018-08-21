<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 通过读取html表格实现插件仓库的下载、安装及卸载等功能
 * 
 * @package TeStore
 * @author 羽中, zhulin3141
 * @version 1.1.2
 * @link http://www.yzmb.me
 */
class TeStore_Plugin implements Typecho_Plugin_Interface
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
        $tempDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/TeStore/.tmp';
        $dataDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/TeStore/data';

        if ( ! is_dir($tempDir) and ! @mkdir($tempDir) ) {
            throw new Typecho_Plugin_Exception('无法创建临时目录.');
        }

        if( ! self::testWrite($tempDir) ){
            throw new Typecho_Plugin_Exception('.tmp目录没有写入的权限');
        }

        if ( ! file_exists($dataDir) and ! @mkdir($dataDir) ) {
            throw new Typecho_Plugin_Exception('无法创建缓存目录.');
        }

        if( ! self::testWrite($dataDir) ){
            throw new Typecho_Plugin_Exception('data目录没有写入的权限');
        }

        Typecho_Plugin::factory('admin/menu.php')->navBar = array('TeStore_Plugin', 'render');
        Helper::addPanel(1, 'TeStore/market.php', 'TE插件仓库', 'TE插件仓库', 'administrator');
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
        //源文件地址
        $source = new Typecho_Widget_Helper_Form_Element_Textarea('source', NULL, 'https://github.com/typecho-fans/plugins/blob/master/TESTORE.md' . PHP_EOL . 'https://github.com/typecho-fans/plugins/blob/master/README.md', _t('插件信息源'),
        _t('应为可公开访问且包含准确表格内容的页面地址, 每行一个, 例: ') . '<br/>
        <strong><a href="https://github.com/typecho-fans/plugins/blob/master/README.md">https://github.com/typecho-fans/plugins/blob/master/README.md</a> - <span class="warning">' . _t('Typecho-Fans插件集群索引(社区维护版目录)') . '</span><br/>
        <a href="https://github.com/typecho-fans/plugins/blob/master/TESTORE.md">https://github.com/typecho-fans/plugins/blob/master/TESTORE.md</a> - <span class="warning">' . _t('Typecho-Fans外部插件登记表(TeStore专用)') . '</span></strong><br/>
        ' . _t('以上Markdown格式文件可以在Github上方便地进行多人修改更新, 参与方式详见文件说明'));
        $source->addRule('required',_t('源文件地址不能为空'));
        $form->addInput($source);

        //缓存设置
        $cache = new Typecho_Widget_Helper_Form_Element_Select('cache_time',
            array(
                '0'=>_t('不缓存'),
                '6'=>_t('6小时'),
                '12'=>_t('12小时'),
                '24'=>_t('1天'),
                '72'=>_t('3天'),
                '168'=>_t('1周')
            ),
            '24',
            _t('缓存时间'),
            _t('列表数据的缓存时间')
        );
        $form->addInput($cache);

        $curl = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'curl',
            array(
                1 => '是'
            ),
            0,
            _t('使用curl下载'),
            '默认file_get_contents方式无效时可尝试'
        );
        $form->addInput($curl);

        $showNavMenu = new Typecho_Widget_Helper_Form_Element_Radio(
            'showNavMenu' ,
            array(
                'true' => _t('是'),
                'false' => _t('否'),
            ),
            'true' ,
            _t('显示导航条按钮')
        );
        $form->addInput($showNavMenu);
    }
    
    /**
     * 检查curl支持
     *
     * @param array $settings
     * @return string
     */
    public static function configCheck(array $settings)
    {
        if ( $settings['curl'] && !extension_loaded('curl') ) {
            return _t('主机没有安装curl扩展');
        }
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
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render()
    {
        $options = Helper::options();
        $pluginOpts = Typecho_Widget::widget('Widget_Options')->plugin('TeStore');
        if( $pluginOpts->showNavMenu == 'true' ){
            echo '<a href="';
            $options->adminUrl('extending.php?panel=TeStore%2Fmarket.php');
            echo '"><span class="message success"><i class="mime-script"></i>' . _t('TE插件仓库') . '</span></a>';
        }
    }

    /**
     * 判断目录是否可写
     */
    public static function testWrite($dir) {
        $testFile = "_test.txt";
        $fp = @fopen($dir . "/" . $testFile, "w");
        if (!$fp) {
            return false;
        }
        fclose($fp);
        $rs = @unlink($dir . "/" . $testFile);
        if ($rs) {
            return true;
        }
        return false;
    }
}
