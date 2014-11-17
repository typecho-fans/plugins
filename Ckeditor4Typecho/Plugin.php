<?php
/**
 * 集成CkEditor编辑器,支持上传图片功能
 * 
 * @category editor
 * @package Ckeditor4Typecho
 * @author zhulin3141
 * @version 1.0.0
 * @link http://zhulin31410.blog.163.com/
 */
class Ckeditor4Typecho_Plugin implements Typecho_Plugin_Interface
{
    const CONTACT_CHAR = 'x';
    /**
     * 默认设置的值
     *
     * @access private
     * @static var
     */
    private static $_defaultConfig = array(
        'width' => 'auto',
        'height' => 'auto',
        'toolbar' => 'SIMPLE',
        'toolbarCanCollapse' => 'false',
        'enterMode' => 'CKEDITOR.ENTER_P',
        'skin' => 'moono',
        'extraPlugins' => '',
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
     * 获取编辑器长和宽的数组
     *
     * @access private
     * @return array
     */
    private static function getLayoutArr($savedWidthAndHeight)
    {
        $widthAndHeight = explode(self::CONTACT_CHAR, $savedWidthAndHeight);
        @list($width, $height) = $widthAndHeight;

        if( self::validWidthAndHeight($width, $height) ) {
            return $widthAndHeight;
        }else{
            return array(self::getDefaultConfig('width'), self::getDefaultConfig('height'));
        }
    }

    /**
     * 验证宽度和高度的有效性
     *
     * @param mixed $expression [, mixed $... ]
     * @return boolean
     */
    private static function validWidthAndHeight()
    {
        $params = func_get_args ();
        $pass = false;

        if( count($params) > 0 ){
            foreach ($params as $val) {
                $pass = ( is_numeric($val) || in_array($val, array('auto')) ) ? true : false;
                if( ! $pass ){
                    return $pass;
                }
            }
        }

        return $pass;
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
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('Ckeditor4Typecho_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('Ckeditor4Typecho_Plugin', 'render');
        
        //去除段落
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('Ckeditor4Typecho_Plugin', 'filter');    
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('Ckeditor4Typecho_Plugin', 'filter');
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
        $widthAndHeight = new Typecho_Widget_Helper_Form_Element_Text(
            'widthAndHeight',
            NULL, 
            $defaultConfig->width . self::CONTACT_CHAR . $defaultConfig->height,
            _t('设置宽度和高度'));
        $form->addInput($widthAndHeight);

        //*工具栏按钮样式
        $toolbar = new Typecho_Widget_Helper_Form_Element_Select(
            'toolbar' ,
            array(
                'STANDARD' => '标准模式' ,
                'SIMPLE' => '简单模式' ,
                'MINI' => '迷你模式' ,
            ) ,
            $defaultConfig->toolbar , 
            _t('工具按钮'),
            _t('工具栏按钮设置')
        );
        $form->addInput($toolbar);

        $enterMode = new Typecho_Widget_Helper_Form_Element_Select(
            'enterMode' ,
            array(
                'CKEDITOR.ENTER_P' => 'P' ,
                'CKEDITOR.ENTER_BR' => 'BR' ,
                'CKEDITOR.ENTER_DIV' => 'DIV' ,) ,
            $defaultConfig->enterMode ,
            _t('回车产生的标签'),
            NULL
        );
        $form->addInput($enterMode);

        //皮肤
        $skins = self::getDir(dirname(__FILE__) . '/ckeditor/skins');
        $skins = array_combine($skins, $skins);
        $skin = new Typecho_Widget_Helper_Form_Element_Select(
            'skin' ,
            $skins ,
            in_array($defaultConfig->skin, $skins) ? $defaultConfig->skin : $skins[0] ,
            _t('皮肤'),
            null
        );
        $form->addInput($skin);

        $toolbarCanCollapse = new Typecho_Widget_Helper_Form_Element_Radio(
            'toolbarCanCollapse' ,
            array(
                'true' => '是',
                'false' => '否',
            ),
            $defaultConfig->toolbarCanCollapse ,
            _t('是否可收缩')
        );
        $form->addInput($toolbarCanCollapse);

        $extraPlugins = new Typecho_Widget_Helper_Form_Element_Textarea(
            'extraPlugins',
            NULL, 
            $defaultConfig->extraPlugins,
            _t('需要加载的插件'));

        $extraPlugins->input->setAttribute('style', 'height: 60px;');
        $form->addInput($extraPlugins);
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
        $plugin_options = $options->plugin('Ckeditor4Typecho');
        $pluginRoot = Typecho_Common::url('Ckeditor4Typecho/ckeditor', $options->pluginUrl);
        list($width, $height) = self::getLayoutArr($plugin_options->widthAndHeight);
        $isEdit = isset($_GET['cid']) && $_GET['cid'] > 0 ? 'false' : 'true';

        //调用编辑器
        echo <<<CODE
        <script type="text/javascript" src="{$pluginRoot}/ckeditor.js"></script>
        <script type="text/javascript">
        var ckeditors = CKEDITOR.replace( 'text', {
            toolbar : '{$plugin_options->toolbar}',
            filebrowserUploadUrl : '{$pluginRoot}/upload.php?no_db=1&no_thumb=1&return=ckeditor',
            filebrowserImageUploadUrl : '{$pluginRoot}/upload.php?type=images&no_db=1&no_thumb=1&return=ckeditor',
            extraPlugins : '{$plugin_options->extraPlugins}',
            width: '{$width}',
            height: '{$height}',
            enterMode : {$plugin_options->enterMode},
            skin: '{$plugin_options->skin}',
            toolbarCanCollapse: {$plugin_options->toolbarCanCollapse},
            autoGrow_minHeight : 400
        });

        window.onbeforeunload = function(event){
            if(ckeditors.getData() != '' && {$isEdit}){
              event.returnValue = '即将离开页面，是否确认编辑的内容已使用？';   
            }
        }
        </script>
CODE;

    }
}
