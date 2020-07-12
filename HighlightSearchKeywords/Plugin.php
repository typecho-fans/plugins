<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 高亮页面中的搜索关键字 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 *
 * @package HighlightSearchKeywords
 * @author 羽中, gouki
 * @version 0.1.3
 * @link https://github.com/typecho-fans/plugins/tree/master/HighlightSearchKeywords
 *
 * 0.1.3 修正内部搜索无效问题，恢复自带样式
 *
 * 更新日志：
 * 0.1.0 高亮从google,yahoo,baidu过来的关键字
 * 0.1.1 文件名hightlight.js写错，改为highlight.js
 * 0.1.2 増加网站内部搜索关键字高亮
 */
class HighlightSearchKeywords_Plugin implements Typecho_Plugin_Interface
{

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */	
    public static function activate ()
	{
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('HighlightSearchKeywords_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header = array('HighlightSearchKeywords_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('HighlightSearchKeywords_Plugin', 'footer');
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
     * 定义来源地址变量：httpd_referer
     * 
     * @access public
     * @return unknown
     */
    public static function header()
    {
        $referer = self::getHttpReferer();
        echo "<style type=\"text/css\">.searchword { background-color: yellow; }</style>";
        echo "<script>var httpd_referer='{$referer}';</script>";
    }

    /**
     * 引入涉及的JS，并自动初始化
     * 
     * @access public
     * @return unknown
     */
    public static function footer()
    {
        $highlightJS = Helper::options()->pluginUrl . "/HighlightSearchKeywords/src/highlight.js";
        echo "<script type='text/javascript' src='{$highlightJS}'></script>";
    }    
    
    /**
     * 取得页面来源，基本上只针对google,baidu和yahoo
     * 
     * @access private
     * @return string
     */
    private static function getHttpReferer()
    {
        if(isset($_GET['highlight'])){
            return urldecode(trim($_GET['highlight']));
        }
        $request = Typecho_Request::getInstance();
        if( $referer = $request->getReferer() ){
            parse_str( parse_url( $referer , PHP_URL_QUERY  ) , $query);
            if(isSet( $query['q'] ) ){
                return $query['q'];
            }else if ( isSet( $query['p'] ) ){
                return $query['p'];
            }else if ( isSet( $query['wd'] ) ){//baidu
                if(!$query['wd']){
                    return '';
                }
                return iconv( 'gb2312', 'utf-8', urldecode( $query['wd'] ) );   //百度过来是gb2312，要转成utf-8
                //return urlencode(iconv( 'gb2312', 'utf-8', urldecode( $query['wd'] ) ));
            }
        }
        if(preg_match('|/search/(.*?)/|i', urldecode($request->getPathInfo()) , $result)){
            return $result[1];
        }
        return '';
    }

    public static function parse($text, $widget, $lastResult){
        $text = empty($lastResult) ? $text : $lastResult;
        if ($widget instanceof Widget_Archive && Typecho_Router::$current == 'search') {
            if($highlight = self::getHttpReferer()){
                $_GET['highlight'] = $highlight;
                $text['permalink'] .= "?". http_build_query($_GET);
            }
        }
        return $text;
    }
}