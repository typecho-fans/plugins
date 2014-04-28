<?php
/**
 * Github Card
 *
 * @package Github Card
 * @author chekun
 * @version 1.0.0
 * @dependence 9.9.2-*
 * @link http://me.dilicms.com/coding/github-card.html
 */
class GithubCard_Plugin implements Typecho_Plugin_Interface
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
	Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('GithubCard_Plugin', 'parse');
	Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('GithubCard_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('GithubCard_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('GithubCard_Plugin', 'footer');
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
     * 输出尾部js
     *
     * @access public
     * @return void
     */
    public static function footer() {
        $jsUrl = Helper::options()->pluginUrl . '/GithubCard/js/widget.js';
    	echo '<script src="'.$jsUrl.'"></script>';
    }

    /**
     * 解析card标签
     *
     * @access public
     * @
     */
    public static function parse($text, $widget, $lastResult) {

        $text = empty($lastResult) ? $text : $lastResult;

        if ($widget instanceof Widget_Archive
            || $widget instanceof Widget_Abstract_Comments) {
            return preg_replace(
                "/(\[card\](.*?)\[\/card\])/is",
                '<div class="github-card" data-github=$2></div>',
                $text
            );
        }

        return $text;
    }
}
