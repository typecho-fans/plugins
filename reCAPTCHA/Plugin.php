<?php
/**
 * reCAPTCHA验证码插件
 * 
 * @package reCAPTCHA
 * @author D-Bood, 啸傲居士
 * @version 0.0.1
 * @link http://geaya.com
 */

require_once('lib/src/autoload.php');

class reCAPTCHA_Plugin implements Typecho_Plugin_Interface
{

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
		Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'filter');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}
    
	/**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
	public static function config(Typecho_Widget_Helper_Form $form) {
		$siteKeyDescription = _t("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
		$siteKey = new Typecho_Widget_Helper_Form_Element_Text('siteKey', NULL, '', _t('Site Key:'), $siteKeyDescription);
		$secretKey = new Typecho_Widget_Helper_Form_Element_Text('secretKey', NULL, '', _t('Serect Key:'), _t(''));
		
		$form->addInput($siteKey);
		$form->addInput($secretKey);
	}
	
	/**
	 * 展示验证码
	 */
  public static function output() {
    $siteKey = Typecho_Widget::widget('Widget_Options')->plugin('reCAPTCHA')->siteKey;
		$secretKey = Typecho_Widget::widget('Widget_Options')->plugin('reCAPTCHA')->secretKey;
      if ($siteKey != "" && $secretKey != "") {
        echo '<script src="https://recaptcha.net/recaptcha/api.js" async defer data-no-instant></script>
              <div class="g-recaptcha" data-sitekey=' . $siteKey . '></div>';
      } else { throw new Typecho_Widget_Exception(_t('No reCAPTCHA Site/Secret Keys! Please set it/them!')); }
  }
  
	public static function filter($comments, $obj) {
    $userObj = $obj->widget('Widget_User');
    if($userObj->hasLogin() && $userObj->pass('administrator', true)) {
      return $comments;
    }
	  elseif (isset($_POST['g-recaptcha-response'])) {
		$siteKey = Typecho_Widget::widget('Widget_Options')->plugin('reCAPTCHA')->siteKey;
		$secretKey = Typecho_Widget::widget('Widget_Options')->plugin('reCAPTCHA')->secretKey;
		$recaptcha = new \ReCaptcha\ReCaptcha($secretKey);
		$ip = $_SERVER['REMOTE_ADDR'];	
		$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $ip);

		if (!$resp->isSuccess()) {throw new Typecho_Widget_Exception(_t('验证码不正确哦！'));} else {return $comments;}
		} else {
      throw new Typecho_Widget_Exception(_t('未成功加载验证码！请科学上网！'));
	  }
  }

}
