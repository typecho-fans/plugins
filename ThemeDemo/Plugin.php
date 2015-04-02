<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * URL后添加 ?theme={主题目录} | 为空则删除cookie，恢复默认
 *
 * @category system
 * @package ThemeDemo
 * @author doudou
 * @version 1.0.1
 * @link https://github.com/doudoutime
 */
class ThemeDemo_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->handleInit = array('ThemeDemo_Plugin', 'setTheme');
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
    public static function config(Typecho_Widget_Helper_Form $form){}

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
    public static function setTheme($widget)
    {
        $cookie = Array (
            'name'   => '__typecho_theme',
            'expire' => 86400, //默认cookie存活时间
        );
        $options = Typecho_Widget::widget('Widget_Options');

        if (isset($widget->request->theme) && $widget->request->isGet()) {
            if ($widget->request->theme) {
                $theme = $widget->request->theme;
                if (static::check($theme)) {
                    Typecho_Cookie::set($cookie['name'], $widget->request->theme, $options->gmtTime + $cookie['expire'], $options->siteUrl);
                } else {
                    $widget->response->redirect(Typecho_Common::url($widget->request->getPathInfo(), $options->siteUrl));
                }
            } else {
                Typecho_Cookie::delete($cookie['name']); //直接提交?theme将删除cookie，恢复默认主题
                return;
            }
        } else {
            $theme = Typecho_Cookie::get($cookie['name']);
            if (!$theme) return;
            if (!static::check($theme)) {
                Typecho_Cookie::delete($cookie['name']);
                return;
            }
        }

        /** 删除旧主题的相关设置 */
        $themeRow = 'theme:' . $options->theme;
        if (isset($options->{$themeRow})) {
            $config = unserialize($options->{$themeRow});
            $options->{$themeRow} = '';
            foreach ($config as $row => $value) {
                $options->{$row} = '';
            }
        }

        /** 载入新主题的相关设置 参考var/Widget/Themes/Edit.php */
        $themeDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR;
        $configFile = $themeDir . 'functions.php';

        if (file_exists($configFile)) {
            require_once $configFile;
            if (function_exists('themeConfig')) {
                $form = new Typecho_Widget_Helper_Form();
                themeConfig($form);
                $config = $form->getValues();
                if ($config) {
                    $options->{'theme:' . $theme} = serialize($config);
                    foreach ($config as $row => $value) {
                        $options->{$row} = $value;
                    }
                }
            }
        }

        /** 修改$this->options->theme */
        $options->theme = $theme;

        /** 修改$this->_themeDir */
        $widget->setThemeDir($themeDir);
    }

    /**
     * 检查主题目录是否存在
     *
     * @access public
     * @return void
     */
    public static function check($theme)
    {
        $themeDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . DIRECTORY_SEPARATOR . $theme;
        if (is_dir($themeDir)) {
            return true;
        }
        return false;
    }
}
