<?php
/**
 * 把外部链接转换为 your_blog_path/go/key/<br>
 * 通过菜单“撰写->链接转换”设置
 * 
 * @package 链接转换 GoLinks
 * @author DEFE
 * @version 0.3.0
 * @link http://defe.me
 */
class GoLinks_Plugin implements Typecho_Plugin_Interface
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
        $db = Typecho_Db::get();
        $golinks = $db->getPrefix() . 'golinks';
        $adapter = $db->getAdapterName();
        if("Pdo_SQLite" === $adapter || "SQLite" === $adapter){
           $db->query(" CREATE TABLE IF NOT EXISTS ". $golinks ." (
               id INTEGER PRIMARY KEY, 
               key TEXT,
               target TEXT,
               count NUMERIC)");
        }
        if("Pdo_Mysql" === $adapter || "Mysql" === $adapter){
            $db->query("CREATE TABLE IF NOT EXISTS ". $golinks ." (
                  `id` int(8) NOT NULL AUTO_INCREMENT,
                  `key` varchar(32) NOT NULL,
                  `target` varchar(10000) NOT NULL,
                  `count` int(8) DEFAULT '0',
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
        }

        Helper::addAction('golinks', 'GoLinks_Action');
        Helper::addRoute('go', '/go/[key]/', 'GoLinks_Action', 'golink');
        Helper::addPanel(2, 'GoLinks/panel.php', '链接转换', '链接转换管理',   'administrator');
        return('数据表 '.$golinks.' 创建成功, 插件已经成功激活!');
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
        Helper::removeRoute('go');
        Helper::removeAction('golinks');
        Helper::removePanel(2, 'GoLinks/panel.php');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

}
