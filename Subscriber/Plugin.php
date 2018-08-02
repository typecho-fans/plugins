<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 配合自定义字段功能实现指定内容仅注册会员可读。
 * 
 * @package Subscriber
 * @author 羽中
 * @version 1.0.0beta
 * @dependence 13.12.12-*
 * @link http://www.jzwalk.com/archives/net/subscriber-for-typecho
 */
class Subscriber_Plugin implements Typecho_Plugin_Interface
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Subscriber_Plugin','filtcontent');
		Typecho_Plugin::factory('admin/common.php')->begin = array('Subscriber_Plugin','fieldset');
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
		$vispost = new Typecho_Widget_Helper_Form_Element_Checkbox('vispost',
		self::visposts(),array(),_t('公开文章列表→'),_t('<span style="position:relative;left:330px;bottom:80px;">[勾选切换]</span>'));
		$form->addInput($vispost->multiMode()->setAttribute('style','float:left;width:300px;'));
		
		$subpost = new Typecho_Widget_Helper_Form_Element_Checkbox('subpost',
		self::subposts(),array(),_t('←会员文章列表'),_t(''));
		$form->addInput($subpost->multiMode()->setAttribute('style','float:right;width:300px;'));
		
		$submsgp = new Typecho_Widget_Helper_Form_Element_Textarea('submsgp',NULL,'<div class="sub2view"><i class="icon-lock"></i>本文仅对注册会员开放阅读。</div>',_t('会员文章隐藏提示'),_t('未登录访客看到的会员文章显示，可使用html鼓励注册'));
		$form->addInput($submsgp->setAttribute('style','clear:both;'));
		
		$submsga1 = new Typecho_Widget_Helper_Form_Element_Textarea('submsga1',NULL,'<div class="sub2view"><i class="icon-lock"></i>此处内容需要登录才能查看。</div>',_t('会员内容隐藏提示'),_t('公开文章中用[sub][/sub]隐藏的会员内容对访客的提示'));
		$form->addInput($submsga1);
		
		$submsga2 = new Typecho_Widget_Helper_Form_Element_Textarea('submsga2',NULL,'<p>您已登录，可以阅读以下内容：</p><div class="sub2view"><i class="icon-lock-open"></i>{content}</div>',_t('会员内容可见效果'),_t('已登录会员看到隐藏内容显示效果(勿删改{content}标签)'));
		$form->addInput($submsga2);
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
	 * 内容输出过滤
	 * 
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function filtcontent($content,$widget)
	{
		$option = Helper::options()->plugin('Subscriber');

		$subcs = str_replace('{content}','$1',$option->submsga2);

		$contents = ($widget->widget('Widget_User')->hasLogin())?
					preg_replace("/\[sub\](.*?)\[\/sub\]/sm",''.$subcs.'',$content):
					''.$option->submsgp.'';

		$content = isset($widget->fields->sub)?$contents:$content;

		$content = $widget->widget('Widget_User')->hasLogin()?
			preg_replace("/\[sub\](.*?)\[\/sub\]/sm",''.$subcs.'',$content):
			preg_replace("/\[sub\](.*?)\[\/sub\]/sm",''.$option->submsga1.'',$content);

		return $content;
	}

	/**
	 * 设置自定义字段
	 * 
	 * @access public
	 * @return void
	 */
	public static function fieldset()
	{
		$widget = Typecho_Widget::widget('Widget_Archive');
		$option = Helper::options()->plugin('Subscriber');

		if (($option->vispost)) {
			$sets = $option->vispost;
			foreach ($sets as $set) {
				$widget->setField('sub','str','',$set);}
			}

		if (($option->subpost)) {
			$db = Typecho_Db::get();
			$cids = implode(",",$option->subpost);
			$db->query($db->delete('table.fields')->where('table.fields.cid in ('.$cids.')'));
			}
	}

	/**
	 * 获取公开文章列表
	 * 
	 * @access private
	 * @return array
	 */
	private static function subposts()
	{
		$db = Typecho_Db::get();

		$subdata = $db->fetchAll($db
		->select('table.contents.cid','table.contents.title')->from('table.contents')
		->join('table.fields','table.fields.cid = table.contents.cid',Typecho_Db::INNER_JOIN)
		->where('table.contents.type=?','post')
		->where('table.fields.name=?','sub'));

		$subposts = array();
		foreach($subdata as $items){
			$subposts[$items['cid']]=($items['title']);
		}

		return $subposts;
	}

	/**
	 * 获取会员文章列表
	 * 
	 * @access private
	 * @return array
	 */
	private static function visposts()
	{
		$db = Typecho_Db::get();

		$alldata = $db->fetchAll($db
		->select('table.contents.cid','table.contents.title')->from('table.contents')
		->where('table.contents.type=?','post'));

		$allposts = array();
		foreach($alldata as $itema){
			$allposts[$itema['cid']]=($itema['title']);
		}

		$visposts = array_diff($allposts,self::subposts());

		return $visposts;
	}

}
