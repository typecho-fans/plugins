<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 自定义CSS文件和JS文件。
 * 在header，和footer分别加入自定义的css和js。
 *
 * @package CustomCssAndJs
 * @author  KyuuSeiryuu
 * @version 1.0
 * @link http://www.chioy.cn
 */
class CustomCssAndJs_Plugin implements Typecho_Plugin_Interface
{

    public static function activate(){
      Typecho_Plugin::factory('Widget_Archive')->header = array('CustomCssAndJs_Plugin', 'header');
      Typecho_Plugin::factory('Widget_Archive')->footer = array('CustomCssAndJs_Plugin', 'footer');
      return _t("插件已启用");
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
      return _t("插件已禁用");
    }

    public static function config(Typecho_Widget_Helper_Form $form){
      $headcss = new Typecho_Widget_Helper_Form_Element_Textarea('headcss', NULL, '', _t('CSS文件url，多个请用英文","号分隔'));
      $footjs = new Typecho_Widget_Helper_Form_Element_Textarea('footjs', NULL, '', _t('JS文件url，多个请用英文","号分隔'));
      $custcss = new Typecho_Widget_Helper_Form_Element_Textarea('custcss', NULL, '', _t('自定义CSS'));
      $custjs = new Typecho_Widget_Helper_Form_Element_Textarea('custjs', NULL, '', _t('自定义JS脚本'));
      $form->addInput($headcss);
      $form->addInput($footjs);
      $form->addInput($custcss);
      $form->addInput($custjs);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function header(){
      $template = '<link rel="stylesheet" type="text/css" href="CSSURL" />';
      $urls = Typecho_Widget::widget('Widget_Options')->plugin('CustomCssAndJs')->headcss;
      $array = explode(',',$urls);
      $len = count($array);
      echo "\n";
      echo '<!--CustomCssAndJs Header CSS Start-->';
      echo "\n";
      for($i=0;$i<$len;$i++){
        if(trim($array[$i])=='') continue;
        echo str_replace('CSSURL',$array[$i],$template);
        echo "\n";
      }

      $custcss = Typecho_Widget::widget('Widget_Options')->plugin('CustomCssAndJs')->custcss;
      if(trim($custcss) != ''){
        echo '<style type="text/css">';
        echo "\n";
        echo $custcss;
        echo "\n";
        echo '</style>';
      }
      echo "\n";
      echo '<!--CustomCssAndJs Header CSS End-->';
      echo "\n";
      $template = '<script src="JSURL"></script>';
      $urls = Typecho_Widget::widget('Widget_Options')->plugin('CustomCssAndJs')->footjs;
      $array = explode(',',$urls);
      $len = count($array);
      echo "\n";
      echo '<!--CustomCssAndJs  JS Start-->';
      echo "\n";
      for($i=0;$i<$len;$i++){
        if(trim($array[$i])=='') continue;
        echo str_replace('JSURL',$array[$i],$template);
        echo "\n";
      }
      echo '<!--CustomCssAndJs  JS End-->';
      echo "\n";
    }

    public static function footer()
    {
    
      
      $custjs = Typecho_Widget::widget('Widget_Options')->plugin('CustomCssAndJs')->custjs;
      if(trim($custjs) != '')
      {
        echo '<!--CustomCssAndJs Footer JS Start-->';
        echo '<script>';
        echo $custjs;
        echo '</script>';
        echo "\n";
        echo '<!--CustomCssAndJs Footer JS End-->';
        echo "\n";     
      }
        
      
      
    }
}
