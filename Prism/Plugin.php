<?php
/**
 * Prism 是一个轻量级，可扩展的语法着色工具，符合 Web 标准。
 * 
 * @category content
 * @package Prism
 * @author 冰剑
 * @version 1.0.1
 * @link http://www.binjoo.net/
 */
class Prism_Plugin implements Typecho_Plugin_Interface
{
    public static function activate() {
        Typecho_Plugin::factory('Widget_Archive')->header = array('Prism_Plugin', 'headlink');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Prism_Plugin', 'footlink');
    }

    public static function deactivate(){}

    public static function config(Typecho_Widget_Helper_Form $form){
        $style = new Typecho_Widget_Helper_Form_Element_Radio('style',
            array('default' => 'Default',
                  'dark' => 'Dark',
                  'funky' => 'Funky',
                  'okaidia' => 'Okaidia',
                  'twilight' => 'Twilight',
                  'coy' => 'Coy',
                  'solarizedlight' => 'SolarizedLight',
                  'tomorrow' => 'Tomorrow'),
                  'default', '高亮样式', NULL);
        $form->addInput($style);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form){} 

    /**
     * 头部样式
     *
     * @access public
     * @param unknown $headlink
     * @return unknown
     */
    public static function headlink($cssUrl) {
        $settings = Helper::options()->plugin('Prism');
        $url = Helper::options()->pluginUrl .'/Prism/';
        $links = '<link rel="stylesheet" type="text/css" href="'.$url.'css/prism-'.$settings->style.'.css" />';
        echo $links;
    }

    /**
     * 底部脚本
     *
     * @access public
     * @param unknown $footlink
     * @return unknown
     */
    public static function footlink($links) {
        $settings = Helper::options()->plugin('Prism');
        $url = Helper::options()->pluginUrl .'/Prism/';
        $links= '<script type="text/javascript" src="'.$url.'js/prism.js"></script>';
        echo $links;
    }
}