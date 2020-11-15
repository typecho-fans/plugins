<?php
// if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 基于<a href="https://www.ihewro.com/archives/489/">handsome主题</a>的<a href="https://moe.best/">神代綺凜</a>式魔改主题 </br> 更新时间: <span style="color:red">2020-08-24</span>      
 *
 * @package KirinShiKi
 * @author Sanakey,JeffersonQin
 * @version 3.0.1
 * @link https://github.com/JeffersonQin/KirinShiKi
 */
class KirinShiKi_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'footer');
        return "插件启动成功";
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
        return "插件禁用成功";
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

        // 插件信息与更新检测
        function check_update($version)
        {
            echo "<style>.info{text-align:center; margin:20px 0;} .info > *{margin:0 0 15px} .buttons a{background:#467b96; color:#fff; border-radius:4px; padding: 8px 10px; display:inline-block;}.buttons a+a{margin-left:10px}</style>";
            echo "<div class='info'>";
            echo "<h2>神代綺凜式魔改主题插件 (" . $version . ")</h2>";
            echo "<p>By: <a href='https://github.com/Sanakey'>Sanakey</a></p>";
            echo "<p class='buttons'><a href='https://keymoe.com/archives/31/'>插件说明</a>
                <a href='https://github.com/Sanakey/KirinShiKi'>查看更新</a></p>";
            echo "<p>更多说明请点击插件说明或<a href='https://github.com/Sanakey/KirinShiKi'>点击前往github查看</a>~</p>";

            echo "</div>";
        }
        check_update("3.0.1");

        // 自定义pc背景
        $pcBg = new Typecho_Widget_Helper_Form_Element_Text(
            'pcBg',
            NULL,
            'https://api.btstu.cn/sjbz/?lx=dongman',
            _t('pc端背景图：'),
            _t('pc端背景图，请输入图片的地址，为空时不设置背景图片。默认提供随机动漫背景图，<a href="https://www.lxzzz.cn/337.html">想要更多风格请点击</a>')
        );
        $form->addInput($pcBg);

        // 自定义手机端背景
        $mpBg = new Typecho_Widget_Helper_Form_Element_Text(
            'mpBg',
            NULL,
            'https://api.btstu.cn/sjbz/?lx=m_dongman',
            _t('手机端背景图：'),
            _t('手机端背景图，请输入图片的地址，为空时不设置背景图片。默认提供随机动漫背景图，<a href="https://www.lxzzz.cn/337.html">想要更多风格请点击</a>')
        );
        $form->addInput($mpBg);

        // 是否启用标题卖萌
        $moeTitle = new Typecho_Widget_Helper_Form_Element_Radio(
            'moeTitle',
            array(
                '0' => _t('否'),
                '1' => _t('是'),
            ),
            '1',
            _t('是否启用标题卖萌'),
            _t('此选项控制浏览器标签是否启用卖萌标题。')
        );
        $form->addInput($moeTitle);

        // 是否启用复制版权提醒
        $copyTips = new Typecho_Widget_Helper_Form_Element_Radio(
            'copyTips',
            array(
                '0' => _t('否'),
                '1' => _t('是'),
            ),
            '1',
            _t('是否启用复制版权提醒'),
            _t('开启此选项时，用户在博客内复制时将会弹出版权提醒')
        );
        $form->addInput($copyTips);

        // 右下角版权样式
        $copyrightType = new Typecho_Widget_Helper_Form_Element_Radio(
            'copyrightType',
            array(
                '0' => _t('美化样式'),
                '1' => _t('文本样式'),
            ),
            '0',
            _t('右下角版权样式')
        );
        $form->addInput($copyrightType);

        // 是否启用了pjax
        $pjax = new Typecho_Widget_Helper_Form_Element_Radio(
            'pjax',
            array(
                '0' => _t('否'),
                '1' => _t('是'),
            ),
            '1',
            _t('是否启用了PJAX'),
            _t('如果你启用了pjax，函数将会每次在pjax回调内执行。如果没启用，函数将在页面加载完时执行一次。<b style="color:#f23232">如果你不懂此选项的含义，请跟着handsome主题是否设置了pjax来设置此选项。</b>')
        );
        $form->addInput($pjax);
    }
    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 页脚输出相关代码
     *
     * @access public
     * @param unknown render
     * @return unknown
     */
    public static function footer()
    {
        //  获取用户配置
        $options = Helper::options();
        $pcBg = $options->plugin('KirinShiKi')->pcBg;
        $mpBg = $options->plugin('KirinShiKi')->mpBg;
        $moeTitle = $options->plugin('KirinShiKi')->moeTitle;
        $copyTips = $options->plugin('KirinShiKi')->copyTips;
        $copyrightType = $options->plugin('KirinShiKi')->copyrightType;
        // 输出css文件
        $path = $options->pluginUrl . '/KirinShiKi/';
        echo '<link rel="stylesheet" type="text/css" href="' . $path . 'css/kirin.css" />';
        //  输出js文件
        $src = $options->pluginUrl . '/KirinShiKi/js/kirin.js';
        echo "<script src='$src'></script>";
        //   echo '<script type="text/javascript" src="' . $src . '"></script>';

        $code = 'setHref(getHref());colorfulTags();' . ($moeTitle ? 'moeTitle();' : '') . ($copyTips ? 'copyTips();' : '');

        $pjax = $options->plugin('KirinShiKi')->pjax;
        $script = '<script> setCopyright(' . $copyrightType . ');';
        if ($pjax) { //开启pjax
            $script .= '$(document).on("ready pjax:end", ' . 'function() { ' . $code . '});';
        } else {
            $script .= '$(document).ready(function() {' . $code . '});';
        }
        $script .= '</script>';
        // $script = '<script>$(document).on("ready pjax:end", ' . 'function() {needpjax()});</script>';
        $css = '<style>
            #mybg{background-image:url(' . $pcBg . ');}
            @media screen and (max-width:991px) {
                #mybg { 
                    background-image:url(' . $mpBg . ');
                }
            }
        </style>';

        echo $css;
        echo $script;
    }
}
