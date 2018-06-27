<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为博客添加flash mp3播放器Audio Player
 * 
 * @package AudioPlayer
 * @author 羽中
 * @version 1.2.6
 * @dependence 14.5.26-*
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
	private static $colors = array(
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('AudioPlayer_Plugin','textparse');

		Typecho_Plugin::factory('Widget_Archive')->header = array('AudioPlayer_Plugin','html5css');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('AudioPlayer_Plugin','html5js');

		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('AudioPlayer_Plugin','apbutton');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('AudioPlayer_Plugin','apbutton');

		/* 模版调用钩子 例: <?php $this->audioplayer('http://test.mp3'); ?> 第2个参数(可略)为播放器参数数组 */
		Typecho_Plugin::factory('Widget_Archive')->callAudioplayer = array('AudioPlayer_Plugin', 'getPlayer');
		Typecho_Plugin::factory('Widget_Archive')->callMiniplayer = array('AudioPlayer_Plugin', 'html5player');
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
		$security = Helper::security();

		//格式化默认配色
		$colors = self::$colors;
		$colorset = array();
		foreach ($colors as $key=>$color) {
			$colorset[$key] = '#'.$color;
		}
		$colors = Json::encode($colorset);

		if (isset($options->plugins['activated']['AudioPlayer'])) {
			$settings = $options->plugin('AudioPlayer');
			$colorset = Json::decode($settings->ap_colors,true);
		}

//输出面板效果
?>
<div id="description">
<?php _e('编辑文章或页面写入%s文件地址%s即可显示音频播放器, 多个mp3文件连播可用%s号隔开, <br/>参数间用%s号隔开, 支持%s(自动播放)%s(循环播放)%s(曲名)%s(艺术家名)等. 示例','<strong>[mp3]<span>','</span>[/mp3]</strong>','<strong>,</strong>','<strong>|</strong>','<span>autostart</span>','<span>loop</span>','<span>titles</span>','<span>artists</span>'); ?>
</div>
<div id="sample">
<span>[mp3]</span>http://a.mp3<span>,</span>http://b.mp3<span>|</span><br/>
<span>titles=</span>aaa<span>,</span>bbb<span>|</span><br/>
<span>artists=</span>aa<span>,</span>bb<span>|</span><br/>
<span>autostart=</span>no<span>|<br/>
loop=</span>yes<span>[/mp3]</span></div>

<link href="<?php $options->pluginUrl('AudioPlayer/admin/audio-player-admin.css'); ?>" rel="stylesheet"/>
<link href="<?php $options->pluginUrl('AudioPlayer/assets/cpicker/colorpicker.css'); ?>" rel="stylesheet"/>
<link href="<?php $options->pluginUrl('AudioPlayer/assets/miniplayer.css'); ?>" rel="stylesheet"/>
<style>
.mbMiniPlayer.custom .playerTable{background-color:transparent;}
.mbMiniPlayer.custom .playerTable span{color:<?php echo $colorset['lefticon']; ?>;background-color:<?php echo $colorset['leftbg']; ?>;text-shadow:none !important;}
.mbMiniPlayer.custom .playerTable span.map_play{border-left:1px solid <?php echo $colorset['bg']; ?>;}
.mbMiniPlayer.custom .playerTable span.map_volume{padding-left:6px !important;border-right:1px solid <?php echo $colorset['bg']; ?>;}
.mbMiniPlayer.custom .playerTable span.map_volume.mute{color:<?php echo $colorset['skip']; ?>;}
.mbMiniPlayer.custom .playerTable span.map_title{color:<?php echo $colorset['text']; ?>;}
.mbMiniPlayer.custom .playerTable .jp-load-bar{background-color:<?php echo $colorset['track']; ?>;}
.mbMiniPlayer.custom .playerTable .jp-play-bar{background-color:<?php echo $colorset['tracker']; ?>;}
.mbMiniPlayer.custom .playerTable span.map_volumeLevel a{background-color:<?php echo $colorset['voltrack']; ?>;}
.mbMiniPlayer.custom .playerTable span.map_volumeLevel a.sel{background-color:<?php echo $colorset['volslider']; ?>;}
</style>
<script src="<?php $options->adminUrl('js/jquery.js'); ?>"></script>
<script src="<?php $options->pluginUrl('AudioPlayer/assets/audio-player.js'); ?>"></script>
<script>
//菜单项背景色
$(function(){
	$('option:eq(5),option:eq(6),option:eq(7),option:eq(8),option:eq(12),option:eq(13)').attr('style','background-color:#E9E9E6;color:#999;');
});
AudioPlayer.setup("<?php $options->pluginUrl('AudioPlayer/assets/player.swf'); ?>",<?php echo self::getSets(); ?>);
AudioPlayer.embed("ap_demoplayer",{demomode:"yes"});
</script>
<script src="<?php $options->pluginUrl('AudioPlayer/admin/audio-player-admin.js'); ?>"></script>
<script src="<?php $options->pluginUrl('AudioPlayer/assets/cpicker/colorpicker.js'); ?>"></script>

<div id="ap_colorscheme">
	<div id="ap_colorselector">
		<input name="ap_colorvalue" type="text" id="ap_colorvalue" size="15" maxlength="7"/>
		<span id="ap_colorsample"></span>
		<span id="ap_picker-btn"><?php _e('选色器'); ?></span>
<?php
$tcolors = self::getThemeColors();
if ($tcolors) {
?>
		<span id="ap_themecolor-btn"><?php _e('来自主题'); ?></span>
		<div id="ap_themecolor">
			<span>yzmb.me</span>
			<ul>
<?php
	foreach ($tcolors as $tcolor) {
 ?>
				<li style="background:#<?php echo $tcolor ?>;" title="#<?php echo $tcolor ?>">#<?php echo $tcolor ?></li>
<?php
	}
?>
			</ul>
		</div>
<?php
}
?>
	</div>
</div>

<?php
		if (Typecho_Request::getInstance()->is('action=resetcolor') && isset($options->plugins['activated']['AudioPlayer'])) {
			$security->protect();
			Helper::configPlugin('AudioPlayer',array('ap_colors'=>$colors));
			Typecho_Response::getInstance()->goBack();
		}
		//重置动作按钮
		$resetcolor = new Typecho_Widget_Helper_Form_Element_Submit();
		$resetcolor->value(_t('重置'));
		$resetcolor->input->setAttribute('id','ap_resetcolor');
		$resetcolor->input->setAttribute('class','btn btn-xs');
		$resetcolor->input->setAttribute('formaction',$security->getAdminUrl('options-plugin.php?config=AudioPlayer&action=resetcolor'));
		$form->addItem($resetcolor);

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
	<div id="mp" isplaying="true" tabindex="1" class="mbMiniPlayer custom jp-state-playing"><div class="playerTable"><div style="user-select: none;" unselectable="on" class="muteBox"><span class="map_volume">Vm</span></div><div style="user-select: none; display: table-cell;" unselectable="on" class="volumeLevel"><span class="map_volumeLevel" style="width: 40px;"><a style="opacity: 0.4; height: 80%; width: 2px;" class="sel"></a><a style="opacity: 0.4; height: 80%; width: 2px;" class="sel"></a><a style="opacity: 0.4; height: 80%; width: 2px;" class="sel"></a><a style="opacity: 0.4; height: 80%; width: 2px;" class="sel"></a><a style="opacity: 0.4; height: 80%; width: 2px;" class="sel"></a><a style="opacity: 0.4; height: 80%; width: 2px;" class="sel"></a><a style="opacity: 0.4; height: 80%; width: 2px;" class="sel"></a><a style="opacity: 0.1; height: 80%; width: 2px;"></a><a style="opacity: 0.1; height: 80%; width: 2px;"></a><a style="opacity: 0.1; height: 80%; width: 2px;"></a><a style="opacity: 0.1; height: 80%; width: 2px;"></a><a style="opacity: 0.1; height: 80%; width: 2px;"></a></span></div><div style="user-select: none; display: table-cell;" unselectable="on" class="map_controlsBar"><div class="map_controls" style="display: block; height: 20px; width: 121px;"><span class="map_title">HTML5: Demo Mode</span><div class="jp-progress"><div class="jp-load-bar" id="loadBar_mp_mbmaplayer_1524372259773" style="width: 100%;"><div class="jp-play-bar" id="playBar_mp_mbmaplayer_1524372259773" style="width: 58.3111%; overflow: hidden;"></div></div></div></div></div><div style="user-select: none; display: table-cell;" unselectable="on" class="timeBox"><span class="map_time" style="width: 34px;" title="03:45">02:11</span></div><div style="user-select: none; display: table-cell;" unselectable="on" class="rewBox"><span class="map_rew" style="width: 20px;">R</span></div><div style="user-select: none;" unselectable="on"><span class="map_play" style="opacity: 1;">p</span></div></div></div>
</div>
<span class="predes">'._t('注意菜单内灰色项不适用于HTML5版播放器').'</span>');
		$ap_fieldselector->input->setAttribute('id','ap_fieldselector');
		$form->addInput($ap_fieldselector);

		$ap_width = new Typecho_Widget_Helper_Form_Element_Text('ap_width',
		NULL,'290',_t('播放器宽度'),_t('输入像素值(如200不用带px)或百分数(如80%)'));
		$ap_width->addRule('required',_t('播放器宽度不能为空'));
		$ap_width->addRule(array(new AudioPlayer_Plugin,'widthformat'),_t('请填写整数或百分数'));
		$form->addInput($ap_width);

		$ap_initialvolume = new Typecho_Widget_Helper_Form_Element_Text('ap_initialvolume',
		NULL,'60',_t('初始音量大小'),_t('播放器启动时的音量起步值, 最大100, 默认60'));
		$ap_initialvolume->addRule('required',_t('初始音量不能为空'));
		$form->addInput($ap_initialvolume->addRule('isInteger',_t('请填写整数数字')));

		$ap_buffer = new Typecho_Widget_Helper_Form_Element_Text('ap_buffer',
		NULL,'5',_t('缓冲预读时间').' <span>HTML5<em>&#10008;</em></span>',_t('单位秒(不用填写), 若播放经常卡顿可适当提高'));
		$ap_buffer->addRule('required',_t('缓冲时间不能为空'));
		$form->addInput($ap_buffer->addRule('isInteger',_t('请填写整数数字')));

		$ap_animation = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_animation',
		array(1=>_t('省去点击操作让播放器直接处于展开状态')),NULL,_t('禁用动画效果'));
		$form->addInput($ap_animation);

		$ap_encode = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_encode',
		array(1=>_t('隐藏文件真实的url, 在源码中显示为乱码')),NULL,_t('加密mp3地址'));
		$form->addInput($ap_encode);

		$ap_behaviour = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_behaviour',
		array(1=>_t('将文中指向mp3的链接自动替换为播放器')),NULL,_t('替换mp3链接'));
		$form->addInput($ap_behaviour);

		$ap_remaining = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_remaining',
		array(1=>_t('显示音频的剩余倒计时而非已播放的时长')),NULL,_t('显示剩余时长').' <span>HTML5<em>&#10008;</em></span>');
		$form->addInput($ap_remaining);

		$ap_noinfo = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_noinfo',
		array(1=>_t('隐藏曲名/艺术家名等标签信息仅显示空白')),NULL,_t('禁用曲目信息'));
		$form->addInput($ap_noinfo);

		$ap_html5 = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_html5',
		array(1=>_t('若浏览器不支持flash显示HTML5版播放器')),1,_t('使用缺省播放'));
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
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function playerparse($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;

		if ($widget instanceof Widget_Archive && !$widget->request->feed && false!==stripos($content,'[mp3]')) {
			$callback = array('AudioPlayer_Plugin','parseCallback');
			//替换播放器标签
			$content = preg_replace_callback('/\[(mp3)](.*?)\[\/\\1]/si',$callback,$content);

			//替换mp3链接
			if (Helper::options()->plugin('AudioPlayer')->ap_behaviour) {
				$content = preg_replace_callback('/<a ([^=]+=[\'"][^mp_.*?]+[\'"] )*href=[\'"]([^\s]+\.mp3)[\'"]( [^=]+=[\'"][^"\']+[\'"])*>.*?<\/a>/si',$callback,$content);
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
	 * @param array $match
	 * @return string
	 */
	public static function parseCallback($match)
	{
		//过滤html标签
		$atts = explode('|',trim(Typecho_Common::stripTags($match['2'])));
		$files = array_shift($atts);

		$pair = array();
		$data = array();
		foreach ($atts as $att) {
			$pair = explode('=',$att);
			$data[trim($pair['0'])] = trim($pair['1']);
		}

		return self::getPlayer(Typecho_Widget::widget('Widget_Archive'),array($files,$data,true));
	}

	/**
	 * 输出播放器实例
	 * 
	 * @access public
	 * @param array $params 实例参数
	 * @return string
	 */
	public static function getPlayer($widget,array $params)
	{
		$options = Helper::options();
		$settings = $options->plugin('AudioPlayer');
		$playerurl = $options->pluginUrl.'/AudioPlayer/assets/';
		$playerid = "audioplayer_".++self::$playerID;

		//处理实例参数
		$source = '';
		$source = isset($params['0']) && is_string($params['0']) ? $params['0'] : $source;
		$playerOptions = array();
		$playerOptions = isset($params['1']) && is_array($params['1']) ? $params['1'] : $playerOptions;
		$isCall = false;
		$isCall = !empty($params['2']) && is_bool($params['2']) ? $params['2'] : $isCall;

		$source = html_entity_decode($source);
		$playerOptions['soundFile'] = $settings->ap_encode ? self::encodeSource($source) : $source;
		//缺省调用html5
		$fallback = $settings->ap_html5 ? self::html5player($widget,array($source,$playerOptions,true))
			: '<span style="padding:5px;border:1px solid #dddddd;background:#f8f8f8;">'._t('播放此段音频需要Adobe Flash Player, 请点击%s下载最新版本%s并确认浏览器已开启JavaScipt支持','<a href="https://get.adobe.com/flashplayer/" target="_blank">','</a>').'</span>';

		//播放器实例代码
		$playerCode = '<script type="text/javascript">
window.audioplayer_swfobject || document.write(\'<script type="text/javascript" src="'.$playerurl.'audio-player.js"><\/script>\')</script>'; //不重复加载
		$playerCode .= '<script type="text/javascript">AudioPlayer.setup("'.$playerurl.'player.swf",'.self::getSets().');</script>';
		$playerCode .= '<div class="audioplayer_container" id="'.$playerid.'">'.$fallback.'</div>';
		$playerCode .= '<script type="text/javascript">';
		$playerCode .= 'AudioPlayer.embed("'.$playerid.'",'.self::php2js($playerOptions).');';
		$playerCode .= '</script>';

		//模版输出判断
		if ($isCall) {
			return $playerCode;
		} else {
			echo $playerCode;
		}
	}

	/**
	 * 输出html5版实例
	 * 
	 * @access public
	 * @param array $params 实例参数
	 * @return string
	 */
	public static function html5player($widget,array $params)
	{
		$options = Helper::options();
		$settings = $options->plugin('AudioPlayer');
		$playerurl = $options->pluginUrl.'/AudioPlayer/assets/';
		$playerid = "mp_".++self::$playerID;

		//处理实例参数
		$source = '';
		$source = isset($params['0']) && is_string($params['0']) ? $params['0'] : $source;
		$playerOptions = array();
		$playerOptions = isset($params['1']) && is_array($params['1']) ? $params['1'] : $playerOptions;
		$isCall = false;
		$isCall = !empty($params['2']) && is_bool($params['2']) ? $params['2'] : $isCall;

		//处理播放参数
		$autostart = isset($playerOptions['autostart']) ? $playerOptions['autostart'] : '';
		$loop = isset($playerOptions['loop']) ? $playerOptions['loop'] : '';
		$title = isset($playerOptions['titles']) ? $playerOptions['titles'] : '';
		$artist = isset($playerOptions['artists']) ? $playerOptions['artists'] : '';
		$noinfo = $settings->ap_noinfo;
		$param = '{autoplay:'.($autostart=='yes' ? 'true' : 'false').',loop:'.($loop=='yes' ? 'true' : 'false').($title || $noinfo ? ',id3:false' : '').'}';

		$infos = self::infos($source,$title,$artist);
		$sources = explode(',',$source);
		$mp3 = trim($sources['0']);
		$name = $noinfo ? '' : $infos['0'];
		$mp3 = $settings->ap_encode ? self::encodeSource($mp3) : $mp3;
		$html = '<a id="'.$playerid.'" class="mb_map '.$param.'" href="'.$mp3.'">'.$name.'</a>';

		//输出列表模式
		if (count($sources)>1) {
			$html = '
<div class="map_pl_container">'.$html.'
<div class="pl_items_container">
';
			foreach ($sources as $i=>$source) {
				$mp3 = trim($source);
				$name = $infos[''.$i.''];
				$mp3 = $settings->ap_encode ? self::encodeSource($mp3) : $mp3;
				$html .= '<div class="pl_item'.($i==0 ? ' sel' : '').'" onclick="$(\'#'.$playerid.'\').mb_miniPlayer_changeFile({mp3:\''.$mp3.'\'},\''.$name.'\')" style="cursor:pointer;">'.$name.'</div>
';
			}
			$html .= '</div></div>
';
		}

		//模版输出判断
		if ($isCall) {
			return $html;
		} else {
			echo $html;
		}
	}

	/**
	 * 构造曲目信息
	 * 
	 * @param string $source 文件地址
	 * @param string $title 曲目名称
	 * @param string $artist 艺术家名
	 * @return array
	 */
	private static function infos($source,$title,$artist)
	{
		$sources = explode(',',$source);
		$titles = explode(',',$title);
		$artists = explode(',',$artist);

		$song = '';
		$star = '';
		$con = '';
		$info = array();
		foreach ($sources as $i=>$file) {
			$song = empty($titles[''.$i.'']) ? preg_replace('/^.+[\\\\\\/]/','',$file) : trim($titles[''.$i.'']);
			$star = isset($artists[''.$i.'']) ? trim($artists[''.$i.'']) : '';
			$con = $star ? ' - ' : '';
			$info[] = $song.$con.$star;
		}

		return $info;
	}

	/**
	 * 输出头部样式
	 * 
	 * @access public
	 * @return void
	 */
	public static function html5css()
	{
		$options = Helper::options();
		$settings = $options->plugin('AudioPlayer');
		$playerurl = $options->pluginUrl.'/AudioPlayer/assets/';
		$width = $settings->ap_width;
		$colorset = Json::decode($settings->ap_colors,true);
		$css = '';

		if ($settings->ap_html5) {
			$css = '
<link href="'.$playerurl.'miniplayer.css" rel="stylesheet" type="text/css"/>
<style type="text/css">
.mbMiniPlayer.custom .playerTable{background-color:transparent;}
.mbMiniPlayer.custom .playerTable span{color:'.$colorset['lefticon'].';background-color:'.$colorset['leftbg'].';text-shadow:none !important;}
.mbMiniPlayer.custom .playerTable span.map_play{border-left:1px solid '.$colorset['bg'].';}
.mbMiniPlayer.custom .playerTable span.map_volume{padding-left:6px !important;border-right:1px solid '.$colorset['bg'].';}
.mbMiniPlayer.custom .playerTable span.map_volume.mute{color:'.$colorset['skip'].';}
.mbMiniPlayer.custom .playerTable span.map_title{color:'.$colorset['text'].';}
.mbMiniPlayer.custom .playerTable .jp-load-bar{background-color:'.$colorset['track'].';}
.mbMiniPlayer.custom .playerTable .jp-play-bar{background-color:'.$colorset['tracker'].';}
.mbMiniPlayer.custom .playerTable span.map_volumeLevel a{background-color:'.$colorset['voltrack'].';}
.mbMiniPlayer.custom .playerTable span.map_volumeLevel a.sel{background-color:'.$colorset['volslider'].';}
.pl_items_container {width:'.(false!==strpos($width,'%') ? $width : $width.'px').' !important;}
</style>
';
		}
		echo $css;
	}

	/**
	 * 输出底部脚本
	 * 
	 * @access public
	 * @return void
	 */
	public static function html5js()
	{
		$options = Helper::options();
		$settings = $options->plugin('AudioPlayer');
		$playerurl = $options->pluginUrl.'/AudioPlayer/assets/';
		$width = $settings->ap_width;
		$js = '';

		if ($settings->ap_html5) {
			$js = '
<script type="text/javascript">
window.jQuery || document.write(\'<script type="text/javascript" src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"><\/script>\')</script>
<script type="text/javascript" src="'.$playerurl.'miniplayer.js"></script>
<script type="text/javascript">
$(function(){
	$(\'a[id^="mp_"]\').mb_miniPlayer({
		width:"'.(false!==strpos($width,'%') ? $width : $width+10).'",
		animate:'.($settings->ap_animation ? 'false' : 'true').',
		volume:'.$settings->ap_initialvolume*0.01.($settings->ap_encode ? ',
		encode:true' : '').'
	});
});
</script>
';
		}
		echo $js;
	}

	/**
	 * 输出flash版配置
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
		$colors = self::$colors;

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

		return self::php2js($ap_options+$colors);
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
	 * @return array
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
	 * 输出编辑器按钮
	 * 
	 * @access public
	 * @return void
	 */
	public static function apbutton()
	{
?>
<script>
$(function(){
	var wmd = $('#wmd-image-button');
	if (wmd.length>0) {
		wmd.after(
	'<li class="wmd-button" id="wmd-ap-button" style="padding-top:5px;" title="<?php _e("插入MP3"); ?>"><img src="<?php echo Helper::options()->pluginUrl; ?>/AudioPlayer/admin/audio.svg"/></li>');
	} else {
		$('.url-slug').after('<button type="button" id="wmd-ap-button" class="btn btn-xs" style="margin-right:5px;"><?php _e("插入MP3"); ?></button>');
	}
	$('#wmd-ap-button').click(function(){
		$('body').append('<div id="apanel">' +
		'<div class="wmd-prompt-background" style="position:absolute;z-index:1000;opacity:0.5;top:0px;left:0px;width:100%;height:954px;"></div>' +
		'<div class="wmd-prompt-dialog"><div><p><b><?php _e("插入MP3"); ?></b></p>' +
			'<p><?php _e("请在下方的输入框内输入要插入的MP3地址"); ?></p></div>' +
			'<form><input type="text"></input><button type="button" class="btn btn-s primary" id="ok"><?php _e("确定"); ?></button>' +
			'<button type="button" class="btn btn-s" id="cancel"><?php _e("取消"); ?></button></form>' +
		'</div></div>');
		var aplog = $('.wmd-prompt-dialog input'),
			textarea = $('#text');
		aplog.val('http://').select();
		$('#cancel').click(function(){
			$('#apanel').remove();
			textarea.focus();
		});
		$('#ok').click(function(){
			var apinput = '[mp3]' + aplog.val() + '[/mp3]',
				sel = textarea.getSelection(),
				offset = (sel ? sel.start : 0)+apinput.length;
			textarea.replaceSelection(apinput);
			textarea.setSelection(offset,offset);
			$('#apanel').remove();
		});
	});
});
</script>
<?php
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
