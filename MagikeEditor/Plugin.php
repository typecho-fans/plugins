<?php
/**
 * 简易编辑器，从Magike移植过来的 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package Magike Editor 
 * @author 羽中, Hanny
 * @version 1.1.1
 * @dependence 13.10.18-*
 * @link https://github.com/typecho-fans/plugins/tree/master/MagikeEditor
 *
 * version 1.1.1 at 2020-07-10
 * 修正自定义按键默认值及引号转义问题
 * 修正搭配Attachment插件获取cid问题
 *
 * 历史版本
 * version 1.1.0 at 2014-01-15
 * 支持Typecho 0.9
 * 新方法兼容Attachment插件
 * 小小修改图片的插入方式
 *
 * version 1.0.3 at 2010-10-13
 * 修正一个JS的Bug
 *
 * version 1.0.2 at 2010-10-08
 * 与附件管理器插件相接
 * 多动插入图片的方式选择
 * 允许自定义一些简单按钮
 * 自动转换 http https ftp 地址
 *
 * version 1.0.1 at 2009-12-01
 * 修正一个插入图片的Bug
 *
 * version 1.0.0 at 2009-11-27
 * 完成从Magike的移植
 */
class MagikeEditor_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('MagikeEditor_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('MagikeEditor_Plugin', 'render');
        
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('MagikeEditor_Plugin', 'write');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('MagikeEditor_Plugin', 'write');
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
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('buttons', NULL,
        		"ul,<ul>,</ul>,u\nol,<ol>,</ol>,o\nli,<li>,</li>,l\npage,<!--nextpage-->,,p",
        		_t('自定义按键'), _t('按键参数用逗号隔开，多个按键请用换行符隔开')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('picmode', array("piconly" => "仅插入图片", "piclink" => "插入图片和图片链接"), "piconly",
				_t('图片插入方式'), _t('修改附件中图片的插入方式')));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Checkbox('autourl', array('autourl'=>'自动转换URL'), NULL,
        		_t('URL插入方式'), _t('自动为URL添加链接（包括http:// https:// ftp://）')));
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
    public static function render($post)
    {
        $options = Helper::options();
		$css = Typecho_Common::url('MagikeEditor/magike_style.css?v=1.0.0', $options->pluginUrl);
		$js1 = Typecho_Common::url('MagikeEditor/magike_control.js?v=1.1.0', $options->pluginUrl);
		$js2 = Typecho_Common::url('MagikeEditor/magike_editor.js?v=1.1.0', $options->pluginUrl);
		$autoSave = $options->autoSave ? 'true' : 'false';
		$autoSaveLeaveMessage = '您的内容尚未保存, 是否离开此页面?';
		$resizeUrl = Typecho_Common::url('/action/ajax', $options->index);
		$insMode = 0;
		if (isset($options->plugins['activated']['Attachment'])) {
			$insMode |= (1 << 0);  //如果安装了附件管理器，就改变附件的插入方式
		}
		$picmode = $options->plugin('MagikeEditor')->picmode;
		if ($picmode == "piconly") {
			$insMode |= (1 << 1);
		} else if ($picmode == "piclink") {
			$insMode |= (2 << 1);
		}
		
		echo <<<EOT
<link href="{$css}" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{$js1}"></script>
<script type="text/javascript" src="{$js2}"></script>
<script type="text/javascript">
	$(function() {
		initEditor('text', {$insMode});
		
EOT;
		$buttons = htmlspecialchars($options->plugin('MagikeEditor')->buttons);
		$b_lines = explode("\n", $buttons);
		foreach ($b_lines AS $b_line) {
			if (strlen($b_line) > 0) {
				$b_button = explode(",", $b_line);
				echo "addButton('".trim($b_button[0])."', '".trim($b_button[1])."', '".trim($b_button[2])."', '".trim($b_button[3])."');\r\n";
			}
		}
		echo <<< EOT
	});
</script>
EOT;

    }

    public static function parseCallback($matches)
    {
    	if ($matches[2] != '"' && $matches[2] != '<' && $matches[2] != '>') {  //防止重复识别
			$matches[1] = '<a href="'.$matches[1].'" target="_blank">'.$matches[1].'</a>';
		}
		return $matches[1];
    }
		    
    //URL自动识别
    public static function write($contents, $pedit)
    {
    	$options = Helper::options();
    	$autourl = $options->plugin('MagikeEditor')->autourl;
    	if ($autourl) {
	    	$pattern = '/((\S)*((http)|(https)|(ftp)):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"\s])*)/i';
			$contents['text'] = preg_replace_callback($pattern, array('MagikeEditor_Plugin', 'parseCallback'), $contents['text']);
		}
    	return $contents;
    }

}
