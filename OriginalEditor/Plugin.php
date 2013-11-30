<?php
/**
 * 禁止Typecho使用Markdown编辑器回归老版纯HTML模式
 *
 * @package OriginalEditor
 * @author 公子
 * @version 1.0.0
 * @link http://zh.eming.li/#typecho
 */

class OriginalEditor_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '0.0.1';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
    	//forbidden markdown editor
		Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('OriginalEditor_Plugin', 'forbidden');
		Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('OriginalEditor_Plugin', 'forbidden');

		//add preview
		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('OriginalEditor_Plugin', 'preview');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('OriginalEditor_Plugin', 'preview');

		//forbidden markdwon parse
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('OriginalEditor_Plugin', 'unparse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('OriginalEditor_Plugin', 'unparse');
    }

    public static function forbidden() 
    {
	}
	
	public static function preview()
	{
		?>
		<script type="text/javascript">
			$('p.submit').after('<div id="wmd-preview"></div>');
			$('textarea#text').on('input propertychange', function() {
				$('#wmd-preview').html($(this).val());
			});
		</script>
		<?php
	}

	public static function unparse($text, $widget, $lastResult) 
	{
        $text = empty($lastResult) ? $text : $lastResult;
        return $text;
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

