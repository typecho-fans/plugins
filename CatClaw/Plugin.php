<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * CatClaw 猫爪抓抓抓影视采集插件
 * 
 * @package CatClaw
 * @author jrotty
 * @version 1.1.2
 * @link https://qqdie.com
 */
class CatClaw_Plugin implements Typecho_Plugin_Interface
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
        Helper::addRoute("route_catclaw","/catclaw","CatClaw_Action",'action');
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
        Helper::removeRoute("route_catclaw");
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
        $set1 = new Typecho_Widget_Helper_Form_Element_Text('url', NULL, NULL, _t('采集站接口URL'), _t('一般采集站会提供m3u8的接口，比如ok资源网就是：https://cj.okzy.tv/inc/apickm3u8s_subname.php'));
        $form->addInput($set1);
        
        $set6 = new Typecho_Widget_Helper_Form_Element_Text('autoup', NULL, NULL, _t('自动更新参数'), _t('autoup插件的自动更新参数，比如ok资源网就是okzyw，具体见autoup插件设置说明，此项为选填，不填则默认不设置自动更新参数'));
        $form->addInput($set6);
        
        
        $set2 = new Typecho_Widget_Helper_Form_Element_Text('pass', NULL, NULL, _t('访问密码'), _t('访问密码'));
        $form->addInput($set2);
        
        $set = new Typecho_Widget_Helper_Form_Element_Text('username', NULL, NULL, _t('用户名'), _t('用来发布文章的用户名'));
        $form->addInput($set); 
        $set0 = new Typecho_Widget_Helper_Form_Element_Text('password', NULL, NULL, _t('用户密码'), _t('上方用户名对应的用户密码'));
        $form->addInput($set0);
        
        
        
        

$f='动作片：
爱情片：
喜剧片：
科幻片：
恐怖片：
剧情片：
战争片：
记录片：
微电影：
伦理片：
动漫电影：';

$t='国产剧：
香港剧：
台湾剧：
韩国剧：
欧美剧：
日本剧：
泰国剧：
其他剧：';

$a='中国动漫：
港台动漫：
日本动漫：
韩国动漫：
欧美动漫：
其他动漫：';


$set3 = new Typecho_Widget_Helper_Form_Element_Textarea('film', NULL,$f, _t('电影分类绑定'), _t('请在冒号后面填写对应的分类mid，不填或者填0采集时则越过该分类'));
$form->addInput($set3);

$set4 = new Typecho_Widget_Helper_Form_Element_Textarea('tv', NULL,$t, _t('电视剧分类绑定'), _t('请在冒号后面填写对应的分类mid，不填或者填0采集时则越过该分类'));
$form->addInput($set4);

$set5 = new Typecho_Widget_Helper_Form_Element_Textarea('anime', NULL,$a, _t('动漫分类绑定'), _t('请在冒号后面填写对应的分类mid，不填或者填0采集时则越过该分类
<section id="custom-field" class="typecho-post-option">
<label id="custom-field-expand" class="typecho-label">采集插件说明</label>
   <br>插件采集会默认跳过同名已存在的文章，会自动更新同名连载状态的文章！文章标签如果采集站接口未提供则默认为【待定】<br>
   <br>1.采集站必须使用m3u8接口<br>2.以下是操作地址：<br>
    先手动添加：<br>
    Url:http://你的地址/catclaw/?pg=1&type=add&day=1&id=1&pass=你的密码 (GET)<br>
    参数：<br>
    pg = 页数<br>
    type = 操作类型（add和cron，add是手动采集，cron是用于服务器定时任务的）<br>
    day = 采集天数，可输入1,7,max（输入1就是采集最近24小时内更新的资源，7就是一周，max就是采集全部）<br>
    id = 采集站上面的分类ID<br>
    pass = 插件后台设置的密码<br>
    <br>
    下面是监控地址：
    <br>
    http://你的地址/catclaw/?pg=1&type=cron&day=1&id=1&pass=你的密码 (GET)
    <br>监控地址一般填于服务器定时任务，day参数不要填max以免卡死！
    <p></p>
    </section>'));
$form->addInput($set5);

    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
   
}
