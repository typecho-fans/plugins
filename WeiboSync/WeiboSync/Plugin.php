<?php
/**
 * Typecho 自动同步文章至新浪微博
 * 
 * @package     WeiboSync
 * @author 		vfhky
 * @version 	1.0.0
 * @update: 	2015.12.11
 * @link https://typecodes.com
 */

class WeiboSync_Plugin implements Typecho_Plugin_Interface
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
    	Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('WeiboSync_Plugin', 'TsinaSync');
    	return _t('请设置WeiboSync的信息，以使插件正常使用！');
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
    	return _t('插件已禁用成功！');
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
    	self::SinaAuth();
        
        $sina_token = new Typecho_Widget_Helper_Form_Element_Text('sina_token',NULL,NULL,_t('新浪微博Access Token'),_t('填写Sina_weibo_Access_token的值'));
        $form->addInput($sina_token);
        
        $sina_uid = new Typecho_Widget_Helper_Form_Element_Text('sina_uid',NULL,NULL,_t('个人微博ID'),_t('填写Sina_weibo_Uid的值'));
        $form->addInput($sina_uid);
        
        $sina_mode = new Typecho_Widget_Helper_Form_Element_Radio('sina_mode',array( 0 => '仅新建时同步',1 => '新建和修改时同步'),'0', '选择同步方式');
        $form->addInput($sina_mode);
        
        $sina_imgflag = new Typecho_Widget_Helper_Form_Element_Radio('sina_imgflag',array( 1 => '是', 0 => '否' ),'1', '发布微博时是否添加第一张缩略图');
        $form->addInput($sina_imgflag);
        
        $sina_format = new Typecho_Widget_Helper_Form_Element_Textarea('sina_format', NULL, _t('我在TypeCodes上发表了一篇文章《{title}》，链接地址：{link}'), _t('微博内容'), _t('可用参数: {title}标题 {link}文章链接 {more}60字内的文章摘要'));
        $form->addInput($sina_format);
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
     *  是否已经获取到了token，未获取则显示获取token的图标，否则显示当前登录账号
     *
     * @access public
     * @param 
     * @return string
     */
    public static function SinaAuth()
    {
    	self::getPubFile();
    	$sina_auth = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
    	$authurl = $sina_auth->getAuthorizeURL( WB_CALLBACK_URL , 'code');
    	$img_path = Helper::options()->pluginUrl . '/WeiboSync/weibo.png';
    	echo $sina_profile = '<ul class="typecho-option"><li><a href="' . $authurl . '"><img src="' . $img_path . '"></a>&nbsp;&nbsp;<b>点击左边图标获取微博Access_token信息</b></li></ul>';
    }
    
    
    /**
     * 同步文章到新浪微博
     *
     * @access public
     * @param $content $class
     * @return status
     */
    public static function TsinaSync($content,$class)
    {
    	//获取插件的配置
		$config = self::getWeiboSyncCfg();
     	
		//如果不是文章编辑（对象$class属于Widget_Abstract_Contents，是否等于对象的值Widget_Contents_Post_Edit，它继承Widget_Abstract_Contents对象），则直接返回内容
		if(!is_a($class,'Widget_Contents_Post_Edit'))
		{
			return $content;
		}
		
		if($config->sina_mode == '1')
		{
			if( !$class->request->is("do=publish") 
				|| ($class->request->is("do=publish") && !$class->have()) )
			{
				return $content;
			}
		}
		else
		{
			if(!$class->request->is("do=publish") 
				||($class->request->is("do=publish") && !empty($class->request->cid)))
			{
				return $content;
			}
		}
		
		$format = $config->sina_format?$config->sina_format:'我在TypeCodes上发表了一篇文章《{title}》，链接地址{link}';
		$title = $content['title'];
		$link = self::SinaShortUrl($content['permalink']);
		
		//如果插件配置中出现了{more}标签，即需要提取文章的摘要信息
		if( strpos($format,'{more}') !== false )
		{
			if(strpos($content['text'], '<!--more-->')  !== false )
			{
				$more_t = explode('<!--more-->', $content['text']);
				list($more) = $more_t;
				$more = Typecho_Common::fixHtml(Typecho_Common::cutParagraph($more));
				$more = Typecho_Common::subStr(strip_tags($more), 0, 60, '...');
			}
			else
			{
				$more = $content['text'];
				$more = Typecho_Common::fixHtml(Typecho_Common::cutParagraph($more));
				$more = Typecho_Common::subStr(strip_tags($more), 0, 60, '...');
			}
		}
		else $more = "";
		
		$search = array('{title}','{link}','{more}');
		$replace = array($title,$link,$more);
		$format = str_replace($search,$replace,$format);
		
		$post_img = '';
		if( $config->sina_imgflag )
		{
			$content_substr = mb_substr( $content['text'], 0, 900, 'utf-8' );
			if( preg_match( '/!\[[^\]]*]\((https):\/\/[^\)]*\.(png|jpg)(.*)\)/i', $content_substr, $img_match ) )
			{
			    if( preg_match( '/(https:\/\/)[^>]*?\.(png|jpg)/i',$img_match[0],$img_match_retult ) )
			        $post_img = $img_match_retult[0];
			}
		}
		
		self::PostWeibo( $format, $post_img );
		return $content;
    }
	
	
    /**
     * 调用新浪微博SDK的API，生成微博短连接
     *
     * @access public
     * @param $url
     * @return string
     */
    public static function SinaShortUrl($url)
    {
    	$token = self::GetAuthInfo();
    	$api_url = "https://api.weibo.com/2/short_url/shorten.json";
    	$send_url = $api_url . '?access_token='. $token['sina_token'] .'&url_long='.urlencode($url);
    	$json_result = file_get_contents($send_url);
    	$short_url = json_decode($json_result);
    	return $short_url->urls[0]->url_short;
    }
    
    
    /**
     * 调用新浪微博SDK的API，发送微博
     *
     * @access public
     * @param $post		内容
     * @param $img_url		图片地址
     * @return void
     */
    public static function PostWeibo( $post, $img_url )
    {
    	$post_token = self::GetAuthInfo();
    	$sina_auth = $post_token['sina_auth'];
    	if( empty($img_url) )
    		$sina_auth->update( $post );
    	else
    		$sina_auth->upload( $post, $img_url );
    }
	
	
    /**
     * 获取WeiboSync插件的配置
     * 
     * @static
     * @access public
     * @return void
     */
    public static function getWeiboSyncCfg()
    {
        return Typecho_Widget::widget('Widget_Options')->plugin('WeiboSync');
    }
	
	
    /**
     * 获取新浪微博SDK文件信息
     * 
     * @static
     * @access public
     * @return void
     */
    public static function getPubFile()
    {
    	require_once 'config.php';
    	require_once 'saetv2.ex.class.php';
    }
	
	
    /**
     * 获取新浪微博的个人授权信息
     * 
     * @access public
     * @param 
     * @return void
     */
    public static function GetAuthInfo()
    {
    	self::getPubFile();
    	$option = self::getWeiboSyncCfg();
    	$sina_token = $option->sina_token;
    	$sina_uid = $option->sina_uid;
    	$sina_auth = new SaeTClientV2( WB_AKEY , WB_SKEY , $sina_token );
    	$auth = array();
    	$auth['sina_auth'] = $sina_auth;
    	$auth['sina_token'] = $sina_token;
    	$auth['sina_uid'] = $sina_uid;
    	return $auth;
    }
}
