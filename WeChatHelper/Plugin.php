<?php
/**
 * 让你的微信公众帐号和Typecho博客联系起来
 * 
 * @package WeChatHelper
 * @author 冰剑
 * @version 2.0.0
 * @link http://www.binjoo.net
 */
class WeChatHelper_Plugin implements Typecho_Plugin_Interface {
    public static function activate() {
        $db = Typecho_Db::get();
        if("Pdo_Mysql" === $db->getAdapterName() || "Mysql" === $db->getAdapterName()){
            $db->query("CREATE TABLE IF NOT EXISTS " . $db->getPrefix() . 'wxh_keywords' . " (
                      `kid` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `rid` int(11) NOT NULL,
                      PRIMARY KEY (`kid`)
                    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
            $db->query("CREATE TABLE IF NOT EXISTS " . $db->getPrefix() . 'wxh_reply' . " (
                      `rid` int(11) NOT NULL AUTO_INCREMENT,
                      `keywords` varchar(200) DEFAULT NULL,
                      `type` varchar(20) DEFAULT 'text',
                      `command` varchar(20) DEFAULT NULL,
                      `param` char(1) DEFAULT '0',
                      `content` text,
                      `status` char(1) DEFAULT '0',
                      `created` int(10) DEFAULT '0',
                      PRIMARY KEY (`rid`)
                    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
        }else{
            throw new Typecho_Plugin_Exception(_t('对不起, 本插件仅支持MySQL数据库。'));
        }

        $index = Helper::addMenu('微信助手');
        Helper::addAction('WeChat', 'WeChatHelper_Action');
        Helper::addPanel($index, 'WeChatHelper/Page/BaseConfig.php', '基础设置', '基础设置', 'administrator');
        Helper::addPanel($index, 'WeChatHelper/Page/DeluxeConfig.php', '高级设置', '高级设置', 'administrator');
        Helper::addPanel($index, 'WeChatHelper/Page/CustomReply.php', '自定义回复', '自定义回复', 'administrator');
        return('微信助手已经成功激活，请进入设置Token!');
    }

    public static function deactivate() {
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        if (isset($options->WeChatHelper_dropTable) && $options->WeChatHelper_dropTable) {
            if("Pdo_Mysql" === $db->getAdapterName() || "Mysql" === $db->getAdapterName()){
               $db->query("drop table ".$db->getPrefix()."wxh_keywords, ".$db->getPrefix()."wxh_reply");
               $db->query($db->sql()->delete('table.options')->where('name like ?', "WeChatHelper_%"));
            }
        }
        $index = Helper::removeMenu('微信助手');
        Helper::removePanel($index, 'WeChatHelper/Page/BaseConfig.php');
        Helper::removePanel($index, 'WeChatHelper/Page/DeluxeConfig.php');
        Helper::removePanel($index, 'WeChatHelper/Page/CustomReply.php');
        Helper::removeAction('WeChat');
    }

    public static function config(Typecho_Widget_Helper_Form $form) {
    }
    
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
