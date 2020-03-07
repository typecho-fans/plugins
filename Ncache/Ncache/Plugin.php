<?php
/**
 * Nginx fastcgi 缓存
 * 
 * @package Ncache
 * @author WeiCN
 * @version 1.1
 * @link https://cuojue.org
 */
class Ncache_Plugin implements Typecho_Plugin_Interface
{ 
    private static $pluginName = 'Ncache';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('Widget_Feedback')->finishComment = array(self::$pluginName . '_Plugin', 'delCache');
		Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array(self::$pluginName . '_Plugin', 'delCache');
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array(self::$pluginName . '_Plugin', 'delCache');
        return _t('请进行<a href="options-plugin.php?config='.self::$pluginName.'">初始化设置</a>');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){

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
        $selectArr = array('\/$|\/page\/\d'=>'首页','\/category'=>'分类','\/archive'=>'内容页','\/tags'=>'标签页面'          );
        $element = new Typecho_Widget_Helper_Form_Element_Checkbox('cacheType', $selectArr, array(),'自动更新缓存项目', '更新文章后，刷新哪部分缓存');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio(
          'permalink', array(1 => '启用', 0 => '禁用'), 0,
          '地址重写', 'typecho <a href="options-permalink.php">永久链接设置</a> 需要与本处设置完全一致');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Textarea('addurl', NULL, "?_pjax=%23content",
			_t('Url后缀'), _t('多条后缀请用换行符隔开<br />如果启用了pjax之类的插件获取新页面会附带后缀，这里填写会一并刷新'));
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('ncache_token', NULL,'', _t('nginx刷新缓存的token'),'这里设置需要与Nginx内配置完全相同<br />配置方法见<a href="https://cuojue.org/read/typecho-fastcgi_cache.html" target="_blank">配置说明</a>');
        $form->addInput($element);
    }
    /**
     * 手动保存配置句柄
     * @param $config array 插件配置
     */
    public static function configHandle($config)
    {
		Helper::configPlugin('Ncache', $config);
    }

	/**
	 * @清除指定标帜缓存
	 */
	public static function delCache($param,$param2){
		$config  = Helper::options()->plugin(self::$pluginName);
		$index_url = "";
		$root_url = Helper::options()->rootUrl;
		if($config->permalink==0)$index_url = "/index.php";
		if(is_object($param) and intval($param->cid)>0){#评论更新
			$delcommenturl = $index_url.preg_replace('/\/comment$/i','',$param->request->getPathinfo());
			$del = array($delcommenturl);
			//评论分页
			$size = (int) Helper::options()->commentsPageSize;
			if($size>0){
				$db = Typecho_Db::get();
				$num = $db->fetchRow($db->select('commentsNum')->from('table.contents')->where('cid = ?', $param->cid));
				$currentPage = ceil($num["commentsNum"] / $size);
				for ($x=1; $x<=$currentPage; $x++) {
					$pageRow = array('permalink' => $delcommenturl, 'commentPage' => $x);
					$delurl = Typecho_Router::url('comment_page', $pageRow, Helper::options()->index);
					$delurl = str_ireplace($root_url,"",$delurl);
					array_push($del,$delurl);
					unset($pageRow,$delurl);
				}
			}
			self::del($del);
		}elseif(is_array($param) and $param['text']){#发布文章更新
			$s = implode('|',$config->cacheType);
			$del = array($index_url.$param2->pathinfo);
			if(strstr($s, 'page',TRUE)){
				array_push($del, '/','/index.php');
			}
			if(strstr($s, 'category',TRUE)){
				foreach ($param2->categories as $key => $value) {
					$delurl = str_ireplace($root_url,"",$value["permalink"]);
					array_push($del, $delurl);
					unset($pregc);
				}
			}
			if(strstr($s, 'tags',TRUE) && count($param2->tags)>0){
				foreach ($param2->tags as $key => $value) {
					$delurl = str_ireplace($root_url,"",$value["permalink"]);
					array_push($del, $delurl);
					unset($pregc);
				}
			}
			self::del($del);
			return $param;
		}else{
			return;
		}
	}

    /**
     * 删除缓存
     * 
     * @access public
     * @return void
     */
    public static function del($cachekey)
    {
		$config = Helper::options()->plugin(self::$pluginName);
		$root_url = Helper::options()->rootUrl;
		$token = $config->ncache_token;
		if(is_array($cachekey)){
			foreach($cachekey as $k=>$v){
				self::del($v);
			}
		}else{
//			file_put_contents(dirname(__FILE__) . '/cache/log.txt', "{$root_url}/{$token}/_clean_cache{$cachekey}".PHP_EOL, FILE_APPEND);
			file_get_contents("{$root_url}/{$token}/_clean_cache{$cachekey}");
			$words = explode("\n", $config->addurl);
			foreach ($words as $word) {
				file_get_contents("{$root_url}/{$token}/_clean_cache{$cachekey}{$word}");
			}
//			file_get_contents("{$root_url}/{$token}/_clean_cache{$cachekey}?_pjax=%23content");//pjax路径缓存清理
/*			缓存路径，预留，可手动删除文件位置
			$key = md5($cachekey);
			$dir1 = substr($key,-1,1);
			$dir2 = substr($key,-3,2);
			$dir = "/{$dir1}/{$dir2}/$key";
*/
		}
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {
        
    }
}
