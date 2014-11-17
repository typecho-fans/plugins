<?php
/**
 * 集成百度编辑器
 * 
 * @category editor
 * @package Ueditor
 * @author zhulin3141
 * @version 1.0.0
 * @link http://zhulin31410.blog.163.com/
 */
class Ueditor_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 默认设置的值
     *
     * @access private
     * @static var
     */
    private static $_defaultConfig = array(
        'theme' => 'default',
    );

    /**
     * 获取默认的配置
     *
     * @access private
     * @return mixed
     */
    private static function getDefaultConfig($key = null)
    {
        if( isset($key) )
            return self::$_defaultConfig[$key];
        return (object)self::$_defaultConfig;
    }

    /**
     * 获取目录下的文件夹
     *
     * @access private
     * @return array
     */
    private static function getDir($targetDir)  
    {
        $dirs = array();
        $files = scandir($targetDir);
        foreach($files as $file){
            if( is_dir($targetDir . '/' . $file) && !in_array($file, array('.', '..')) ){
                $dirs[] = $file;
            }
        }
        return $dirs;
    }

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('Ueditor_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('Ueditor_Plugin', 'render');
        
        //去除段落
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('Ueditor_Plugin', 'filter');    
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('Ueditor_Plugin', 'filter');
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
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $defaultConfig = self::getDefaultConfig();

        //皮肤
        $skins = self::getDir(dirname(__FILE__) . '/ueditor/themes');
        $skins = array_combine($skins, $skins);
        $skin = new Typecho_Widget_Helper_Form_Element_Select(
            'skin' ,
            $skins ,
            in_array($defaultConfig->theme, $skins) ? $defaultConfig->theme : $skins[0] ,
            _t('皮肤'),
            null
        );
        $form->addInput($skin);
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
     * 去除段落
     * 
     * @access public
     * @param array $post 数据结构体
     * @return array
     */
    public static function filter($post)
    {
        $post['text'] = str_replace("\n", '', $post['text']);
        return $post;
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render($post)
    {
        $options = Helper::options();
        $plugin_options = $options->plugin('Ueditor');
        $pluginRoot = Typecho_Common::url('Ueditor/ueditor', $options->pluginUrl);

        $isEdit = isset($_GET['cid']) && $_GET['cid'] > 0 ? 'false' : 'true';
        
        //调用编辑器
        echo <<<CODE
        <script type="text/javascript" charset="utf-8" src="{$pluginRoot}/ueditor.config.js"></script>
        <script type="text/javascript" charset="utf-8" src="{$pluginRoot}/ueditor.all.min.js"> </script>
        <script type="text/javascript">
        var ue = UE.getEditor('text');

        window.onbeforeunload = function(event){
            if(getContent() != '' && {$isEdit}){
              event.returnValue = '即将离开页面，是否确认编辑的内容已使用？';   
            }
        }
        </script>
CODE;

    }
}
