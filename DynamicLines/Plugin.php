<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 有丰富自定义选项的动态聚合线条特效插件
 *
 * @package DynamicLines
 * @author 长江
 * @link http://www.changjiangblog.top
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
        echo '<script type="text/javascript" color="' . $options->color . '" opacity="' . $options->opacity . '" zIndex="-2" count="' . $options->count . '" src="usr/plugins/DynamicLines/canvas-nest.js"></script>';
    }

}
