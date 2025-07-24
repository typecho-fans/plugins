<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 模板预览插件 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 *
 * @package ThemeDemo
 * @author ShingChi, doudou, hongweipeng
 * @version 1.2.2
 * @link https://github.com/typecho-fans/plugins/tree/master/ThemeDemo
 */
/**
 * Example:
 *
 * URL后添加 ?theme=主题 | 为空则删除cookie，恢复默认
 *
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
        Typecho_Plugin::factory('index.php')->begin = array('ThemeDemo_Plugin', 'setMode');
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
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $mode = new Typecho_Widget_Helper_Form_Element_Radio('mode',
            array(
                'cookie' => 'Cookie',
                'route' => '子路径'
            ),
            'cookie',
            '演示模式',
            'Cookie模式仅作用于访客浏览器，用?theme空参数手动清除或过期后失效；<br/>子路径模式在服务端生成各主题专用地址，不影响根地址下的默认模板访问。'
        );
        $form->addInput($mode);

        $display = new Typecho_Widget_Helper_Form_Element_Radio('display',
            array(
                'true' => '开启',
                'false' => '关闭'
            ),
            'true',
            '前台导航',
            '关闭后前台将不会显示底部演示用的模板切换导航条。'
        );
        $form->addInput($display);
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
     * 输出导航条并配置路由表
     *
     * @access public
     * @return void
     */
    public static function setMode(){
        $options = Helper::options();
        $settings = $options->plugin('ThemeDemo');

        //输出导航条
        if ($settings->display == 'true') {
            $themes = scandir(__TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__);
            $uri = Typecho_Request::getInstance()->getRequestUri();
            echo '<div class="headwrap">' . "\r\n";
            echo '<div class="switch">' . "\r\n";
            echo '<span class="theme-title">模板预览: </span>' . "\r\n";
            echo '<select name="theme-dropdown" onchange="document.location.href=this.options[this.selectedIndex].value;">' . "\r\n";
            $suf = $settings->mode == 'cookie' ? '?theme=' : '/';
            foreach ($themes as $key => $value) {
                $ed = strpos($uri, $value) ? ' selected="selected"' : '';
                if (($key != '0') && ($key != '1')) {
                    echo '<option value="' . Helper::options()->index . $suf . $value . '"' . $ed . '>' . $value . '</option>' . "\r\n";
                }
            }
            $tip = $settings->mode == 'cookie' ? '在预览主题时请允许这个站点的 Cookie！' : '可选择下拉菜单中的主题名称切换访问路径。';
            echo '</select>' . "\r\n";
            echo '<span>这只是一个演示站点，' . $tip . '</span>';
            echo '</div>' . "\r\n";
            echo '</div>' . "\r\n";
            echo '<style type="text/css">
.headwrap { position: fixed; bottom: 0; left: 0; text-align: center; z-index: 9999; width: 100%; height: 35px; background-color: rgba(0, 0, 0, 0.8); -webkit-box-shadow: 0 1px 2px rgba(0, 0, 0, .5); -moz-box-shadow: 0 1px 2px rgba(0,0,0,.5); box-shadow: 0 1px 2px rgba(0, 0, 0, .5); }
.headwrap .switch { margin: 0 20px; font: 14px/34px "Microsoft YaHei"; color: white; }
.headwrap select { height: 22px; color: black; }
</style>' . "\r\n";
        }

        //配置路由表
        if ($settings->mode == 'cookie') {
            return;
        }
        $routes = $options->routingTable;
        $pathinfo = Typecho_Request::getInstance()->getPathInfo();
        if (preg_match('([^/]+)', $pathinfo, $matches)) {
            if (!self::check($matches[0])) return;
        } else {
            return;
        }
        global $theme;
        $theme = $matches[0];
        foreach ($routes[0] as $k => $v) {
            if ($k!='comment_page' && $k!='feedback') {
                $routes[0][$k]['url'] = '/' . $theme . $v['url'];
                $routes[0][$k]['regx'] = str_ireplace("|^", "|^/" . $theme, $v['regx']);
                $routes[0][$k]['format'] = implode("", array("/" . $theme, $v['format']));
            }
        }
        foreach ($routes as $k => $v) {
            if ($k!=0) {
                $routes[$k]['url'] = '/' . $theme . $v['url'];
            }
        }
        Typecho_Router::setRoutes($routes);
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function setTheme($widget)
    {
        $options = Helper::options();
        $settings = Helper::options()->plugin('ThemeDemo');

        if ($settings->mode == 'cookie') {
            $cookie = array(
                'key'   => '__typecho_theme',
                'expire' => 86400, //默认cookie存活时间
            );

            /** 请求模版预览时设置cookie */
            $request = $widget->request;
            if (isset($request->theme) && $request->isGet()) {
                $themeName = $request->theme;

                if (!empty($themeName) && static::check($themeName)) {
                    $value = static::themeInfo($themeName);
                    Typecho_Cookie::set($cookie['key'], serialize($value), $options->gmtTime + $cookie['expire']);
                } else {
                    Typecho_Cookie::delete($cookie['key']);
                    return;
                }
            }

            /** 配置初始化模版 */
            $themeCookie = Typecho_Cookie::get($cookie['key']);
            if (!$themeCookie) {
                return;
            }
            $themeInfo = unserialize($themeCookie);

            if (!static::check($themeInfo['theme'])) {
                Typecho_Cookie::delete($cookie['key']);
                return;
            }
        }

        if ($settings->mode == 'route') {
            global $theme;
            if (!$theme) return;
            $themeInfo = static::themeInfo($theme);
        }

        $themeName = $themeInfo['theme'];
        $themeDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR;

        /** 配置模版信息 */
        if (!empty($themeInfo['config'])) {
            $options->{'theme:' . $themeName} = $themeInfo['config'];
            foreach (unserialize($themeInfo['config']) as $row => $value) {
                $options->{$row} = $value;
            }
        }

        /** 配置模版 */
        $options->theme = $themeName;

        /** 配置模版路径 */
        $widget->setThemeDir($themeDir);
    }

    /**
     * 检测主题是否存在
     *
     * @access public
     * @param string $theme 主题名
     * @return boolean
     */
    public static function check($theme)
    {
        $themeDir = __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . DIRECTORY_SEPARATOR . $theme;
        if (is_dir($themeDir)) {
            return true;
        }
        return false;
    }

    /**
     * 获取主题相关信息
     *
     * @access public
     * @param string $themeName 主题名
     * @return array
     */
    public static function themeInfo($themeName)
    {
        $configFile = Helper::options()->themeFile($themeName, 'functions.php');
        if (file_exists($configFile)) {
            require_once $configFile;
            if (function_exists('themeConfig')) {
                $form = new Typecho_Widget_Helper_Form();
                themeConfig($form);
                $config = serialize($form->getValues());
            }
        }

        return array('theme' => $themeName, 'config' => isset($config) ? $config : '');
    }
}
