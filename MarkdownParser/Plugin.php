<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 主流的PHP MarkeDown 解析插件
 *
 * @package MarkeDownParser
 * @author Gourd
 * @version 1.0.0
 * @link http://yutonger.com
 */
class MarkdownParser_Plugin implements Typecho_Plugin_Interface
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->markdown = array('MarkdownParser_Plugin', 'markdown');
		Typecho_Plugin::factory('Widget_Abstract_Comments')->markdown = array('MarkdownParser_Plugin', 'markdown');
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
		$parserType = new Typecho_Widget_Helper_Form_Element_Radio('parserType',
			array(
				'Michelf' => 'michelf/php-markdown',
				'Parsedown' => 'erusev/parsedown',
			),
			'Michelf',
			_t('选择Markdown Parser : '),
			_t('插件将替换Typecho原生的 Markdown 解析，具体解析可以参考 <a href="https://github.com/michelf/php-markdown" '.
				'target="_blank">michelf/php-markdown</a> 和 <a href="https://github.com/erusev/parsedown" target="_blank">erusev/parsedown</a>'));
		$form->addInput($parserType);
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
	public static function render(){}

	/**
	 * MakeDown Parser
	 * @param $text
	 * @return string
	 */
	public static function markdown($text){
		$config = Helper::options()->plugin('MarkdownParser');
		$parserType = $config->parserType ? $config->parserType : 'Michelf';

		if($parserType == '')
		{
			require_once __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__ . '/MarkdownParser/Michelf/MarkdownExtra.inc.php';
			return \Michelf\MarkdownExtra::defaultTransform($text);
		}
		else
		{
			require_once __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__ . '/MarkdownParser/Parsedown/Parsedown.php';
			$Parsedown = new Parsedown();
			return $Parsedown->text($text);
		}


	}
}
