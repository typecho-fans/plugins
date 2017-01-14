<?php
/**
 * 自动向百度提交链接，访客越多，收录量越大。
 * @package AutoBaiduSubmit
 * @author JinFeiJie
 * @version 1.0.0
 * @link http://jinfeijie.cn
 */
/* 激活插件方法 */
class AutoBaiduSubmit_Plugin implements Typecho_Plugin_Interface
{
    public static function activate(){
        Typecho_Plugin::factory('Widget_Archive')->header = array('AutoBaiduSubmit_Plugin', 'submit');
        return _t('插件已启用');
    }
     
    /* 禁用插件方法 */
    public static function deactivate(){
        return _t('插件已禁用');
    }
     
    /* 插件配置方法 */
    public static function config(Typecho_Widget_Helper_Form $form){}
     
    /* 个人用户的配置方法 */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
     
    /* 插件实现方法 */
    public static function submit(){
        $BaiduSubmit_code = "
    <script>
        (function(){
            var bp = document.createElement('script');
            var curProtocol = window.location.protocol.split(':')[0];
            if (curProtocol === 'https') {
                bp.src = 'https://zz.bdstatic.com/linksubmit/push.js';        
            }
            else {
                bp.src = 'http://push.zhanzhang.baidu.com/push.js';
            }
            var s = document.getElementsByTagName(\"script\")[0];
            s.parentNode.insertBefore(bp, s);
        })();
    </script>
            ";
        echo $BaiduSubmit_code;
    }
}