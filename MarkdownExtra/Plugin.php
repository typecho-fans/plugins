<?php
/**
 * Markdown 语法增强扩展
 * 
 * @package MarkdownExtra
 * @author ShingChi
 * @version 1.0.0
 * @link http://lcz.me
 */

/** 载入MarkdownExtra支持 */
require_once 'MarkdownExtra.php';

class MarkdownExtra_Plugin implements Typecho_Plugin_Interface
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
        /** 前端输出处理接口 */
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('MarkdownExtra_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('MarkdownExtra_Plugin', 'parse');
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
    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;

        if ($widget instanceof Widget_Archive) {
            if ($widget->isMarkdown) {
                $markdown = new MarkdownExtra();
                $text = $markdown->transform($text);
            } else {
                $text = Typecho_Common::cutParagraph($text);
            }
        }

        return $text;
    }
}
