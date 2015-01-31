<?php
/**
 * 极验验证
 *
 * @category comment
 * @package GeeTest
 * @author 啸傲居士
 * @link http://jiya.io
 * @version 1.0.1
 * @date 2015-02-01
 * 
 * 更新：1. 更新geetestlib到最新版本；2. 增加样式选择选项；3. 如果选择弹出样式，请将提交按钮id设为“submit-button”
 */

require_once('lib/geetestlib.php');

class GeeTest_Plugin implements Typecho_Plugin_Interface
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
    $captchakeyDescription = _t("To use GeeTest you must get an API key from <a href='http://www.geetest.com/'>http://www.geetest.com/</a>");
    $captchakey = new Typecho_Widget_Helper_Form_Element_Text('captchakey', NULL, '', _t('Captcha key:'), $captchakeyDescription);
    $privatekey = new Typecho_Widget_Helper_Form_Element_Text('privatekey', NULL, '', _t('Private key:'), _t(''));
    $dispmode = new Typecho_Widget_Helper_Form_Element_Select('dispmode', array('float' => '浮动式','embed' => '嵌入式','popup' => '弹出式'), 'float', _t('Display mode(<a href="http://geetest.com/experience">experience online</a>):'), _t(''));
    
    $form->addInput($captchakey);
    $form->addInput($privatekey);
    $form->addInput($dispmode);
  }
  
  /**
   * 展示验证码
   */
  public static function output() {
    $captchakey = Typecho_Widget::widget('Widget_Options')->plugin('GeeTest')->captchakey;
    $dispmode = Typecho_Widget::widget('Widget_Options')->plugin('GeeTest')->dispmode;

    $str = '&product='.$dispmode;
    if ($dispmode == 'popup') {
      $str = $str.'&popupbtnid=submit-button';
    }

    echo "<script async type='text/javascript' src='http://api.geetest.com/get.php?gt=$captchakey$str'></script>"; 
  }
  
  public static function filter($comment, $obj) {
    $userObj = $obj->widget('Widget_User');
    if($userObj->hasLogin() && $userObj->pass('administrator', true)) {
      return $comment;
    }
    $privatekey = Typecho_Widget::widget('Widget_Options')->plugin('GeeTest')->privatekey;

    $geetest = new GeetestLib($privatekey);
    $validate_response = $geetest->geetest_validate( @$_POST ['geetest_challenge'],
                                                     @$_POST ['geetest_validate'], @$_POST ['geetest_seccode']);

    if ($validate_response) {
      return $comment;
    }

    throw new Typecho_Widget_Exception(_t('验证码不正确哦！'));
  }
}
