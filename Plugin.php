<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为博客添加Gravatar头像墙功能, 支持镜像加速与缓存
 * 
 * @package Avatars
 * @author 羽中
 * @version 1.2.4
 * @dependence 14.10.10
 * @link http://www.yzmb.me/archives/net/avatars-for-typecho
 */
class Avatars_Plugin implements Typecho_Plugin_Interface
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Avatars_Plugin','walls');
		Typecho_Plugin::factory('Widget_Abstract_Comments')->gravatar = array('Avatars_Plugin','avatars');
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
		echo
'<div style="color:#999;font-size:13px;word-break:break-all;"><p>'
._t('在主题模版文件中的适当位置插入代码%s<br/>或编辑文章/页面写入%s即可显示读者墙(黑字标签与class名称可自定义)','<span style="color:#467B96;font-weight:bold;">&lt;?php Avatars_Plugin::output("<span style="color:#444;">li</span>","<span style="color:#444;">mostactive</span>"); ?&gt;</span>','<span style="color:#467B96;font-weight:bold;">[AVATARS|<span style="color:#444;">li</span>|<span style="color:#444;">mostactive</span>]</span>').
'</p></div>';

		$wsize = new Typecho_Widget_Helper_Form_Element_Text('wsize',
		NULL,'32',_t('读者墙头像大小'),_t('读者墙上的头像尺寸(单位px无需填写)'));
		$wsize->input->setAttribute('class','w-10');
		$wsize->addRule('required',_t('头像尺寸不能为空'));
		$form->addInput($wsize->addRule('isInteger',_t('请填写整数数字')));

		$wdefault = new Typecho_Widget_Helper_Form_Element_Text('wdefault',
		NULL,'',_t('读者墙缺省头像'),_t('支持gravatar随机头像后缀(identicon/monsterid/wavatar/retro等), 自定义图片url注意尺寸应一致'));
		$wdefault->input->setAttribute('class','w-60');
		$form->addInput($wdefault);

		$listnumber = new Typecho_Widget_Helper_Form_Element_Text('listnumber',
		NULL,'10',_t('读者墙头像数目'),_t('读者墙上最多展示的评论者头像个数'));
		$listnumber->input->setAttribute('class','w-10');
		$listnumber->addRule('required',_t('头像个数不能为空'));
		$form->addInput($listnumber->addRule('isInteger',_t('请填写整数数字')));

		$since = new Typecho_Widget_Helper_Form_Element_Text('since',
		NULL,'30',_t('读者墙收录时间'),_t('读者墙将展示该天数以内的评论排行'));
		$since->input->setAttribute('class','w-10');
		$form->addInput($since->addRule('isInteger',_t('请填写整数数字')));

		$altword = new Typecho_Widget_Helper_Form_Element_Text('altword',
		NULL,'条评论',_t('读者墙提示文字'),_t('个性化读者墙头像的评论数提示文字'));
		$altword->input->setAttribute('class','mini');
		$form->addInput($altword);

		$proxy = new Typecho_Widget_Helper_Form_Element_Radio('proxy',
		array(''=>_t('否'),'https://cdn.v2ex.com/gravatar/'=>_t('v2ex镜像'),'moecdn'=>_t('MoeCDN镜像')),'',_t('用代理加速头像'),_t('国内直连gravatar服务器不流畅时可选'));
		$form->addInput($proxy);

		$cache = new Typecho_Widget_Helper_Form_Element_Checkbox('cache',
		array(1=>_t('是')),NULL,_t('在本地缓存头像'),_t('将头像下载到插件的cache目录中调用'));
		$form->addInput($cache);

		$comment = new Typecho_Widget_Helper_Form_Element_Checkbox('comment',
		array(1=>_t('是')),NULL,_t('作用评论区头像'),_t('评论区头像也用上两项设置(加速/缓存)'));
		$form->addInput($comment);

		if (Typecho_Request::getInstance()->is('action=deletefiles')) {
			self::deletefiles();
		}
		//清空动作按钮
		$deletefiles = new Typecho_Widget_Helper_Form_Element_Submit();
		$deletefiles->value(_t('清空缓存'));
		$deletefiles->setAttribute('style','position:relative');
		$deletefiles->input->setAttribute('style','position:absolute;bottom:127.5px;left:110px;padding-bottom:1px');
		$deletefiles->input->setAttribute('class','btn btn-xs btn-warn');
		$deletefiles->input->setAttribute('formaction',Helper::options()->adminUrl.'options-plugin.php?config=Avatars&action=deletefiles');
		$form->addItem($deletefiles);
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
	 * 读者墙标签解析
	 * 
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function walls($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;

		if ($widget->is('page') && false!==stripos($content,'[AVATARS')) {
			$content = preg_replace_callback('/\[AVATARS(\w*[^>]*)\]/i',array('Avatars_Plugin','callback'),$content);
		}

		return $content;
	}

	/**
	 * 标签参数回调
	 * 
	 * @access public
	 * @param array $matche
	 * @return string
	 */
	public static function callback($matche)
	{
		$listtag = 'li';
		$class = 'mostactive';

		if ($matche['1']) {
			if (preg_match('/\|([\w-]*)\|([\w-]*)/i',$matche['1'],$out)) {
				$listtag = trim($out['1']);
				$class = trim($out['2']);
			}
		}

		return self::output($listtag,$class,true);
	}

	/**
	 * 读者墙实例输出
	 * 
	 * @access public
	 * @param string $listtag 标签名称
	 * @param string $class class名称
	 * @param boolean $iscall 是否回调
	 * @return void
	 */
	public static function output($listtag='li',$class='mostactive',$iscall=false)
	{
		$options = Helper::options();
		$settings = $options->plugin('Avatars');
		$listtag = $listtag ? $listtag : 'li';
		$wurl = '';
		$mostactive = '';

		//获取评论计数
		$db = Typecho_Db::get();
		$select = $db->select(array('COUNT(author)'=>'cnt'),'author','url','mail')->from('table.comments')
			->where('status = ?','approved')
			->where('authorId = ?','0') //排除博主
			->where('type = ?','comment')
			->where('created > ?',$options->gmtTime + $options->timezone - $settings->since*24*3600) //收录时间
			->limit($settings->listnumber)
			->group('author')
			->order('cnt',Typecho_Db::SORT_DESC); //降序排列
		$counts = $db->fetchAll($select);

		foreach ($counts as $count) {
			//获取优化地址
			$wurl = self::avurl($count['mail'],$settings->wsize,$options->commentsAvatarRating //同步评级设置
				,$settings->wdefault,Typecho_Widget::widget('Widget_Archive')->request->isSecure() //获取安全请求
			);

			$mostactive .= '
		<'.$listtag;
			$mostactive .= $class ? ' class="'.$class.'"' : '';
			$mostactive .= '><a href="';
			$mostactive .= $count['url'] ? $count['url'] : '###'; //静默空白链接
			$mostactive .= '"';
			$mostactive .= $options->commentsUrlNofollow ? ' rel="external nofollow"' : ''; //同步nofollow设置
			$mostactive .= ' title="'.$count['author'].' - '.$count['cnt'].$settings->altword.'">';
			$mostactive .= $wurl ? '<img src="'.$wurl.'" alt="'.$count['author'].'" class="avatar" />' : _t('<span style="font-weight:bold;color:#467B96;">%s</span>','缓存出错!');
			$mostactive .= '</a></'.$listtag.'>';
		}

		//修正替换输出
		if ($iscall) {
			return $mostactive;
		} else {
			echo $mostactive;
		}
	}

	/**
	 * 输出头像地址
	 * 
	 * @access private
	 * @param string $mail 邮箱地址
	 * @param integer $size 头像尺寸
	 * @param string $rate 头像评级
	 * @param string $default 默认头像
	 * @param boolean $secure https请求
	 * @return string
	 */
	private static function avurl($mail,$size,$rating,$default,$secure=false)
	{
		$options = Helper::options();
		$settings = $options->plugin('Avatars');
		$hash = $mail ? md5(strtolower(trim($mail))) : '';

		$proxy = $settings->proxy;
		$server = $proxy ? 
			($proxy=='moecdn' ? ($secure ? 'https://gravatar.moefont.com/avatar/' : 'http://gravatar.moefont.com/avatar/') : $proxy)
			 : ($secure ? 'https://secure.gravatar.com/avatar/' : 'http://'.rand(0,2).'.gravatar.com/avatar/');

		$url = $server.$hash;
		$url .= '?s='.$size;
		$url .= '&r='.$rating;
		$url .= '&d='.$default;

		if ($settings->cache) {
			$path = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/Avatars/cache/';
			//生成缓存目录
			if (!is_dir($path)) {
				if (!self::makedir($path)) {
					return false;
				}
			}

			//默认缓存15日
			$cachetime = 14*24*3600;
			$cachedir = $path.$hash.$size;

			//生成缓存头像
			if (!is_file($cachedir) || (time()-filemtime($cachedir))>$cachetime) {
				if (!@copy($url,$cachedir)) {
					return false;
				}
			}

			$url = $options->pluginUrl.'/Avatars/cache/'.$hash.$size;
		}

		return $url;
	}

	/**
	 * 缓存头像清空
	 *
	 * @access private
	 * @return void
	 */
	private function deletefiles()
	{
		$path = glob(__TYPECHO_ROOT_DIR__.'/usr/plugins/Avatars/cache/*');
		if ($path) {
			foreach ($path as $filename) {
				@unlink($filename);
			}
		}

		Typecho_Widget::widget('Widget_Notice')->set(_t('本地头像缓存已清空!'),'notice');
		Typecho_Response::getInstance()->goBack();
	}

	/**
	 * 本地目录创建
	 * 
	 * @access private
	 * @param string $path 路径
	 * @return boolean
	 */
	private static function makedir($path)
	{
		$path = preg_replace("/\\\+/", '/', $path);
		$current = rtrim($path, '/');
		$last = $current;

		while (!is_dir($current) && false !== strpos($path, '/')) {
			$last = $current;
			$current = dirname($current);
		}
		if ($last == $current) {
			return true;
		}
		if (!@mkdir($last)) {
			return false;
		}

		$stat = @stat($last);
		$perms = $stat['mode'] & 0007777;
		@chmod($last, $perms);

		return self::makedir($path);
	}

	/**
	 * 兼容评论区头像
	 * 
	 * @access public
	 * @param integer $size 头像尺寸
	 * @param string $default 默认头像
	 * @return void
	 */
	public static function avatars($size,$rating,$default,$comments)
	{
		$mail = $comments->mail;
		$issecure = $comments->request->isSecure();

		$url = Helper::options()->plugin('Avatars')->comment ? self::avurl($mail,$size,$rating,$default,$issecure)
			 : Typecho_Common::gravatarUrl($mail,$size,$rating,$default,$issecure);

		echo $url ? '<img class="avatar" src="'.$url.'" alt="'.$comments->author.'" />' : _t('<span style="font-weight:bold;color:#467B96;">%s</span>','缓存出错!');
	}

}