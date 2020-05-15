<?php

/**
 * 更快、更强的 Markdown 解析插件
 *
 * @package MarkdownParse
 * @author  mrgeneral
 * @version 1.2.3
 * @link    https://www.chengxiaobai.cn
 */

require_once 'ParsedownExtension.php';

class MarkdownParse_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->markdown = [__CLASS__, 'parse'];
        Typecho_Plugin::factory('Widget_Abstract_Comments')->markdown = [__CLASS__, 'parse'];
    }

    public static function deactivate()
    {
        // TODO: Implement deactivate() method.
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $elementToc = new Typecho_Widget_Helper_Form_Element_Radio('is_available_toc', [0 => _t('不解析'), 1 => _t('解析')], 1, _t('是否解析 [TOC] 语法'), _t('开会后支持 [TOC] 语法来生成目录'));
        $form->addInput($elementToc);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function parse($text)
    {
        return ParsedownExtension::instance()
            ->setBreaksEnabled(true)
            ->setTocEnabled((bool)Helper::options()->plugin('MarkdownParse')->is_available_toc)
            ->text($text);
    }
}
