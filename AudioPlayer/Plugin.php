<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为博客添加flash mp3播放器Audio Player
 * 
 * @category player
 * @package AudioPlayer
 * @author 羽中
 * @version 1.2.5
 * @dependence 14.10.10-*
 * @link http://www.yzmb.me/archives/net/audio-player-for-typecho
 */
class AudioPlayer_Plugin implements Typecho_Plugin_Interface
{
	/**
	* 初始播放器ID
	* 
	* @access private
	* @var integer
	*/
	private static $playerID = 0;

	/**
	* 默认配色数组
	* 
	* @access private
	* @var array
	*/
	private static $Colors = array(
		'bg'=>'E5E5E5',
		'leftbg'=>'CCCCCC',
		'lefticon'=>'333333',
		'voltrack'=>'FFFFFF',
		'volslider'=>'666666',
		'rightbg'=>'B4B4B4',
		'rightbghover'=>'999999',
		'righticon'=>'333333',
		'righticonhover'=>'FFFFFF',
		'text'=>'333333',
		'tracker'=>'DDDDDD',
		'track'=>'FFFFFF',
		'border'=>'CCCCCC',
		'loader'=>'009900',
		'skip'=>'666666',
	);

	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('AudioPlayer_Plugin','playerparse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('AudioPlayer_Plugin','playerparse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('AudioPlayer_Plugin','textparse');
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
		$options = Helper::options();
		//格式化默认配色
		$colors = self::$Colors;
		foreach ($colors as $key=>$color) {
			$colors[$key] = '#'.$color;
		}
		$colors = Json::encode($colors);

//输出面板效果
?>
<div style="color:#999;font-size:13px;">
<?php _e('编辑文章或页面写入%s文件地址%s即可显示音频播放器, 多个mp3文件连播可用%s号隔开, <br/>参数间用%s号隔开, 支持%s(自动播放)%s(循环播放)%s(曲名)%s(艺术家名)等. 示例','<span style="color:#467B96;font-weight:bold;">[mp3]</span><span style="color:#444;font-weight:bold;">','</span><span style="color:#467B96;font-weight:bold;">[/mp3]</span>','<span style="color:#467B96;font-weight:bold;">,</span>','<span style="color:#467B96;font-weight:bold;">|</span>','<span style="color:#444;font-weight:bold;">autostart</span>','<span style="color:#444;font-weight:bold;">loop</span>','<span style="color:#444;font-weight:bold;">titles</span>','<span style="color:#444;font-weight:bold;">artists</span>'); ?>
</div>
<div style="color:#444;font-size:13px;font-weight:bold;padding:5px 8px;width:210px;background:#E9E9E6;">
<span style="color:#467B96;">[mp3]</span>http://dfp.mp3<span style="color:#467B96;">,</span>http://m.mp3<span style="color:#467B96;">|</span><br/>
<span style="color:#467B96;">titles=</span>东风破<span style="color:#467B96;">,</span>The Monster<span style="color:#467B96;">|</span><br/>
<span style="color:#467B96;">artists=</span>周杰伦<span style="color:#467B96;">,</span>Eminem<span style="color:#467B96;">|</span><br/>
<span style="color:#467B96;">autostart=</span>no<span style="color:#467B96;">|<br/>
loop=</span>yes<span style="color:#467B96;">[/mp3]</span></div>

<link href="<?php $options->pluginUrl('AudioPlayer/assets/cpicker/colorpicker.css'); ?>" rel="stylesheet"/>
<link href="<?php $options->pluginUrl('AudioPlayer/assets/audio-player-admin.css'); ?>" rel="stylesheet"/>
<script src="<?php $options->adminUrl('js/jquery.js'); ?>"></script>
<script>
$(function() {
	//冲突选项显隐
	var e = $('#ap_encode-1'),
		h = $('#typecho-option-item-ap_html5-10'),
		hl = $('label',h), hi = $('input',h);
	if (e.is(':checked')) disabled();
	e.click(function() {
		if ($(this).prop('checked')) {
			disabled();
		} else {
			hl.removeAttr('style');
			hi.removeAttr('disabled').prop('checked','true');
		}
	});
	function disabled() {
		hl.attr('style','color:#999;');
		hi.removeAttr('checked').attr('disabled','true');
	}
	//定义配色数据
	colorInput = $("#ap_colors"),
	colorDatas = eval('('+colorInput.val()+')'),
	colorDefault = eval('(<?php echo $colors; ?>)');
});
</script>
<script src="<?php $options->pluginUrl('AudioPlayer/assets/cpicker/colorpicker.js'); ?>"></script>
<script src="<?php $options->pluginUrl('AudioPlayer/assets/audio-player-admin.js'); ?>"></script>
<script src="<?php $options->pluginUrl('AudioPlayer/assets/audio-player.js'); ?>"></script>
<script >
	AudioPlayer.setup("<?php $options->pluginUrl('AudioPlayer/assets/player.swf'); ?>",<?php echo self::getSets(); ?>);
</script>

<div id="ap_colorscheme">
	<div id="ap_colorselector">
		<input name="ap_colorvalue" type="text" id="ap_colorvalue" size="15" maxlength="7" />
		<span id="ap_colorsample"></span>
		<span id="ap_picker-btn"><?php _e('选色器'); ?></span>
		<?php $themeColors = self::getThemeColors(); if ($themeColors) { ?>
		<span id="ap_themecolor-btn"><?php _e('来自主题'); ?></span>
		<div id="ap_themecolor">
			<span>yzmb.me</span>
			<ul>
			<?php foreach($themeColors as $themeColor) { ?>
				<li style="background:#<?php echo $themeColor ?>;" title="#<?php echo $themeColor ?>">#<?php echo $themeColor ?></li>
			<?php } ?>
			</ul>
		</div>
		<?php } ?>
	</div>
</div>

<?php
		$ap_fieldselector = new Typecho_Widget_Helper_Form_Element_Select('ap_fieldselector',
			array(
				'bg'=>_t('背景'),
				'leftbg'=>_t('左侧背景'),
				'lefticon'=>_t('左侧图标'),
				'voltrack'=>_t('音量背景'),
				'volslider'=>_t('音量滑块'),
				'rightbg'=>_t('右侧背景'),
				'rightbghover'=>_t('右侧背景(悬停)'),
				'righticon'=>_t('右侧图标'),
				'righticonhover'=>_t('右侧图标(悬停)'),
				'text'=>_t('文本'),
				'tracker'=>_t('进度条'),
				'track'=>_t('进度条(剩余)'),
				'border'=>_t('进度条(边框)'),
				'loader'=>_t('加载条'),
				'skip'=>_t('切歌按钮')
			),
			'bg',_t('播放器配色'),'
			<div id="ap_demoplayer">
				Audio Player
			</div>
			<script>
			AudioPlayer.embed("ap_demoplayer",{demomode:"yes"});
			</script>');
		$ap_fieldselector->input->setAttribute('id','ap_fieldselector');
		$ap_fieldselector->input->setAttribute('style','height:23px');
		$form->addInput($ap_fieldselector);

		if (Typecho_Request::getInstance()->is('action=resetcolor') && isset($options->plugins['activated']['AudioPlayer'])) {
			Helper::configPlugin('AudioPlayer',array('ap_colors'=>$colors));
			Typecho_Response::getInstance()->goBack();
		}
		//重置动作按钮
		$resetcolor = new Typecho_Widget_Helper_Form_Element_Submit();
		$resetcolor->value(_t('重置'));
		$resetcolor->setAttribute('style','position:relative');
		$resetcolor->input->setAttribute('id','ap_resetcolor');
		$resetcolor->input->setAttribute('class','btn btn-xs');
		$resetcolor->input->setAttribute('formaction',Helper::security()->getAdminUrl('options-plugin.php?config=AudioPlayer&action=resetcolor'));
		$form->addItem($resetcolor);

		$ap_width = new Typecho_Widget_Helper_Form_Element_Text('ap_width',
		NULL,'290',_t('播放器宽度'),_t('输入像素值(如200不用带px)或百分数(如80%)'));
		$ap_width->input->setAttribute('style','width:50px');
		$ap_width->addRule(array(new AudioPlayer_Plugin,'widthformat'),_t('请填写整数或百分数'));
		$ap_width->addRule('required',_t('播放器宽度不能为空'));
		$form->addInput($ap_width);

		$ap_initialvolume = new Typecho_Widget_Helper_Form_Element_Text('ap_initialvolume',
		NULL,'60',_t('初始音量大小'),_t('播放器启动时的音量起步值, 最大100, 默认60'));
		$ap_initialvolume->input->setAttribute('style','width:50px');
		$ap_initialvolume->addRule('isInteger',_t('请填写整数数字'));
		$ap_initialvolume->addRule('required',_t('初始音量不能为空'));
		$form->addInput($ap_initialvolume);

		$ap_buffer = new Typecho_Widget_Helper_Form_Element_Text('ap_buffer',
		NULL,'5',_t('缓冲等待时间'),_t('单位秒(不用填写), 视播放卡顿情况可适当提高'));
		$ap_buffer->input->setAttribute('style','width:50px');
		$ap_buffer->addRule('isInteger',_t('请填写整数数字'));
		$ap_buffer->addRule('required',_t('缓冲时间不能为空'));
		$form->addInput($ap_buffer);

		$ap_animation = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_animation',
		array(1=>_t('省去点击操作让播放器直接处于展开状态')),NULL,_t('禁用动画效果'));
		$form->addInput($ap_animation);

		$ap_encode = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_encode',
		array(1=>_t('隐藏文件真实url(不支持HTML5缺省播放)')),NULL,_t('加密mp3地址'));
		$form->addInput($ap_encode);

