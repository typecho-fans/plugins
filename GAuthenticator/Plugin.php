<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Google Authenticator for Typecho
 * 
 * @package GAuthenticator
 * @author WeiCN
 * @version 0.0.4
 * @link https://cuojue.org/read/Typecho_Google_Authenticator_02.html
 */
class GAuthenticator_Plugin implements Typecho_Plugin_Interface
{
	private static $pluginName = 'GAuthenticator';
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Helper::addRoute('GAuthenticator', '/GAuthenticator', 'GAuthenticator_Action', 'Action');
		Typecho_Plugin::factory('admin/menu.php')->navBar = array(__CLASS__, 'Authenticator_safe');
		Typecho_Plugin::factory('admin/common.php')->begin = array(__CLASS__, 'Authenticator_verification');
		return _t('当前两步验证还未启用，请进行<a href="options-plugin.php?config=' . self::$pluginName . '">初始化设置</a>');
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
		Helper::removeRoute('GAuthenticator');
	}
	
	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void'
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		$qrurl = 'http://qr.liantu.com/api.php?text='.urlencode('otpauth://totp/'.urlencode(Helper::options()->title.':'.Typecho_Widget::widget('Widget_User')->mail).'?secret='.Helper::options()->plugin(self::$pluginName)->SecretKey);//生成安全密钥的二维码网址
		$element = new Typecho_Widget_Helper_Form_Element_Text('SecretKey', NULL, '', _t('SecretKey'), '安装的时候自动计算密钥,手动修改无效,如需要修改请卸载重新安装或者手动修改数据库<br><span style="font-weight: bold; color: #000; text-align: center; display: block;padding: 30px 0 30px 0;font-size: 24px;">请扫描下面的二维码绑定<br><img style="padding-top: 20px;" src="'.$qrurl.'"></span>');
		$form->addInput($element);
		$element = new Typecho_Widget_Helper_Form_Element_Text('SecretQRurl', NULL, '', _t('二维码的网址'), '本选项已过时，保留只是为了向下兼容。和上面图片的地址是相同的');
		$form->addInput($element);
		$element = new Typecho_Widget_Helper_Form_Element_Text('SecretTime', NULL, 2, _t('容差时间'), '允许的容差时间,单位为30秒的倍数,如果这里是2 那么就是 2* 30 sec 一分钟.');
		$form->addInput($element);
		$element = new Typecho_Widget_Helper_Form_Element_Text('SecretCode', NULL, '', _t('客户端代码'), '输入你APP或者其他什么鬼上面显示的六位数字。<br>用兼容的APP上面的扫描二维码或者手动输入第一行的SecretKey即可生成');
		$form->addInput($element);
		$element = new Typecho_Widget_Helper_Form_Element_Radio('SecretOn', array('1' => '开启','0' => '关闭'), 0, _t('插件开关'), '这里关掉了，就不需要验证即可登录。');
		$form->addInput($element);
	}
	/**
	 * 手动保存配置面板
	 * @param $config array 插件配置
	 * @param $is_init bool 是否初始化
	 */
	public static function configHandle($config, $is_init)
	{
		if ($is_init) {//如果是第一次初始化插件
			require_once 'GoogleAuthenticator.php';
			$Authenticator = new PHPGangsta_GoogleAuthenticator();//初始化生成类
			$config['SecretKey'] = $Authenticator->createSecret();//生成一个随机安全密钥
			$config['SecretQRurl'] = 'http://qr.liantu.com/api.php?text='.urlencode('otpauth://totp/'.urlencode(Helper::options()->title.':'.Typecho_Widget::widget('Widget_User')->mail).'?secret='.$config['SecretKey']);//生成安全密钥的二维码网址
		}else{
			$config_old = Helper::options()->plugin(self::$pluginName);
			if(($config['SecretCode']!='' && $config['SecretOn']==1) || $config['SecretOn']==1){//如果启用,并且验证码不为空
				require_once 'GoogleAuthenticator.php';
				$Authenticator = new PHPGangsta_GoogleAuthenticator();
				if($Authenticator->verifyCode($config['SecretKey'], $config['SecretCode'], $config['SecretTime'])){
					$config['SecretOn'] = 1;//如果匹配,则启用
				}else{
					throw new Typecho_Plugin_Exception('两步验证代码校验失败,请重试或选择关闭');
				}
			}
			$config['SecretKey'] = $config_old->SecretKey;//保持初始化SecretKey不被修改
			$config['SecretQRurl'] = $config_old->SecretQRurl;//保持初始化SecretQRurl不被修改 过时选项 兼容保留
		}
		$config['SecretCode'] = '';//每次保存不保存验证码
		Helper::configPlugin(self::$pluginName, $config);//保存插件配置
	}
	/**
	 * 个人用户的配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
	/**
	 * 插件实现方法
	 * 
	 * @access public
	 * @return void
	 */
	public static function Authenticator_safe()
	{
		$config = Helper::options()->plugin(self::$pluginName);
		if($config->SecretOn==1){
			echo '<span class="message success">'.htmlspecialchars('已启用 Authenticator 验证').'</span>';
		}else{
			echo '<span class="message error">'.htmlspecialchars('未启用 Authenticator 验证').'</span>';
		}
	}

	public static function Authenticator_verification()
	{
		if(isset($Authenticator_init))return;
		$Authenticator_init = true;
		if (!Typecho_Widget::widget('Widget_User')->hasLogin()){
			return;//如果没登录则直接返回
		}else{
			//已经登录就验证
			$config = Helper::options()->plugin(self::$pluginName);
			if (isset($_SESSION['GAuthenticator'])&&$_SESSION['GAuthenticator']) return;//如果SESSION匹配则直接返回
			if (Typecho_Cookie::get('__typecho_GAuthenticator') == md5($config->SecretKey.Typecho_Cookie::getPrefix().Typecho_Widget::widget('Widget_User')->uid)) return;//如果COOKIE匹配则直接返回
			if($config->SecretOn==1){
				$options = Helper::options();
				$request = new Typecho_Request();
				require_once 'verification.php';
			}else{
				return;//如果未开启插件则直接返回
			}
		}
	}
}
