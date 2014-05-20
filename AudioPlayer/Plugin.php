<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 嵌入高定制度flash mp3播放器Audio Player
 * 
 * @package AudioPlayer
 * @author 羽中
 * @version 1.2.0
 * @dependence 13.12.12-*
 * @link http://www.jzwalk.com/archives/net/audio-player-for-typecho
 */
class AudioPlayer_Plugin implements Typecho_Plugin_Interface
{
	protected static $playerID = 0;
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('AudioPlayer_Plugin', 'playerparse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('AudioPlayer_Plugin', 'playerparse');
		Typecho_Plugin::factory('Widget_Archive')->header = array('AudioPlayer_Plugin','playerjs');
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
?>
	<div style="color:#999;font-size:0.92857em;font-weight:bold;">
	<?php _e('编辑文章或页面写入如<span style="color:#467B96;">[mp3]</span><span style="color:#E47E00;">文件地址</span><span style="color:#467B96;">[/mp3]</span>发布即可. 多个mp3地址可用<span style="color:#467B96;">,</span>号隔开. <br/>
		<p>可带参数autostart(自动播放)loop(循环播放)titles(曲名)artists(艺术家名)用<span style="color:#467B96;">|</span>号隔开. <br/>
		例: %s','<span style="color:#467B96;">[mp3]</span><span style="color:#E47E00;">http://1.mp3</span><span style="color:#467B96;">,</span><span style="color:#E47E00;">http://2.mp3</span><span style="color:#467B96;">|</span><span style="color:#E47E00;">titles=简单爱<span style="color:#467B96;">,</span>The Monster</span><span style="color:#467B96;">|</span><span style="color:#E47E00;">artists=周杰伦<span style="color:#467B96;">,</span>Eminem</span><span style="color:#467B96;">|</span><span style="color:#E47E00;">autostart=yes</span><span style="color:#467B96;">|</span><span style="color:#E47E00;">loop=no</span><span style="color:#467B96;">[/mp3]</span></p>'); ?>
	</div>

	<link href="<?php $options->pluginUrl('AudioPlayer/assets/audio-player-admin.css');?>" rel="stylesheet" type="text/css" />
	<link href="<?php $options->pluginUrl('AudioPlayer/assets/cpicker/colorpicker.css');?>" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="<?php $options->adminUrl('js/jquery.js');?>"></script>
	<script type="text/javascript" src="<?php $options->pluginUrl('AudioPlayer/assets/cpicker/colorpicker.js');?>"></script>
	<script type="text/javascript" src="<?php $options->pluginUrl('AudioPlayer/assets/audio-player-admin.js');?>"></script>
	<script type="text/javascript" src="<?php $options->pluginUrl('AudioPlayer/assets/audio-player.js');?>"></script>
	<script type="text/javascript">
		AudioPlayer.setup("<?php $options->pluginUrl('AudioPlayer/assets/player.swf');?>", <?php echo self::getsets();?>);
	</script>

	<div id="ap_colorscheme">
		<div id="ap_colorselector">
			<input name="ap_colorvalue" type="text" id="ap_colorvalue" size="15" maxlength="7" />
			<span id="ap_colorsample"></span>
			<span id="ap_picker-btn"><?php _e('选色器'); ?></span>
			<?php if (count(self::getThemeColors())) { ?>
			<span id="ap_themecolor-btn"><?php _e('来自主题'); ?></span>
			<div id="ap_themecolor">
				<span><?php _e('主题用色'); ?></span>
				<ul>
				<?php foreach(self::getThemeColors() as $themeColor) { ?>
					<li style="background:#<?php echo $themeColor ?>" title="#<?php echo $themeColor ?>">#<?php echo $themeColor ?></li>
				<?php } ?>
				</ul>
			</div>
			<?php } ?>
		</div>
	</div>
<?php
		$ap_width = new Typecho_Widget_Helper_Form_Element_Text('ap_width',
			NULL,'290',_t('播放器宽度'),_t('输入像素值(如200)或百分数(如100%)'));
		$ap_width->input->setAttribute('class','w-10');
		$ap_width->addRule(array(new AudioPlayer_Plugin,'widthformat'),_t('请输入整数或百分数'));
		$ap_width->addRule('required',_t('播放器宽度不能为空'));
		$form->addInput($ap_width);

		$ap_fieldselector= new Typecho_Widget_Helper_Form_Element_Select('ap_fieldselector',
			array(
				'bg'=>_t('背景'),
				'leftbg'=>_t('左侧背景'),
				'lefticon'=>_t('左侧图标(喇叭)'),
				'voltrack'=>_t('音量背景'),
				'volslider'=>_t('音量滑块'),
				'rightbg'=>_t('右侧背景'),
				'rightbghover'=>_t('右侧背景(悬停时)'),
				'righticon'=>_t('右侧图标(播放/暂停)'),
				'righticonhover'=>_t('右侧图标(悬停时)'),
				'text'=>_t('文本'),'tracker'=>_t('进度条'),
				'track'=>_t('进度条(剩余)'),
				'border'=>_t('进度条(边框)'),
				'loader'=>_t('加载条'),
				'skip'=>_t('切歌按钮')
			),
			'bg',_t('配色方案'),'
			<div id="ap_demoplayer">
				Audio Player
			</div>
			<script type="text/javascript">
			AudioPlayer.embed("ap_demoplayer", {demomode:"yes"});
			</script>');
		$ap_fieldselector->input->setAttribute('id','ap_fieldselector');
		$ap_fieldselector->input->setAttribute('style','height:23px;');
		$form->addInput($ap_fieldselector);

		$ap_behaviour = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_behaviour',
	 	array('1'=>_t('将文章内地址为mp3的链接替换成播放器')),NULL,_t('替换mp3链接'));
		$form->addInput($ap_behaviour);

		$ap_encode = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_encode',
	 	array('1'=>_t('对非法下载或盗链行为起到一定防范作用')),NULL,_t('加密mp3地址'));
		$form->addInput($ap_encode);

		$ap_animation = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_animation',
	 	array('1'=>_t('省去点击动作让播放器直接处于展开状态')),NULL,_t('禁用动画效果'));
		$form->addInput($ap_animation);

