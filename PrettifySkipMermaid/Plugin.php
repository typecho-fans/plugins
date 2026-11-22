<?php
/**
 * Google代码高亮（跳过 Mermaid 图表）
 * 
 * @package PrettifySkipMermaid
 * @author heky12356
 * @version 2.0.2
 * @link https://github.com/heky12356
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// Based on GoogleCodePrettify v2.0.0 by 公子 (https://github.com/lizheming).
class PrettifySkipMermaid_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('PrettifySkipMermaid_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('PrettifySkipMermaid_Plugin', 'footer');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
	public static function config(Typecho_Widget_Helper_Form $form)
    {
	    $color = array('desert' => _t('Desert'),
	    			   'doxy' => _t('Doxy'),
	    			   'sons-of-obsidian' => _t('Sons of obsidian'),
	    			   'sunburst' => _t('Sunburst'),
	    			   'github' => _t('Github'));
		$type = new Typecho_Widget_Helper_Form_Element_Select('type', $color,'desert',_t('请选择代码配色样式'));
    	$form->addInput($type);

    	$textarea = new Typecho_Widget_Helper_Form_Element_Textarea('custom', NULL, NULL, _t('自定义CSS代码'));
    	$form->addInput($textarea);
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
     * 输出头部css
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function header() {
    	$config = Helper::options()->plugin('PrettifySkipMermaid');
    	$type = $config->type;
    	$custom = $config->custom;
    	$cssUrl = Helper::options()->pluginUrl . '/PrettifySkipMermaid/src/' . $type . '.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
        if($custom != '') echo "<style type=\"text/css\">$custom</style>";
    }
    
    /**
     * 输出尾部js
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function footer() {
        $jsUrl = Helper::options()->pluginUrl . '/PrettifySkipMermaid/prettify.js';
        echo '<script type="text/javascript" src="'.$jsUrl.'"></script>';
        echo <<<'HTML'
<script type="text/javascript">
window.addEventListener('load', function () {
    var pre = document.getElementsByTagName('pre');
    var mermaidSelector = '.mermaid, .language-mermaid, .lang-mermaid';

    for (var i = 0; i < pre.length; i++) {
        var block = pre[i];

        // Mermaid must receive the complete source, without highlighting or line numbers.
        if (block.matches(mermaidSelector) || block.querySelector(mermaidSelector)) {
            block.classList.remove('prettyprint', 'linenums');
            continue;
        }

        block.classList.add('prettyprint', 'linenums');
    }

    prettyPrint();
});
</script>
HTML;
    }
    
}
