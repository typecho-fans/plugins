<?php

/**
 * 畅言评论回调插件，主要用于畅言评论后回推到 Typecho 自有评论系统<br />回推地址:http[s]://YOUR_HOST/changyan-callback
 *
 * @package ChangyanCallback
 * @author  mrgeneral
 * @version 1.0.0
 * @link    https://www.chengxiaobai.cn
 */
class ChangyanCallback_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        /**
         * Initialization column for multilevel comments.
         *
         * It's wouldn't be deleted when plugin was disabled.
         */
        $db                = Typecho_Db::get();
        $commentsTableName = $db->getPrefix() . 'comments';
        $commentsColumns   = $db->fetchAll($db->query("show columns from $commentsTableName"));
        if (empty(array_filter($commentsColumns, function ($commentsColumn) {
            return $commentsColumn['Field'] === 'cmtid';
        }))) {
            $db->query("ALTER TABLE `$commentsTableName` ADD `cmtid` INT(10)  NOT null  DEFAULT '0'");
        }

        Helper::addRoute('ChangyanCallback', '/changyan-callback/', 'ChangyanCallback_Action', 'action');
    }

    public static function deactivate()
    {
        Helper::removeRoute('ChangyanCallback');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement config() method.
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

}
