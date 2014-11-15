<?php
/**
 * 修改Markdown编辑器为左右样式
 *
 * @category editor
 * @package LREditor
 * @author Austin
 * @version 0.0.3
 * @link http://zh.eming.li/#typecho
 */
class LREditor_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '0.0.3';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('LREditor_Plugin', 'Change');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('LREditor_Plugin', 'Change');

    }

    public static function Change() 
    {
    	$options		= Helper::options();
    	$cssUrl			= Typecho_Common::url('LREditor/lr.css', $options->pluginUrl);
        $jsUrl          = Typecho_Common::url('LREditor/prettify.js', $options->pluginUrl);

    	echo '<link rel="stylesheet" type="text/css" href="'.$cssUrl.'" />';
        echo '<script type="text/javascript" src="'.$jsUrl.'"></script>';
 		?>

 		<script>
            function prettify() {
                $("pre").addClass("prettyprint");
                prettyPrint();
            }
 			$(function() {
 				setInterval("$('#wmd-preview').css('height', (parseInt($('#text').height()) - 5)+'px');", 500);
                setInterval("prettify()", 10);

 				//Typecho.editorResize('wmd-preview', '<?php $options->index('/action/ajax?do=editorResize'); ?>');
 				//$('#wmd-preview').remove();
 				//$('textarea').after('<div id="wmd-preview"></div>');
 			});
 		</script>
 		<?php
	}
	
    public static function config(Typecho_Widget_Helper_Form $form)
    {
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
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

 

}

