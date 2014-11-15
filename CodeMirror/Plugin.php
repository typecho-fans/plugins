<?php
/**
 * 主题编辑器
 * 
 * @category editor
 * @package CodeMirror
 * @author zhulin3141
 * @version 1.0.0
 * @link http://zhulin31410.blog.163.com/
 */
class CodeMirror_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/theme-editor.php')->bottom = array('CodeMirror_Plugin', 'render');
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
    public static function config(Typecho_Widget_Helper_Form $form){}

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
    public static function render($post)
    {
        $options = Helper::options();
        // $plugin_options = $options->plugin('CodeMirror');
        $pluginRoot = Typecho_Common::url('CodeMirror/static', $options->pluginUrl);
        
        //调用编辑器
        echo <<<CODE
        <link rel="stylesheet" href="{$pluginRoot}/codemirror.css">
        <script type="text/javascript" src="{$pluginRoot}/codemirror.js"></script>
        <script>
            var editor = CodeMirror.fromTextArea(document.getElementById('content'), {
                lineNumbers: true
            });
        </script>
CODE;
    }
}
