<?php
/**
 * Xiami
 * 
 * @package Xiami
 * @author Mufeng
 * @version 0.0.1
 * @link http://mufeng.me
 */
class Xiami_Plugin implements Typecho_Plugin_Interface
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
        Helper::addPanel(1, 'Xiami/panel.php', _t('虾米音乐同步预览'), _t('虾米音乐同步预览'), 'administrator');
        Typecho_Plugin::factory('Widget_Archive') ->header = array('Xiami_Plugin', 'headerScript');
        Typecho_Plugin::factory('Widget_Archive') ->footer = array('Xiami_Plugin', 'footerScript');

        return _t('请在插件设置里设置参数') . $error;
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
        Helper::removePanel(1, 'Xiami/panel.php');
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
        Typecho_Widget::widget('Widget_Contents_Page_List')->to($pages);
        $page_arr = array();
        while($pages->next()):
            $page_arr[$pages->slug] = $pages->title;
        endwhile;

        $user_id = new Typecho_Widget_Helper_Form_Element_Text('user_id', NULL, '',
        _t('虾米用户ID'), _t('虾米用户ID：http://www.xiami.com/u/<strong>33663442</strong> （填入数字）'));
        $user_id->addRule('isInteger', _t('填写的虾米用户ID必须为数字'));
        $user_id->addRule('required', _t('必须填写一个虾米用户ID'));

        $form->addInput($user_id);


        $user_page = new Typecho_Widget_Helper_Form_Element_Select('user_page', $page_arr, '',
            _t('选择页面'), _t('此页面用来展示虾米专辑或精选集。'));

        $form->addInput($user_page);

        $user_type = new Typecho_Widget_Helper_Form_Element_Select('user_type', array(
                'all' => '所有',
                'collects' => '精选集',
                'albums' => '专辑'
            ), 'all',
            _t('同步选择'), _t('默认选择“全部”，同步精选集和专辑。'));

        $form->addInput($user_type);

        $user_width = new Typecho_Widget_Helper_Form_Element_Text('user_width', NULL, '',
            _t('页面宽度'), _t('如果与主题宽度不一致可以试着调节此项。默认使用主题上层结构宽度。'));
        $user_width->addRule('isInteger', _t('填写的宽度ID必须为数字'));

        $form->addInput($user_width);

        $user_fullwidth = new Typecho_Widget_Helper_Form_Element_Radio('user_fullwidth', array(
                '0' => '页面宽度',
                '1' => '浏览器宽度'
            ), '0',
            _t('专辑预览宽度'), _t('如果与主题宽度不一致可以试着调节此项。默认选择页面宽度。'));

        $form->addInput($user_fullwidth);

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
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('Xiami');
        $page_slug = $config->user_page;

        if( $page_slug && Typecho_Widget::widget('Widget_Archive')->is('page', $page_slug) ){
            $Helper_options = Helper::options();
            $css_url = Typecho_Common::url('Xiami/static/css/wp-xiami.css', $Helper_options->pluginUrl);
            echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$css_url}\" media=\"all\" />\n";
        }
    }

    /**
     * 加入 footer
     *
     * @access public
     * @return void
     */
    public static function footerScript()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('Xiami');
        $page_slug = $config->user_page;

        if( $page_slug && Typecho_Widget::widget('Widget_Archive')->is('page', $page_slug) ){
            $Helper_options = Helper::options();
            $jquery_url = Typecho_Common::url('Xiami/static/js/jquery.js', $Helper_options->pluginUrl);
            $js_url = Typecho_Common::url('Xiami/static/js/wp-xiami.js', $Helper_options->pluginUrl);

            $static = Typecho_Common::url('Xiami/static/', $Helper_options->pluginUrl);
            $user_id = $config->user_id;
            $user_type = $config->user_type;
            $user_width = $config->user_width;
            $user_fullwidth = $config->user_fullwidth;

            echo "<script>var global = {remote:'http:\/\/goxiami.duapp.com\/do.php',static:'$static',jquery:'$jquery_url',user_width:'$user_width',user_id:'$user_id',user_type:'$user_type',user_fullwidth:'$user_fullwidth'}\n</script><script src='{$js_url}'></script>\n";
        }
    }

}
