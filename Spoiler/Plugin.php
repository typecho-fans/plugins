<?php

namespace TypechoPlugin\Spoiler;
 
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Radio;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 敏感内容遮罩，由后端直接渲染，不会短暂显示内容
 *
 * @package Spoiler
 * @author Ect07
 * @version 1.0.0
 * @since 1.3.0
 * @link https://ect.fyi/
 */
class Plugin implements PluginInterface
{
    /**
     * 激活插件方法
     */
    public static function activate()
    {
    }
 
    /**
     * 禁用插件方法  
     */
    public static function deactivate()
    {
    }
 
    /**
     * 获取插件配置面板
     */
    public static function config(Form $form)
    {
        $enableHtmlComments = new Radio('enableHtmlComments', array('0'=> '启用', '1'=> '禁用'), 0, 'HTML注释语法',
            nl2br(htmlspecialchars("使用形如“
!!!
<!--SPOILER
本文章可能含有轻度的性暗示/血腥暴力/自伤自残等
使您感到轻微不适的内容。
请确定您的年龄与心智适宜阅读。
-->
!!!
”的语法创建一个遮罩。")));
        $enableNoteTags = new Radio('enableNoteTags', array('0'=> '启用', '1'=> '禁用'), 0, '检测note标签',
            nl2br(htmlspecialchars("检测页面的 [note type=\"warning\"] / [note type=\"danger\"] 来自动创建遮罩。
实际上它只检测HTML输出中同时含有 note 和 warning / danger 类的元素。Butterfly等主题会自动输出这些类。
在它们同时存在时，遮罩文案优先级为 SPOILER注释 > danger > warning 。")));
        $form->addInput($enableHtmlComments);
        $form->addInput($enableNoteTags);
    }
 
    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Form $form)
    {
    }


    public static function smartSpoiler() {
        include "smart-spoiler.php";
    }
    
}
