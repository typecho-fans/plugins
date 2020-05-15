<?php
/**
 * typecho 博客的一款返回顶部插件
 *
 * @package TopTop
 * @author Heeeepin
 * @version 1.0.0
 * @link http://heeeepin.com
 */

class TopTop_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('TopTop_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('TopTop_Plugin', 'footer');
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
        $m1 = Helper::options()->pluginUrl . '/TopTop/models/1.png';
        $m2 = Helper::options()->pluginUrl . '/TopTop/models/2.png';
        $model1 = "<img src=$m1 alt='模型1'/>";
        $model2 = "<img src=$m2 alt='模型2'/>";
        $jquery = new Typecho_Widget_Helper_Form_Element_Checkbox('jquery', array('jquery' => '禁止加载jQuery'), false, _t('Jquery设置'), _t('插件需要加载jQuery，如果主题模板已经引用加载JQuery，则可以勾选。'));
        $model = new Typecho_Widget_Helper_Form_Element_Radio('model', array('model1' => $model1, 'model2' => $model2), 'model2', _t('模型设置'), _t('选择一个你喜欢的模型'));
        $form->addInput($jquery);
        $form->addInput($model);
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
     * 页头输出相关代码
     *
     * @access public
     * @param unknown header
     * @return unknown
     */
    public static function header()
    {
        $path = Helper::options()->pluginUrl . '/TopTop/';
        $options = Helper::options()->plugin('TopTop');
        $model = $options->model;
        echo '<link rel="stylesheet" type="text/css" href="' . $path . 'css/' . $model . '.css" />';
    }


    /**
     * 页脚输出相关代码
     *
     * @access public
     * @param unknown footer
     * @return unknown
     */
    public static function footer()
    {
        $path = Helper::options()->pluginUrl . '/TopTop/';
        $options = Helper::options()->plugin('TopTop');
        echo '<div class="back-to-top" style="top: -700px;"></div>';
        if (!$options->jquery) {
            echo '<script type="text/javascript" src="' . $path . 'js/jquery.min.js"></script>';
        }
        echo '<script type="text/javascript" src="' . $path . 'js/toptop.js"></script>';
    }
}




