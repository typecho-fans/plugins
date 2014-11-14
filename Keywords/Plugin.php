<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 自动为文章中出现的关键词添加链接
 * @category content
 * @package Keywords
 * @author 羽中
 * @version 1.1.1
 * @dependence 13.12.12-*
 * @link http://www.jzwalk.com/archives/net/keywords-for-typecho
 */
class Keywords_Plugin implements Typecho_Plugin_Interface
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Keywords_Plugin','kwparse');
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
		$keywords = new Typecho_Widget_Helper_Form_Element_Textarea('keywords',NULL,"",_t('关键词链接'),_t('以“关键词”|(英文半角分隔号)“链接”形式填写，每行一组。如：<br/>google|http://www.google.com'));
		$keywords->input->setAttribute('style','width:345px;height:150px;');
		$form->addInput($keywords);
		$tagslink = new Typecho_Widget_Helper_Form_Element_Checkbox('tagslink',array('true'=>'自动替换'),NULL,_t('标签链接'),_t('文中若出现与本站标签相同的关键词则自动添加标签页链接'));
		$form->addInput($tagslink);
		$limits = new Typecho_Widget_Helper_Form_Element_Text('limits',NULL,"1",_t('链接频率'),_t('文中有多个重复关键词或标签时可限制链接替换次数'));
		$limits->input->setAttribute('style','width:60px');
		$form->addInput($limits->addRule('isInteger',_t('请填写整数数字')));
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
	 * 关键词与标签替换
	 * 
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function kwparse($content,$widget,$lastResult)
	{
		$content = empty($lastResult)?$content:$lastResult;

		$db = Typecho_Db::get();
		$settings = Helper::options()->plugin('Keywords');
		$limit = $settings->limits;

		$tagselect = $db->select()->from('table.metas')->where('type=?','tag');
		$tagdata = $db->fetchAll($tagselect,array($widget->widget('Widget_Abstract_Metas'),'filter'));

		$wlsets = explode("\n", $settings->keywords);

		if (!empty($settings->keywords)&&true==strchr($settings->keywords,'|')) {
			foreach ($wlsets as $wlset) {
				$wlarray = explode("|",$wlset);
				$content = preg_replace('/(?!<[^>]*)('.$wlarray[0].')(?![^<]*>)/i','<a href="'.$wlarray[1].'" target="_blank" title="'.$wlarray[0].'">'.$wlarray[0].'</a>',$content,$limit);
			}
		}

		if ($tagdata&&$settings->tagslink) {
			foreach ($tagdata as $tag) {
				$content = preg_replace('/(?!<[^>]*)('.$tag['name'].')(?![^<]*>)/i','<a href="'.$tag['permalink'].'" target="_blank" title="'.$tag['name'].'">'.$tag['name'].'</a>',$content,$limit);
			}
		}

		return $content;
	}

}