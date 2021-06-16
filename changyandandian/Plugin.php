<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 
 * 
 * @package 畅言单点登录插件
 * @author 泽泽社长
 * @version 1.0.0
 * @link https://zezeshe.com/archives/typecho-changyan-plugin.html
 */
class changyandandian_Plugin implements Typecho_Plugin_Interface
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
        Helper::addRoute("route_changyan","/changyan","changyandandian_Action",'action');
        Helper::addRoute("route_changyanlogout","/changyan/logout","changyandandian_Action",'logout');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('changyandandian_Plugin', 'footer');
       
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute("route_changyan");
        Helper::removeRoute("route_changyanlogout");
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
               
$set1 = new Typecho_Widget_Helper_Form_Element_Textarea('loginjsurl', NULL,$f, _t('网站登录地址'), _t('请在此处填写网站登录地址，不填则调用默认的typecho登录地址，如果您的主题用的是弹窗式的登录界面，您也可以在此写js代码调用出登录弹窗！'));
$form->addInput($set1);  
    }
    
    public static function footer($obj)
    {
$loginurl=Typecho_Widget::widget('Widget_Options')->adminUrl.'login.php';
$url=Helper::options()->Plugin('changyandandian')->loginjsurl;
if(empty($url)){
$url='window.location.href="'.$loginurl.'";';  
}
elseif(strpos($url,'https://') !== false||strpos($url,'http://') !== false){ 
$url='window.location.href="'.$url.'";'; 
}
?>
<script>
function changyanlogin() {
<?php echo $url;?>
}
<?php 
 if (!Typecho_Widget::widget('Widget_User')->hasLogin()&&$_COOKIE["cyCookie"]){setcookie("cyCookie",''); echo 'if(localStorage.getItem("cy_lt")){localStorage.removeItem("cy_lt");window.location.reload();}';
 }
?>
</script>
<?php
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){

        
    }
    
   
}
