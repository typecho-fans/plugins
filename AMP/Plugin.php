<?php
/**
 * AMP 插件 for Typecho
 *
 * @package AMP
 * @author Holmesian
 * @version 0.1
 * @link https://holmesian.org
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class AMP_Plugin implements Typecho_Plugin_Interface
{

    public static function activate()
    {
	    Typecho_Plugin::factory('Widget_Archive')->header = array('AMP_Action','headlink');
	    Helper::addRoute('amp_map', '/amp/[slug]', 'AMP_Action', 'AMPpage');
    }
	

    public static function deactivate()
    {
        $msg = self::uninstall();
        return $msg . '插件卸载成功';
    }

    public static function index(){
        echo 1;
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
	
	    $element = new Typecho_Widget_Helper_Form_Element_Text('defaultPIC', null,'https://holmesian.org/usr/themes/Holmesian/images/holmesian.png', _t('默认图片地址'), '默认图片地址');
	    $form->addInput($element);
	
	    $element = new Typecho_Widget_Helper_Form_Element_Text('LOGO', null, 'https://holmesian.org/usr/themes/Holmesian/images/holmesian.png' , _t('默认LOGO地址'), '根据AMP的限制，尺寸最大不超过60*60');
	    $form->addInput($element);
   
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }



    public static function uninstall()
    {
        //删除路由
        Helper::removeRoute('amp_map');
       
    }
	
	




}
