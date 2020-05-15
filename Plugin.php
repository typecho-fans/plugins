<?php
/**
 * 随机跳转一篇文章
 *
 * @package GoodLuck
 * @author Ryan
 * @version 1.0.2
 * @link http://blog.iplayloli.com/typecho-plugin-goodluck
 */
 class GoodLuck_Plugin implements Typecho_Plugin_Interface {
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
	public static function activate() {
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
	public static function deactivate() {
		Helper::removeRoute('goodluck');
	}
	/**
	 * 获取插件配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form) {
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
		$rand_ids = Typecho_Cookie::get('contents_rand_ids');//获取最近5篇随机展示文章ID
		if (empty($rand_ids)) {
			$rand_ids = array();
		} else {
			$rand_ids = explode(',', $rand_ids);
		}
		$times_out = 0;//计算循环次数
		$target = Typecho_Widget::widget('Widget_Options')->siteUrl;//默认跳转首页
		while($result == NULL) {
			$times_out++;//循环计数
			$rand_id = mt_rand($min_id,$max_id);
			//查询数据
			$sql = $db->select()->from('table.contents')
				->where('status = ?','publish')
				->where('type = ?', 'post')
				->where('created <= unix_timestamp(now())', 'post')
				->where('cid = ?',$rand_id);
			$result = $db->fetchAll($sql);
			if (in_array($rand_id, $rand_ids)) {
				$result = NULL;
			} else {
				if($result !=NULL) {
					$rand_ids = array_push($rand_ids, $rand_id);
					if (count($rand_ids) == 5) {
						unset($rand_ids[0]);
						$rand_ids = array_values($rand_ids);
					}
					$rand_ids = implode(',', $rand_ids);
					Typecho_Cookie::set("contents_rand_ids",$rand_ids);
					$target = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($result['0']);
				}
			}
			//超过100次不干了
			if ($times_out > 100)
				break;
		}
		header("Cache-Control: no-store, no-cache, must-revalidate");
		Typecho_Response::redirect($target['permalink'], 307);
	}
}