		$ap_remaining = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_remaining',
	 	array('1'=>_t('显示音频的剩余时长而不是已经播放时长')),NULL,_t('显示剩余时长'));
		$form->addInput($ap_remaining);

		$ap_checkpolicy = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_checkpolicy',
	 	array('1'=>_t('检查mp3所在服务器是否允许读取<a rel="help" href="http://baike.baidu.com/view/66078.htm">ID3</a>标签')),'1',_t('探测跨域许可'));
		$form->addInput($ap_checkpolicy);

		$ap_noinfo = new Typecho_Widget_Helper_Form_Element_Checkbox('ap_noinfo',
	 	array('1'=>_t('即使有也不显示曲名/艺术家名等标签信息')),NULL,_t('禁用曲目信息'));
		$form->addInput($ap_noinfo);

		//配色参数隐藏域
		$filtformat = array(new AudioPlayer_Plugin,'colorformat');
		$ap_bgcolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_bgcolor',NULL,'#E5E5E5',NULL);
		$ap_bgcolor->input->setAttribute('id','ap_bgcolor');
		$form->addInput($ap_bgcolor->addRule($filtformat));
		$ap_leftbgcolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_leftbgcolor',NULL,'#CCCCCC',NULL);
		$ap_leftbgcolor->input->setAttribute('id','ap_leftbgcolor');
		$form->addInput($ap_leftbgcolor->addRule($filtformat));
		$ap_lefticoncolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_lefticoncolor',NULL,'#333333',NULL);
		$ap_lefticoncolor->input->setAttribute('id','ap_lefticoncolor');
		$form->addInput($ap_lefticoncolor->addRule($filtformat));
		$ap_voltrackcolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_voltrackcolor',NULL,'#FFFFFF',NULL);
		$ap_voltrackcolor->input->setAttribute('id','ap_voltrackcolor');
		$form->addInput($ap_voltrackcolor->addRule($filtformat));
		$ap_volslidercolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_volslidercolor',NULL,'#666666',NULL);
		$ap_volslidercolor->input->setAttribute('id','ap_volslidercolor');
		$form->addInput($ap_volslidercolor->addRule($filtformat));
		$ap_rightbgcolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_rightbgcolor',NULL,'#B4B4B4',NULL);
		$ap_rightbgcolor->input->setAttribute('id','ap_rightbgcolor');
		$form->addInput($ap_rightbgcolor->addRule($filtformat));
		$ap_rightbghovercolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_rightbghovercolor',NULL,'#999999',NULL);
		$ap_rightbghovercolor->input->setAttribute('id','ap_rightbghovercolor');
		$form->addInput($ap_rightbghovercolor->addRule($filtformat));
		$ap_righticoncolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_righticoncolor',NULL,'#333333',NULL);
		$ap_righticoncolor->input->setAttribute('id','ap_righticoncolor');
		$form->addInput($ap_righticoncolor->addRule($filtformat));
		$ap_textcolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_textcolor',NULL,'#333333',NULL);
		$ap_textcolor->input->setAttribute('id','ap_textcolor');
		$form->addInput($ap_textcolor->addRule($filtformat));
		$ap_trackercolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_trackercolor',NULL,'#DDDDDD',NULL);
		$ap_trackercolor->input->setAttribute('id','ap_trackercolor');
		$form->addInput($ap_trackercolor->addRule($filtformat));
		$ap_righticonhovercolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_righticonhovercolor',NULL,'#FFFFFF',NULL);
		$ap_righticonhovercolor->input->setAttribute('id','ap_righticonhovercolor');
		$form->addInput($ap_righticonhovercolor->addRule($filtformat));
		$ap_trackcolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_trackcolor',NULL,'#FFFFFF',NULL);
		$ap_trackcolor->input->setAttribute('id','ap_trackcolor');
		$form->addInput($ap_trackcolor->addRule($filtformat));
		$ap_bordercolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_bordercolor',NULL,'#CCCCCC',NULL);
		$ap_bordercolor->input->setAttribute('id','ap_bordercolor');
		$form->addInput($ap_bordercolor->addRule($filtformat));
		$ap_loadercolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_loadercolor',NULL,'#009900',NULL);
		$ap_loadercolor->input->setAttribute('id','ap_loadercolor');
		$form->addInput($ap_loadercolor->addRule($filtformat));
		$ap_skipcolor = new Typecho_Widget_Helper_Form_Element_Hidden('ap_skipcolor',NULL,'#666666',NULL);
		$ap_skipcolor->input->setAttribute('id','ap_skipcolor');
		$form->addInput($ap_skipcolor->addRule($filtformat));
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
	 * 标签链接替换
	 * 
	 * @param string $content
	 * @return string
	 */
	public static function playerparse($content,$widget,$lastResult)
	{
		$content = empty($lastResult)?$content:$lastResult;
		$settings = Helper::options()->plugin('AudioPlayer');

		if ($widget instanceof Widget_Archive) {
			//替换mp3链接
			if ($settings->ap_behaviour) {
				$pattern = "/<a ([^=]+=['\"][^\"']*['\"] )*href=['\"](([^\"']+\.mp3))['\"]( [^=]+=['\"][^\"']*['\"])*>([^<]+)<\/a>/is";
				$content = preg_replace_callback($pattern,array('AudioPlayer_Plugin',"parseCallback"),$content);
			}
			$content = preg_replace_callback("/\[(mp3)](([^]]+))\[\/\\1]/si",array('AudioPlayer_Plugin',"parseCallback"),$content);
		}

		return $content;
	}

	/**
	 * 参数回调解析
	 * 
	 * @param array $matches
	 * @return string
	 */
	public static function parseCallback($matches)
	{
		$atts = explode("|",$matches[3]);
		$data[0] = $atts[0];

		for ($i=1;$i<count($atts);$i++) {
			$pair = explode("=",$atts[$i]);
			$data[trim($pair[0])] = trim($pair[1]);
		}

		//分离参数
		$url = array_shift($data);

		return self::getPlayer($url,$data);
	}

	/**
	 * 输出播放器实例
	 * 
	 * @param string $source
	 * @param array $playerOptions
	 * @return string
	 */
	public static function getPlayer($source,$playerOptions = array())
	{
		$settings = Helper::options()->plugin('AudioPlayer');
		$archive = Typecho_Widget::widget('Widget_Archive');

		//文件地址
		if (function_exists("html_entity_decode")) {
			$source = html_entity_decode($source);
		}

		//加密地址
		if ($settings->ap_encode) {
			$playerOptions["soundFile"] = self::encodeSource($source);
		} else {
			$playerOptions["soundFile"] = $source;
		}

		//生成实例
		$playerElementID = "audioplayer_".++self::$playerID;
		$playerCode = '<p class="audioplayer_container"><span style="padding:5px;border:1px solid #dddddd;background:#f8f8f8" id="'.$playerElementID.'">'._t('播放此段音频需要Adobe Flash Player, 请点击<a href="%s" title="下载Adobe Flash Player">下载最新版本</a>并确认浏览器已开启JavaScipt支持','http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash&amp;promoid=BIOW').'</span></p>';
		$playerCode .= '<script type="text/javascript">';
		$playerCode .= 'AudioPlayer.embed("'.$playerElementID.'",'.self::php2js($playerOptions).');';
		$playerCode .= '</script>';

		return $playerCode;
	}

	/**
	 * 输出js嵌载方法
	 * 
	 * @return void
	 */
	public static function playerjs()
	{
		$options = Helper::options();
		$playerurl = $options->pluginUrl.'/AudioPlayer/assets/';
		echo '<script type="text/javascript" src="'.$playerurl.'audio-player.js"></script>';
		echo "\n";
		echo '<script type="text/javascript">';
		echo 'AudioPlayer.setup("'.$playerurl.'player.swf", '.self::getsets().');';
		echo '</script>';
		echo "\n";
	}

	/**
	 * 输出配置参数
	 * 
	 * @return string
	 */
	public static function getsets()
	{
		//初始参数
		$options = array(
			"width"=>"290",
			"encode"=>false,
			"animation"=>true,
			"remaining"=>false,
			"checkpolicy"=>true,
			"noinfo"=>false,
			"initialvolume"=>"60",
			"buffer"=>"5",
			"rtl"=>false,
			"bg"=>"E5E5E5",
			"text"=>"333333",
			"leftbg"=>"CCCCCC",
			"lefticon"=>"333333",
			"volslider"=>"666666",
			"voltrack"=>"FFFFFF",
			"rightbg"=>"B4B4B4",
			"rightbghover"=>"999999",
			"righticon"=>"333333",
			"righticonhover"=>"FFFFFF",
			"track"=>"FFFFFF",
			"loader"=>"009900",
			"border"=>"CCCCCC",
			"tracker"=>"DDDDDD",
			"skip"=>"666666",
			"transparentpagebg"=>true
		);

		//设置参数
		if (isset(Helper::options()->plugins['activated']['AudioPlayer'])) {
			$settings = Helper::options()->plugin('AudioPlayer');
			$options["width"] = $settings->ap_width;
			$options["encode"] = ($settings->ap_encode)?true:false;
			$options["animation"] = ($settings->ap_animation)?false:true;
			$options["remaining"] = ($settings->ap_remaining)?true:false;
			$options["checkpolicy"] = ($settings->ap_checkpolicy)?true:false;
			$options["noinfo"] = ($settings->ap_noinfo)?true:false;
			$options["bg"] = substr($settings->ap_bgcolor,1);
			$options["text"] = substr($settings->ap_textcolor,1);
			$options["leftbg"] = substr($settings->ap_leftbgcolor,1);
			$options["lefticon"] = substr($settings->ap_lefticoncolor,1);
			$options["volslider"] = substr($settings->ap_volslidercolor,1);
			$options["voltrack"] = substr($settings->ap_voltrackcolor,1);
			$options["rightbg"] = substr($settings->ap_rightbgcolor,1);
			$options["rightbghover"] = substr($settings->ap_righticonhovercolor,1);
			$options["righticon"] = substr($settings->ap_righticoncolor,1);
			$options["righticonhover"] = substr($settings->ap_rightbghovercolor,1);
			$options["track"] = substr($settings->ap_trackcolor,1);
			$options["loader"] = substr($settings->ap_loadercolor,1);
			$options["border"] = substr($settings->ap_bordercolor,1);
			$options["tracker"] = substr($settings->ap_trackercolor,1);
			$options["skip"] = substr($settings->ap_skipcolor,1);
		}

		return self::php2js($options);
	}

	/**
	 * 配置参数转js
	 * 
	 * @param array $object
	 * @return string
	 */
	private static function php2js($object)
	{
		$js_options = '{';
		$separator = "";
		$real_separator = ",";

		foreach($object as $key=>$value) {
			//布尔型格式
			if (is_bool($value)) $value = $value?"yes":"no";
			else if (in_array($key,array("soundFile","titles","artists"))) {
				if (in_array($key,array("titles","artists"))) {
					//标题艺术家信息
					if (function_exists("html_entity_decode")) {
						$value = html_entity_decode($value);
					}
				}
				$value = rawurlencode($value);
			}
			$js_options .= $separator.$key.':"'.$value.'"';
			$separator = $real_separator;
		}

		$js_options .= "}";

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
		$source = utf8_decode($string);
		$ntexto = "";
		$codekey = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-";

		for ($i=0;$i<strlen($string);$i++) {
			$ntexto .= substr("0000".base_convert(ord($string{$i}),10,2),-8);
		}

		$ntexto .= substr("00000",0,6-strlen($ntexto)%6);
		$string = "";

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
	private static function getThemeColors() {
		$theme = Helper::options()->theme;
		$cssfile = __TYPECHO_ROOT_DIR__.__TYPECHO_THEME_DIR__.'/'.$theme.'/style.css';

		if (file_exists($cssfile)) {
			preg_match_all('/:[^:,;\{\}].*?#([abcdef1234567890]{3,6})/i',file_get_contents($cssfile),$matches);
			return array_unique($matches[1]);
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
		return preg_match("/^[0-9]+%?$/",$width);
	}

	/**
	 * 判断颜色格式
	 * 
	 * @access public
	 * @param string $color
	 * @return boolean
	 */
	public static function colorformat($color)
	{
		return preg_match("/^#[0-9A-Fa-f]{6}$/",$color);
	}

}
