<?php

/**
 * 更快、更强的 Markdown 解析插件
 *
 * @package MarkdownParse
 * @author  mrgeneral
 * @version 1.2.0
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
        // TODO: Implement config() method.
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    public static function parse($text)
    {
        return ParsedownExtension::instance()
            ->setBreaksEnabled(true)
            ->setTocEnabled(true)
            ->setIsOriginalBlockEnabled(true)
            ->text($text);
    }
}
