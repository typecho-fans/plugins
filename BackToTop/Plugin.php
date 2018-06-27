<?php
/**
 * 东方返回顶部样式
 *
 * @package BackToTop
 * @author 夏目贵志
 * @version 1.0
 * @link https://xiamuyourenzhang.cn/
 */

class BackToTop_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Archive')->header = array('BackToTop_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('BackToTop_Plugin', 'footer');
    }

   /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {

    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        $jquery = new Typecho_Widget_Helper_Form_Element_Checkbox('jquery', array('jquery' => '禁止加载jQuery'), null, _t('Js设置'), _t('插件需要加载jQuery，如果主题模板已经引用加载JQuery，则可以勾选。'));
        $form->addInput($jquery);
    }
    

   /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {

    }

    
    /**
     * 页头输出相关代码
     *
     * @access public
     * @param unknown header
     * @return unknown
     */
    public static function header() {
        $Path = Helper::options()->pluginUrl . '/BackToTop/';
        echo '<link rel="stylesheet" type="text/css" href="' . $Path . 'css/BackToTop.css" />';
    }


    /**
     * 页脚输出相关代码
     *
     * @access public
     * @param unknown footer
     * @return unknown
     */
    public static function footer() {
		 srand( microtime() * 1000000 );
		 $num = rand( 1, 3 );
		  
		 switch( $num )
		 {
		 case 1: $image_file = "flandre.png";
			 break;
		 case 2: $image_file = "marisa.png";
			 break;
		 case 3: $image_file = "reimu.png";
			 break;
		 }		
        $Options = Helper::options()->plugin('BackToTop');
        $Path = Helper::options()->pluginUrl . '/BackToTop/';	
        echo '<img id="BackToTop" src="' . $Path . 'images/'.$image_file.'" title="返回顶部~">';
        if (!$Options->jquery || !in_array('jquery', $Options->jquery)) {
            echo '<script type="text/javascript" src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>';
        }
        echo '	
		<script type="text/javascript">
		$(function(){
		  $("#BackToTop").click(function() {
			  $("html,body").animate({scrollTop:0}, 500);
		  }); 
		 })

		</script>	
		';
    }
}




