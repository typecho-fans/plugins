<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * CatClaw 猫爪抓抓抓影视采集插件
 * 
 * @package CatClaw
 * @author 泽泽社长
 * @version 1.8.1
 * @link https://blog.zezeshe.com/
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
       if(Helper::options()->rewrite==0){$index=Helper::options()->rootUrl.'/index.php/';}else{$index=Helper::options()->rootUrl.'/';}
        
        $set0 = new Typecho_Widget_Helper_Form_Element_Text('listurl', NULL, NULL, _t('视频列表接口URL'), _t('请填写资源站提供的json接口'));
        $form->addInput($set0);
        
        $set1 = new Typecho_Widget_Helper_Form_Element_Text('detailurl', NULL, NULL, _t('视频详情接口URL'), _t('请填写资源站提供的json接口'));
        $form->addInput($set1);
        
        $set2 = new Typecho_Widget_Helper_Form_Element_Text('autoup', NULL, NULL, _t('自动更新参数'), _t('autoup插件的自动更新参数，具体见autoup插件设置说明，此项为选填，不填则默认不设置自动更新参数'));
        $form->addInput($set2);
        

        $set3 = new Typecho_Widget_Helper_Form_Element_Text('aid', NULL, NULL, _t('资源站分类id'), _t('填写你想的采集的视频资源站的分类id'));
        $form->addInput($set3);
        
        $set4 = new Typecho_Widget_Helper_Form_Element_Text('bid', NULL, NULL, _t('本站分类mid'), _t('从资源站采集后写入本站的分类'));
        $form->addInput($set4);


        $lianzai = new Typecho_Widget_Helper_Form_Element_Radio('tiao',array('1' => _t('跳过'),'2' => _t('不跳过')),'1',_t('采集时跳过完结番剧'), _t('采集时遇到同名文章，默认会只更新连载状态的视频列表，选择不跳过则不管视频状态是什么都将进行更新操作'));
        $form->addInput($lianzai);  
  
        
        $set5 = new Typecho_Widget_Helper_Form_Element_Text('pass', NULL, NULL, _t('访问密码'), _t('访问密码'));
        $form->addInput($set5);
        
        $set6 = new Typecho_Widget_Helper_Form_Element_Text('username', NULL, NULL, _t('用户名'), _t('用来发布文章的用户名'));
        $form->addInput($set6); 
        $set7 = new Typecho_Widget_Helper_Form_Element_Text('password', NULL, NULL, _t('用户密码'), _t('上方用户名对应的用户密码<section id="custom-field" class="typecho-post-option">
<label id="custom-field-expand" class="typecho-label">采集插件说明</label>
   <br>插件采集会默认跳过同名已存在的文章，会自动更新同名连载状态的文章！文章标签因为采集站接口未提供所以不会写入标签项<br>
   <br>1.采集站必须使用m3u8接口<br>2.以下是操作地址：<br>
    先手动添加：<br>
    <span style="color: red;font-size: 16px;">'.$index.'catclaw/?pg=1&day=1&pass=你的密码 (GET)</span><br>
    参数：<br>
    pg = 页数,至从第几页开始采集，一般填1就行<br>
    day = 采集天数，可输入1,7,max（输入1就是采集最近24小时内更新的资源，7就是一周，max就是采集全部）<br>
    pass = 插件后台设置的密码<br>
    </section>'));
        $form->addInput($set7);
        

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
