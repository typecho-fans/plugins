<?php
/**
 * 最小巧的首页图片自动轮播
 * 
 * @package Tinyfader
 * @author Willin Kan
 * @version 1.0.0
 * @update: 2011.07.3
 * @link http://kan.willin.org/typecho/
 */
class Tinyfader_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header       = array('Tinyfader_Plugin', 'headerScript');
        Typecho_Plugin::factory('Widget_Archive')->beforeRender = array('Tinyfader_Plugin', 'appendBox');

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

        $index_only = new Typecho_Widget_Helper_Form_Element_Radio(
          'index_only', array(0 => '所有页', 1 => '只有首页'), 1,
          '图片自动插入', '不用改模板, 会自动插入.');
        $form->addInput($index_only);

        $content_id = new Typecho_Widget_Helper_Form_Element_Text(
          'content_id', NULL, 'content',
          '搜索目标 id', '图片框会自动插入此 id 之前.<br/>(若前台不显示, 请查找 index.php 中已存在的 id 填入.)');
        $content_id->input->setAttribute('class', 'mini');
        $form->addInput($content_id);

        $nav_width = new Typecho_Widget_Helper_Form_Element_Text(
          'nav_width', NULL, 153,
          '导航宽度(px)', '右侧导航可配合模板自行设定宽度, 宽度设 0 不显示.<br/>最好同时在 tinyfader.css 调整 #slidebox 的 margin.<br/>');
        $nav_width->input->setAttribute('class', 'mini');
        $form->addInput($nav_width->addRule('isInteger', _t('请填入一个数字')));

        $typecho_intro = new Typecho_Widget_Helper_Form_Element_Radio(
          'typecho_intro', array(0 => '不显示', 1 => '显示'), 1,
          'Typecho intro', '这是个 html 內容, 你可以选择不显示或改写其内容.<br/>其內容在 Plugin.php appendBox() 函数中.');
        $form->addInput($typecho_intro);

echo "<div style='padding:10px 20px;font-size:13px;background:#E8EFD1'>
本插件为了配合傻瓜操作, 已完全自动化. 正常情况, 是不用设定就可正常执行, 但还是要注意以下事项:<br/>
1. 图片要 ftp 上传到 usr/plugins/Tinyfader/photos/  程序会自动读取, 此位置请勿放置其它文件.<br/>
2. 文件名请勿使用中文或特殊字符.<br/>
3. 所有图片长宽尺寸都一定要相同, 任何图片格式都可用, 建议使用 jpg.<br/>
4. 数量没限制, 为了网页读取速度, 建议在 4 ~ 6 张图片就好.</div>
";

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
     * 加入 header
     * 
     * @access public
     * @return void
     */
    public static function headerScript()
    {
        /** 载入插件的 css **/
        echo "<link rel='stylesheet' type='text/css' href='", Typecho_Widget::widget('Widget_Options')->pluginUrl, "/Tinyfader/tinyfader.css' />\n";

        /** 载入插件的 js **/
        echo "<script type='text/javascript' src='", Typecho_Widget::widget('Widget_Options')->pluginUrl, "/Tinyfader/tinyfader.js'></script>\n";

    }

    /**
     * 在元素之前追加图片
     *
     * @access public
     * @return void
     */
    public static function appendBox()
    {
        $config = Typecho_Widget::widget('Widget_Options')->plugin('Tinyfader');
        if ($config->index_only && !Typecho_Widget::widget('Widget_Archive')->is('index')) return;
        
        function appendBox($input) {
            $options = Typecho_Widget::widget('Widget_Options');
            $plug_url = $options->pluginUrl . '/Tinyfader/';
            $file_url = $options->pluginUrl . '/Tinyfader/photos/';
            $file_dir = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/Tinyfader/photos/';
            $files = scandir($file_dir);
            $num = count($files) - 2;
            list($width, $height) = getimagesize($file_dir . $files[2]);

            $config = $options->plugin('Tinyfader');
            $content_id = $config->content_id;
            $nav_width = $config->nav_width;
            $i = 0; $j = 1;

            // Typecho intro 內容
            if ($config->typecho_intro) {
                $img_li = "<li id='typecho_intro' style='width:{$width}px;height:{$height}px'><a class='guide' href='http://typecho.org/'></a><div class='guide_txt'>请至官方网站下载Typecho</div></li>\n";
                $nav_li = "<li onclick='slideshow.pos($i)' style='height:27px;text-align:center'>Typecho intro</li>\n";
                $i++;
            }

            $total_width = $width + $nav_width;
            $arrow = 70;
            $top = ($height - $arrow) / 2;
            $left = $width - $arrow * 2;
            $intro_height = $config->typecho_intro ? 27 : 0;
            $nav_height = round(($height - $intro_height) / $num) - 2;
            $thumb_width = $width * $nav_height / $height;

            foreach($files as $file) {
                $is_img = getimagesize($file_dir . $file);
                if ($is_img) {
                    $img_li .= "<li><img src='{$file_url}{$file}' alt='' /></li>\n";
                    $nav_li .= "<li onclick='slideshow.pos($i)' style='height:{$nav_height}px'><img src='{$file_url}{$file}' style='width:{$thumb_width}px;height:{$nav_height}px' alt=''/>$j</li>\n";
                    $i++; $j++;
                }
            }

            return preg_replace("#<div(.*)id=(.*)[\'|\"]{$content_id}[\'|\"](.*)>#", "
<div id='slidebox' style='width:{$total_width}px;height:{$height}px'>
<ul id='slides'>\n{$img_li}</ul>
<div class='sliderbutton_left' style='top:{$top}px' onclick='slideshow.move(-1)'></div>
<div class='sliderbutton_right' style='top:{$top}px;left:{$left}px' onclick='slideshow.move(1)'></div>
<ul id='img_nav' style='width:{$nav_width}px'>\n{$nav_li}</ul>
</div>
<script type='text/javascript'>
var slideshow=new TINY.fader.fade('slideshow',{
	id:'slides',
	auto:5,
	resume:true,
	navid:'img_nav',
	activeclass:'slidecurrent',
	visible:true,
	position:0
});
</script>\n
<div$1id=$2\"{$content_id}\"$3>", $input);
        }
        ob_start('appendBox');

    }

}
