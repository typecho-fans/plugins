<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 有丰富自定义选项的动态聚合线条特效插件
 *
 * @package DynamicLines
 * @author Mario
 * @link https://www.changjiangblog.top
 * @version 1.0.0
 */
class DynamicLines_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->footer = array('DynamicLines_Plugin', 'footer');
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
        $mobile = new Typecho_Widget_Helper_Form_Element_Radio('mobile', array('1' => '是', '0' => '否'), 0, _t('移动端是否加载'), _t('配置移动端是否加载，默认不加载'));
        $form->addInput($mobile);
        /** 分类名称 */
        $color = new Typecho_Widget_Helper_Form_Element_Text('color', NULL, '0,0,255', _t('线条颜色'), _t("输入RGB颜色值(数字之间使用英文逗号隔开)，默认是0,0,255(蓝色)"));
        $form->addInput($color);
        $count = new Typecho_Widget_Helper_Form_Element_Text('count', NULL, '99', _t("线条数"), _t("页面上的线条数量，建议为50~200"));
        $form->addInput($count);
        $opacity = new Typecho_Widget_Helper_Form_Element_Text('opacity', NULL, '0.7', _t("线条透明度"), _t("填入0~1之间的数，默认是0.7"));
        $form->addInput($opacity);
        $zIndex = new Typecho_Widget_Helper_Form_Element_Text('zIndex',NULL,'999',_t('覆盖顺序'),_t("值高，则优先显示。可以为负数,为负数时则显示的优先级最低，即有可能被其他元素覆盖"));
        $form->addInput($zIndex);

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
     * 输出底部
     *
     * @access public
     * @return void
     */
     public static function footer()
    {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('DynamicLines');
        if (self::ismobile()&&$options->mobile=="0") {
            return;
        }
        echo '<script type="text/javascript" color="' . $options->color . '" opacity="' . $options->opacity . '" zIndex="' . $options->zIndex . '"count="' . $options->count . '" src="/usr/plugins/DynamicLines/canvas-nest.js"></script>';
        echo '<!--author:https://github.com/1379-->';
    }

    /**
     * 是否是移动端
     * @return bool 是否是移动端
     */
    public static function ismobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
            return true;

        //此条摘自TPM智能切换模板引擎，适合TPM开发
        if (isset ($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT'])
            return true;
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA']))
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
            );
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }
}
