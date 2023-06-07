<?php

/**
 * 把外部链接转换为指定内部链接
 *
 * @package ShortLinks
 * @author Ryan
 * @version 1.2.0
 * @link https://github.com/benzBrake/ShortLinks
 */

class ShortLinks_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return String
     * @throws Typecho_Plugin_Exception
     * @throws \Typecho\Db\Exception
     */
    public static function activate()
    {
        $db = self::db();
        $tableName = $db->getPrefix() . 'shortlinks';
        $adapter = $db->getAdapterName();
        if ("Pdo_SQLite" === $adapter || "SQLite" === $adapter) {
            $db->query(" CREATE TABLE IF NOT EXISTS " . $tableName . " (
			   id INTEGER PRIMARY KEY,
			   key TEXT,
			   target TEXT,
			   count NUMERIC)");
        }
        if ("Pdo_Mysql" === $adapter || "Mysql" === $adapter) {
            $dbConfig = null;
            if (class_exists('\Typecho\Db')) {
                $dbConfig = $db->getConfig($db::READ);
            } else {
                $dbConfig = $db->getConfig()[0];
            }
            $charset = $dbConfig->charset;
            $db->query("CREATE TABLE IF NOT EXISTS " . $tableName . " (
				  `id` int(8) NOT NULL AUTO_INCREMENT,
				  `key` varchar(64) NOT NULL,
				  `target` varchar(10000) NOT NULL,
				  `count` int(8) DEFAULT '0',
				  PRIMARY KEY (`id`)
				) DEFAULT CHARSET=$charset AUTO_INCREMENT=1");
        }
        if ("Pdo_Pgsql" === $adapter || "Pgsql" === $adapter) {
            $db->query("CREATE TABLE IF NOT EXISTS " . $tableName . " (
                id SERIAL PRIMARY KEY,
                key TEXT NOT NULL,
                target TEXT NOT NULL,
                count INTEGER DEFAULT 0)");
        }

        Helper::addAction('shortlinks', 'ShortLinks_Action');
        Helper::addRoute('go', '/go/[key]/', 'ShortLinks_Action', 'shortlink');
        Helper::addPanel(2, 'ShortLinks/panel.php', '短链管理', '短链接管理', 'administrator');

        if (class_exists('\Widget\Base\Contents')) {
            Typecho\Plugin::factory('\Widget\Base\Contents')->contentEx = array('ShortLinks_Plugin', 'replace');
            Typecho\Plugin::factory('\Widget\Base\Contents')->excerptEx = array('ShortLinks_Plugin', 'replace');
            Typecho\Plugin::factory('\Widget\Base\Contents')->filter = array('ShortLinks_Plugin', 'forceConvert');
            Typecho\Plugin::factory('\Widget\Base\Comments')->contentEx = array('ShortLinks_Plugin', 'replace');
            Typecho\Plugin::factory('\Widget\Base\Comments')->filter = array('ShortLinks_Plugin', 'authorUrlConvert');
            Typecho\Plugin::factory('\Widget\Archive')->singleHandle = array('ShortLinks_Plugin', 'fieldsConvert');
        } else {
            Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('ShortLinks_Plugin', 'replace');
            Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('ShortLinks_Plugin', 'replace');
            Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('ShortLinks_Plugin', 'forceConvert');
            Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('ShortLinks_Plugin', 'replace');
            Typecho_Plugin::factory('Widget_Abstract_Comments')->filter = array('ShortLinks_Plugin', 'authorUrlConvert');
            Typecho_Plugin::factory('Widget_Archive')->singleHandle = array('ShortLinks_Plugin', 'fieldsConvert');
        }

        return ('数据表 ' . $tableName . ' 创建成功，插件已经成功激活！');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return String
     * @throws Typecho_Plugin_Exception
     * @throws \Typecho\Plugin\Exception
     */
    public static function deactivate()
    {
        $config = self::options('ShortLinks');
        $db = self::db();
        $adapter = $db->getAdapterName();
        $dropTableSql = '';

        Helper::removeRoute('go');
        Helper::removeAction('shortlinks');
        Helper::removePanel(2, 'ShortLinks/panel.php');

        if ($config->isDrop == 0) {
            if ("Pdo_SQLite" === $adapter || "SQLite" === $adapter) {
                $dropTableSql = "DROP TABLE '{$db->getPrefix()}shortlinks'";
            }
            if ("Pdo_Mysql" === $adapter || "Mysql" === $adapter) {
                $dropTableSql = "DROP TABLE `{$db->getPrefix()}shortlinks`";
            }
            if ("Pdo_Pgsql" === $adapter || "Pgsql" === $adapter) {
                $dropTableSql = "DROP TABLE \"{$db->getPrefix()}shortlinks\"";
            }
            $db->query($dropTableSql, Typecho_Db::WRITE);
            return (_t('短链接插件已被禁用，其表（%s）已被删除！', $db->getPrefix() . 'shortlinks'));
        } else {
            return (_t('短链接插件已被禁用，但是其表（%s）并没有被删除！', $db->getPrefix() . 'shortlinks'));
        }
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
        $radio = new Typecho_Widget_Helper_Form_Element_Radio('convert', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('外链转内链'), _t('开启后会帮你把外链转换成内链'));
        $form->addInput($radio);
        $radio = new Typecho_Widget_Helper_Form_Element_Radio('convertCommentLink', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('转换评论者链接'), _t('开启后会帮你把评论者链接转换成内链'));
        $form->addInput($radio);
        $template_files = scandir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates');
        $goTemplates = array('NULL' => '禁用');
        foreach ($template_files as $item) {
            if (PATH_SEPARATOR !== ':') {
                $item = mb_convert_encoding($item, "UTF-8", "GBK");
            }

            $name = mb_split("\.", $item)[0];
            if (empty($name)) {
                continue;
            }

            $goTemplates[$name] = $name;
        }
        $edit = new Typecho_Widget_Helper_Form_Element_Select('goTemplate', $goTemplates, 'NULL', _t('跳转页面模板'));
        $form->addInput($edit);
        $edit = new Typecho_Widget_Helper_Form_Element_Text('goDelay', null, _t('3'), _t('跳转延时'), _t('跳转页面停留时间（秒）'));
        $form->addInput($edit);
        $edit = new Typecho_Widget_Helper_Form_Element_Text('siteCreatedYear', null, _t('2020'), _t('建站年份'), _t('建站年份，用于模板内容替换模板中使用 <code>{{siteCreatedYear}}</code> 来代表建站年份'));
        $form->addInput($edit);

        $radio = new Typecho_Widget_Helper_Form_Element_Radio('target', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('新窗口打开文章中的链接'), _t('开启后给文章中的链接新增 target 属性'));
        $form->addInput($radio);

        $radio = new Typecho_Widget_Helper_Form_Element_Radio('authorPermalinkTarget', array('1' => _t('开启'), '0' => _t('关闭')), '0', _t('新窗口打开评论者链接'), _t('开启后给评论者链接新增 target 属性。（URL 中 target 属性，<b style="color:red">开启可能会引起主题异常</b>）'));
        $form->addInput($radio);

        $radio = new Typecho_Widget_Helper_Form_Element_Radio('forceSwitch', array('1' => _t('开启'), '0' => _t('关闭')), '0', _t('强力模式'), _t('主要为了支持 editor.md / vditor 等前台解析<b style="color:red">（实验性功能）</b>'));
        $form->addInput($radio);

        $textarea = new Typecho_Widget_Helper_Form_Element_Textarea('convertCustomField', null, null, _t('需要处理的自定义字段'), _t('在这里设置需要处理的自定义字段，一行一个<b style="color:red">（实验性功能）</b>'));
        $form->addInput($textarea);
        $radio = new Typecho_Widget_Helper_Form_Element_Radio('nullReferer', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('允许空 referer'), _t('开启后会允许空 referer'));
        $form->addInput($radio);
        $refererList = new Typecho_Widget_Helper_Form_Element_Textarea('refererList', null, null, _t('referer 白名单'), _t('在这里设置 referer 白名单，一行一个'));
        $form->addInput($refererList);
        $nonConvertList = new Typecho_Widget_Helper_Form_Element_Textarea('nonConvertList', null, _t("b0.upaiyun.com" . PHP_EOL . "glb.clouddn.com" . PHP_EOL . "qbox.me" . PHP_EOL . "qnssl.com"), _t('外链转换白名单'), _t('在这里设置外链转换白名单（评论者链接不生效）'));
        $form->addInput($nonConvertList);
        $isDrop = new Typecho_Widget_Helper_Form_Element_Radio('isDrop', array('0' => '删除', '1' => '不删除'), '1', '彻底卸载(<b style="color:red">请慎重选择</b>)', '请选择是否在禁用插件时，删除数据表');
        $form->addInput($isDrop);
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
     * 外链转内链
     *
     * @access public
     * @param string $text
     * @param mixed $widget
     * @param mixed $lastResult
     * @return array|string|string[] $content
     * @throws \Typecho\Plugin\Exception
     */
    public static function replace(string $text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;
        $pluginOption = self::options('ShortLinks'); // 插件选项
        $target = ($pluginOption->target) ? ' target="_blank" ' : ''; // 新窗口打开
        if ($pluginOption->convert == 1) {
            if ($widget->fields) {
                $fields = unserialize($widget->fields);
                if (is_array($fields) && array_key_exists("noshort", $fields)) {
                    // 部分文章不转换
                    return $text;
                }
            }
            // 文章内容和评论内容处理
            @preg_match_all('/<a(.*?)href="(?!#)(.*?)"(.*?)>/', $text, $matches);
            if ($matches) {
                foreach ($matches[2] as $link) {
                    $text = str_replace("href=\"$link\"", "href=\"" . self::convertLink($link) . "\"" . $target, $text);
                }
            }
        }
        return $text;
    }

    /**
     * 自定义字段处理
     *
     * @param Widget_Archive $widget
     * @param Typecho_Db_Query $select
     * @param mixed $lastResult
     * @return void
     * @throws \Typecho\Plugin\Exception
     */
    public static function fieldsConvert($widget, $select, $lastResult)
    {
        $widget = empty($lastResult) ? $widget : $lastResult;
        $pluginOption = self::options('ShortLinks'); // 插件选项
        $fieldsList = self::textareaToArr($pluginOption->convertCustomField);
        if ($pluginOption->convert == 1) { // 总开关
            if ($fieldsList) {
                foreach ($fieldsList as $field) {
                    if (isset($text->fields[$field])) {
                        // 非强力模式转换 a 标签
                        @preg_match_all('/<a(.*?)href="(?!#)(.*?)"(.*?)>/', $widget->fields[$field], $matches);
                        if ($matches) {
                            foreach ($matches[2] as $link) {
                                $widget->fields[$field] = str_replace("href=\"$link\"", "href=\"" . self::convertLink($link) . "\"", $widget->fields[$field]);
                            }
                        }

                        // 强力模式匹配所有链接
                        if ($pluginOption->forceSwitch == 1) {
                            $widget->fields[$field] = self::autoLink($widget->fields[$field]);
                        }
                    }
                }
            }
        }
    }

    /**
     * 用户链接转换
     *
     * @param Array $value
     * @param Widget_Abstract_Comments $widget
     * @param mixed $lastResult
     * @return void
     * @throws \Typecho\Plugin\Exception
     */
    public static function authorUrlConvert($value, $widget, $lastResult)
    {
        $value = empty($lastResult) ? $value : $lastResult;
        $pluginOption = self::options('ShortLinks'); // 插件选项
        if ($pluginOption->convert == 1) { // 总开关
            if ($pluginOption->convertCommentLink == 1) {
                // 评论者链接处理
                $url = $value['url'];
                if (strpos($url, '://') !== false && strpos($url, rtrim(self::options()->siteUrl, '/')) === false) {
                    $value['url'] = self::convertLink($url, false);
                    if ($pluginOption->authorPermalinkTarget) {
                        $value['url'] = $value['url'] . '" target="_blank';
                    }
                }
            }
        }
        return $value;
    }

    /**
     * 转换链接形式
     *
     * @access public
     * @param $link
     * @param bool $check
     * @return mixed $string
     * @throws \Typecho\Plugin\Exception
     */
    public static function convertLink($link, bool $check = true)
    {
        $pluginOption = self::options('ShortLinks');
        $linkBase = ltrim(rtrim(Typecho_Router::get('go')['url'], '/'), '/'); // 防止链接形式修改后不能用
        $siteUrl = self::options()->siteUrl;
        $nonConvertList = self::textareaToArr($pluginOption->nonConvertList); // 不转换列表
        if ($check) {
            if (strpos($link, '://') !== false && strpos($link, rtrim($siteUrl, '/')) !== false) {
                return $link;
            }
            // 本站链接不处理 不转换列表中的不处理
            if (self::checkDomain($link, $nonConvertList)) {
                return $link;
            }

            // 图片不处理
            if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i', $link)) {
                return $link;
            }
        }
        return Typecho_Common::url(str_replace('[key]', self::urlSafeB64Encode(htmlspecialchars_decode($link)), $linkBase), self::options()->index);
    }

    /**
     * 强力转换
     *
     * @param Array $value
     * @param mixed $widget
     * @param mixed $lastResult
     * @return Array
     * @throws \Typecho\Plugin\Exception
     */
    public static function forceConvert(array $value, $widget, $lastResult): array
    {
        $value = empty($lastResult) ? $value : $lastResult;
        $pluginOption = self::options('ShortLinks');
        if ($pluginOption->convert == 1 && $pluginOption->forceSwitch == 1) {
            $value['text'] = self::autoLink($value['text']);
        }
        return $value;
    }

    /**
     * 文本链接转A标签
     *
     * @param string $content
     * @return string
     * @throws \Typecho\Plugin\Exception
     */
    public static function autoLink($content)
    {

        $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
        $target = (self::options()->target) ? ' target="_blank" ' : ''; // 新窗口打开
        return preg_replace_callback($url, function ($matches) use ($target) {
            if (preg_match('/\.(jpg|jepg|png|ico|bmp|gif|tiff)/i', $matches[0])) {
                return $matches[0];
            }
            if (strpos($matches[0], '://') !== false && strpos($matches[0], rtrim(self::options()->siteUrl, '/')) !== false) {
                return '<a href="' . self::convertLink($matches[0]) . '" title="' . $matches[0] . '"' . $target . '>' . $matches[0] . '</a>';
            }
            return $matches[0];
        }, $content);
    }

    /**
     * 检查域名是否在数组中存在
     *
     * @access public
     * @param string $url
     * @param array|null $arr
     * @return boolean
     */
    public static function checkDomain(string $url, ?array $arr): bool
    {
        if ($arr === null) {
            return false;
        }

        if (count($arr) === 0) {
            return false;
        }

        foreach ($arr as $a) {
            if (strpos($url, $a) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 一行一个文本框转数组
     *
     * @access public
     * @param string|null $textarea
     * @return array
     */
    public static function textareaToArr(?string $textarea): ?array
    {
        $str = str_replace(array("\r\n", "\r", "\n"), "|", $textarea ?? "");
        if ($str == "") {
            return null;
        }

        return explode("|", $str);
    }

    /**
     * Base64 解码
     *
     * @param string $str
     * @return string
     * @date 2020-05-01
     */
    public static function urlSafeB64Decode($str)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $str);
        $mod = strlen($data) % 4;
        if ($mod) {
            $data .= substr('====', $mod);
        }
        return base64_decode($data);
    }

    /**
     * Base64 编码
     *
     * @param string|null $str
     * @return string
     * @date 2020-05-01
     */
    public static function urlSafeB64Encode(?string $str): string
    {
        $data = base64_encode($str ?? "");
        return str_replace(array('+', '/', '='), array('-', '_', ''), $data);
    }

    /**
     * 获得配置信息
     *
     * @return Typecho\Config|Typecho_Config
     * @throws \Typecho\Plugin\Exception
     */
    public static function options($plugin = null)
    {
        $options = null;
        if (function_exists('\Widget\Options::alloc')) {
            $options = \Widget\Options::alloc();
        } else {
            $options = Typecho_Widget::widget('Widget_Options');
        }
        if ($plugin) {
            $options = $options->plugin($plugin);
        }
        return $options;
    }

    /**
     * 获取数据库对象
     * @return mixed
     * @throws \Typecho\Db\Exception
     */
    public static function db()
    {
        if (class_exists('Typecho\Db')) {
            return Typecho\Db::get();
        } else {
            return Typecho_Db::get();
        }
    }
}
