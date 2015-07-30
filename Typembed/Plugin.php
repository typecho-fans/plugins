<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typembed 视频播放插件
 * 
 * @package Typembed
 * @author Fengzi
 * @version 1.0.6
 * @dependence 13.12.12-*
 * @link http://www.fengziliu.com/typembed.html
 */
class Typembed_Plugin implements Typecho_Plugin_Interface{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Typembed_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Typembed_Plugin', 'parse');
    }
    
    public static function parse($content, $widget, $lastResult){
        $content = empty($lastResult) ? $content : $lastResult;
        if ($widget instanceof Widget_Archive){
            $content = preg_replace_callback('/<p>(?:(?:<a[^>]+>)?(?<video_url>(?:(http|https):\/\/)+[a-z0-9_\-\/.%]+)(?:<\/a>)?)<\/p>/si', array('Typembed_Plugin', 'parseCallback'), $content);
        }
        return $content;
    }
    
    public static function parseCallback($matches){
        $no_html5 = array('www.letv.com', 'v.yinyuetai.com', 'v.ku6.com');
        $providers = array(
            'v.youku.com' => array(
                '#https?://v\.youku\.com/v_show/id_(?<video_id>[a-z0-9_=\-]+)#i',
                'http://player.youku.com/player.php/sid/{video_id}/v.swf',
                'http://player.youku.com/embed/{video_id}',
            ),
            'www.tudou.com' => array(
                '#https?://(?:www\.)?tudou\.com/(?:programs/view|listplay/(?<list_id>[a-z0-9_=\-]+))/(?<video_id>[a-z0-9_=\-]+)#i', 
                'http://www.tudou.com/v/{video_id}/&resourceId=0_05_05_99&bid=05/v.swf',
                'http://www.tudou.com/programs/view/html5embed.action?type=0&code={video_id}',
            ),
            'www.56.com' => array(
                '#https?://(?:www\.)?56\.com/[a-z0-9]+/(?:play_album\-aid\-[0-9]+_vid\-(?<video_id>[a-z0-9_=\-]+)|v_(?<video_id2>[a-z0-9_=\-]+))#i',
                'http://player.56.com/v_{video_id}.swf',
                'http://www.56.com/iframe/{video_id}',
            ),
            'v.qq.com' => array(
                '#https?://v\.qq\.com/(?:[a-z0-9_\./]+\?vid=(?<video_id>[a-z0-9_=\-]+)|(?:[a-z0-9/]+)/(?<video_id2>[a-z0-9_=\-]+))#i',
                'http://static.video.qq.com/TPout.swf?vid={video_id}',
                'http://v.qq.com/iframe/player.html?vid={video_id}',
            ),
            'my.tv.sohu.com' => array(
                '#https?://my\.tv\.sohu\.com/us/(?:\d+)/(?<video_id>\d+)#i',
                'http://share.vrs.sohu.com/my/v.swf&topBar=1&id={video_id}&autoplay=false&xuid=&from=page',
                'http://tv.sohu.com/upload/static/share/share_play.html#{video_id}_0_0_9001_0',
            ),
            'www.wasu.cn' => array(
                '#https?://www\.wasu\.cn/play/show/id/(?<video_id>\d+)#i',
                'http://s.wasu.cn/portal/player/20141216/WsPlayer.swf?mode=3&vid={video_id}&auto=0&ad=4228',
                'http://www.wasu.cn/Play/iframe/id/{video_id}',
            ),
            'www.letv.com' => array(
                '#https?://www\.letv\.com/ptv/vplay/(?<video_id>\d+)#i',
                'http://i7.imgs.letv.com/player/swfPlayer.swf?id={video_id}&autoplay=0',
                '',
            ),
            'www.acfun.tv' => array(
                '#https?://www\.acfun\.tv/v/ac(?<video_id>\d+)#i',
                'http://static.acfun.mm111.net/player/ACFlashPlayer.out.swf?type=page&url=http://www.acfun.tv/v/ac{video_id}',
                '',
            ),
            'www.bilibili.com' => array(
                '#https?://www\.bilibili\.com/video/av(?<video_id>\d+)#i',
                'http://static.hdslb.com/miniloader.swf?aid={video_id}&page=1',
                '',
            ),
            'v.yinyuetai.com' => array(
                '#https?://v\.yinyuetai\.com/video/(?<video_id>\d+)#i',
                'http://player.yinyuetai.com/video/player/{video_id}/v_0.swf',
                '',
            ),
            'v.ku6.com' => array(
                '#https?://v\.ku6\.com/show/(?<video_id>[a-z0-9\-_\.]+).html#i',
                'http://player.ku6.com/refer/{video_id}/v.swf',
                '',
            ),
        );
        $parse = parse_url($matches['video_url']);
        $site = $parse['host'];
        if(!in_array($site, array_keys($providers))){
            return '<p><a href="' . $matches['video_url'] . '">' . $matches['video_url'] . '</a></p>';
        }
        preg_match_all($providers[$site][0], $matches['video_url'], $match);
        $id = $match['video_id'][0] == '' ? $match['video_id2'][0] : $match['video_id'][0];
        if(self::isMobile()){
            $width = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->mobile_width;
            $height = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->mobile_height;
        }else{
            $width = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->width;
            $height = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->height;
        }
        if(self::isMobile() && !in_array($site, $no_html5)){
            $url = str_replace('{video_id}', $id, $providers[$site][2]);
            $html = sprintf(
                '<iframe src="%1$s" width="%2$s" height="%3$s" frameborder="0" allowfullscreen="true"></iframe>',
                $url, $width, $height);
        }else{
            $url = str_replace('{video_id}', $id, $providers[$site][1]);
            $html = sprintf(
                '<embed src="%1$s" allowFullScreen="true" quality="high" width="%2$s" height="%3$s" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>',
                $url, $width, $height);
        }
        return '<div id="typembed">'.$html.'</div>';
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
    public static function config(Typecho_Widget_Helper_Form $form){
        //$options = Helper::options();
        $width = new Typecho_Widget_Helper_Form_Element_Text('width', NULL, '100%', _t('播放器宽度'));
        $form->addInput($width);
        $height = new Typecho_Widget_Helper_Form_Element_Text('height', NULL, '500', _t('播放器高度'));
        $form->addInput($height);
        $mobile_width = new Typecho_Widget_Helper_Form_Element_Text('mobile_width', NULL, '100%', _t('移动设备播放器宽度'));
        $form->addInput($mobile_width);
        $mobile_height = new Typecho_Widget_Helper_Form_Element_Text('mobile_height', NULL, '250', _t('移动设备播放器高度'));
        $form->addInput($mobile_height);
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
     * 移动设备识别
     * 
     * @return boolean
     */
    private static function isMobile(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $mobile_browser = Array(
            "mqqbrowser", // 手机QQ浏览器
            "opera mobi", // 手机opera
            "juc","iuc", 'ucbrowser', // uc浏览器
            "fennec","ios","applewebKit/420","applewebkit/525","applewebkit/532","ipad","iphone","ipaq","ipod",
            "iemobile", "windows ce", // windows phone
            "240x320","480x640","acer","android","anywhereyougo.com","asus","audio","blackberry",
            "blazer","coolpad" ,"dopod", "etouch", "hitachi","htc","huawei", "jbrowser", "lenovo",
            "lg","lg-","lge-","lge", "mobi","moto","nokia","phone","samsung","sony",
            "symbian","tablet","tianyu","wap","xda","xde","zte"
        );
        $is_mobile = false;
        foreach ($mobile_browser as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }
        return $is_mobile;
    }
}