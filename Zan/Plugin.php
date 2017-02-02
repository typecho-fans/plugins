<?php
/**
 * 赞一个
 * 
 * @package Zan
 * @author 冰剑
 * @version 1.0.0
 * @link http://www.binjoo.net
 */
class Zan_Plugin implements Typecho_Plugin_Interface {
    public static function activate() {
        //Typecho_Plugin::factory('Widget_Archive')->header = array('Zan_Plugin', 'headlink');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Zan_Plugin', 'footlink');
        Helper::addAction('Zan', 'Zan_Action');
    }
    public static function deactivate(){
        Helper::removeAction('Zan');
    }
    public static function config(Typecho_Widget_Helper_Form $form){}
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    public static function headlink() {
        $css_url = Typecho_Common::url('Zan/css/Zan.min.css', Helper::options()->pluginUrl);
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$css_url}\" media=\"all\" />\n";
    }
    public static function footlink() {
        //echo Typecho_Common::url('action/WeChat?customreply', '');
        $script = '<script type="text/javascript">//<![CDATA[
	window.jQuery || document.write("<script type=\"text/javascript\" src=\"http://cdn.staticfile.org/jquery/1.8.3/jquery.min.js\"><\/script>")//]]></script>
';
        $script .= "<script type=\"text/javascript\">";
        $script .= '$(document).ready(function(){
            $(".post-zan").on("click", function(){
                var zan = $(this);
                $.post("' . Typecho_Widget::widget('Widget_Security')->getIndex('action/Zan') . '", {cid: zan.attr("data-cid")},function(data){
                        if(data.result == 1){
                            var val = zan.find("span").text();
                            zan.find("span").text(parseInt(val) + 1);
                        }
                        //alert(data.message);
                }, "json");
            })
})';
        $script .= "</script>\n";
        echo $script;
    }
}
