<?php
/**
 * 页面浏览次数统计插件 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package Stat
 * @author 羽中, Jozhn, Hanny
 * @version 1.0.4
 * @dependence 17.10.30-*
 * @link https://github.com/typecho-fans/plugins/tree/master/Stat
 *
 * version 1.0.4 at 2020-07-08
 * 增加计数输出和忽略重复访问功能
 * 增加排行输出可指定分类ID与类型
 *
 * version 1.0.3 at 2018-08-24
 * 修复PDO下数据表检测失败的错误
 *
 * 历史版本
 * version 1.0.2 at 2010-07-03
 * 终于支持前台调用了
 * 接口支持Typecho 0.8的计数
 * 增加SQLite的支持
 *
 * version 1.0.1 at 2010-01-02
 * 修改安装出错处理
 * 修改安装时默认值错误
 *
 * version 1.0.0 at 2009-12-12
 * 实现浏览次数统计的基本功能
 *
 */
class Stat_Plugin implements Typecho_Plugin_Interface
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
		$info = Stat_Plugin::sqlInstall();
		Typecho_Plugin::factory('Widget_Archive')->singleHandle = array('Stat_Plugin', 'singleHandle');
		Typecho_Plugin::factory('Widget_Archive')->select = array('Stat_Plugin', 'selectHandle');
		/* 模版调用钩子 */
		Typecho_Plugin::factory('Widget_Archive')->callStat = array('Stat_Plugin', 'outputStat');
		/* 输出排行支持4种参数如: <?php $this->rank(
			'源码含{link}{title}',
			'类型如post/page/attachment',
			'分类或标签mid用逗号隔开',
			条目数
			); ?>  */
		Typecho_Plugin::factory('Widget_Archive')->callRank = array('Stat_Plugin', 'outputRank');
		return _t($info);
	}

	//SQL创建
	public static function sqlInstall()
	{
		$db = Typecho_Db::get();
		$type = explode('_', $db->getAdapterName());
		$type = array_pop($type);
		$prefix = $db->getPrefix();
		try {
			$select = $db->select('table.contents.views')->from('table.contents');
			$db->query($select, Typecho_Db::READ);
			return '检测到统计字段，插件启用成功';
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql' == $type && ('42S22'==$code || 1054 == $code)) ||
					('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
				try {
					if ('Mysql' == $type) {
						$db->query("ALTER TABLE `".$prefix."contents` ADD `views` INT( 10 ) NOT NULL  DEFAULT '0' COMMENT '页面浏览次数';");
					} else if ('SQLite' == $type) {
						$db->query("ALTER TABLE `".$prefix."contents` ADD `views` INT( 10 ) NOT NULL  DEFAULT '0'");
					} else {
						throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
					}
					return '建立统计字段，插件启用成功';
				} catch (Typecho_Db_Exception $e) {
					$code = $e->getCode();
					if(('Mysql' == $type && ('42S21'==$code || 1060 == $code)) ) {
						return '统计字段已经存在，插件启用成功';
					}
					throw new Typecho_Plugin_Exception('统计插件启用失败。错误号：'.$code);
				}
			}
			throw new Typecho_Plugin_Exception('数据表检测失败，统计插件启用失败。错误号：'.$code);
		}
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
    public static function config(Typecho_Widget_Helper_Form $form){}
      
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
    public static function outputStat($widget)
    {
		$db = Typecho_Db::get();
		$row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $widget->cid));
		echo $row['views'];
    }

    public static function viewStat($cid)
    {
		$name = '__StatViews';
		$cids = Json::decode(Typecho_Cookie::get($name), true);
		if (!empty($cids) && in_array($cid, $cids)) {
			return;
		} else {
			if (!$cids) $cids = array();
			array_push($cids, $cid);
			Typecho_Cookie::set($name, Json::encode($cids), time()+60*60); //默认60分钟可自行修改

			$db = Typecho_Db::get();
			$prefix  = $db->getPrefix();
			$sql = "UPDATE `".$prefix."contents` SET `views` = `views` + 1 WHERE `cid` = ".intval($cid).";";
			$db->query($sql);
		}
    }

	public static function selectHandle($archive)
	{
		$db = Typecho_Db::get();
		$options = Typecho_Widget::widget('Widget_Options');
		return $db->select('*')->from('table.contents')->where('table.contents.status = ?', 'publish')
                ->where('table.contents.created < ?', $options->gmtTime);
	}

    public static function singleHandle($select, $archive)
    {
		Stat_Plugin::viewStat($select->stack[0]['cid']);
    }

    public static function outputRank($widget, array $params)
    {
		$db = Typecho_Db::get();
		$items = $db->fetchAll($db->select('table.metas.mid')->from('table.metas')
			->where('table.metas.type = ?', 'category'));
		foreach ($items as $item) {
			$all[] = $item['mid'];
		}

		$format = !empty($params[0]) && is_string($params[0]) ? $params[0] : '<li><a href="{permalink}">{title}</a></li>';
		$type = !empty($params[1]) && is_string($params[1]) ? $params[1] : 'post';
		$category = !empty($params[2]) && is_string($params[2]) ? $params[2] : 'ALL';
		$limit = !empty($params[3]) && is_numeric($params[3]) ? $params[3] : 10;

		if ($type == 'post') {
			$select = $db->select()->from('table.contents')
			->join('table.relationships', 'table.relationships.cid = table.contents.cid',Typecho_Db::INNER_JOIN)
			->where('table.contents.status = ?', 'publish')
			->where('table.relationships.mid in ('.($category=='ALL' ? implode(',', $all) : $category).')')
			->order('views', Typecho_Db::SORT_DESC)
			->limit($limit);
		} else {
			$select = $db->select()->from('table.contents')
			->where('table.contents.status = ?', 'publish')
			->where('table.contents.type = ?', ''.$type.'')
			->order('views', Typecho_Db::SORT_DESC)
			->limit($limit);
		}
		$posts = $db->fetchAll($select);

		foreach ($posts as $post) {
			$value = Typecho_Widget::widget('Widget_Abstract_Contents')->push($post);
			echo str_replace(array('{link}','{title}'), array($value['permalink'], $value['title']), $format);
		}
    }
}
