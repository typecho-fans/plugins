<?php

/**
 * Typecho 公告栏插件
 * 
 * @package Announcement
 * @author skylzl
 * @version 1.0.0
 * @link http://www.phoneshuo.com
 */

class Announcement_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Announcement_Plugin', 'footer');
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
        /** 公告栏的显示模式 */
        $annMode = new Typecho_Widget_Helper_Form_Element_Radio(
          'annMode', array('2' => '底部固定', '1' => '弹窗展示'), '2',
          '公告模式', '默认底部固定，可选择在底部固定展示或者弹窗展示');
        $form->addInput($annMode);
        /** 展示区域 */
        $showArea = new Typecho_Widget_Helper_Form_Element_Radio(
          'showArea', array('2' => '仅首页展示', '1' => '全站展示'), '2',
          '展示区域', '默认仅首页，可选择仅首页或全站展示');
        $form->addInput($showArea); 
        /** 是否加载jquery */
        $jquery = new Typecho_Widget_Helper_Form_Element_Radio(
        'jquery', array('0'=> '手动加载', '1'=> '自动加载'), 0, '选择jQuery来源',
            '若选择"手动加载",则需要你手动加载jQuery到你的主题里,若选择"自动加载",本插件会自动加载jQuery到你的主题里。');
        $form->addInput($jquery);
        /** 公告内容 */
        $content = new Typecho_Widget_Helper_Form_Element_Textarea('content', NULL, "测试公告1\n测试公告2",
			_t('公告内容'), _t('多条公告请用换行符隔开'));
        $form->addInput($content);         
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){     
    }
  
    /**
     * 公告相关js加载在尾部
     */
    public static function footer() {
        include 'announce-js.php';
    }    
}
