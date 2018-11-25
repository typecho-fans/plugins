<?php
/**
 * GAuthenticator Plugin
 *
 * @copyright  Copyright (c) 2018 WeiCN (https://cuojue.org)
 * @license	GNU General Public License 2.0
 * 
 */
class GAuthenticator_Action extends Typecho_Widget implements Widget_Interface_Do
{

	public function __construct($request, $response, $params = NULL)
	{
		parent::__construct($request, $response, $params);
	}
	/**
	 * 验证GAuthenticator POST
	 * 
	 */
	public function auth(){
		if(intval($this->request->get('otp'))>0){
			//获取到CODE
			if (isset($_SESSION['GAuthenticator'])&&$_SESSION['GAuthenticator']) return;//如果SESSION匹配则直接返回
			$config = Helper::options()->plugin('GAuthenticator');
			require_once 'GoogleAuthenticator.php';
			$referer = $this->request->getReferer();
			$Authenticator = new PHPGangsta_GoogleAuthenticator();//初始化生成类
			$oneCode = intval($this->request->get('otp'));//手机端生成的一次性代码
			if($Authenticator->verifyCode($config->SecretKey, $oneCode, $config->SecretTime)){//验证一次性代码
				$expire = 1 == $this->request->get('remember') ? Helper::options()->time + Helper::options()->timezone + 30*24*3600 : 0;
				$_SESSION['GAuthenticator'] = true;//session保存
				Typecho_Cookie::set('__typecho_GAuthenticator',md5($config->SecretKey.Typecho_Cookie::getPrefix().Typecho_Widget::widget('Widget_User')->uid),$expire);//cookie保存
			}else{
				Typecho_Widget::widget('Widget_Notice')->set(_t('两步验证失败'), 'error');
			}
			Typecho_Response::redirect($referer);
		}
	}

	public function action(){
		$this->widget('Widget_User')->pass('administrator');
		$this->on($this->request->is('otp'))->auth();
	}
}
?>