		$ap_behaviour = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_behaviour',
		array(1=>_t('将文内指向mp3的链接自动替换成播放器')),NULL,_t('代替mp3链接'));
		$form->addInput($ap_behaviour);

		$ap_remaining = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_remaining',
		array(1=>_t('显示音频的剩余倒计时而非已播放的时长')),NULL,_t('显示剩余时长'));
		$form->addInput($ap_remaining);

		$ap_noinfo = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_noinfo',
		array(1=>_t('隐藏曲名/艺术家名等标签信息仅显示空白')),NULL,_t('禁用曲目信息'));
		$form->addInput($ap_noinfo);

		$ap_html5 = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_html5',
		array(1=>_t('若浏览器不支持flash则显示HTML5播放器')),1,_t('使用缺省播放'));
		$form->addInput($ap_html5);

		//配色保存隐藏域
		$ap_colors = new Typecho_Widget_Helper_Form_Element_Hidden('ap_colors',NULL,$colors,NULL);
		$ap_colors->input->setAttribute('id','ap_colors');
		$form->addInput($ap_colors);
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
	 * 内容标签替换
	 * 
	 * @param string $content
	 * @return string
	 */
	public static function playerparse($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;

		if ($widget instanceof Widget_Archive && !$widget->request->feed && false!==stripos($content,'[mp3]')) {
			$pattern = '/\[(mp3)](.*?)\[\/\\1]/si';
			$callback = array('AudioPlayer_Plugin','parseCallback');

			//替换播放器标签
			$content = preg_replace_callback($pattern,$callback,$content);

			//替换mp3链接
			if (Helper::options()->plugin('AudioPlayer')->ap_behaviour) {
				$content = preg_replace_callback('/<a ([^=]+=[\'"][^"\']+[\'"] )*href=[\'"]([^\s]+\.mp3)[\'"]( [^=]+=[\'"][^"\']+[\'"])*>.*?<\/a>/si',$callback,$content);
			}
		}

		return $content;
	}

	/**
	 * 摘要文本替换
	 * 
	 * @param string $text
	 * @return string
	 */
	public static function textparse($text,$widget,$lastResult)
	{
		$text = empty($lastResult) ? $text : $lastResult;

		if ($widget instanceof Widget_Archive && false!==stripos($text,'[mp3]')) {
			$text = preg_replace('/\[(mp3)](.*?)\[\/\\1]/si',_t(' [音频片段: 请查看全文播放] '),$text);
		}

		return $text;
	}

	/**
	 * 参数回调解析
	 * 
	 * @param array $matche
	 * @return string
	 */
	public static function parseCallback($matche)
	{
		//过滤html标签
		$atts = explode('|',trim(Typecho_Common::stripTags($matche['2'])));
		$files = array_shift($atts);

		$pair = array();
		$data = array();
		foreach ($atts as $att) {
			$pair = explode('=',$att);
			$data[trim($pair['0'])] = trim($pair['1']);
		}

		return self::getPlayer($files,$data,true);
	}

	/**
	 * 输出播放器实例
	 * 
	 * @param string $source 音频地址
	 * @param array $playerOptions 参数设置
	 * @param boolean $isCall 是否回调
	 * @return void
	 */
	public static function getPlayer($source,$playerOptions=array(),$isCall=false)
	{
		$options = Helper::options();
		$settings = $options->plugin('AudioPlayer');
		$playerurl = $options->pluginUrl.'/AudioPlayer/assets/';
		$playerElementID = "audioplayer_".++self::$playerID;

		//url编码处理
		$source = html_entity_decode($source);
		if (function_exists('iconv')) {
			$address = iconv('gbk','utf-8',$source);
		}
		$playerOptions['soundFile'] = $settings->ap_encode ? self::encodeSource($address) : $address;

		$fallback = '<span style="padding:5px;border:1px solid #dddddd;background:#f8f8f8;">'._t('播放此段音频需要Adobe Flash Player, 请点击<a href="%s" title="下载Adobe Flash Player">下载最新版本</a>并确认浏览器已开启JavaScipt支持','https://get.adobe.com/flashplayer/').'</span>';
		//不加密可html5
		if ($settings->ap_html5 && !$settings->ap_encode) {
			$fallback = '';
			$sources = explode(',',$source);
			foreach ($sources as $source) {
				$fallback .= '<audio src="'.$source.'" controls preload="none"></audio>';
			}
		}

		//播放器实例代码
		$playerCode = '<script type="text/javascript">//<![CDATA[
	window.audioplayer_swfobject || document.write("<script type=\"text/javascript\" src=\"'.$playerurl.'audio-player.js\"><\/script>")//]]></script>';
		$playerCode .= '<script type="text/javascript">AudioPlayer.setup("'.$playerurl.'player.swf",'.self::getSets().');</script>';
		$playerCode .= '<p class="audioplayer_container" id="'.$playerElementID.'">'.$fallback.'</p>';
		$playerCode .= '<script type="text/javascript">';
		$playerCode .= 'AudioPlayer.embed("'.$playerElementID.'",'.self::php2js($playerOptions).');';
		$playerCode .= '</script>';

		//模版输出判断
		if ($isCall) {
			return $playerCode;
		} else {
			echo $playerCode;
		}
	}

	/**
	 * 输出插件设置
	 * 
	 * @return string
	 */
	public static function getSets()
	{
		$options = Helper::options();
		//加载默认参数
		$ap_options = array(
			'width'=>'290',
			'initialvolume'=>'60',
			'buffer'=>'5',
			'animation'=>true,
			'encode'=>false,
			'remaining'=>false,
			'noinfo'=>false,
			'checkpolicy'=>true,
			'transparentpagebg'=>true,
			'rtl'=>false
			);
		$colors = self::$Colors;

		//读取插件设置
		if (isset($options->plugins['activated']['AudioPlayer'])) {
			$settings = $options->plugin('AudioPlayer');
			$ap_options['width'] = $settings->ap_width;
			$ap_options['initialvolume'] = $settings->ap_initialvolume;
			$ap_options['buffer'] = $settings->ap_buffer;
			$ap_options['animation'] = $settings->ap_animation ? false : true;
			$ap_options['encode'] = $settings->ap_encode ? true : false;
			$ap_options['remaining'] = $settings->ap_remaining ? true : false;
			$ap_options['noinfo'] = $settings->ap_noinfo ? true : false;

			//解析配色数据
			$colors = Json::decode($settings->ap_colors,true);
			foreach ($colors as $key=>$color) {
				$colors[$key] = substr($color,1);
			}
		}
		return self::php2js(array_merge($ap_options,$colors));
	}

	/**
	 * 格式化配置参数
	 * 
	 * @param array $object
	 * @return string
	 */
	private static function php2js($object)
	{
		$separator = '';
		$real_separator = ',';

		$js_options = '{';
		foreach($object as $key=>$value) {
			//布尔型处理
			if (is_bool($value)) {
				$value = $value ? 'yes' : 'no';
			} elseif (in_array($key,array('soundFile','titles','artists'))) {
				//文本型处理
				if (in_array($key,array('titles','artists'))) {
					$value = html_entity_decode($value);
				}
				$value = rawurlencode($value);
			}
			$js_options .= $separator.$key.':"'.$value.'"';
			$separator = $real_separator;
		}
		$js_options .= '}';

		return $js_options;
	}

	/**
	 * 加密音频地址
	 * 
	 * @param string $string
	 * @return string
	 */
	private static function encodeSource($string)
	{
		//对应swf解码修正
		$string = rawurlencode($string);

		$ntexto = '';
		$codekey = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-';
		for ($i=0;$i<strlen($string);$i++) {
			$ntexto .= substr('0000'.base_convert(ord($string{$i}),10,2),-8);
		}
		$ntexto .= substr('00000',0,6-strlen($ntexto)%6);

		$string = '';
		for ($i=0;$i<strlen($ntexto)-1;$i=$i+6) {
			$string .= $codekey{intval(substr($ntexto,$i,6),2)};
		}

		return $string;
	}

	/**
	 * 解析主题配色
	 * 
	 * @return void
	 */
	private static function getThemeColors()
	{
		$theme = Helper::options()->theme;
		$cssfile = __TYPECHO_ROOT_DIR__.__TYPECHO_THEME_DIR__.'/'.$theme.'/style.css';

		if (is_file($cssfile)) {
			preg_match_all('/:[^:,;\{\}].*?#([abcdef1234567890]{3,6})/i',file_get_contents($cssfile),$matches);
			return array_unique($matches['1']);
		}
	}

	/**
	 * 判断宽度格式
	 * 
	 * @access public
	 * @param string $width
	 * @return boolean
	 */
	public static function widthformat($width)
	{
		return preg_match('/^[0-9]+%?$/',$width);
	}

}
