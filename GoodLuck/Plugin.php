<?php
/**
 * 随机跳转一篇文章
 *
 * @package GoodLuck
 * @author Ryan
 * @version 1.0.1
 * @link http://blog.iplayloli.com/typecho-plugin-goodluck
 */
 class GoodLuck_Plugin implements Typecho_Plugin_Interface
 {
	 /**
	 * execute function.
	 *
	 * @access public
	 * @return void
	 */
	public function execute(){}
	 /**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 *
	 * @access public
	 * @return String
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Helper::addRoute('goodluck', '/goodluck', 'GoodLuck_Plugin', 'goodluck');
		return('插件已经成功激活!');
	}
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return String
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate()
	{
		Helper::removeRoute('goodluck');
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
	 * 手气不错核心函数
	 *
	 * @access public
	 * @return void
	 */
	public static function goodluck() {
		$db = Typecho_Db::get();
		$sql = $db->select('MAX(cid)')->from('table.contents')
			->where('status = ?','publish')
			->where('type = ?', 'post')
			->where('created <= unix_timestamp(now())', 'post');
		$result = $db->fetchAll($sql);
		$max_id = $result[0]['MAX(`cid`)'];//POST类型数据最大的CID
		$sql = $db->select('MIN(cid)')->from('table.contents')
			->where('status = ?','publish')
			->where('type = ?', 'post')
			->where('created <= unix_timestamp(now())', 'post');
		$result = $db->fetchAll($sql);
		$min_id = $result[0]['MIN(`cid`)'];//POST类型数据最小的CID
		$result = NULL;
		while($result == NULL) {
			$rand_id = mt_rand($min_id,$max_id);
			$sql = $db->select()->from('table.contents')
				->where('status = ?','publish')
				->where('type = ?', 'post')
				->where('created <= unix_timestamp(now())', 'post')
				->where('cid = ?',$rand_id);
			$result = $db->fetchAll($sql);
		}
		$target = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($result['0']);
		Typecho_Response::redirect($target['permalink'], 307);
	}
 }
