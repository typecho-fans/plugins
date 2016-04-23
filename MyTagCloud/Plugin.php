<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * MyTagCloud插件，后台控制前台标签智能显示
 *
 * @package MyTagCloud
 * @author  Ma Yanlong
 * @version 1.0.0
 * @link http://www.mayanlong.com
 */
class MyTagCloud_Plugin implements Typecho_Plugin_Interface
{

    // 是否启用
    const ENABLE_YES = 10;  //启用
    const ENABLE_NO = 20;  //不启用

    // 是否显示没使用的标签
    const ZERO_SHOW = 10;   //显示
    const ZERO_HIDE = 20;   //不显示

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // factory('name') name是插件接口名称 可以取任何名称 为方便寻找我们以文件目录命名
        Typecho_Plugin::factory('usr/themes/sidebar.php')->tagCloud = array('MyTagCloud_Plugin', 'process');
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

        // 是否启用
        $compatibilityMode = new Typecho_Widget_Helper_Form_Element_Radio('enable', array(
            self::ENABLE_YES   =>  _t('启用'),
            self::ENABLE_NO   =>  _t('不启用')
        ), self::ENABLE_YES, _t('是否启用该插件'), _t("启用后将这段PHP代码放到需要显示标签的模板中即可 Typecho_Plugin::factory('usr/themes/sidebar.php')->tagCloud(); "));
        $form->addInput($compatibilityMode->addRule('enum', _t('必须选择一个模式'), array(self::ENABLE_YES, self::ENABLE_NO)));

        // 是否显示没使用的标签
        $compatibilityMode = new Typecho_Widget_Helper_Form_Element_Radio('zero', array(
            self::ZERO_SHOW   =>  _t('显示'),
            self::ZERO_HIDE   =>  _t('不显示')
        ), self::ZERO_SHOW, _t('显示没使用的标签'), _t("默认显示所有标签，请根据自己需要进行设置。"));
        $form->addInput($compatibilityMode->addRule('enum', _t('必须选择一个模式'), array(self::ZERO_SHOW, self::ZERO_HIDE)));

        // 前台显示栏目标题
        $title = new Typecho_Widget_Helper_Form_Element_Text('title', NULL, '标签', _t('前台显示栏目标题'));
        $form->addInput($title);

        // 最多显示标签数量
        $limit = new Typecho_Widget_Helper_Form_Element_Text('limit', NULL, '20', _t('最多显示标签数量'));
        $form->addInput($limit);
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
     * @return void
     */
    public static function process()
    {
        $enable = Typecho_Widget::widget('Widget_Options')->plugin('MyTagCloud')->enable;
        $zero = Typecho_Widget::widget('Widget_Options')->plugin('MyTagCloud')->zero;
        $title = Typecho_Widget::widget('Widget_Options')->plugin('MyTagCloud')->title;
        $limit = (int)Typecho_Widget::widget('Widget_Options')->plugin('MyTagCloud')->limit;

        // 是否启用
        if ($enable != self::ENABLE_YES) {
            return;
        }

        // 查找满足条件的标签
        $tags = Typecho_Widget::widget('Widget_Metas_Tag_Cloud', array(
            'sort' => 'count',
            'ignoreZeroCount' => $zero == self::ZERO_HIDE ? true : false,
            'desc' => true,
            'limit' => $limit
        ));

        // 是否有标签
        if ($tags->have()) {
            self::render($title, $tags);
        }
    }

    /**
     * 输出Html标签
     *
     * @access public
     * @return void
     */
    public static function render($title, $tags)
    {
        // 拼接并输出html
        $html = '<section class="widget">
                    <h3 class="widget-title">'. $title .'</h3>
                    <div class="widget-list">';

                        while ($tags->next()) {
                            $html .= "<a href='{$tags->permalink}' style='display: inline-block; margin: 0 5px 5px 0;'>{$tags->name}</a>";
                        }

        $html .=    '</div>
                </section>';

        echo $html;
    }


}
