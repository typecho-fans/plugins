<?php
/**
 * SlimBox2 精致小巧的灯箱效果，需jQuery的支持。
 * 
 * @package SlimBox2
 * @author 冰剑
 * @version 1.0.5
 * @link http://www.binjoo.net/
 */
class SlimBox2_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Archive')->header = array('SlimBox2_Plugin', 'headlink');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('SlimBox2_Plugin', 'footlink');
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
	}
   
    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $selectImg = new Typecho_Widget_Helper_Form_Element_Text('selectImg',NULL,'.entry_content a:has(img)','范围选择器', '根据你所使用的主题而修改，一般只需修改.entry_content部分即可。');
        $form->addInput($selectImg);

        $overlayOpacity = new Typecho_Widget_Helper_Form_Element_Text('overlayOpacity',NULL,'0.75','遮罩层透明度', '默认为0.75，1 为不透明，0 为完全透明。');
        $overlayOpacity->input->setAttribute('class', 'mini');
        $form->addInput($overlayOpacity->addRule('isInteger','请输入0-1之间的数字，推荐默认0.75。')->addRule('required', '请设置遮罩层透明度，推荐默认0.75。'));

        $overlayFadeDuration = new Typecho_Widget_Helper_Form_Element_Text('overlayFadeDuration',NULL,'400','遮罩层隐现速度', '单位为毫秒，默认为400，禁用动画效果为1。');
        $overlayFadeDuration->input->setAttribute('class', 'mini');
        $form->addInput($overlayFadeDuration->addRule('isInteger','请输入数字，推荐默认400毫秒。')->addRule('required', '请设置遮罩层隐现速度，推荐默认400毫秒。'));

        $imageFadeDuration = new Typecho_Widget_Helper_Form_Element_Text('imageFadeDuration',NULL,'400','图片滑出速度', '单位为毫秒，默认为400，禁用动画效果为1。');
        $imageFadeDuration->input->setAttribute('class', 'mini');
        $form->addInput($imageFadeDuration->addRule('isInteger','请输入数字，推荐默认400毫秒。')->addRule('required', '请设置图片滑出速度，推荐默认400毫秒。'));

        $title = new Typecho_Widget_Helper_Form_Element_Radio('title',
            array('true' => '显示',
                  'false' => '隐藏'),
                  'true', '标题栏','隐藏后将不会显示标题、计数器、CLOSE关闭按钮。');
        $form->addInput($title);

        $captionAnimationDuration = new Typecho_Widget_Helper_Form_Element_Text('captionAnimationDuration',NULL,'400','标题栏滑出速度', '单位为毫秒，默认为400，禁用动画效果为1，标题栏隐藏后此设置失去效果。');
        $captionAnimationDuration->input->setAttribute('class', 'mini');
        $form->addInput($captionAnimationDuration->addRule('isInteger','请输入数字，推荐默认400毫秒。')->addRule('required', '请输入数字，推荐默认400毫秒。'));

        $loop = new Typecho_Widget_Helper_Form_Element_Radio('loop',
            array('true' => '是',
                  'false' => '否'),
                  'false', '图片循环','浏览至页面中第一张或最后一张图片时，是否可以循环。');
        $form->addInput($loop);

        $counterText = new Typecho_Widget_Helper_Form_Element_Text('counterText',NULL,'Image {x} of {y}','计数器提示', '<b>{x}</b>为当前图片索引，<b>{y}</b>为当前页面总图片数。<br />填写 <b>false</b> 是关闭此功能，不显示任何计数。');
        $form->addInput($counterText->addRule('required', '如果你不知道写什么，建议填写默认的：Image {x} of {y}。'));

        $jquerySelect= new Typecho_Widget_Helper_Form_Element_Radio('jquerySelect',
            array('true' => '是',
                  'false' => '否'),
                  'false', 'Google CDN jQuery库','如果主题本身已经引用了jQuery库，那么请无视此选项。');
        $form->addInput($jquerySelect);
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
     * 头部样式
     *
     * @access public
     * @param unknown $headlink
     * @return unknown
     */
    public static function headlink($cssUrl) {
        $Settings = Helper::options()->plugin('SlimBox2');
        //$Archive = Typecho_Widget::widget('Widget_Archive');
        $SlimBox2_url = Helper::options()->pluginUrl .'/SlimBox2/';
        $links = '<link rel="stylesheet" type="text/css" href="'.$SlimBox2_url.'css/slimbox2.css" />
';
        if($Settings->jquerySelect != "false"){
            $links .= '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
';
        }
        echo $links;
    }

    /**
     * 底部脚本
     *
     * @access public
     * @param unknown $footlink
     * @return unknown
     */
    public static function footlink($links) {
        $Settings = Helper::options()->plugin('SlimBox2');
        $SlimBox2_url = Helper::options()->pluginUrl .'/SlimBox2/';
        $links= '<script type="text/javascript" src="'.$SlimBox2_url.'js/slimbox2.js"></script>';
        $links.= '<script type="text/javascript">';
        $links.= 'jQuery(function($) {
            $("'.$Settings->selectImg.'").slimbox({
                overlayOpacity: '.$Settings->overlayOpacity.',
                overlayFadeDuration: '.$Settings->overlayFadeDuration.',
                imageFadeDuration: '.$Settings->imageFadeDuration.',
                captionAnimationDuration: '.$Settings->captionAnimationDuration.',
                loop:'.$Settings->loop.',
                counterText:"'.$Settings->counterText.'"
            });
            });';
        $links.= '</script>';
        if($Settings->title != "true"){
            $links.= '<style>#lbBottomContainer{display:none;}</style>';
        }
        echo $links;
    }
}