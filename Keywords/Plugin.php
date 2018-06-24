<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为文中的指定关键词添加链接
 * 
 * @package Keywords
 * @author 羽中
 * @version 1.0.8
 * @dependence 13.12.12-*
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
		$keywords = new Typecho_Widget_Helper_Form_Element_Textarea('keywords',NULL,'',_t('关键词链接'),_t('每行1组以"关键词<strong style="color:#467B96;">|</strong>(半角竖线)链接"形式填写, 可用第2个竖线追加参数: </br>
		<strong style="color:#467B96;">n</strong>代表nofollow标记, <strong style="color:#467B96;">e</strong>代表external nofollow标记, <strong style="color:#467B96;">b</strong>代表本窗口打开. 例: <br/>google<strong>|</strong>http://www.google.com<strong>|</strong>n 即此链接带nofollow(默认新窗口打开)'));
		$keywords->input->setAttribute('style','max-width:400px;height:150px;');
		$form->addInput($keywords);

		$autolink = new Typecho_Widget_Helper_Form_Element_Checkbox('autolink',array('catslink'=>_t('分类名称'),'tagslink'=>_t('标签名称')),NULL,_t('自动内链'),_t('将与分类/标签名相同的词替换为分类/标签页链接'));
		$form->addInput($autolink);

		$nofollow = new Typecho_Widget_Helper_Form_Element_Checkbox('nofollow',
		array(1=>_t('nofollow标记')),NULL,_t('内链设置'));
		$form->addInput($nofollow);

		$blank = new Typecho_Widget_Helper_Form_Element_Select('blank',
		array(0=>_t('本窗口打开'),1=>_t('新窗口打开')),0,'');
		$blank->input->setAttribute('style','position:absolute;bottom:11px;left:115px;');
		$blank->setAttribute('style','position:relative;');
		$form->addInput($blank);

		$limits = new Typecho_Widget_Helper_Form_Element_Text('limits',NULL,'1',_t('链接频次'),_t('文中有多个重复关键词时可指定替换为链接的次数'));
		$limits->input->setAttribute('style','width:40px;');
		$limits->addRule('required',_t('链接次数不能为空'));
		$form->addInput($limits->addRule('isInteger',_t('请填写整数数字')));

		$pagelinks = new Typecho_Widget_Helper_Form_Element_Radio('pagelinks',array(1=>_t('是'),0=>_t('否')),1,_t('页面使用'),_t('除文章外是否将替换链接效果作用于独立页面内容'));
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

			//关闭页面内容替换
			if ($widget->is('page') && !$settings->pagelinks) {
				return $content;
			}
			foreach ($keywords as $i=>$row) {
				$txt = trim($row['0']);
				if ($txt) {
					$link = trim($row['1']);
					$set = trim($row['2']);
					$rel = '';
					$open = '_blank';

					//处理标记与打开方式
					 if ($set) {
					 	 if (false!==stripos($set,'e')) {
					 	 	 $rel = ' rel="external nofollow"';
					 	 } elseif (false!==stripos($set,'n')) {
					 	 	 $rel = ' rel="nofollow"';
					 	 }
					 	 $open = false!==stripos($set,'b') ? '_self' : $open;
					 }

					$content = false!==strpos($content,$txt)
						//正则排除参数和链接
						? preg_replace('/(?!<[^>]*)'.$txt.'(?![^<]*(>|<\/[a|sc]))/s'
					,'<a href="'.$link.'"'.$rel. 'target="'.$open.'" title="'.$txt.'">'.$txt.'</a>',$content,$settings->limits) : $content;
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
		$autolink = $settings->autolink;
		$kwarray = array();
		$keyword = trim(Typecho_Common::stripTags($settings->keywords));

		if (strpos($keyword,'|')) {
			//解析关键词数组
			$kwsets = array_filter(preg_split("/(\r|\n|\r\n)/",$keyword));
			foreach ($kwsets as $kwset) {
				$kwarray[] = explode('|',$kwset);
			}
		}

		if ($autolink) {
			$db = Typecho_Db::get();
			$nofollow = $settings->nofollow ? 'n' : '';
			$blank = $settings->blank ? '' : 'b';

			if (in_array('catslink',$autolink)) {
				$catselect = $db->select()->from('table.metas')->where('type = ?','category');
				$catdata = $db->fetchAll($catselect,array(Typecho_Widget::widget('Widget_Abstract_Metas'),'filter'));

				//并入分类链接
				$cats = array();
				foreach ($catdata as $cat) {
					$cats[] = array($cat['name'],$cat['permalink'],$nofollow.$blank);
				}
				$kwarray = array_merge($kwarray,$cats);
			}

			if (in_array('tagslink',$autolink)) {
				$tagselect = $db->select()->from('table.metas')->where('type = ?','tag');
				$tagdata = $db->fetchAll($tagselect,array(Typecho_Widget::widget('Widget_Abstract_Metas'),'filter'));

				//并入标签链接
				if ($tagdata) {
					$tags = array();
					foreach ($tagdata as $tag) {
						$tags[] = array($tag['name'],$tag['permalink'],$nofollow.$blank);
					}
					$kwarray = array_merge($kwarray,$tags);
				}
			}
		}

		if ($kwarray) {
			//优先处理长词
			usort($kwarray,array(new Keywords_Plugin,'lsort'));
		}

		return $kwarray;
	}

	/**
	 * 按字符长短排序
	 * 
	 * @access private
	 * @return integer
	 */
	private static function lsort($a,$b) {
		return strlen($a['0'])<strlen($b['0']) ? 1 : -1;
	}

}