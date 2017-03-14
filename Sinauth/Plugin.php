<?php
/**
 * Sina 微博登陆插件
 * 
 * @package Sinauth
 * @author jimmy chaw
 * @version 1.0.0 Beta
 * @link http://x3d.cnblogs.com
 */
class Sinauth_Plugin implements Typecho_Plugin_Interface
{
    private static $pluginName = 'Sinauth';
    private static $tableName = 'users_oauth';
    
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
    	$meg = self::install();
    	
        Typecho_Plugin::factory('Widget_User')->___sinauthAuthorizeIcon = array('Sinauth_Plugin', 'authorizeIcon');
        
        Helper::addAction('sinauthAuthorize', 'Sinauth_AuthorizeAction');
        Helper::addRoute('sinauthAuthorize', '/sinauthAuthorize/', 'Sinauth_AuthorizeAction', 'action');
        Helper::addRoute('sinauthCallback', '/sinauthCallback/', 'Sinauth_AuthorizeAction', 'callback');
        Helper::addPanel(1, 'Sinauth/panel.php', 'Sinauth', 'Sinauth用户管理',   'administrator');

        return _t($meg.'。请进行<a href="options-plugin.php?config='.self::$pluginName.'">初始化设置</a>');
    }
    
    public static function install()
	{           
                                
		$installDb = Typecho_Db::get();
		$prefix = $installDb->getPrefix();
        $oauthTable = $prefix. self::$tableName;
		try {
                        $installDb->query("CREATE TABLE `$oauthTable` (
                        `moid` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `plateform` varchar(45) NOT NULL DEFAULT 'sina',
                      `uid` int(10) unsigned NOT NULL,
                      `openid` varchar(80) NOT NULL,
                      `bind_time` int(10) unsigned NOT NULL,
                      `expires_in` int(10) unsigned DEFAULT NULL,
                      `refresh_token` varchar(300) DEFAULT NULL,
                      PRIMARY KEY (`moid`),
                      KEY `uid` (`uid`),
                      KEY `plateform` (`plateform`),
                      KEY `openid` (`openid`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

                       return('表创建成功, 插件已经被激活!');
                    
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql' == $type && 1050 == $code)) {
					$script = 'SELECT `moid` from `' . $oauthTable . '`';
					$installDb->query($script, Typecho_Db::READ);
					return '数据表已存在，插件启用成功';	
			} else {
				throw new Typecho_Plugin_Exception('数据表'.$oauthTable.'建立失败，插件启用失败。错误号：'.$code);
			}
		}
	}
    
    //在前台登陆页面增加oauth跳转图标
    public static function authorizeIcon() {
        return '<a href="' . Typecho_Router::url('sinauthAuthorize', array('feed' => '/atom/comments/')) . '">新浪登陆</a>';
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
    	Helper::removeRoute('sinauthAuthorize');
	Helper::removeRoute('sinauthCallback');
	Helper::removeAction('sinauthAuthorize');
        
        Helper::removePanel(1, 'Sinauth/panel.php');
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
        $client_id = new Typecho_Widget_Helper_Form_Element_Text('client_id', NULL,'', _t('App Key'),'请在微博开放平台查看http://open.weibo.com');
        $form->addInput($client_id);
        
        $client_secret = new Typecho_Widget_Helper_Form_Element_Text('client_secret', NULL,'', _t('App Secret'),'请在微博开放平台查看http://open.weibo.com');
        $form->addInput($client_secret);
        
        $callback_url = new Typecho_Widget_Helper_Form_Element_Text('callback_url', NULL,'http://', _t('回调地址'),'请与微博开放平台中设置一致');
        $form->addInput($callback_url);
        
        //$callback_url = new Typecho_Widget_Helper_Form_Element_Text('email_domain', NULL,'v.sina.com', _t('虚拟email后缀'),'创建用户帐号时构造一个虚拟email，如uid@v.sina.com');
        //$form->addInput($callback_url);
        
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
    

    
}
