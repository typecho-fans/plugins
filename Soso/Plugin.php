<?php
/**
 * 搜索增强插件,支持typecho1.1及以上版本,插件开源地址：https://github.com/jrotty/soso
 * 
 * @package Soso
 * @author 泽泽社长
 * @version 1.2.2
 * @link http://zezeshe.com/
 */
class Soso_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->search = array('Soso_Plugin', 'soso'); 
        return _t('插件已激活，现在可以对插件进行设置！');
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
       $Somo = new Typecho_Widget_Helper_Form_Element_Radio('Somo',array('1' => _t('常规模式'),'2' => _t('仅搜索标题')),'1',_t('搜索模式'),_t(""));
       $form->addInput($Somo); 
       $sid = new Typecho_Widget_Helper_Form_Element_Text('sid', NULL, NULL, _t('搜索结果不显示的分类'), _t('多个请用英文逗号隔开'));
        $form->addInput($sid);
      
      
      
      
    $tuozhan = new Typecho_Widget_Helper_Form_Element_Checkbox('tuozhan', 
    array(
    'pinlv' => _t('勾选该项开启搜索频率限制，开启后请配置下方设置'),
),

    array(), _t('拓展设置'), _t(''));
    $form->addInput($tuozhan->multiMode());
      
    
    $count = new Typecho_Widget_Helper_Form_Element_Text('count', NULL, '1', _t('限制搜索次数'), _t(''));
    $form->addInput($count->addRule('isInteger', '请填纯数字次数'));  
    
    $time = new Typecho_Widget_Helper_Form_Element_Text('time', NULL, '60', _t('阻止时间（以秒为单位）'), _t(''));
    $form->addInput($time->addRule('isInteger', '请填正确秒数'));  
      
    $txt = new Typecho_Widget_Helper_Form_Element_Text('txt', NULL, '一分钟只能搜索一次，请稍后再试！', _t('被限制后的显示提示'), _t(''));
    $form->addInput($txt); 

 
      
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
 
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function soso($keywords, $obj) {
$count=intval(Typecho_Widget::widget('Widget_Options')->plugin('Soso')->count);
$time=Typecho_Widget::widget('Widget_Options')->plugin('Soso')->time;
$txt=Typecho_Widget::widget('Widget_Options')->plugin('Soso')->txt;
$Somo = Typecho_Widget::widget('Widget_Options')->plugin('Soso')->Somo;//获取设置参数

if(empty($count)){$count=1;}
if(empty($time)){$time=60;}
if(empty($txt)){$txt=$time."秒内只能搜索".$count."次，请稍后再试！";}
 $cat=intval($obj->request->get('cat'));

  $searchQuery = '%' . str_replace(' ', '%', $keywords) . '%';



if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan) && in_array('pinlv',  Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan)){
session_start();



$ip=self::get_the_user_ip();//获取请求者ip

if(!empty($ip)){

if(!empty($_SESSION[$ip])&&empty($page)){
$timeout=time()-$_SESSION[$ip];//获取两次请求的时间差
if($timeout<$time){//如果时间差达到被限制的时间
if($_SESSION['count']<=$count){//如果请求次数未超过规定次数
$_SESSION['count']++;//请求次数+1
include('search.php');
}else{
    
include('theme.php');
exit;//否则就发出限制提示
} 
    
} 
else{//如果搜索间隔超过限制就允许正常搜索
$_SESSION['count']=1;//请求次数重置
$_SESSION[$ip]=time();
$_SESSION['count']++;//请求次数+1
include('search.php');  
}
}else{//如果ip为第一次搜索，或者处于搜索页面翻页状态就照常搜索
if(empty($page)){//翻页时不重置时间，不减少次数
$_SESSION[$ip]=time();
$_SESSION['count']=1;//请求次数重置
}
include('search.php');  
}

}
else{
    
$txt= 'IP读取失败，请关闭隐藏IP的相关工具后再次进行搜索！'; 
include('theme.php');
exit;
    
}

}
else{
include('search.php');   
}





}
    



    private static function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
  

  
  
  
}
