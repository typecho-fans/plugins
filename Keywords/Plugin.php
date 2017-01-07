<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为文中的指定关键词添加链接
 * 
 * @package Keywords
 * @author 羽中
 * @version 1.0.7
 * @dependence 14.10.10
 * @link http://www.yzmb.me/archives/net/keywords-for-typecho
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Keywords_Plugin','kwparse');
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
		$keywords = new Typecho_Widget_Helper_Form_Element_Textarea('keywords',NULL,'',_t('关键词链接'),_t('以"关键词|(半角分隔号)链接"的形式填写, 每行一组<br/>例: google|http://www.google.com'));
		$keywords->input->setAttribute('style','width:400px;height:150px');
		$form->addInput($keywords);

		$tagslink = new Typecho_Widget_Helper_Form_Element_Checkbox('tagslink',array(1=>_t('自动替换')),NULL,_t('标签链接'),_t('将与本站标签相同的关键词自动替换为标签页链接'));
		$form->addInput($tagslink);

		$limits = new Typecho_Widget_Helper_Form_Element_Text('limits',NULL,'1',_t('链接频次'),_t('文中有多个重复的关键词或标签时可限制链接次数'));
		$limits->input->setAttribute('style','width:40px');
		$limits->addRule('required',_t('链接次数不能为空'));
		$form->addInput($limits->addRule('isInteger',_t('请填写整数数字')));

		$pagelinks = new Typecho_Widget_Helper_Form_Element_Radio('pagelinks',array(1=>_t('是'),0=>_t('否')),1,_t('在页面使用'),_t('是否将以上的链接替换设置也作用于独立页面内容'));
		$form->addInput($pagelinks);
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
	 * 执行数据替换
	 * 
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function kwparse($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;
		$keywords = self::keywords();

		if ($widget instanceof Widget_Archive && $keywords) {
			$settings = Helper::options()->plugin('Keywords');

			//关闭页面替换
			if ($widget->is('page') && !$settings->pagelinks) {
				return $content;
			}

			foreach($keywords as $i=>$row) {
				if (false!==strpos($content,$row['0'])) {
					$content = preg_replace('/(?!<[^>]*)'.$row['0'].'(?![^<]*(>|<\/[a|sc]))/s' //排除参数与链接
					,'<a href="'.$row['1'].'" target="_blank" title="'.$row['0'].'">'.$row['0'].'</a>',$content,$settings->limits);
				}
			}
		}

		return $content;
	}

	/**
	 * 输出关键词数据
	 * 
	 * @access private
	 * @return array
	 */
	private static function keywords()
	{
		$settings = Helper::options()->plugin('Keywords');
		$kwarray = array();
		$keyword = trim(Typecho_Common::stripTags($settings->keywords));

		if ($keyword && strpos($keyword,'|')) {
			//解析关键词数组
			$kwsets = array_filter(preg_split("/(\r|\n|\r\n)/",$keyword));
			foreach ($kwsets as $kwset) {
				$kwarray[] = explode('|',trim($kwset));
			}
		}

		if ($settings->tagslink) {
			$db = Typecho_Db::get();
			$tagselect = $db->select()->from('table.metas')->where('type = ?','tag');
			$tagdata = $db->fetchAll($tagselect,array(Typecho_Widget::widget('Widget_Abstract_Metas'),'filter'));

			//并入标签链接
			if ($tagdata) {
				$tags = array();
				foreach ($tagdata as $tag) {
					$tags[] = array($tag['name'],$tag['permalink']);
				}
				$kwarray = array_merge($kwarray,$tags);
			}
		}

		if ($kwarray) {
			//优先处理长词
			usort($kwarray,function($a,$b){
				return (strlen($a['0']) < strlen($b['0'])) ? 1 : -1;
			});
		}

		return $kwarray;
	}

}