<?php
/**
 * 用户个性签名
 * 
 * @package UserSign
 * @author hmoe
 * @version 0.0.1
 * @dependence 10.8.15-*
 * @link https://github.com/hmoe/typechoSignPlugin
 *
 */
class UserSign_Plugin implements Typecho_Plugin_Interface
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
		$info = UserSign_Plugin::sqlInstall();
//		Typecho_Plugin::factory('Widget_Users_Profile')->personalConfigHandle = array('UserSign_Plugin', 'personalConfigHandle');
//		Typecho_Plugin::factory('Widget_Archive')->select = array('UserSign_Plugin', 'selectHandle');
		return _t($info);
	}

	//SQL创建
	public static function sqlInstall()
	{
		$db = Typecho_Db::get();
		$type = explode('_', $db->getAdapterName());
		$type = array_pop($type);
		$prefix = $db->getPrefix();
		try {
			$select = $db->select('table.users.userSign')->from('table.users');
			$db->query($select);
			return '检测到个性签名字段，插件启用成功';
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql' == $type && (0 == $code ||1054 == $code || $code == '42S22')) ||
					('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
				try {
					if ('Mysql' == $type) {
						$db->query("ALTER TABLE `".$prefix."users` ADD `userSign` VARCHAR( 255 )  DEFAULT '' COMMENT '用户个性签名';");
					} else if ('SQLite' == $type) {
						$db->query("ALTER TABLE `".$prefix."users` ADD `userSign` VARCHAR( 10 )  DEFAULT ''");
					} else {
						throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
					}
					return '建立个性签名字段，插件启用成功';
				} catch (Typecho_Db_Exception $e) {
					$code = $e->getCode();
					if(('Mysql' == $type && 1060 == $code) ) {
						return '个性签名已经存在，插件启用成功';
					}
					throw new Typecho_Plugin_Exception('个性签名插件启用失败。错误号：'.$code);
				}
			}
			throw new Typecho_Plugin_Exception('数据表检测失败，个性签名插件启用失败。错误号：'.$code);
		}
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

	}
      
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){
		$db = Typecho_Db::get();

		$user = Typecho_Widget::widget('Widget_User');
		$user->execute();

		$res = $db->fetchRow($db->select('table.users.userSign')->from('table.users')->where('uid = ?', $user->uid));

		$sign = new Typecho_Widget_Helper_Form_Element_Text('userSign', NULL, $res['userSign'], _t('个性签名'));

		$form->addInput($sign);
	}

	public static function personalConfigHandle($settings,$isSetup){
		$db = Typecho_Db::get();

		if($isSetup)
		{
			Typecho_Widget::widget('Widget_Abstract_Options')->insert(array(
				'name'  =>  '_plugin:UserSign',
				'value' =>  serialize($settings),
				'user'  =>  0
			));
		}


		$user = Typecho_Widget::widget('Widget_User');
		$user->execute();

		$db->query($db->sql()->where('uid = ?', $user->uid)->update('table.users')->rows(array('userSign'=>Typecho_Common::removeXSS($settings['userSign']))));
	}

}
