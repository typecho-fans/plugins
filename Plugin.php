<?php
/**
 * 文章插入音乐播放器(Two Styles)
 * 
 * @package PlayAtWill
 * @author  vfhky
 * @version 1.0.0
 * @link 	http://typecodes.com
 * 
 */
class PlayAtWill_Plugin implements Typecho_Plugin_Interface
{
	/**
	 * 激活插件方法，如果激活失败，直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 * 
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx=array('PlayAtWill_Plugin','parse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx=array('PlayAtWill_Plugin','parse');
	}
	
	/**
	 * 禁用插件的方法，如果禁用失败，直接抛出异常
	 *
	 * @static
	 * @access public 
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 * 
	 */
	public static function deactivate(){}
	
	/**
	 *获取插件配置面板 
	 * 
	 * @access public 
	 * @param  Typecho_Widget_Helper_Form $form 配置面板
	 * @return void 
	 * 
	 */
	public static function config(Typecho_Widget_Helper_Form $form){}
	
	/**
	 *个人用户的配置面板 
	 * 
	 * @access public
	 * @param  Typeecho_Widget_Helper_Form $form
	 * @return void
	 * 
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
	/**
	 *插件实现方法 
	 * 
	 * @access public
	 * @return void
	 * 
	 */
	public static function parse($text = '', $widget, $lastResult)
	{
		$text=empty($lastResult) ? $text : $lastResult;
		if($widget instanceof Widget_Archive)
		{
			if( preg_match("/<(music1)>(.*?)<\/\\1>/is", $text) ){
					
				$swfUrl=Typecho_Common::url('PlayAtWill/mplayer1.swf',Helper::options()->pluginUrl);
				$text=preg_replace("/<(music1)>(.*?)<\/\\1>/is",
						"<embed src=\"{$swfUrl}?soundFile=\\2\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" allowscriptaccess=\"always\" width=\"290\" height=\"30\"",
						$text
						);
			}
			if( preg_match("/<(music2)>(.*?)<\/\\1>/is", $text) )
			{	
				$swfUrl=Typecho_Common::url('PlayAtWill/mplayer2.swf',Helper::options()->pluginUrl);
				$text=preg_replace("/<(music2)>(.*?)<\/\\1>/is",
						"<embed src=\"{$swfUrl}\" flashvars=\"mp3=\\2\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"240\" height=\"20\"></embed>",
						$text
						);
			}
		}
		return $text;
	}
}
