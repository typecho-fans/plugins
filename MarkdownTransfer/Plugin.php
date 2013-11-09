<?php
/**
 * Markdown 转换助手，转换旧文章成 Markdown 格式，并激活它的编辑器
 *
 * @package MarkdownTransfer
 * @author ShingChi
 * @version 1.0.0
 * @link http://lcz.me
 * @date 2013-11-09
 */
class MarkdownTransfer_Plugin implements Typecho_Plugin_Interface
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
        Helper::addAction('MarkdownTransfer', 'MarkdownTransfer_Action');
        Helper::addPanel(4, 'MarkdownTransfer/panel.php', _t('Markdown 转换助手'), _t('Markdown 转换助手'), 'administrator');
        return('MD转换助手已经成功激活，请进入菜单 “设置” --> “Markdown 转换助手” 中进行操作!');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction('MarkdownTransfer');
        Helper::removePanel(4, 'MarkdownTransfer/panel.php');
    }

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
}
