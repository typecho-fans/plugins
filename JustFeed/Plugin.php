<?php
/**
 * Just Feed
 * 
 * @package JustFeed
 * @author jKey
 * @version 0.1.2
 * @link http://typecho.jkey.lu/
 * @fix the 'date' bug by https://eallion.com, update to 0.1.2 -20190412
 * @license GPL3
 */

class JustFeed_Plugin implements Typecho_Plugin_Interface
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
		Helper::removeRoute('feed');
		Helper::addRoute('feed', '/feed[feed:string:0]', 'JustFeed_Widget', 'feed');
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
		Helper::removeRoute('feed');
		Helper::addRoute('feed', '/feed[feed:string:0]', 'Widget_Archive', 'feed');
	}

	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		$cfg_copyright = new Typecho_Widget_Helper_Form_Element_Textarea(
			'cfg_copyright',
			NULL,
			'<hr /><small>Copyright &copy; <a href="{authorurl}" target="_blank">{author}</a> for <a href="{siteurl}" target="_blank">{sitetitle}</a> 2010 | <a href="{permalink}" target="_blank">Permalink</a> | {commentsnumber} 条评论</small>',
			'在 feed 尾部添加',
			'可用标记：{sitetitle}{siteurl}{author}{authorurl}{permalink}{date}{time}{commentsnumber}<br /><br />Example: &lt;a href="{permalink}#comments" title="to the comments"&gt;To the comments&lt;/a&gt;, Author: &lt;a href="{authorlink}" &gt;{author}&lt;/a&gt;'
		);
		$form->addInput($cfg_copyright);
		
/* 		$cfg_related_post = new Typecho_Widget_Helper_Form_Element_Checkbox(
			'cfg_related_post',
			array('show' => '是否在 feed 尾部显示相关内容？'),
			NULL,
			'相关日志'
		);
		$form->addInput($cfg_related_post);
		
		$cfg_related_post_num = new Typecho_Widget_Helper_Form_Element_Text(
			'cfg_related_post_num',
			NULL,
			'5',
			'日志数量'
		);
		$form->addInput($cfg_related_post_num); */
	}

	/**
	 * 个人用户的配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}