<?php
/**
 * 自动生成缩略名
 *
 * @category system
 * @package AutoSlug
 * @author ShingChi
 * @version 1.0.0
 * @link http://lcz.me
 */
class AutoSlug_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('AutoSlug_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('AutoSlug_Plugin', 'render');

        return _t('请配置此插件的API KEY, 以使您的插件生效');
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
        /** 百度应用 API Key */
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text(
            'apiKey', NULL, '',
            _t('百度应用 API Key'),
            _t('<a href="http://developer.baidu.com/dev">获取 API Key</a>')
        );
        $form->addInput($apiKey);

        /** 生成模式 */
        $mode = new Typecho_Widget_Helper_Form_Element_Radio(
            'mode',
            array('en' => _t('英文'), 'zh' => _t('拼音')),
            'en',
            _t('生成模式'),
            _t('默认为英文模式')
        );
        $form->addInput($mode);
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
     * 插件实现方法
     *
     * @access public
     * @param array $contents 文章输入信息
     * @return void
     */
    public static function render($contents)
    {
        if (empty($contents['slug'])) {
            $builder = self::get();
            $settings = Helper::options()->plugin('AutoSlug');
            if ($settings->mode == 'zh') {
                $result = $builder->stringToPinyin($contents['title']);
            } else {
                $result = $builder->transform($contents['title']);
            }
            $contents['slug'] = $result;
        }
        return $contents;
    }

    /**
     * 获取生成器实例化对象
     *
     * @access public
     * @return object Pinyin or DuTranslate
     */
    public static function get()
    {
        $settings = Helper::options()->plugin('AutoSlug');

        if ($settings->mode == 'zh') {
            require_once 'AutoSlug/lib/Pinyin.php';
            return new Pinyin();
        } else {
            require_once 'AutoSlug/lib/Translate.php';
            return new DuTranslate($settings->apiKey);
        }
    }
}
