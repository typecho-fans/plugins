<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Google Sitemap 生成器 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package Sitemap
 * @author 迷你日志, Hanny
 * @version 1.0.4
 * @dependence 9.9.2-*
 * @link https://github.com/typecho-fans/plugins/blob/master/Sitemap
 *
 * version 1.0.4 at 2020-07-02 by Typecho Fans (合并多人修改)
 * 调整优先级比例，增加分类页面及首页链接 by 迷你日志/羽中
 * 页面改xml后缀，加入美化样式，简化时间戳 by Suming/八云酱
 *
 * version 1.0.3 at 2017-03-28 by 禾令奇
 * 修改增加标签链接，修改页面权重分级
 *
 * 历史版本
 * version 1.0.1 at 2010-01-02
 * 修改自定义静态链接时错误的Bug
 * version 1.0.0 at 2010-01-02
 * Sitemap for Google
 * 生成文章和页面的Sitemap
 */
class Sitemap_Plugin implements Typecho_Plugin_Interface
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
		Helper::addRoute('sitemap', '/sitemap.xml', 'Sitemap_Action', 'action');
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
		Helper::removeRoute('sitemap');
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
