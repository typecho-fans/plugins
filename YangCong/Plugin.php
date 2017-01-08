<?php
/**
 * 简单、安全的身份验证服务
 * 
 * @package YangCong
 * @author 冰剑
 * @version 2.0.0
 * @link http://www.binjoo.net
 */
class YangCong_Plugin implements Typecho_Plugin_Interface {
    public static function activate() {
        Typecho_Plugin::factory('admin/header.php')->header = array('YangCong_Plugin', 'js_header');
        Typecho_Plugin::factory('admin/profile.php')->bottom = array('YangCong_Plugin', 'js_profile');
        Typecho_Plugin::factory('admin/login.php')->bottom = array('YangCong_Plugin', 'js_login');
        Helper::addAction('YangCong', 'YangCong_Action');
        return('微信助手已经成功激活，请进入设置Token!');
    }

    public static function deactivate() {
        Helper::removeAction('YangCong');
    }

    public static function config(Typecho_Widget_Helper_Form $form) {
        $yc_id = new Typecho_Widget_Helper_Form_Element_Text('yc_id', NULL, NULL, _t('应用 ID'), '啦啦啦啦');
        $form->addInput($yc_id);

        $yc_key = new Typecho_Widget_Helper_Form_Element_Text('yc_key', NULL, NULL, _t('应用 KEY'), '啦啦啦啦');
        $form->addInput($yc_key);

        $yc_input = new Typecho_Widget_Helper_Form_Element_Radio('yc_input', array('1' => '是', '0' => '否'), '0', '是否关闭传统登陆', '关闭后仅能使用扫码登陆。');
        $form->addInput($yc_input);

        $yc_request = new Typecho_Widget_Helper_Form_Element_Text('yc_request', NULL, '5', _t('验证频率'), '请求洋葱服务器的频率，如果你不明白这是什么，请不要修改。');
        $yc_request->input->setAttribute('class', 'mini');
        $yc_request->addRule('isInteger','你不填写数字让我怎么办？实在搞不明白你就写个 5 得了。');
        $form->addInput($yc_request);

        $yc_auth_type = new Typecho_Widget_Helper_Form_Element_Radio('yc_auth_type', array('1' => '确认按钮', '2' => '手势密码', '3' => '人脸', '4' => '声纹'), '1', '验证方式', '扫码成功后确认操作使用的验证方式');
        $form->addInput($yc_auth_type);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function js_header($str) {
        $str .= '<style type="text/css">
                .tab_panel, .typecho-login form{display: none}
                #tab_qrcode{padding-bottom: 1em; text-align: center}
                #tab_qrcode span{height: 100%; display: inline-block; vertical-align: middle}
                #tab_qrcode img, .bind_qrcode img{height: 280px; width: 280px; vertical-align: middle;cursor:pointer}
                #tab_qrcode img{margin-top: 1em}
                .bind_qrcode{display:none}
                </style>';
        echo $str;
    }

    public static function js_profile($str) {
        $settings = Helper::options()->plugin('YangCong');
        $str .= '<script type="text/javascript">';
        $str .= 'jQuery(function($) {
                var bindTxt = \'<p class="bind_status">绑定状态：<span>未绑定</span></p><p class="bind_qrcode"><img src="" /></p>\';
                $("div.typecho-page-main div:first").append(bindTxt);
                var timer = null;

                $(".bind_qrcode img").click(function(){
                    $.getJSON("' . Helper::security()->getIndex('/action/YangCong?do=bind') . '", function(data){
                        if(!data){
                            $(".bind_status span").html("已绑定");
                            $(".bind_qrcode").remove();
                        } else if(data && data.status == 200){
                            $(".bind_status span").html("未绑定");
                            $(".bind_qrcode").show().find("img").attr("src", data.qrcode_url);
                            timer = window.clearInterval(timer);
                            timer = window.setInterval(function(){auth(data.event_id)},' . ($settings->yc_request * 1000) . ');
                        }
                    });
                }).click();

                auth = function(event_id){
                    $.getJSON("' . Helper::security()->getIndex('/action/YangCong?do=auth') . '", {event_id : event_id, action : "bind"},  function(data){
                        if(data.status == 200){
                            timer = window.clearInterval(timer);
                            $(".bind_status span").html("绑定成功");
                            $(".bind_qrcode").remove();
                        }
                    });
                }
            });';
        $str .= '</script>';
        echo $str;
    }

    public static function js_login($str) {
        $settings = Helper::options()->plugin('YangCong');
        $str .= '<script type="text/javascript">';
        $str .= 'jQuery(function($) {
            $("form[name=login]").wrap(\'<div id="tab_account" class="tab_panel"></div>\');
            var tab = \'<ul class="typecho-option-tabs clearfix">\';
               tab += \'<li class="w-50 active"><a href="#tab_qrcode">扫码登陆</a></li>\';';
        if($settings->yc_input){
            //$str .= '$("div.login_account").remove();';
        } else {
            $str .= 'tab += \'<li class="w-50"><a href="#tab_account">传统登陆</a></li>\';';
            $str .= '$(".typecho-login form").show();';
        }
        $str .= 'tab += \'</ul>\';
                $("div.typecho-login h1").after(tab);
                $("p.more-link").before(\'<div id="tab_qrcode" class="tab_panel"><span></span><img src="" /></div>\');
                $("ul.typecho-option-tabs li a").click(function(){
                    $("ul.typecho-option-tabs li").removeClass("active");
                    $("div.tab_panel").hide();
                    $(this).parent("li").addClass("active");
                    $($(this).attr("href")).show();
                });
                $("#tab_qrcode").show();

                var timer = null;
                $("#tab_qrcode img").click(function(){
                    $.getJSON("' . Helper::security()->getIndex('/action/YangCong?do=login') . '", function(data){
                        if(data.status == 200){
                            $("#tab_qrcode img").attr("src", data.qrcode_url);
                            timer = window.clearInterval(timer);
                            timer = window.setInterval(function(){auth(data.event_id)},' . ($settings->yc_request * 1000) . ');
                        }
                    });
                }).click();

                auth = function(event_id){
                    $.getJSON("' . Helper::security()->getIndex('/action/YangCong?do=auth') . '", {event_id : event_id, action : "login"},  function(data){
                        if(data.status == 200){
                            timer = window.clearInterval(timer);
                            window.location.href = data.redirect;
                        }
                    });
                }
            });';
        $str .= '</script>';
        echo $str;
    }
}
