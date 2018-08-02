<?php
/**
 * 博客飘雪插件
 *
 * 让你的博客飘起雪花来...
 * 
 * 1.0.1 2013-11-08  增加雪花随机色功能
 * 
 * @package Snowstorm
 * @author 阳光
 * @version 1.0.1
 * @link http://ysido.com
 */
class Snowstorm_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Snowstorm_Plugin', 'Snowstorm');
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
    public static function deactivate(){
        return _t('插件已禁用！');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $freezeOnBlur = new Typecho_Widget_Helper_Form_Element_Radio('freezeOnBlur', array('1'=>_t('是'), '0'=>_t('否')), '1', _t('失去焦点时停止'),_t('可节约CPU资源'));
        $form->addInput($freezeOnBlur);
        $followMouse = new Typecho_Widget_Helper_Form_Element_Radio('followMouse', array('1'=>_t('是'), '0'=>_t('否')), '1', _t('是否开启鼠标跟随效果'),_t('雪花飘动方向跟随鼠标'));
        $form->addInput($followMouse);
        $animationInterval = new Typecho_Widget_Helper_Form_Element_Radio('animationInterval', array('20'=>_t('快'), '30'=>_t('中'),'40'=>_t('慢')), '30', _t('雪花飘动速度'),_t('雪花飘动速度,建议设置中'));
        $form->addInput($animationInterval);
        $snowColorRand = new Typecho_Widget_Helper_Form_Element_Radio('snowColorRand', array('1'=>_t('开启'), '0'=>_t('关闭')), '0', _t('雪花颜色随机'),_t('若打开此选项，则雪花颜色设置将无效'));
        $form->addInput($snowColorRand);
        $snowColor = new Typecho_Widget_Helper_Form_Element_Text('snowColor', NULL, '#FFFFFF', _t('雪花颜色'), _t('必须为16位颜色值'));
        $form->addInput($snowColor);

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
     * 设置参数，并加入脚部
     * 
     * @access public
     * @return void
     */
    public static function Snowstorm()
    {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('Snowstorm'); 
        $color = preg_match('/^#[0-9a-f]{3,6}$/is', $options->snowColor);   //判断是否为颜色代码
        $color = ($color) ? $options->snowColor : '#fff';
        echo '<script>snowColor = "'.$color.'";freezeOnBlur = '.$options->freezeOnBlur.';followMouse = '.$options->followMouse.';animationInterval = '.$options->animationInterval.';snowColorRand='.$options->snowColorRand.';</script>'."\n\r".'<script type="text/javascript" src="/usr/plugins/Snowstorm/res/snowstorm.min.js"></script>';
    }
}
