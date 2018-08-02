<?php
/**
 * 为含有多个标题的文章生成目录
 * 
 * @package ContentIndex
 * @author laobubu
 * @version 1.0.0
 * @link http://laobubu.net
 */
class ContentIndex_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('ContentIndex_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->singleHandle = array('ContentIndex_Plugin', 'singleHandle');
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
    public static function config(Typecho_Widget_Helper_Form $form){
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
    public static function header($header,$that){
    	$siteUrl = Helper::options()->siteUrl;
    	echo "<link href=\"{$siteUrl}usr/plugins/ContentIndex/ContentIndex.css\" rel=\"stylesheet\" type=\"text/css\" />";
    }

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
	public static function singleHandle($that,$select) {
		if (preg_match_all("/<h(\d)>(.*)<\/h\d>/isU",$that->content,$outarr)) {
			$index = array();
			$index_out = "";
			$minlevel = 6;
			for ($key=0; $key<count($outarr[2]);$key++) {
				$ta = $that->content;
				$tb = strpos($ta, $outarr[0][$key]);
				$that->content = substr($ta, 0, $tb)."<a name=\"ci_title{$key}\"></a>".substr($ta, $tb);

				if ($outarr[1][$key]<$minlevel) 
					$minlevel = $outarr[1][$key];
				array_push($index,
					array(
						"level"=>$outarr[1][$key],
						"link"=>"<a href=\"#ci_title{$key}\">{$outarr[2][$key]}</a>"
					)
				);
			}
			$curlevel = 0;
			foreach ($index as $i) {
				if ($i["level"]>$curlevel) $index_out.="<ul>\n";
				elseif ($i["level"]<$curlevel) $index_out.=str_repeat("</ul>\n", $curlevel-$i["level"]);
				$curlevel = $i["level"];
				$index_out .= "<li>{$i['link']}</li>\n";
			}
			$index_out.=str_repeat("</ul>\n", $curlevel - $minlevel + 1);

			$that->content = "<div id=\"theContentIndex\">{$index_out}</div>". $that->content;
		}
	}
}
