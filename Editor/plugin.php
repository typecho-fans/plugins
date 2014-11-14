<?php
/**
 * 另外一款Markdown开源编辑器（By <a href="https://github.com/lepture/editor">@lepture</a>）
 *
 * @category editor
 * @package Editor
 * @author 公子
 * @version 1.0.2
 * @link http://zh.eming.li/#typecho
 */
class Editor_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '1.0.2';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('Editor_Plugin', 'Change');
		Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('Editor_Plugin', 'Change');

    }

    public static function Change() 
    {
    	$options = Helper::options();
        $jsUrl = Typecho_Common::url('Editor/editor.js', $options->pluginUrl);
        $cssUrl = Typecho_Common::url('Editor/editor.css', $options->pluginUrl);
    	?>
    	<link rel="stylesheet" href="<?php echo $cssUrl; ?>" />
    	<script type="text/javascript" src="http://lab.lepture.com/editor/marked.js"></script>
		<script type="text/javascript" src="<?php echo $jsUrl; ?>"></script>
		<script>var editor = new Editor();editor.render();</script>
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

