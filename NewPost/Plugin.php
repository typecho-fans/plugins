<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 通过POSTh或GET发送文章
 * 
 * @package NewPost
 * @author ilay
 * @version 0.1
 * @link https://wei.bz
 */
class NewPost_Plugin implements Typecho_Plugin_Interface {
   /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
      Helper::addAction("import", "NewPost_Action");
      return "请设置sign值";
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
     Helper::removeAction('import');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        echo '<p>首先添加一位用户，用户组为 编辑 ，把账号密码填在下面。<p>key为验证密钥</p><p>mid为要通过post发送的文章分类,例如某分类设置页url为 https://wei.bz/admin/category.php?mid=2 ，mid就等于2</p>';
      echo '通过post请求 http://你的域名/action/import 参数：title 标题 ;  text 正文内容;  key 验证密钥</p>';
       $username = new Typecho_Widget_Helper_Form_Element_Text('username', NULL, _t(''), _t('username'));
    	$form->addInput($username);
      $password = new Typecho_Widget_Helper_Form_Element_Text('password', NULL, _t(''), _t('password'));
    	$form->addInput($password);
    $key = new Typecho_Widget_Helper_Form_Element_Text('sign', NULL, _t('123456'), _t('key'));
      $form->addInput($key);
    $mid = new Typecho_Widget_Helper_Form_Element_Text('mid', NULL, _t(''), _t('mid'));
    	$form->addInput($mid);
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
  
}
