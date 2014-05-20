<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 按评论数量排序输出活跃读者头像墙
 * 
 * @package Avatars
 * @author 羽中
 * @version 1.2.1
 * @dependence 13.12.12-*
 * @link http://www.jzwalk.com/archives/net/avatars-for-typecho
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Avatars_Plugin','parse');
		Helper::addAction('avatars-delete','Avatars_Action');
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
		Helper::removeAction('avatars-delete');
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
		Avatars_Plugin::form()->setAttribute('style','position:relative;bottom:-627px;left:217px;')->render();
		echo "<div style='color:#999;font-size:0.92857em;font-weight:bold;'><p>在模版适当位置插入代码<span style='color:#467B96;font-weight:bold'>&lt;?php Avatars_Plugin::output('<span style='color:#E47E00;'>li</span>','<span style='color:#E47E00;'>mostactive</span>'); ?&gt;</span>, 或在页面内容<br/>写入标签<span style='color:#467B96;font-weight:bold'>[AVATARS|<span style='color:#E47E00;'>li</span>|<span style='color:#E47E00;'>mostactive</span>]</span>均可. 橙色部分为自定义tag名(如span, div, p等)和class名. </p></div>";

		$listnumber = new Typecho_Widget_Helper_Form_Element_Text('listnumber',
			NULL,'10','读者墙头像数目','设置最多显示多少个评论者头像');
		$listnumber->input->setAttribute('class','mini');
		$form->addInput($listnumber->addRule('isInteger','请填入一个数字'));

		$since = new Typecho_Widget_Helper_Form_Element_Text('since',
			NULL,'30','读者墙收录时间','设置显示多少*天*以内的评论者头像');
		$since->input->setAttribute('class','mini');
		$form->addInput($since->addRule('isInteger','请填入一个数字'));

		$altword = new Typecho_Widget_Helper_Form_Element_Text('altword',
			NULL,'条评论','读者墙提示文字','设置评论者头像上的评论数提示后缀');
		$altword->input->setAttribute('class','mini');
		$form->addInput($altword);

		$avsize = new Typecho_Widget_Helper_Form_Element_Text('avsize',
			NULL,'32','读者墙头像尺寸','设置读者墙显示的gravatar头像大小(*px*)');
		$avsize->input->setAttribute('class','mini');
		$form->addInput($avsize->addRule('isInteger','请填入一个数字'));

		$avdefault = new Typecho_Widget_Helper_Form_Element_Text('avdefault',
			NULL,'','读者墙缺省头像','设置没有gravatar头像的读者显示图片url(注意与上一项尺寸一致)');
		$avdefault->input->setAttribute('style','width:550px;');
		$form->addInput($avdefault->addRule('url','请填入一个url地址'));

		$avcache = new Typecho_Widget_Helper_Form_Element_Radio('avcache',
			array('false'=>'否','true'=>'是'),'false','读者墙开启缓存','开启后若修改了以上两项设置请点击清空缓存重新生成');
		$form->addInput($avcache);
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
	 * 清空缓存功能表单
	 * 
	 * @access public
	 * @return Typecho_Widget_Helper_Form
	 */
	public static function form($action = NULL)
	{
		$options = Typecho_Widget::widget('Widget_Options');
		$form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/avatars-delete',$options->index),
			Typecho_Widget_Helper_Form::POST_METHOD);

		$do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
		$form->addInput($do);

		$submit = new Typecho_Widget_Helper_Form_Element_Submit();
		$submit->input->setAttribute('class','btn btn-s btn-warn btn-operate');
		$form->addItem($submit);

		$do->value('delete');
		$submit->value('清空缓存');
		$_action = 'delete';

		if (empty($action)) {
			$action = $_action;
		}

		return $form;
	}

	/**
	 * 读者墙标签解析
	 * 
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function parse($content,$widget,$lastResult)
	{
		$content = empty($lastResult)?$content:$lastResult;

		if ($widget instanceof Widget_Archive) {
			return preg_replace_callback("/\[AVATARS(\w*[^>]*)\]/i",array('Avatars_Plugin','callback'),$content);
		} else {
			return $content;
		}

	}

	/**
	 * 标签参数回调
	 * 
	 * @access public
	 * @param array $matches
	 * @return string
	 */
	public static function callback($matches)
	{
		$listtag = 'li';
		$class = 'mostactive';
		if (!empty($matches[1])) {
			if (preg_match("/\|([\w-]*)\|([\w-]*)/i",$matches[1],$out)) {
				$listtag = trim($out[1])==''?$listtag:trim($out[1]);
				$class = trim($out[2])==''?$class:trim($out[2]);
			}
		}
		return self::output($listtag,$class);
	}

	/**
	 * 读者墙模板输出
	 * 
	 * @access public
	 * @param string $listtag 标签名称
	 * @param string $class class名称
	 * @return void
	 */
	public static function output($listtag = 'li',$class = 'mostactive')
	{
		$options = Helper::options();
		$settings = $options->plugin('Avatars');
		$mostactive = '';

		//兼容缓存的默认头像
		$avdefault = (!empty($settings->avdefault))?$settings->avdefault:'http://gravatar.duoshuo.com/avatar/?s='.$settings->avsize.'&amp;d=';

		//同步系统nofollow设置
		$nofollow = ($options->commentsUrlNofollow)?'rel="external nofollow"':'';

		//收录时间计算
		$expire = $options->gmtTime+$options->timezone-$settings->since*24*3600;

		$db = Typecho_Db::get();
		$select = $db->select(array('COUNT(author)'=>'cnt'),'author','url','mail')->from('table.comments')
			->where('status=?','approved')
			->where('authorId=?','0')
			->where('type=?','comment')
			->where('created>?',$expire)
			->limit($settings->listnumber)
			->group('author')
			->order('cnt',Typecho_Db::SORT_DESC);
		$counts = $db->fetchAll($select);

		foreach ($counts as $count) {
			//url未填写链接静默
			$visurl = (!empty($count['url']))?$count['url']:'###';
			$avhash = md5($count['mail']);

			//同步系统头像评级
			$avurl = 'http://gravatar.duoshuo.com/avatar/'.$avhash.'?s='.$settings->avsize.'&amp;r='.$options->commentsAvatarRating.'&amp;d='.$avdefault.'';

			//调用缓存地址判断
			$imgurl = ($settings->avcache=='true')?self::cache($avdefault,$avurl,$avhash):$avurl;

			$mostactive .= 
				'<'.$listtag.''.(empty($class)?'':' class="'.$class.'"').'>'.'<a href="'.$visurl.'"'.$nofollow.'title="'.$count['author'].' - '.$count['cnt'].$settings->altword.'"><img src="'.$imgurl.'" alt="'.$count['author'].' - '.$count['cnt'].$settings->altword.'" class="avatar" /></a></'.$listtag.'>';
		}

		echo $mostactive;
	}

	/**
	 * 读者墙缓存生成
	 * 
	 * @access public
	 * @param string $default 默认头像
	 * @param string $image 用户头像
	 * @param string $mailhash 邮箱哈希
	 * @return string
	 */
	private static function cache($default,$image,$mailhash)
	{
		$options = Helper::options();
		$settings = $options->plugin('Avatars');

		//缓存目录绝对路径
		$setdir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/Avatars/cache/';
		$defaultdir = $setdir.'default';
		$sampledir = $setdir.'set';
		$cachedir = $setdir.$mailhash;

		//缓存默认时限15日
		$cachetime = 14*24*3600;

		if (!is_file($defaultdir))
			copy($default,$defaultdir);
		if (!is_file($sampledir))
			copy('http://gravatar.duoshuo.com/avatar/?s='.$settings->avsize.'&amp;d=',$sampledir);

		//不存在或过期则生成
		if (!is_file($cachedir)||(time()-filemtime($cachedir))>$cachetime)
			copy($image,$cachedir);

		//自定义默认头像覆盖
		if (filesize($cachedir)==filesize($sampledir))
			copy($defaultdir,$cachedir);

		return $options->pluginUrl.'/Avatars/cache/'.$mailhash;
	}

}