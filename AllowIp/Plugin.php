<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 后台管理IP白名单
 * 
 * @package AllowIp
 * @author Fuzqing
 * @version 1.0.1
 * @link https://huangweitong.com
 */
class AllowIp_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '1.0.1';
    
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/common.php')->begin = array('AllowIp_Plugin', 'check');
        Typecho_Plugin::factory('Widget_Login')->loginSucceed = array('AllowIp_Plugin', 'check');
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
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 允许登陆后台的ip */
        $allow_ip = new Typecho_Widget_Helper_Form_Element_Text('allow_ip', NULL, NULL, _t('后台管理IP白名单'),'请输入ip地址，如果有多个请使用逗号隔开');
        $form->addInput($allow_ip);
        /** 跳转链接 */
        $location_url = new Typecho_Widget_Helper_Form_Element_Text('location_url', NULL, 'https://www.google.com/', _t('跳转链接'),'请输入标准的URL地址，IP白名单外的IP访问后台将会跳转至这个URL');
        $form->addInput($location_url);
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
     * 检测ip白名单
     * 
     * @access public
     * @return void
     */
    public static function check()
    {
        static $realip = NULL;
        //判断服务器是否允许$_SERVER
        if(isset($_SERVER)) {
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        }else{
            //不允许就使用getenv获取
            if(getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv( "HTTP_X_FORWARDED_FOR");
            }elseif(getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            }else {
                $realip = getenv("REMOTE_ADDR");
            }
        }

        if($realip !== NULL){
            $config = json_decode(json_encode(unserialize(Helper::options()->plugin('AllowIp'))));
            if(empty($config->allow_ip)) {
                $options = Typecho_Widget::widget('Widget_Options');
                $config_url = trim($options->siteUrl,'/').'/'.trim(__TYPECHO_ADMIN_DIR__,'/').'/options-plugin.php?config=AllowIp';
                echo '<span style="text-align: center;display: block;margin: auto;font-size: 1.5em;color:#1abc9c">您还没有设置后台管理IP白名单，<a href="'.$config_url.'">马上去设置</a></span>';
            } else {
                $allow_ip_arr = str_replace('，',',',$config->allow_ip);
                $allow_ip = explode(',', $allow_ip_arr);
                
                //如果允许所有IP都通行的话，就打开下一行注释
                //$allow_ip[] = '0.0.0.0';
                
                $location_url = trim($config->location_url) ? trim($config->location_url) : 'https://www.google.com/';
                if(!in_array('0.0.0.0', $allow_ip)) {
                    if(!in_array($realip, $allow_ip)) {
                        Typecho_Cookie::delete('__typecho_uid');
                        Typecho_Cookie::delete('__typecho_authCode');
                        @session_destroy();
                        header('Location: '.$location_url);
                        exit;
                    }
                }
            }
        }
    }
}
