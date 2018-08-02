<?php
/**
 * 一款优美的Github Repo插件,你可以使用形如<strong>&lt;repo&gt;typecho/typecho&lt;/repo&gt;</strong>的代码来插入github的库。
 * 
 * @package Reposidget：GitHub 项目挂件
 * @author 西秦公子
 * @version 1.0.0
 * @link http://www.ixiqin.com
 */

class Reposidget_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Reposidget_Plugin', 'parse');
  
        Typecho_Plugin::factory('Widget_Archive')->header = array('Reposidget_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->header = array('Reposidget_Plugin', 'footer');
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
     * 输出头部css
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function header() {
        $cssUrl = Helper::options()->pluginUrl . '/Reposidget/src/reposidget.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }
    
    /**
     * 输出尾部js
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function footer() {
        $jsUrl = Helper::options()->pluginUrl . '/Reposidget/src/reposidget.js';
        echo '<script type="text/javascript" src="'. $jsUrl .'"></script>';
        
   }

/**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function parse($text, $widget, $lastResult)
    {
                $text = empty($lastResult) ? $text : $lastResult;
        	
        if ($widget instanceof Widget_Archive) {
   
            $text = preg_replace("/<(repo)>(.*?)<\/\\1>/is", 
            "<a class=\"reposidget\" href=\"http://github.com/\\2  \">\\2</a>",
            $text);
        }
        
        return $text;
    }
}
