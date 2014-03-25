<?php
/**
 * 数据库优化插件
 * 
 * @package OptimizeDB
 * @author 冰剑
 * @version 1.0.1
 * @link http://www.binjoo.net
 */
class OptimizeDB_Plugin implements Typecho_Plugin_Interface {
    public static function activate() {
        Helper::addAction('OptimizeDB', 'OptimizeDB_Action');
        Helper::addPanel(1, 'OptimizeDB/Panel.php', '数据库优化', '数据库优化面板', 'administrator');
        return('数据库优化插件已经成功激活，请在【控制台->数据库优化】中使用!');
    }
    public static function deactivate() {
        Helper::removeAction('OptimizeDB');
        Helper::removePanel(1, 'OptimizeDB/Panel.php');
    }
    public static function config(Typecho_Widget_Helper_Form $form){}
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

}
