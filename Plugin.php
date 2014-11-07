<?php
/**
 * 集成CkEditor编辑器,支持上传图片功能
 * 
 * @package Ckeditor4Typecho
 * @author zhulin3141
 * @version 1.0.0
 * @link http://zhulin31410.blog.163.com/
 */
class Ckeditor4Typecho_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 默认设置的值
     */
    private static $_defaultConfig = array(
        'width' => 850,
        'height' => 400,
        'tool_style' => 'SIMPLE',
    );

    private static function getDefaultConfig(){
        return (object)self::$_defaultConfig;
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
        $width = new Typecho_Widget_Helper_Form_Element_Text('width', NULL, $defaultConfig->width, _t('设置宽度'));
        $form->addInput($width);

        $height = new Typecho_Widget_Helper_Form_Element_Text('height', NULL, $defaultConfig->height, _t('设置高度'));
        $form->addInput($height);

        //*工具栏按钮样式
        $tool_style = new Typecho_Widget_Helper_Form_Element_Select(
            'tool_style' ,
            array(
                'STANDARD' => '标准模式' ,
                'SIMPLE' => '简单模式' ,
                'MINI' => '迷你模式' ,
            ) ,
            $defaultConfig->tool_style , 
            _t('工具按钮'),
            _t('工具栏按钮设置')
        );
        $form->addInput($tool_style);
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
        
        //调用编辑器
        echo <<<CODE
        <script type="text/javascript" src="{$pluginRoot}/ckeditor.js"></script>
        <script type="text/javascript">
        var ckeditors = CKEDITOR.replace( 'text', {
            toolbar : '{$plugin_options->tool_style}',
            filebrowserUploadUrl : '{$pluginRoot}/upload.php?no_db=1&no_thumb=1&return=ckeditor',
            filebrowserImageUploadUrl : '{$pluginRoot}/upload.php?type=images&no_db=1&no_thumb=1&return=ckeditor',
            extraPlugins : 'autogrow',
            width: {$plugin_options->width},
            height: {$plugin_options->height},
            autoGrow_minHeight : 400
        });

        window.onbeforeunload = function(event){   
            if(ckeditors.getData() != ''){   
              event.returnValue = '即将离开页面，是否确认编辑的内容已使用？';   
            }
        }
        </script>
CODE;

    }
}
