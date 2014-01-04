<?php
/**
 * 路由助手
 *
 * @package RoutesHelper
 * @author doudou
 * @version 1.0.3
 * @dependence 13.12.12-*
 * @link http://doudou.me
 * @date 2014-1-4
 */
class RoutesHelper_Plugin implements Typecho_Plugin_Interface
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
        Helper::addAction('RoutesHelper', 'RoutesHelper_Action');
        Helper::addPanel(4, 'RoutesHelper/panel.php', _t('路由助手'), _t('路由助手'), 'administrator');
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
        Helper::removeAction('RoutesHelper');
        Helper::removePanel(4, 'RoutesHelper/panel.php');
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
