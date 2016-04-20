<?php
/**
 * 自动生成缩略名
 *
 * @package AutoSlug
 * @author ShingChi
 * @version 2.1.0
 * @link http://lcz.me
 * @dependence 14.5.26-*
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
        if (false == Typecho_Http_Client::get()) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持 php-curl 扩展而且没有打开 allow_url_fopen 功能, 无法正常使用此功能'));
        }

        Typecho_Plugin::factory('admin/write-post.php')->bottom_20 = array('AutoSlug_Plugin', 'ajax');
        Typecho_Plugin::factory('admin/write-page.php')->bottom_20 = array('AutoSlug_Plugin', 'ajax');

        Helper::addAction('auto-slug', 'AutoSlug_Action');

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
    public static function deactivate()
    {
        Helper::removeAction('auto-slug');
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
        /** 生成模式 */
        $mode = new Typecho_Widget_Helper_Form_Element_Radio(
            'mode',
            array(
                'baidu' => _t('百度翻译'),
                'youdao' => _t('有道翻译'),
                'google' => _t('谷歌翻译'),
                'pinyin' => _t('拼音')
            ),
            'baidu',
            _t('生成模式'),
            _t('默认为百度模式，除了拼音模式，英文翻译模式都需要填写相应的API')
        );
        $form->addInput($mode);

        /** 百度翻译 */
        $bdAppid = new Typecho_Widget_Helper_Form_Element_Text(
            'bdAppid', NULL, '',
            _t('百度翻译 APP ID'),
            _t('<a href="http://api.fanyi.baidu.com/api/trans/product/index">获取 APP ID</a>')
        );
        $form->addInput($bdAppid);

        $bdKey = new Typecho_Widget_Helper_Form_Element_Text(
            'bdKey', NULL, '',
            _t('百度翻译密钥'),
            _t('<a href="http://api.fanyi.baidu.com/api/trans/product/index">获取密钥</a>')
        );
        $form->addInput($bdKey);

        /** 有道翻译 */
        $ydKey = new Typecho_Widget_Helper_Form_Element_Text(
            'ydKey', NULL, '',
            _t('有道翻译 API Key'),
            _t('<a href="http://fanyi.youdao.com/openapi">获取 API Key</a>')
        );
        $form->addInput($ydKey);

        $ydFrom = new Typecho_Widget_Helper_Form_Element_Text(
            'ydFrom', NULL, '',
            _t('有道翻译 keyfrom'),
            _t('<a href="http://fanyi.youdao.com/openapi">获取 API Key</a>')
        );
        $form->addInput($ydFrom);
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
     * ajax执行action
     *
     * @access public
     * @param array $contents 文章输入信息
     * @return void
     */
    public static function ajax()
    {
        Typecho_Widget::widget('Widget_Options')->to($options);
?>
<script>
// auto slug
function autoSlug() {
    var title = $('#title');
    var slug = $('#slug');

    if (slug.val().length > 0 || title.val().length == 0) {
        return;
    }

    $.ajax({
        url: '<?php $options->index('/action/auto-slug?q='); ?>' + title.val(),
        success: function(data) {
            if (data.result.length > 0) {
                slug.val(data.result).focus();
                slug.siblings('pre').text(data.result);
            }
        }
    });
}

$(function() {
    $('#title').blur(autoSlug);
    $('#slug').blur(autoSlug);
});
</script>
<?php
    }
}
