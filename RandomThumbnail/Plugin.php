<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 *
 * 随机图片挂件widget
 *
 * @package Typecho-RandomThumbnail
 * @author  LittleJake
 * @version 1.0.0
 * @link https://blog.littlejake.net
 */
class RandomThumbnail_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法
     *
     * @return void
     */
    public static function activate(){}

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @access public
     * @return void
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $url = new Typecho_Widget_Helper_Form_Element_Textarea('url', NULL, NULL, _t('图片地址'), _t('输入图片地址，一行一条'));
        $template = new Typecho_Widget_Helper_Form_Element_Textarea(
            'template',
            NULL,
            <<<EOF
<div style="width: fit-content; height: 300px; overflow: hidden; border-radius: 10px; max-height: 100%; max-width: 100%; margin:5% auto;">
    <img src="{img_src}" alt="head-img" class="" style="">
</div>
EOF,
            _t('图片显示自定义模板'),
            _t('可用变量参考：<a href="https://github.com/LittleJake/Typecho-RandomThumbnail/blob/master/README.md" target="_blank">README.md</a>')
        );

        $form->addInput($url);
        $form->addInput($template);
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
     *
     * 获取缩略图
     *
     * @author LittleJake
     * @param int $seed 随机数
     * @return bool
     */

    public static function getThumbnail($seed = 0)
    {
        try{
            $url = Typecho_Widget::widget('Widget_Options')->plugin('RandomThumbnail')->url;
            $urls = explode("\r\n",$url);

            if(sizeof($urls) == 0)
                return false;

            $seed = $seed>0?$seed:rand(0,9999);
            $num = sizeof($urls);
            $index = $seed % $num;

            echo self::format($urls[$index]);

            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    /**
     * 用于处理模板数据
     *
     * @param $url
     * @return string|string[]
     * @throws Typecho_Exception
     */
    public static function format($url){
        return str_replace('{img_src}',$url, Typecho_Widget::widget('Widget_Options')
            ->plugin('RandomThumbnail')->template);
    }
}
