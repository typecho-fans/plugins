<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为博客添加HTML5影音播放器JW Player
 * 
 * @package JWPlayer
 * @author 羽中
 * @version 1.0.9
 * @dependence 14.5.26-*
 * @link http://www.yzmb.me/archives/net/jwplayer-for-typecho
 */
class JWPlayer_Plugin implements Typecho_Plugin_Interface
{
	/**
	* 初始播放器ID
	* 
	* @access private
	* @var integer
	*/
	private static $id = 0;

	/**
	* 默认配色数组
	* 
	* @access private
	* @var array
	*/
	private static $skincs = array(
		'controlbar.text'=>'#FFFFFF',
		'controlbar.icons'=>'rgba(255,255,255,0.8)',
		'controlbar.iconsActive'=>'#FFFFFF',
		'controlbar.background'=>'rgba(0,0,0,0)',
		'timeslider.progress'=>'#F2F2F2',
		'timeslider.rail'=>'rgba(255,255,255,0.3)',
		'menus.text'=>'rgba(255,255,255,0.8)',
		'menus.textActive'=>'#FFFFFF',
		'menus.background'=>'#333333',
		'tooltips.text'=>'#000000',
		'tooltips.background'=>'#FFFFFF'
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('JWPlayer_Plugin','jwparse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('JWPlayer_Plugin','txtparse');

		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('JWPlayer_Plugin','jwbutton');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('JWPlayer_Plugin','jwbutton');

		/* 模版调用钩子 例: <?php $this->jwplayer(array('file'=>'http://test.mp4')); ?> 参数为键值对 */
		Typecho_Plugin::factory('Widget_Archive')->callJwplayer = array('JWPlayer_Plugin', 'output');
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
		$plugurl = $options->pluginUrl;

		//格式化默认配色
		$cset = array();
		$cs = Json::encode(self::$skincs);
		if (isset($options->plugins['activated']['JWPlayer'])) {
			$settings = $options->plugin('JWPlayer');
			$cset = Json::decode($settings->jwcolors,true);
		}

		//输出面板效果
		echo '
<div class="description">
'._t('编辑文章或页面写入%s文件地址%s即可显示影音播放器, ','<strong>&lt;jw&gt;<span>','</span>&lt;/jw&gt;</strong>').'
'._t('多个文件连播可用%s号隔开,','<strong>,</strong>').'<br/>
'._t('参数间用%s号隔开, 支持%s等<a class="tog" href="###" title="点击显隐">[参数列表]</a>. 示例','<strong>|</strong>','<span>width</span>(宽度)<span>height</span>(高度)<span>image</span>(封面)<span>title</span>(标题)').'</div>
<div class="sample">
<span>&lt;jw&gt;</span>http://a.mp4<span>,</span>http://b.flv<span>|</span><br/>
<span>image=</span>http://aa.jpg<span>,</span>http://bb.png<span>|</span><br/>
<span>title=</span>aaa<span>,</span>bbb<span>|</span><br/>
<span>autostart=</span>false<span>|</span><br/>
<span>repeat</span>=true<span>&lt;/jw&gt;</span>
</div>
<link href="'.$plugurl.'/JWPlayer/cpicker/colorpicker.css" rel="stylesheet"/>
<link href="'.$plugurl.'/JWPlayer/admin/admin.css" rel="stylesheet"/>
<style type="text/css">
#demo .jw-controlbar .jw-icon-inline.jw-text, #demo .jw-title-primary, #demo .jw-title-secondary{color:'.$cset['controlbar.text'].';}
#demo .jw-button-color{color:'.$cset['controlbar.icons'].';}
#demo .jw-button-color:hover{color:'.$cset['controlbar.iconsActive'].';}
#demo .jw-controlbar{background-color:'.$cset['controlbar.background'].';}
#demo .jw-progress, #demo .jw-knob{background-color:'.$cset['timeslider.progress'].';}
#demo .jw-rail{background-color:'.$cset['timeslider.rail'].';}
#demo .jw-nextup-tooltip, #demo .jw-nextup-close{color:'.$cset['menus.text'].';}
#demo .jw-nextup-tooltip:hover, #demo .jw-nextup-close:hover{color:'.$cset['menus.textActive'].';}
#demo .jw-nextup{background-color:'.$cset['menus.background'].';}
#demo .jw-tooltip .jw-text{color:'.$cset['tooltips.text'].';}
#demo .jw-tooltip{color:'.$cset['tooltips.background'].';}
#demo .jw-tooltip .jw-text{background-color:'.$cset['tooltips.background'].';}
</style>
<div id="table">
<table>
<colgroup>
<col width="20%"/>
<col width="10%"/>
<col width="70%"/>
</colgroup>
<thead>
<tr>
<th>'._t('参数').'</th>
<th>'._t('默认').'</th>
<th>'._t('说明').'</th>
</tr>
</thead>
<tbody>
<tr>
<td class="param">image</td>
<td class="value">-</td>
<td>'._t('封面图片url，播放音频时全程显示。').'</td>
</tr>
<tr>
<td class="param">title</td>
<td class="value">-</td>
<td>'._t('标题文字，封面窗口和列表项显示。').'</td>
</tr>
<tr>
<td class="param">description</td>
<td class="value">-</td>
<td>'._t('描述文字，封面窗口显示。').'</td>
</tr>
<tr>
<td class="param">tracks</td>
<td class="value">-</td>
<td>'._t('字幕文件url，支持WebVTT/SRT/DFXP格式。').'</td>
</tr>
<tr>
<td class="param">width</td>
<td class="value">640</td>
<td>'._t('宽度像素值(整数)或百分数。').'</td>
</tr>
<tr>
<td class="param">height</td>
<td class="value">360</td>
<td>'._t('高度像素值(整数)，40为音频模式高度。').'</td>
</tr>
<tr>
<td class="param">aspectratio</td>
<td class="value">-</td>
<td>'._t('宽高比如%s，与百分数width锁定画面比例。','<span class="value">16:9</span>').'</td>
</tr>
<tr>
<td class="param">autostart</td>
<td class="value">false</td>
<td>'._t('自动播放，为%s时开启。','<span class="value">true</span>').'</td>
</tr>
<tr>
<td class="param">repeat</td>
<td class="value">false</td>
<td>'._t('循环播放，为%s时开启。','<span class="value">true</span>').'</td>
</tr>
<tr>
<td class="param">mute</td>
<td class="value">false</td>
<td>'._t('静音，为%s时开启。','<span class="value">true</span>').'</td>
</tr>
<tr>
<td class="param">preload</td>
<td class="value">metadata</td>
<td>'._t('预加载，%s最少/%s禁用/%s自动。','<span class="value">metadata</span>','<span class="value">none</span>','<span class="value">auto</span>').'</td>
</tr>
<tr>
<td class="param">abouttext</td>
<td class="value">-</td>
<td>'._t('右击菜单文字，可覆盖插件提示原文链接设置。').'</td>
</tr>
<tr>
<td class="param">aboutlink</td>
<td class="value">-</td>
<td>'._t('右击菜单链接，同上。').'</td>
</tr>
<tr>
<td class="param">stretching</td>
<td class="value">uniform</td>
<td>'._t('画面适应方法，可覆盖插件统一设置，对应值%s固定/%s缩放/%s裁切/%s拉伸。','<span class="value">none</span>','<span class="value">uniform</span>','<span class="value">fill</span>','<span class="value">exactfit</span>').'</td>
</tr>
<tr>
<td class="param">minDvrWindow</td>
<td class="value">120</td>
<td>'._t('(直播流)回放模式判定，默认检测到120个缓存单元开启，设置为0可强制显示录像控制按钮。','<span class="value">monoscopic</span>','<strong>,</strong>').'</td>
</tr>
<tr>
<td class="param">stereomode</td>
<td class="value">-</td>
<td>'._t('VR全景模式解析，使用值%s即为360度自由旋转，多个视频可重复该值用%s号分隔。','<span class="value">monoscopic</span>','<strong>,</strong>').'</td>
</tr>
<tr>
<td class="param">cast</td>
<td class="value">-</td>
<td>'._t('投屏模式，没有值，留空即可自动检测%s或%s设备请求并显示相关按钮。','<a href="https://www.google.com/cast/" target="_blank">Google Cast</a>','<a href="https://support.apple.com/zh-cn/HT204289" target="_blank">Apple Airplay</a>').'</td>
</tr>
</tbody>
</table>
</div>
<script src="'.$options->adminUrl.'js/jquery.js"></script>
<script src="'.$plugurl.'/JWPlayer/cpicker/colorpicker.js"></script>
<script src="'.$plugurl.'/JWPlayer/admin/admin.js"></script>
<script>
$(function(){
	var tab = $("table");
	$(".tog").click(function(){
		tab.css("opacity","0.9").toggle();
		return false;
	});
	$(document).bind("click", function(e){
		if($(e.target).closest(tab).length>0){
			tab.show();
		}else{
			tab.hide();
		}
	});
});
</script>
';
		$skin = new Typecho_Widget_Helper_Form_Element_Select('skin',
		array(
			'controlbar.text'=>_t('控制栏文本'),
			'controlbar.icons'=>_t('图标'),
			'controlbar.iconsActive'=>_t('图标(悬停)'),
			'controlbar.background'=>_t('控制栏背景'),
			'timeslider.progress'=>_t('进度条'),
			'timeslider.rail'=>_t('进度条(剩余)'),
			'menus.text'=>_t('菜单文本'),
			'menus.textActive'=>_t('菜单文本(悬停)'),
			'menus.background'=>_t('菜单背景'),
			'tooltips.text'=>_t('提示文字'),
			'tooltips.background'=>_t('提示文字背景')
		),
		'controlbar.text','
<div id="demo" class="jwplayer jw-reset jw-state-idle jw-state-paused jw-stretch-uniform jw-flag-aspect-mode jw-breakpoint-2 jw-no-focus jw-flag-nextup jw-flag-user-inactive" tabindex="0" aria-label="Video Player" style="max-width: 512px;"><div class="jw-aspect jw-reset" style="padding-top: 56.25%;"></div><div class="jw-media jw-reset"><video class="jw-video jw-reset" disableremoteplayback="" webkit-playsinline="" playsinline="" src="" style="object-fit: fill;"></video></div><div class="jw-preview jw-reset" style="background-image: url(&quot;'.$plugurl.'/JWPlayer/admin/demo-cover.jpg&quot;); background-size: cover;"></div><div class="jw-controls-backdrop jw-reset"></div><div class="jw-captions jw-reset jw-captions-enabled" style="font-size: 14px;"><div class="jw-captions-window jw-reset"><span class="jw-captions-text jw-reset"></span></div></div><div class="jw-title jw-reset"><div class="jw-title-primary jw-reset">'._t('视频标题文本(控制栏)').'</div><div class="jw-title-secondary jw-reset">'._t('视频描述文本(控制栏)').'</div></div><div class="jw-overlays jw-reset"><div id="demo_related" class="jw-plugin jw-reset jw-plugin-related"></div></div><div class="afs_ads" style="width: 1px; height: 1px; position: absolute; background: transparent none repeat scroll 0% 0%;">&nbsp;</div><div class="jw-controls jw-reset"><div class="jw-display jw-reset"><div class="jw-display-container jw-reset"><div class="jw-display-controls jw-reset"><div class="jw-display-icon-container jw-display-icon-rewind jw-reset"><div class="jw-icon jw-icon-rewind jw-button-color jw-reset" role="button" tabindex="0" aria-label="后退10秒"><svg class="jw-svg-icon jw-svg-icon-rewind" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M113.2,131.078a21.589,21.589,0,0,0-17.7-10.6,21.589,21.589,0,0,0-17.7,10.6,44.769,44.769,0,0,0,0,46.3,21.589,21.589,0,0,0,17.7,10.6,21.589,21.589,0,0,0,17.7-10.6,44.769,44.769,0,0,0,0-46.3Zm-17.7,47.2c-7.8,0-14.4-11-14.4-24.1s6.6-24.1,14.4-24.1,14.4,11,14.4,24.1S103.4,178.278,95.5,178.278Zm-43.4,9.7v-51l-4.8,4.8-6.8-6.8,13-13a4.8,4.8,0,0,1,8.2,3.4v62.7l-9.6-.1Zm162-130.2v125.3a4.867,4.867,0,0,1-4.8,4.8H146.6v-19.3h48.2v-96.4H79.1v19.3c0,5.3-3.6,7.2-8,4.3l-41.8-27.9a6.013,6.013,0,0,1-2.7-8,5.887,5.887,0,0,1,2.7-2.7l41.8-27.9c4.4-2.9,8-1,8,4.3v19.3H209.2A4.974,4.974,0,0,1,214.1,57.778Z"></path></svg></div></div><div class="jw-display-icon-container jw-display-icon-display jw-reset" style="cursor: pointer;"><div class="jw-icon jw-icon-display jw-button-color jw-reset" role="button" tabindex="0" aria-label="Start Playback"><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-buffer" viewBox="0 0 240 240" focusable="false"><path d="M120,186.667a66.667,66.667,0,0,1,0-133.333V40a80,80,0,1,0,80,80H186.667A66.846,66.846,0,0,1,120,186.667Z"></path></svg><svg class="jw-svg-icon jw-svg-icon-replay" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M120,41.9v-20c0-5-4-8-8-4l-44,28a5.865,5.865,0,0,0-3.3,7.6A5.943,5.943,0,0,0,68,56.8l43,29c5,4,9,1,9-4v-20a60,60,0,1,1-60,60H40a80,80,0,1,0,80-79.9Z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-play" viewBox="0 0 240 240" focusable="false"><path d="M62.8,199.5c-1,0.8-2.4,0.6-3.3-0.4c-0.4-0.5-0.6-1.1-0.5-1.8V42.6c-0.2-1.3,0.7-2.4,1.9-2.6c0.7-0.1,1.3,0.1,1.9,0.4l154.7,77.7c2.1,1.1,2.1,2.8,0,3.8L62.8,199.5z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-pause" viewBox="0 0 240 240" focusable="false"><path d="M100,194.9c0.2,2.6-1.8,4.8-4.4,5c-0.2,0-0.4,0-0.6,0H65c-2.6,0.2-4.8-1.8-5-4.4c0-0.2,0-0.4,0-0.6V45c-0.2-2.6,1.8-4.8,4.4-5c0.2,0,0.4,0,0.6,0h30c2.6-0.2,4.8,1.8,5,4.4c0,0.2,0,0.4,0,0.6V194.9z M180,45.1c0.2-2.6-1.8-4.8-4.4-5c-0.2,0-0.4,0-0.6,0h-30c-2.6-0.2-4.8,1.8-5,4.4c0,0.2,0,0.4,0,0.6V195c-0.2,2.6,1.8,4.8,4.4,5c0.2,0,0.4,0,0.6,0h30c2.6,0.2,4.8-1.8,5-4.4c0-0.2,0-0.4,0-0.6V45.1z"></path></svg></div></div><div class="jw-display-icon-container jw-display-icon-next jw-reset" style=""><div class="jw-icon jw-icon-next jw-button-color jw-reset" role="button" tabindex="0" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-next" viewBox="0 0 240 240"><path d="M165,60v53.3L59.2,42.8C56.9,41.3,55,42.3,55,45v150c0,2.7,1.9,3.8,4.2,2.2L165,126.6v53.3h20v-120L165,60L165,60z"></path></svg></div></div></div></div></div><div class="jw-nextup-container jw-reset jw-nextup-sticky jw-nextup-container-visible"><div class="jw-nextup jw-background-color jw-reset jw-nextup-thumbnail-visible"><div class="jw-nextup-tooltip jw-reset"><div class="jw-nextup-thumbnail jw-reset" style="background-image: url(&quot;'.$plugurl.'/JWPlayer/admin/next-cover.jpg&quot;);"></div><div class="jw-nextup-body jw-reset"><div class="jw-nextup-header jw-reset">下一个</div><div class="jw-nextup-title jw-reset">'._t('视频标题文本(菜单)').'</div><div class="jw-nextup-duration jw-reset">00:25</div></div></div><button type="button" class="jw-icon jw-nextup-close jw-reset" aria-label="Next Up Close"><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-close" viewBox="0 0 240 240" focusable="false"><path d="M134.8,120l48.6-48.6c2-1.9,2.1-5.2,0.2-7.2c0,0-0.1-0.1-0.2-0.2l-7.4-7.4c-1.9-2-5.2-2.1-7.2-0.2c0,0-0.1,0.1-0.2,0.2L120,105.2L71.4,56.6c-1.9-2-5.2-2.1-7.2-0.2c0,0-0.1,0.1-0.2,0.2L56.6,64c-2,1.9-2.1,5.2-0.2,7.2c0,0,0.1,0.1,0.2,0.2l48.6,48.7l-48.6,48.6c-2,1.9-2.1,5.2-0.2,7.2c0,0,0.1,0.1,0.2,0.2l7.4,7.4c1.9,2,5.2,2.1,7.2,0.2c0,0,0.1-0.1,0.2-0.2l48.7-48.6l48.6,48.6c1.9,2,5.2,2.1,7.2,0.2c0,0,0.1-0.1,0.2-0.2l7.4-7.4c2-1.9,2.1-5.2,0.2-7.2c0,0-0.1-0.1-0.2-0.2L134.8,120z"></path></svg></button></div></div><div class="jw-reset jw-settings-menu" role="menu" aria-expanded="false"><div class="jw-reset jw-settings-topbar" role="menubar"><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-settings-quality jw-submenu-quality" role="menuitemradio" tabindex="0" aria-label="quality" style="" aria-checked="false" name="quality"><svg class="jw-svg-icon jw-svg-icon-quality-100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M55,200H35c-3,0-5-2-5-4c0,0,0,0,0-1v-30c0-3,2-5,4-5c0,0,0,0,1,0h20c3,0,5,2,5,4c0,0,0,0,0,1v30C60,198,58,200,55,200L55,200z M110,195v-70c0-3-2-5-4-5c0,0,0,0-1,0H85c-3,0-5,2-5,4c0,0,0,0,0,1v70c0,3,2,5,4,5c0,0,0,0,1,0h20C108,200,110,198,110,195L110,195z M160,195V85c0-3-2-5-4-5c0,0,0,0-1,0h-20c-3,0-5,2-5,4c0,0,0,0,0,1v110c0,3,2,5,4,5c0,0,0,0,1,0h20C158,200,160,198,160,195L160,195z M210,195V45c0-3-2-5-4-5c0,0,0,0-1,0h-20c-3,0-5,2-5,4c0,0,0,0,0,1v150c0,3,2,5,4,5c0,0,0,0,1,0h20C208,200,210,198,210,195L210,195z"></path></svg><div class="jw-reset jw-tooltip jw-tooltip-quality"><div class="jw-text">画质</div></div></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-settings-close" role="button" tabindex="0" aria-label="Close Settings" style=""><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-close" viewBox="0 0 240 240" focusable="false"><path d="M134.8,120l48.6-48.6c2-1.9,2.1-5.2,0.2-7.2c0,0-0.1-0.1-0.2-0.2l-7.4-7.4c-1.9-2-5.2-2.1-7.2-0.2c0,0-0.1,0.1-0.2,0.2L120,105.2L71.4,56.6c-1.9-2-5.2-2.1-7.2-0.2c0,0-0.1,0.1-0.2,0.2L56.6,64c-2,1.9-2.1,5.2-0.2,7.2c0,0,0.1,0.1,0.2,0.2l48.6,48.7l-48.6,48.6c-2,1.9-2.1,5.2-0.2,7.2c0,0,0.1,0.1,0.2,0.2l7.4,7.4c1.9,2,5.2,2.1,7.2,0.2c0,0,0.1-0.1,0.2-0.2l48.7-48.6l48.6,48.6c1.9,2,5.2,2.1,7.2,0.2c0,0,0.1-0.1,0.2-0.2l7.4-7.4c2-1.9,2.1-5.2,0.2-7.2c0,0-0.1-0.1-0.2-0.2L134.8,120z"></path></svg></div></div><div class="jw-reset jw-settings-submenu" role="menu" aria-expanded="false"><button type="button" class="jw-reset jw-settings-content-item jw-settings-item-active" role="menuitemradio" aria-checked="true">自动</button><button type="button" class="jw-reset jw-settings-content-item" role="menuitemradio" aria-checked="false">1080p</button><button type="button" class="jw-reset jw-settings-content-item" role="menuitemradio" aria-checked="false">720p</button><button type="button" class="jw-reset jw-settings-content-item" role="menuitemradio" aria-checked="false">406p</button><button type="button" class="jw-reset jw-settings-content-item" role="menuitemradio" aria-checked="false">270p</button><button type="button" class="jw-reset jw-settings-content-item" role="menuitemradio" aria-checked="false">180p</button></div></div><div class="jw-controlbar jw-reset"><div class="jw-slider-time jw-background-color jw-reset jw-slider-horizontal jw-reset" aria-hidden="true"><div class="jw-slider-container jw-reset"><div class="jw-rail jw-reset"></div><div class="jw-buffer jw-reset" style="width: 30%;"></div><div class="jw-progress jw-reset" style="width: 30%;"></div><div class="jw-knob jw-reset" style="left: 30%;"></div><div class="jw-icon jw-icon-tooltip jw-tooltip-time jw-button-color jw-reset" style="left: 12.3%;"><div class="jw-overlay jw-reset"><div class="jw-time-tip jw-reset"><div class="jw-time-thumb jw-reset" style="width: 120px; height: 67px; margin: 0px auto; background-position: -120px 0px;"></div><span class="jw-text jw-reset">00:03</span></div></div></div></div></div><div class="jw-reset jw-button-container"><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-icon-playback" role="button" tabindex="0" aria-label="Play" style=""><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-play" viewBox="0 0 240 240" focusable="false"><path d="M62.8,199.5c-1,0.8-2.4,0.6-3.3-0.4c-0.4-0.5-0.6-1.1-0.5-1.8V42.6c-0.2-1.3,0.7-2.4,1.9-2.6c0.7-0.1,1.3,0.1,1.9,0.4l154.7,77.7c2.1,1.1,2.1,2.8,0,3.8L62.8,199.5z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-pause" viewBox="0 0 240 240" focusable="false"><path d="M100,194.9c0.2,2.6-1.8,4.8-4.4,5c-0.2,0-0.4,0-0.6,0H65c-2.6,0.2-4.8-1.8-5-4.4c0-0.2,0-0.4,0-0.6V45c-0.2-2.6,1.8-4.8,4.4-5c0.2,0,0.4,0,0.6,0h30c2.6-0.2,4.8,1.8,5,4.4c0,0.2,0,0.4,0,0.6V194.9z M180,45.1c0.2-2.6-1.8-4.8-4.4-5c-0.2,0-0.4,0-0.6,0h-30c-2.6-0.2-4.8,1.8-5,4.4c0,0.2,0,0.4,0,0.6V195c-0.2,2.6,1.8,4.8,4.4,5c0.2,0,0.4,0,0.6,0h30c2.6,0.2,4.8-1.8,5-4.4c0-0.2,0-0.4,0-0.6V45.1z"></path></svg><svg class="jw-svg-icon jw-svg-icon-stop" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M190,185c0.2,2.6-1.8,4.8-4.4,5c-0.2,0-0.4,0-0.6,0H55c-2.6,0.2-4.8-1.8-5-4.4c0-0.2,0-0.4,0-0.6V55c-0.2-2.6,1.8-4.8,4.4-5c0.2,0,0.4,0,0.6,0h130c2.6-0.2,4.8,1.8,5,4.4c0,0.2,0,0.4,0,0.6V185z"></path></svg></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-icon-rewind" role="button" tabindex="0" aria-label="后退10秒" style=""><svg class="jw-svg-icon jw-svg-icon-rewind" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M113.2,131.078a21.589,21.589,0,0,0-17.7-10.6,21.589,21.589,0,0,0-17.7,10.6,44.769,44.769,0,0,0,0,46.3,21.589,21.589,0,0,0,17.7,10.6,21.589,21.589,0,0,0,17.7-10.6,44.769,44.769,0,0,0,0-46.3Zm-17.7,47.2c-7.8,0-14.4-11-14.4-24.1s6.6-24.1,14.4-24.1,14.4,11,14.4,24.1S103.4,178.278,95.5,178.278Zm-43.4,9.7v-51l-4.8,4.8-6.8-6.8,13-13a4.8,4.8,0,0,1,8.2,3.4v62.7l-9.6-.1Zm162-130.2v125.3a4.867,4.867,0,0,1-4.8,4.8H146.6v-19.3h48.2v-96.4H79.1v19.3c0,5.3-3.6,7.2-8,4.3l-41.8-27.9a6.013,6.013,0,0,1-2.7-8,5.887,5.887,0,0,1,2.7-2.7l41.8-27.9c4.4-2.9,8-1,8,4.3v19.3H209.2A4.974,4.974,0,0,1,214.1,57.778Z"></path></svg><div class="jw-reset jw-tooltip jw-tooltip-rewind" aria-expanded="false"><div class="jw-text">后退10秒</div></div></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-icon-next" role="button" tabindex="0" aria-label="Next" style=""><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-next" viewBox="0 0 240 240"><path d="M165,60v53.3L59.2,42.8C56.9,41.3,55,42.3,55,45v150c0,2.7,1.9,3.8,4.2,2.2L165,126.6v53.3h20v-120L165,60L165,60z"></path></svg><div class="jw-reset jw-tooltip jw-tooltip-next"><div class="jw-text">下一个: Cycle Tour: Tuscany to Umbria</div></div></div><div aria-label="Volume" role="button" tabindex="0" class="jw-icon jw-icon-tooltip jw-icon-volume jw-button-color jw-reset"><div class="jw-overlay jw-reset"><div class="jw-slider-volume jw-volume-tip jw-reset jw-slider-vertical" aria-hidden="true"><div class="jw-slider-container jw-reset"><div class="jw-rail jw-reset"></div><div class="jw-buffer jw-reset"></div><div class="jw-progress jw-reset" style="height: 10%;"></div><div class="jw-knob jw-reset" style="bottom: 10%;"></div></div></div></div><svg class="jw-svg-icon jw-svg-icon-volume-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M116.4,42.8v154.5c0,2.8-1.7,3.6-3.8,1.7l-54.1-48.1H28.9c-2.8,0-5.2-2.3-5.2-5.2V94.2c0-2.8,2.3-5.2,5.2-5.2h29.6l54.1-48.1C114.6,39.1,116.4,39.9,116.4,42.8z M212.3,96.4l-14.6-14.6l-23.6,23.6l-23.6-23.6l-14.6,14.6l23.6,23.6l-23.6,23.6l14.6,14.6l23.6-23.6l23.6,23.6l14.6-14.6L188.7,120L212.3,96.4z"></path></svg><svg class="jw-svg-icon jw-svg-icon-volume-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M116.4,42.8v154.5c0,2.8-1.7,3.6-3.8,1.7l-54.1-48.1H28.9c-2.8,0-5.2-2.3-5.2-5.2V94.2c0-2.8,2.3-5.2,5.2-5.2h29.6l54.1-48.1C114.7,39.1,116.4,39.9,116.4,42.8z M178.2,120c0-22.7-18.5-41.2-41.2-41.2v20.6c11.4,0,20.6,9.2,20.6,20.6c0,11.4-9.2,20.6-20.6,20.6v20.6C159.8,161.2,178.2,142.7,178.2,120z"></path></svg><svg class="jw-svg-icon jw-svg-icon-volume-100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M116.5,42.8v154.4c0,2.8-1.7,3.6-3.8,1.7l-54.1-48H29c-2.8,0-5.2-2.3-5.2-5.2V94.3c0-2.8,2.3-5.2,5.2-5.2h29.6l54.1-48C114.8,39.2,116.5,39.9,116.5,42.8z"></path><path d="M136.2,160v-20c11.1,0,20-8.9,20-20s-8.9-20-20-20V80c22.1,0,40,17.9,40,40S158.3,160,136.2,160z"></path><path d="M216.2,120c0-44.2-35.8-80-80-80v20c33.1,0,60,26.9,60,60s-26.9,60-60,60v20C180.4,199.9,216.1,164.1,216.2,120z"></path></svg></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-icon-volume jw-full" role="button" tabindex="0" aria-label="Volume" style=""><svg class="jw-svg-icon jw-svg-icon-volume-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M116.4,42.8v154.5c0,2.8-1.7,3.6-3.8,1.7l-54.1-48.1H28.9c-2.8,0-5.2-2.3-5.2-5.2V94.2c0-2.8,2.3-5.2,5.2-5.2h29.6l54.1-48.1C114.6,39.1,116.4,39.9,116.4,42.8z M212.3,96.4l-14.6-14.6l-23.6,23.6l-23.6-23.6l-14.6,14.6l23.6,23.6l-23.6,23.6l14.6,14.6l23.6-23.6l23.6,23.6l14.6-14.6L188.7,120L212.3,96.4z"></path></svg><svg class="jw-svg-icon jw-svg-icon-volume-100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M116.5,42.8v154.4c0,2.8-1.7,3.6-3.8,1.7l-54.1-48H29c-2.8,0-5.2-2.3-5.2-5.2V94.3c0-2.8,2.3-5.2,5.2-5.2h29.6l54.1-48C114.8,39.2,116.5,39.9,116.5,42.8z"></path><path d="M136.2,160v-20c11.1,0,20-8.9,20-20s-8.9-20-20-20V80c22.1,0,40,17.9,40,40S158.3,160,136.2,160z"></path><path d="M216.2,120c0-44.2-35.8-80-80-80v20c33.1,0,60,26.9,60,60s-26.9,60-60,60v20C180.4,199.9,216.1,164.1,216.2,120z"></path></svg></div><span class="jw-text jw-reset jw-text-alt" role="status"></span><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-text-live" role="button" tabindex="0" aria-label="在线直播" style="display: none;">在线直播</div><div class="jw-icon jw-icon-inline jw-text jw-reset jw-text-elapsed" role="timer">00:15</div><div class="jw-icon jw-icon-inline jw-text jw-reset jw-text-countdown" role="timer">00:09</div><div class="jw-icon jw-icon-inline jw-text jw-reset jw-text-duration" role="timer">00:50</div><div class="jw-reset jw-spacer"></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-icon-cc jw-settings-submenu-button" role="button" tabindex="0" aria-label="字幕" style="display: none;" aria-haspopup="true"><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-cc-on" viewBox="0 0 240 240" focusable="false"><path d="M215,40H25c-2.7,0-5,2.2-5,5v150c0,2.7,2.2,5,5,5h190c2.7,0,5-2.2,5-5V45C220,42.2,217.8,40,215,40z M108.1,137.7c0.7-0.7,1.5-1.5,2.4-2.3l6.6,7.8c-2.2,2.4-5,4.4-8,5.8c-8,3.5-17.3,2.4-24.3-2.9c-3.9-3.6-5.9-8.7-5.5-14v-25.6c0-2.7,0.5-5.3,1.5-7.8c0.9-2.2,2.4-4.3,4.2-5.9c5.7-4.5,13.2-6.2,20.3-4.6c3.3,0.5,6.3,2,8.7,4.3c1.3,1.3,2.5,2.6,3.5,4.2l-7.1,6.9c-2.4-3.7-6.5-5.9-10.9-5.9c-2.4-0.2-4.8,0.7-6.6,2.3c-1.7,1.7-2.5,4.1-2.4,6.5v25.6C90.4,141.7,102,143.5,108.1,137.7z M152.9,137.7c0.7-0.7,1.5-1.5,2.4-2.3l6.6,7.8c-2.2,2.4-5,4.4-8,5.8c-8,3.5-17.3,2.4-24.3-2.9c-3.9-3.6-5.9-8.7-5.5-14v-25.6c0-2.7,0.5-5.3,1.5-7.8c0.9-2.2,2.4-4.3,4.2-5.9c5.7-4.5,13.2-6.2,20.3-4.6c3.3,0.5,6.3,2,8.7,4.3c1.3,1.3,2.5,2.6,3.5,4.2l-7.1,6.9c-2.4-3.7-6.5-5.9-10.9-5.9c-2.4-0.2-4.8,0.7-6.6,2.3c-1.7,1.7-2.5,4.1-2.4,6.5v25.6C135.2,141.7,146.8,143.5,152.9,137.7z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-cc-off" viewBox="0 0 240 240" focusable="false"><path d="M99.4,97.8c-2.4-0.2-4.8,0.7-6.6,2.3c-1.7,1.7-2.5,4.1-2.4,6.5v25.6c0,9.6,11.6,11.4,17.7,5.5c0.7-0.7,1.5-1.5,2.4-2.3l6.6,7.8c-2.2,2.4-5,4.4-8,5.8c-8,3.5-17.3,2.4-24.3-2.9c-3.9-3.6-5.9-8.7-5.5-14v-25.6c0-2.7,0.5-5.3,1.5-7.8c0.9-2.2,2.4-4.3,4.2-5.9c5.7-4.5,13.2-6.2,20.3-4.6c3.3,0.5,6.3,2,8.7,4.3c1.3,1.3,2.5,2.6,3.5,4.2l-7.1,6.9C107.9,100,103.8,97.8,99.4,97.8z M144.1,97.8c-2.4-0.2-4.8,0.7-6.6,2.3c-1.7,1.7-2.5,4.1-2.4,6.5v25.6c0,9.6,11.6,11.4,17.7,5.5c0.7-0.7,1.5-1.5,2.4-2.3l6.6,7.8c-2.2,2.4-5,4.4-8,5.8c-8,3.5-17.3,2.4-24.3-2.9c-3.9-3.6-5.9-8.7-5.5-14v-25.6c0-2.7,0.5-5.3,1.5-7.8c0.9-2.2,2.4-4.3,4.2-5.9c5.7-4.5,13.2-6.2,20.3-4.6c3.3,0.5,6.3,2,8.7,4.3c1.3,1.3,2.5,2.6,3.5,4.2l-7.1,6.9C152.6,100,148.5,97.8,144.1,97.8L144.1,97.8z M200,60v120H40V60H200 M215,40H25c-2.7,0-5,2.2-5,5v150c0,2.7,2.2,5,5,5h190c2.7,0,5-2.2,5-5V45C220,42.2,217.8,40,215,40z"></path></svg><div class="jw-reset jw-tooltip jw-tooltip-captions"><div class="jw-text">字幕</div></div></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-playlist-btn" button="related" role="button" tabindex="0" aria-label="列表"><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-playlist" viewBox="0 0 240 240"><path d="M205,80H35c-2.7,0-5,2.2-5,5v110c0,2.7,2.2,5,5,5h170c2.7,0,5-2.2,5-5V85C210,82.2,207.8,80,205,80z M145.7,142.6l-41.4,24.9c-2.4,1.4-4.3,0.3-4.3-2.4v-50c0-2.7,1.9-3.8,4.3-2.4l41.4,24.9c1.4,0.5,2.1,2.1,1.6,3.5C147,141.7,146.4,142.2,145.7,142.6z M190,70H50V60h140V70z M170,50H70V40h100V50z"></path></svg><div class="jw-reset jw-tooltip jw-tooltip-related"><div class="jw-text">列表</div></div></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-icon-settings jw-settings-submenu-button" role="button" tabindex="0" aria-label="选项" style="" aria-haspopup="true"><svg class="jw-svg-icon jw-svg-icon-settings" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240" focusable="false"><path d="M204,145l-25-14c0.8-3.6,1.2-7.3,1-11c0.2-3.7-0.2-7.4-1-11l25-14c2.2-1.6,3.1-4.5,2-7l-16-26c-1.2-2.1-3.8-2.9-6-2l-25,14c-6-4.2-12.3-7.9-19-11V35c0.2-2.6-1.8-4.8-4.4-5c-0.2,0-0.4,0-0.6,0h-30c-2.6-0.2-4.8,1.8-5,4.4c0,0.2,0,0.4,0,0.6v28c-6.7,3.1-13,6.7-19,11L56,60c-2.2-0.9-4.8-0.1-6,2L35,88c-1.6,2.2-1.3,5.3,0.9,6.9c0,0,0.1,0,0.1,0.1l25,14c-0.8,3.6-1.2,7.3-1,11c-0.2,3.7,0.2,7.4,1,11l-25,14c-2.2,1.6-3.1,4.5-2,7l16,26c1.2,2.1,3.8,2.9,6,2l25-14c5.7,4.6,12.2,8.3,19,11v28c-0.2,2.6,1.8,4.8,4.4,5c0.2,0,0.4,0,0.6,0h30c2.6,0.2,4.8-1.8,5-4.4c0-0.2,0-0.4,0-0.6v-28c7-2.3,13.5-6,19-11l25,14c2.5,1.3,5.6,0.4,7-2l15-26C206.7,149.4,206,146.7,204,145z M120,149.9c-16.5,0-30-13.4-30-30s13.4-30,30-30s30,13.4,30,30c0.3,16.3-12.6,29.7-28.9,30C120.7,149.9,120.4,149.9,120,149.9z"></path></svg><div class="jw-reset jw-tooltip jw-tooltip-settings"><div class="jw-text">选项</div></div></div><div class="jw-icon jw-icon-inline jw-button-color jw-reset jw-icon-fullscreen" role="button" tabindex="0" aria-label="全屏" style=""><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-fullscreen-off" viewBox="0 0 240 240" focusable="false"><path d="M109.2,134.9l-8.4,50.1c-0.4,2.7-2.4,3.3-4.4,1.4L82,172l-27.9,27.9l-14.2-14.2l27.9-27.9l-14.4-14.4c-1.9-1.9-1.3-3.9,1.4-4.4l50.1-8.4c1.8-0.5,3.6,0.6,4.1,2.4C109.4,133.7,109.4,134.3,109.2,134.9L109.2,134.9z M172.1,82.1L200,54.2L185.8,40l-27.9,27.9l-14.4-14.4c-1.9-1.9-3.9-1.3-4.4,1.4l-8.4,50.1c-0.5,1.8,0.6,3.6,2.4,4.1c0.5,0.2,1.2,0.2,1.7,0l50.1-8.4c2.7-0.4,3.3-2.4,1.4-4.4L172.1,82.1z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" class="jw-svg-icon jw-svg-icon-fullscreen-on" viewBox="0 0 240 240" focusable="false"><path d="M96.3,186.1c1.9,1.9,1.3,4-1.4,4.4l-50.6,8.4c-1.8,0.5-3.7-0.6-4.2-2.4c-0.2-0.6-0.2-1.2,0-1.7l8.4-50.6c0.4-2.7,2.4-3.4,4.4-1.4l14.5,14.5l28.2-28.2l14.3,14.3l-28.2,28.2L96.3,186.1z M195.8,39.1l-50.6,8.4c-2.7,0.4-3.4,2.4-1.4,4.4l14.5,14.5l-28.2,28.2l14.3,14.3l28.2-28.2l14.5,14.5c1.9,1.9,4,1.3,4.4-1.4l8.4-50.6c0.5-1.8-0.6-3.6-2.4-4.2C197,39,196.4,39,195.8,39.1L195.8,39.1z"></path></svg><div class="jw-reset jw-tooltip jw-tooltip-fullscreen"><div class="jw-text">全屏</div></div></div></div></div></div></div>
'._t('定制配色预览').' &#8673;
<div id="widget">
	<div id="picker">
		<input id="field" type="text" maxlength="21" size="21"/>
		<div id="swatch"><div></div></div>
	</div>
</div>',_t('RGBA末位小数0-1代表透明度可手动修改'));

		$skin->input->setAttribute('id','selector');
		$form->addInput($skin);
		if (Typecho_Request::getInstance()->is('action=resetskin') && isset($options->plugins['activated']['JWPlayer'])) {
			$security->protect();
			Helper::configPlugin('JWPlayer',array('jwcolors'=>$cs));
			Typecho_Response::getInstance()->goBack();
		}
		//重置动作按钮
		$resetskin = new Typecho_Widget_Helper_Form_Element_Submit();
		$resetskin->value(_t('重置'));
		$resetskin->input->setAttribute('class','btn btn-xs');
		$resetskin->input->setAttribute('formaction',$security->getAdminUrl('options-plugin.php?config=JWPlayer&action=resetskin'));
		$form->addItem($resetskin);

		$stretch = new Typecho_Widget_Helper_Form_Element_Select('stretch',
		array('none'=>_t('固定'),''=>_t('缩放'),'fill'=>_t('裁切'),'exactfit'=>_t('拉伸')),'',_t('画面适应方法'),_t('视频尺寸与播放器尺寸不同时的修正方式'));
		$form->addInput($stretch);

		$encode = new Typecho_Widget_Helper_Form_Element_Radio('encode',
		array(1=>_t('是'),0=>_t('否')),0,_t('加密视频地址'),_t('是否隐藏文件url使其在源码中显示为乱码'));
		$form->addInput($encode);

		$info = new Typecho_Widget_Helper_Form_Element_Radio('info',
		array(1=>_t('是'),0=>_t('否')),0,_t('隐藏标题描述'),_t('是否在窗口显示title与description参数信息'));
		$form->addInput($info);

		$about = new Typecho_Widget_Helper_Form_Element_Radio('about',
		array(1=>_t('是'),0=>_t('否')),0,_t('提示原文链接'),_t('右击窗口时是否显示原文链接, 参数可覆盖'));
		$form->addInput($about);

		$tedge = new Typecho_Widget_Helper_Form_Element_Select('tedge',
		array(''=>_t('默认'),'dropshadow'=>_t('下阴影'),'depressed'=>_t('上阴影'),'uniform'=>_t('深描边'),'raised'=>_t('浅描边')),'',_t('外挂字幕效果'));
		$form->addInput($tedge);

		$tsize = new Typecho_Widget_Helper_Form_Element_Text('tsize',NULL,'15',_t('字体大小(单位px, 不用填写): '));
		$tsize->input->setAttribute('class','text-s');
		$tsize->label->setAttribute('style','position:absolute;color:#999;font-weight:normal;bottom:10px;left:82px;');
		$tsize->input->setAttribute('style','position:absolute;width:38px;bottom:13px;left:258px;');
		$tsize->setAttribute('style','position:relative');
		$form->addInput($tsize->addRule('isFloat'));

		$logo = new Typecho_Widget_Helper_Form_Element_Text('logo',
		NULL,'',_t('logo水印图片'),_t('填写完整的图片url, 24位透明png效果最佳'));
		$logo->input->setAttribute('class','w-60');
		$form->addInput($logo->addRule('url',_t('请输入合法的图片地址')));

		$llink = new Typecho_Widget_Helper_Form_Element_Text('llink',
		NULL,'',_t('logo链接地址'),_t('填写点击水印图片将跳转到的目标链接url'));
		$llink->input->setAttribute('class','w-60');
		$form->addInput($llink->addRule('url',_t('请输入合法的链接地址')));

		$lpos = new Typecho_Widget_Helper_Form_Element_Select('lpos',
		array(''=>_t('右上角'),'top-left'=>_t('左上角'),'bottom-right'=>_t('右下角'),'bottom-left'=>_t('左下角'),'control-bar'=>_t('控制条')),'',_t('logo显示位置'));
		$form->addInput($lpos);

		$margin = new Typecho_Widget_Helper_Form_Element_Text('margin',NULL,'8',_t('边距(单位px, 不用填写): '));
		$margin->input->setAttribute('class','text-s');
		$margin->label->setAttribute('style','position:absolute;color:#999;font-weight:normal;bottom:10px;left:82px;');
		$margin->input->setAttribute('style','position:absolute;width:38px;bottom:13px;left:230px;');
		$margin->setAttribute('style','position:relative');
		$form->addInput($margin->addRule('isFloat'));

		$hide = new Typecho_Widget_Helper_Form_Element_Checkbox('hide',
		array(1=>_t('自动隐藏')),NULL,'');
		$hide->label->setAttribute('style','position:absolute;color:#999;font-weight:normal;bottom:10px;left:279px;');
		$hide->input->setAttribute('style','position:absolute;bottom:4px;left:60px;');
		$hide->setAttribute('style','position:relative');
		$form->addInput($hide);

		$share = new Typecho_Widget_Helper_Form_Element_Checkbox('share',
		array('weibo'=>_t('新浪微博'),'qzone'=>_t('QQ空间'),'tieba'=>_t('百度贴吧'),'douban'=>_t('豆瓣网'),'renren'=>_t('人人网'),'facebook'=>_t('Facebook'),'twitter'=>_t('Twitter')),NULL,_t('分享功能按钮'));
		$form->addInput($share);

		//配色保存隐藏域
		$jwcolors = new Typecho_Widget_Helper_Form_Element_Hidden('jwcolors',NULL,$cs,NULL);
		$jwcolors->input->setAttribute('id','jwcolors');
		$form->addInput($jwcolors);
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
	public static function jwparse($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;

		$version = explode('/',Helper::options()->version);
		$sign = '</jw>';
		$pattern = '/<(jw)>(.*?)<\/\\1>/si';
		//markdown fix
		if ($version['1']=='17.10.30' && $widget->isMarkdown && !stripos($content,'</jw>')) {
			$sign = '&lt;/jw&gt;';
			$pattern = '/&lt;(jw)&gt;(.*?)&lt;\/\\1&gt;/si';
			$content = str_replace(';/jw&gt;',$sign,$content);
		}

		if ($widget instanceof Widget_Archive && !$widget->request->feed && false!==stripos($content,$sign)) {
			$content = preg_replace_callback($pattern,array('JWPlayer_Plugin','callback'),$content);
		}

		return $content;
	}

	/**
	 * 摘要文本替换
	 * 
	 * @param string $text
	 * @return string
	 */
	public static function txtparse($text,$widget,$lastResult)
	{
		$text = empty($lastResult) ? $text : $lastResult;

		if ($widget instanceof Widget_Archive && false!==stripos($text,'<jw>')) {
			$text = preg_replace('/<(jw)>(.*?)<\/\\1>/si',_t(' [影音片段: 请查看原文播放] '),$text);
		}

		return $text;
	}

	/**
	 * 参数回调解析
	 * 
	 * @param array $match
	 * @return string
	 */
	public static function callback($match)
	{
		$settings = Helper::options()->plugin('JWPlayer');
		$data = array();
		$lists = array();

		//过滤html标签
		$codes = trim(Typecho_Common::stripTags($match['2']));
		if (strpos($codes,'&lt')) $codes = substr($codes,0,-3); //markdown fix
		$atts = strpos($codes,'|') ? explode('|',$codes) : array($codes);

		$file =  trim(array_shift($atts));
		$files = explode(',',$file);
		$fnum = count($files);
		$qfiles = array();
		$qnum = '';
		$source = '';
		$quality = array();
		$vr = strpos($codes,'stereomode');
		$dvr = strpos($codes,'minDvrWindow');
		//处理视频参数
		for ($i=0;$i<$fnum;++$i) {
			//处理画质参数
			if ($files[$i]) {
				$qfiles = explode(';',$files[$i]);
				$qnum = count($qfiles);

				//准备画质数组
				for ($j=0;$j<$qnum;++$j) {
					$source = trim($qfiles[$j]);
					if (false!==strpos($source,'^')) {
						$metas = explode('^',$source);
						$quality[$j]['label'] = $metas['0'];
						$quality[$j]['file'] = self::encode($metas['1']);
						if (isset($metas['2'])) {
							$quality[$j]['type'] = $metas['2'];
						}
					} else {
						$quality[$j]['file'] = self::encode($source);
						//预设3种清晰度
						$quality['0']['label'] = _t('标清');
						if (isset($quality['1'])) {
							$quality['1']['label'] = _t('高清');
						}
						if (isset($quality['2'])) {
							$quality['2']['label'] = _t('超清');
						}
					}
				}
			}

			//多文件画质参数
			if ($fnum>1 || $vr || $dvr) {
				if ($qnum>1) {
					$lists[$i]['sources'] = $quality;
				} else {
					$lists[$i]['file'] = self::encode(trim($files[$i]));
				}
			}
		}
		//单文件画质参数
		if ($fnum<=1) {
			if ($qnum>1) {
				$data['sources'] = $quality;
			} else {
				$data['file'] = self::encode($file);
			}
		}

		$pair = array();
		$key = '';
		$val = '';
		$listkey = false;
		$vals = array();
		$keyvals = '';
		$tfiles = array();
		$tnum = '';
		$track = '';
		$subs = array();
		//处理其他参数
		foreach ($atts as $att) {
			$pair = explode('=',$att);
			$key = trim($pair['0']);
			$val = isset($pair['1']) ? trim($pair['1']) : '';
			$data[$key] = $val;

			$listkey = in_array($key,array('image','title','description','tracks','stereomode','minDvrWindow'));
			if ($listkey) {
				$vals[$key] = explode(',',$data[$key]);
			}

			//处理列表参数
			for ($i=0;$i<$fnum;++$i) {
				if (isset($vals[$key][$i])) {
					$keyvals = trim($vals[$key][$i]);
					//处理字幕参数
					if ($key=='tracks' && $keyvals) {
						$tfiles = explode(';',$keyvals);
						$tnum = count($tfiles);

						//准备语种数组
						for ($j=0;$j<$tnum;++$j) {
							$track = trim($tfiles[$j]);
							if (false!==strpos($track,'^')) {
								$infos = explode('^',$track);
								$subs[$j]['label'] = $infos['0'];
								$subs[$j]['file'] = $infos['1'];
								if (isset($infos['2'])) {
									$subs[$j]['kind'] = $infos['2'];
								}
							} else {
								$subs[$j]['file'] = $track;
								//预设中英字幕
								$subs['0']['label'] = _t('中文');
								if (isset($subs['1'])) {
									$subs['1']['label'] = _t('英文');
								}
							}
						}
						if ($tnum<=1) {
							array_splice($subs,1);
						}

						//多文件字幕参数
						if ($fnum>1) {
							unset($data['tracks']);
							$lists[$i]['tracks'] = $subs;
						}

					//多文件其他参数
					} elseif ($fnum>1 || $vr || $dvr) {
						unset($data[$key]);
						$lists[$i][$key] = $keyvals;
					}

				//对应自动置空
				} elseif ($listkey) {
					$lists[$i][$key] = '';
				}
			}

			//单文件字幕参数
			if ($key=='tracks') {
				if ($fnum<=1) {
					$data['tracks'] = $subs;
				}
				//字幕效果选项
				$tsize = $settings->tsize;
				if ($tsize && $tsize!=='15') {
					$data['captions']['fontSize'] = $tsize;
				}
				if ($settings->tedge) {
					$data['captions']['backgroundOpacity'] = '0';
					$data['captions']['edgeStyle'] = $settings->tedge;
				}
			}
		}

		if (preg_match("/(\.rss|\.json)/i",$file)) {
			unset($data['file']);
			$data['playlist'] = self::encode($file);
		} elseif ($lists) {
			$data['playlist'] = $lists;
		}

		return self::output('',array($data,true));
	}

	/**
	 * 输出播放器实例
	 * 
	 * @access public
	 * @param array $params 实例参数
	 * @return string
	 */
	public static function output($widget,array $params)
	{
		$options = Helper::options();
		$url = $options->pluginUrl.'/JWPlayer/player/';
		$ids = "jwplayer_".++self::$id;

		//处理实例参数
		$jwsets = array();
		$jwsets = isset($params['0']) && is_array($params['0']) ? $params['0'] : $jwsets;
		$iscall = false;
		$iscall = !empty($params['1']) && is_bool($params['1']) ? $params['1'] : $iscall;
		$jwsets = Json::encode($jwsets+self::getsets());

		//播放器实例代码
		$output = '<script type="text/javascript">
window.jwplayer || document.write(\'<script type="text/javascript" src="'.$url.'jwplayer.js"><\/script>\')</script>'; //不重复加载
		$output .= '<div id="'.$ids.'">'._t('播放器载入中...').'</div>';
		$output .= '<script type="text/javascript">jwplayer.defaults = {"base":"'.$url.'"};'.($options->plugin('JWPlayer')->encode ? 'jwplayer.encode = true;' : '').'jwplayer("'.$ids.'").setup('.$jwsets.');</script>';

		//模版输出判断
		if ($iscall) {
			return $output;
		} else {
			echo $output;
		}
	}

	/**
	 * 输出插件设置
	 * 
	 * @return array
	 */
	public static function getsets()
	{
		$options = Helper::options();
		$skincs = self::$skincs;
		$sets =array();

		if (isset($options->plugins['activated']['JWPlayer'])) {
			$settings = $options->plugin('JWPlayer');
			$share = $settings->share;
			$stretch = $settings->stretch;
			$csets = Json::decode($settings->jwcolors,true);
			$archive = Typecho_Widget::widget('Widget_Archive');
			$url = $archive->permalink;
			$key = array();

			//输出配色参数
			foreach ($csets as $keys=>$cs) {
				if ($skincs[''.$keys.'']!==$cs) {
					$key = explode('.',$keys);
					$sets['skin'][''.$key['0'].''][''.$key['1'].''] = $cs;
				}
			}

			if ($stretch) {
				$sets['stretching'] = $stretch;
			}
			if ($settings->info) {
				$sets['displaytitle'] = 'false';
				$sets['displaydescription'] = 'false';
			}
			if ($settings->about) {
				$sets['abouttext'] = _t('原文链接: ').$archive->title;
				$sets['aboutlink'] = $url;
			}
			if ($share) {
				$sets['sharing']['heading'] = _t('分享');
				$sets['sharing']['link'] = $url;
				array_push($share,'email');
				$sets['sharing']['sites'] = $share;
			}

			//输出logo参数
			$logo = $settings->logo;
			$llink = $settings->llink;
			$lops = $settings->lpos;
			$margin = $settings->margin;
			if ($logo) {
				$sets['logo']['file'] = $logo;
				if ($llink) {
					$sets['logo']['link'] = $llink;
				}
				if ($lops) {
					$sets['logo']['position'] = $lops;
				}
				if (false!==$margin && $margin!=='8') {
					$sets['logo']['margin'] = $margin;
				}
				if ($settings->hide) {
					$sets['logo']['hide'] = true;
				}
			}
		}

		return $sets+self::locals();
	}

	/**
	 * 加密视频地址
	 * 
	 * @param string $string
	 * @return string
	 */
	private static function encode($string)
	{
		 if (Helper::options()->plugin('JWPlayer')->encode) {
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
 		}

		return $string;
	}

	/**
	 * 输出本地化参数
	 * 
	 * @return array
	 */
	private static function locals()
	{
		return array(
			'localization'=>array(
				'rewind'=>_t('后退10秒'),
				'settings'=>_t('选项'),
				'cc'=>_t('字幕'),
				'hd'=>_t('画质'),
				'fullscreen'=>_t('全屏'),
				'videoInfo'=>_t('查看媒体信息'),
				'copied'=>_t('复制成功'),
				'playlist'=>_t('列表'),
				'nextUp'=>_t('下一个'),
				'liveBroadcast'=>_t('在线直播')
		));
	}

	/**
	 * 输出编辑器按钮
	 * 
	 * @access public
	 * @return void
	 */
	public static function jwbutton()
	{
?>
<script>
$(function(){
	var wmd = $('#wmd-image-button');
	if (wmd.length>0) {
		wmd.after(
	'<li class="wmd-button" id="wmd-jw-button" style="padding-top:4px;" title="<?php _e("插入视频"); ?>"><img src="<?php echo Helper::options()->pluginUrl; ?>/JWPlayer/admin/video.svg"/></li>');
	} else {
		$('.url-slug').after('<button type="button" id="wmd-jw-button" class="btn btn-xs" style="margin-right:5px;"><?php _e("插入视频"); ?></button>');
	}
	$('#wmd-jw-button').click(function(){
		$('body').append('<div id="jwpanel">' +
		'<div class="wmd-prompt-background" style="position:absolute;z-index:1000;opacity:0.5;top:0px;left:0px;width:100%;height:954px;"></div>' +
		'<div class="wmd-prompt-dialog"><div><p><b><?php _e("插入视频"); ?></b></p>' +
			'<p><?php _e("请在下方的输入框内输入要插入的视频地址"); ?></p></div>' +
			'<form><input type="text"></input><button type="button" class="btn btn-s primary" id="ok"><?php _e("确定"); ?></button>' +
			'<button type="button" class="btn btn-s" id="cancel"><?php _e("取消"); ?></button></form>' +
		'</div></div>');
		var jwlog = $('.wmd-prompt-dialog input'),
			textarea = $('#text');
		jwlog.val('http://').select();
		$('#cancel').click(function(){
			$('#jwpanel').remove();
			textarea.focus();
		});
		$('#ok').click(function(){
			var jwinput = '<jw>' + jwlog.val() + '</jw>',
				sel = textarea.getSelection(),
				offset = (sel ? sel.start : 0)+jwinput.length;
			textarea.replaceSelection(jwinput);
			textarea.setSelection(offset,offset);
			$('#jwpanel').remove();
		});
	});
});
</script>
<?php
	}

}
