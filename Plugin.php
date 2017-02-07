<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为博客添加图片表情功能 (初版js:<a href="http://kan.willin.org/typecho/smilies-plugin.html">willin kan</a>/正文功能:<a href="http://lt21.me">LT21</a>)
 * 
 * @package Smilies
 * @author 羽中
 * @version 1.1.2
 * @dependence 14.10.10-*
 * @link http://www.yzmb.me/archives/net/smilies-for-typecho
 */
class Smilies_Plugin implements Typecho_Plugin_Interface
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
		Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('Smilies_Plugin','showsmilies');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Smilies_Plugin','showsmilies');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Smilies_Plugin','showsmilies');

		Typecho_Plugin::factory('Widget_Archive')->footer = array('Smilies_Plugin','insertjs');
		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('Smilies_Plugin', 'insertjs');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('Smilies_Plugin', 'insertjs');

		Typecho_Plugin::factory('admin/write-post.php')->option = array('Smilies_Plugin', 'render');
		Typecho_Plugin::factory('admin/write-page.php')->option = array('Smilies_Plugin', 'render');
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
		//准备缺省数据
		$curset =  'qq';
		$cursort = 'icon_mrgreen.gif|icon_neutral.gif|icon_twisted.gif|icon_arrow.gif|icon_eek.gif|icon_smile.gif|icon_confused.gif|icon_cool.gif|icon_evil.gif|icon_biggrin.gif|icon_idea.gif|icon_redface.gif|icon_razz.gif|icon_rolleyes.gif|icon_wink.gif|icon_cry.gif|icon_surprised.gif|icon_lol.gif|icon_mad.gif|icon_sad.gif|icon_exclaim.gif|icon_question.gif';
		$lists = array();
		$datas = self::scanfolders();
		if ($datas) {
			$keys = array_keys($datas);
			$lists = array_combine($keys,$keys);
		}

		$option = Helper::options();
		if (isset($option->plugins['activated']['Smilies'])) {
			$settings = $option->plugin('Smilies');
			$curset = $settings->smiliesset;
			$cursort = $settings->smsort;
		}

		$smiliesset = new Typecho_Widget_Helper_Form_Element_Select('smiliesset',
		$lists,$curset,_t('选择表情风格'),_t('可选择在插件目录下新增的表情文件夹进行预览排序'));
		$form->addInput($smiliesset);

		$replacetxt = new Typecho_Widget_Helper_Form_Element_Checkbox('replacetxt',
		array(1=>_t('是')),NULL,_t('代替字符表情'),_t('自动替换上表底部的字符型表情(可能破坏文本结构)'));
		$form->addInput($replacetxt);

		$maxwidth = new Typecho_Widget_Helper_Form_Element_Text('maxwidth',
		NULL,'21',_t('限制表情尺寸'),_t('设置表情在前台显示的最大宽度, 单位: px(无需填写)'));
		$maxwidth->input->setAttribute('class','w-10');
		$form->addInput($maxwidth->addRule('isFloat',_t('请填写数字')));

		$allowpop = new Typecho_Widget_Helper_Form_Element_Radio('allowpop',
		array(1=>_t('开启'),0=>_t('关闭')),0,_t('按钮弹窗效果'));
		$form->addInput($allowpop);

		$jqmode = new Typecho_Widget_Helper_Form_Element_Radio('jqmode',
		array(1=>_t('jQuery'),0=>_t('原生js')),0,_t('操作代码模式'),_t('仅兼容性略有差异, jquery将自动判断加载CDN库文件'));
		$form->addInput($jqmode);

		$textareaid = new Typecho_Widget_Helper_Form_Element_Text('textareaid',
		NULL,_t('一般无需填写'),_t('指定评论框ID'),_t('若插件识别出现问题可在此指定主题使用的评论框id'));
		$textareaid->input->setAttribute('class','w-20');
		$form->addInput($textareaid);

		$postmode = new Typecho_Widget_Helper_Form_Element_Radio('postmode',
		array(1=>_t('开启'),0=>_t('关闭')),0,_t('正文使用表情'),_t('编辑文章或页面时也可以插入表情代码并在前台显示'));
		$form->addInput($postmode);

		//排序保存隐藏域
		$smsort = new Typecho_Widget_Helper_Form_Element_Hidden('smsort',
		NULL,$cursort);
		$form->addInput($smsort);

//输出面板效果
?>
<div style="color:#999;font-size:13px;"><p><?php _e('在主题comments.php文件中的适当位置插入代码%s即可显示表情按钮','<span style="color:#467B96;font-weight:bold;">&lt;?php Smilies_Plugin::output(); ?&gt;</span>'); ?></p></div>
<ul class="typecho-option" id="typecho-option-item-preview">
<li><label class="typecho-label" for="preview"><?php _e('预览与排序'); ?></label></li></ul>

<script src="<?php $option->adminUrl('js/jquery.js'); ?>"></script>
<script src="<?php $option->pluginUrl('Smilies/custom.js'); ?>"></script>
<link href="<?php $option->pluginUrl('Smilies/custom.css'); ?>" rel="stylesheet" type="text/css" />

<script>
$(function() {
	var folders = eval('(<?php echo Json::encode($lists); ?>)');
	//全隐兼容gridly
	function allhide(){
		$.each(folders,function(i){
			$('.'+i).hide();
		});
	}
	allhide();
	$('.<?php echo $curset; ?>').show();
	//点击菜单切换
	$("#smiliesset-0-1").bind("change",function(){
		var datas = eval('(<?php echo Json::encode($datas); ?>)'),
		folder = $(this).val(),
		dorder = datas[folder].join('|');
		$("input[name='smsort']").val(dorder);
		allhide();$('.'+folder).show();
	});
	$(".td").bind('mouseover',function(){
		$(this).css("cursor","move")
	});
	$('textsm').tooltip();
});
//排序结果输入
var reordered = function($elements) {
	var sortid = [];
	$elements.each(function(){
		sortid.push(this.id);
	});
	var neworder = sortid.join('|');
	$("input[name='smsort']").val(neworder);
};
</script>

<div class="table">
	<div class="sample">
		<div class="fix" id="0"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_mrgreen.gif'); ?>" alt="icon_mrgreen.gif" title=":mrgreen:"/></div>
		<div class="fix" id="1"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_neutral.gif'); ?>" alt="icon_neutral.gif" title=":neutral:"/></div>
		<div class="fix" id="2"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_twisted.gif'); ?>" alt="icon_twisted.gif" title=":twisted:"/></div>
		<div class="fix" id="3"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_arrow.gif'); ?>" alt="icon_arrow.gif" title=":arrow:"/></div>
		<div class="fix" id="4"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_eek.gif'); ?>" alt="icon_eek.gif" title=":shock:"/></div>
		<div class="fix" id="5"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_smile.gif'); ?>" alt="icon_smile.gif" title=":smile:"/></div>
		<div class="fix" id="6"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_confused.gif'); ?>" alt="icon_confused.gif" title=":???:"/></div>
		<div class="fix" id="7"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_cool.gif'); ?>" alt="icon_cool.gif" title=":cool:"/></div>
		<div class="fix" id="8"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_evil.gif'); ?>" alt="icon_evil.gif" title=":evil:"/></div>
		<div class="fix" id="9"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_biggrin.gif'); ?>" alt="icon_biggrin.gif" title=":grin:"/></div>
		<div class="fix" id="10"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_idea.gif'); ?>" alt="icon_idea.gif" title=":idea:"/></div>
		<div class="fix" id="11"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_redface.gif'); ?>" alt="icon_redface.gif" title=":oops:"/></div>
		<div class="fix" id="12"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_razz.gif'); ?>" alt="icon_razz.gif" title=":razz:"/></div>
		<div class="fix" id="13"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_rolleyes.gif'); ?>" alt="icon_rolleyes.gif" title=":roll:"/></div>
		<div class="fix" id="14"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_wink.gif'); ?>" alt="icon_wink.gif" title=":wink:"/></div>
		<div class="fix" id="15"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_cry.gif'); ?>" alt="icon_cry.gif" title=":cry:"/></div>
		<div class="fix" id="16"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_surprised.gif'); ?>" alt="icon_surprised.gif" title=":eek:"/></div>
		<div class="fix" id="17"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_lol.gif'); ?>" alt="icon_lol.gif" title=":lol:"/></div>
		<div class="fix" id="18"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_mad.gif'); ?>" alt="icon_mad.gif" title=":mad:"/></div>
		<div class="fix" id="19"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_sad.gif'); ?>" alt="icon_sad.gif" title=":sad:"/></div>
		<div class="fix" id="20"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_exclaim.gif'); ?>" alt="icon_exclaim.gif" title=":!:"/></div>
		<div class="fix" id="21"><img src="<?php $option->pluginUrl('Smilies/wordpress/icon_question.gif'); ?>" alt="icon_question.gif" title=":?:"/></div>
		<div class="fix"><?php _e('默认对照'); ?></div>
	</div>
<?php if ($datas) {
	foreach ($datas as $set=>$data) { ?>
	<div class="gridly <?php echo $set; ?>">
<?php //预览结果显示
		$names = $set==$curset ? explode('|',$cursort) : $data;
		foreach ($names as $name) { ?>
		<div class="td" id="<?php echo $name; ?>"><img style="max-width:22px;" src="<?php $option->pluginUrl('Smilies/'.$set.'/'.$name); ?>" alt="<?php echo $name; ?>" title="<?php _e('尽量对应默认表情风格'); ?>"/></div>
<?php } ?>
	</div>
<?php } ?>
	<div class="caption">
		<div class="fix"><?php _e('拖动排序'); ?></div>
	</div>
<?php } ?>
	<div class="textsm" title="<?php _e('可替换字符表情'); ?>">
		<div class="fix"></div>
		<div class="fix">:|<br/>:-|</div>
		<div class="fix"></div>
		<div class="fix"></div>
		<div class="fix">8O<br/>8-O</div>
		<div class="fix">:)<br/>:-)</div>
		<div class="fix">:?<br/>:-?</div>
		<div class="fix">8)<br/>8-)</div>
		<div class="fix"></div>
		<div class="fix">:D<br/>:-D</div>
		<div class="fix"></div>
		<div class="fix"></div>
		<div class="fix">:P<br/>:-P</div>
		<div class="fix"></div>
		<div class="fix">;)<br/>;-)</div>
		<div class="fix"></div>
		<div class="fix">:o<br/>:-o</div>
		<div class="fix"></div>
		<div class="fix">:x<br/>:-x</div>
		<div class="fix">:(<br/>:-(</div>
		<div class="fix"></div>
		<div class="fix"></div>
	</div>
</div>

<script>
//gridly设置与回调
$('.gridly').gridly({
	base: 28,
	gutter: 1,
	columns: 22,
	callbacks: {reordered: reordered}
});
</script>
<?php
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
	 * 扫描表情文件夹
	 * 
	 * @access private
	 * @return array
	 */
	private static function scanfolders()
	{
		$plugindir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/Smilies/';
		//检索文件夹
		$routes = glob($plugindir.'*',GLOB_ONLYDIR);
		$results = array();

		if ($routes) {
			$desort = array('icon_mrgreen.gif','icon_neutral.gif','icon_twisted.gif','icon_arrow.gif','icon_eek.gif','icon_smile.gif','icon_confused.gif','icon_cool.gif','icon_evil.gif','icon_biggrin.gif','icon_idea.gif','icon_redface.gif','icon_razz.gif','icon_rolleyes.gif','icon_wink.gif','icon_cry.gif','icon_surprised.gif','icon_lol.gif','icon_mad.gif','icon_sad.gif','icon_exclaim.gif','icon_question.gif');
			$folder = '';
			$locations = array();

			foreach ($routes as $route) {
				$folder = str_replace($plugindir,'',$route);
				//检索图片后缀
				$locations = in_array($folder,array('qq','wordpress')) ? $desort
					 : glob($plugindir.$folder.'/*.{gif,jpg,jpeg,png,tiff,bmp,GIF,JPG,JPEG,PNG,TIFF,BMP}',GLOB_BRACE|GLOB_NOSORT);

				array_walk($locations,array(new Smilies_Plugin,'cname'),'');
				if (function_exists('iconv')) {
					$folder = iconv('gbk','utf-8',$folder);
				}

				$results[$folder] = array_slice($locations,-22);
			}
		}

		return $results;
	}

	/**
	 * 兼容中文文件名
	 * 
	 * @access private
	 * @return array
	 */
	private static function cname(&$value) {
		if (function_exists('iconv')) {
			$value = iconv('gbk','utf-8',$value);
		}
		$value = preg_replace('/^.+[\\\\\\/]/','',$value);
	}

	/**
	 * 整理表情数据
	 * 
	 * @access private
	 * @return array
	 */
	private static function parsesmilies()
	{
		$options = Helper::options();
		$settings = $options->plugin('Smilies');
		$maxwidth = $settings->maxwidth;
		$maxwidth = $maxwidth ? 'max-width:'.$maxwidth.'px;' : '';

		$smsort = explode('|',$settings->smsort);
		$pattern = array(':mrgreen:',':neutral:',':twisted:',':arrow:',':shock:',':smile:',':???:',':cool:',':evil:',':grin:',':idea:',':oops:',':razz:',':roll:',':wink:',':cry:',':eek:',':lol:',':mad:',':sad:',':!:',':?:');
		//生成排序数组
		$sortsm = array_combine($pattern,$smsort);

		//11对字符表情
		$textsm = array(
			'8-)'=>$smsort['7'],
			'8-O'=>$smsort['4'],
			':-('=>$smsort['19'],
			':-)'=>$smsort['5'],
			':-?'=>$smsort['6'],
			':-D'=>$smsort['9'],
			':-P'=>$smsort['12'],
			':-o'=>$smsort['16'],
			':-x'=>$smsort['18'],
			':-|'=>$smsort['1'],
			';-)'=>$smsort['14'],
			'8)'=>$smsort['7'],
			'8O'=>$smsort['4'],
			':('=>$smsort['19'],
			':)'=>$smsort['5'],
			':?'=>$smsort['6'],
			':D'=>$smsort['9'],
			':P'=>$smsort['12'],
			':o'=>$smsort['16'],
			':x'=>$smsort['18'],
			':|'=>$smsort['1'],
			';)'=>$smsort['14'],
		);

		$smtrans = $settings->replacetxt ? array_merge($sortsm,$textsm) : $sortsm;

		$smilies = '';
		$smiled = array();
		$smiliesicon = array();
		$smiliestag = array();
		$smiliesimg = array();
		$smurl = Typecho_Common::url('Smilies/'.urlencode($settings->smiliesset).'/',$options->pluginUrl);

		foreach ($smtrans as $tag=>$grin) {
			$smilies = '<img src="'.$smurl.$smtrans[':smile:'].'" alt="'._t('选择表情').'" style="'.$maxwidth.'"/>';

			//过滤重复值
			if (!in_array($grin,$smiled)) {
				$smiled[] = $grin;

				$s = $settings->jqmode ? '' : ' onclick="Smilies.grin(\''.$tag.'\');"';
				$smiliesicon[] = '<span'.$s.' style="cursor:pointer" data-tag=" '.$tag.' "><img style="margin:2px;'.$maxwidth.'" src="'.$smurl.$grin.'" alt="'.$grin.'"/></span>';
			}

			$smiliestag[] = $tag;
			$smiliesimg[] = '<img class="smilies" src="'.$smurl.$grin.'" alt="'.$grin.'" style="'.$maxwidth.'"/>';
		}

		return array($smilies,$smiliesicon,$smiliestag,$smiliesimg);
	}

	/**
	 * 后台编辑选项
	 * 
	 * @access public
	 * @return void
	 */
	public static function render()
	{
		if (Helper::options()->plugin('Smilies')->postmode) {
			echo '<section class="typecho-post-option"><label for="template" class="typecho-label">'._t('选择表情').'</label><p>';
			self::output();
			echo '</p></section>
';
		}
	}

	/**
	 * 解析表情图片
	 * 
	 * @access public
	 * @param string $content 评论内容
	 * @return string
	 */
	public static function showsmilies($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;

		$options = Helper::options();
		//允许图片标签
		$options->commentsHTMLTagAllowed .= '<img src="" alt="" style=""/>';

		if ($widget instanceof Widget_Abstract_Comments || $widget instanceof Widget_Archive && $options->plugin('Smilies')->postmode) {
			$arrays = self::parsesmilies();
			$content = str_replace($arrays['2'],$arrays['3'],$content);
		}

		return $content;
	}

	/**
	 * 输出表情选框
	 * 
	 * @access public
	 * @return void
	 */
	public static function output()
	{
		$options = Helper::options();
		$settings = $options->plugin('Smilies');

		//边框阴影样式
		$shadow = 'box-shadow: rgba(190,190,190,1) 1px 3px 15px';
		$border = 'border-radius: 11px';
		$smiliesdisplay = $settings->allowpop
			 ? ' style="display:none;position:absolute;z-index:99;width:240px;margin-top:-70px;padding:5px;background:#fff;border:1px solid #bbb;-moz-'.$shadow.';-webkit-'.$shadow.';-khtml-'.$shadow.';'.$shadow.';-moz-'.$border.';-webkit-'.$border.';-khtml-'.$border.';'.$border.';"'
			 : ' style="display:block;"';

		//罗列表情图标
		$arrays = self::parsesmilies();
		$smilies = '';
		foreach ($arrays['1'] as $icon) {
			$smilies .= $icon;
		}

		$output = '<div id="smiliesbox"'.$smiliesdisplay.'>';
		$output .= $smilies;
		$output .= '</div>';

		//弹窗风格按钮
		if ($settings->allowpop) {
			$s = $settings->jqmode ? '' : ' onclick="Smilies.showBox();"';
			$output .= '<span'.$s.' style="cursor:pointer;" id="smiliesbutton" title="'._t('选择表情').'">'.$arrays['0'].'</span>';
		}

		echo $output;
	}

	/**
	 * 输出js脚本
	 * 
	 * @access public
	 * @return void
	 */
	public static function insertjs($widget)
	{
		$options = Helper::options();
		$settings = $options->plugin('Smilies');
		$textareaid = $settings->textareaid;
		$textareaid = $textareaid ? $textareaid : _t('一般无需填写');

		$idset = $widget->is('single') ? $textareaid : 'text';
		$txtid = $settings->jqmode ? '#'.$idset : $idset;
		$txtdom = 'domId("'.$txtid.'")';
		if ($widget->is('single') && $idset==_t('一般无需填写')) {
			$txtid = 'textarea';
			$txtdom = 'domTag("'.$txtid.'")';
		}

		//jquery模式
		if ($settings->jqmode) {
			$auto = '';
			$js = '
<script type="text/javascript">
$(function() {
	var box = $("#smiliesbox");
	$("#smiliesbutton").click(function(){
		box.show();
	});
	$("span",box).click(function() {
		$("'.$txtid.'").insert($(this).attr("data-tag"));';
			if ($settings->allowpop) {
				$js .= '
		box.hide();';
				$auto = '
	$(document).mouseup(function(e) {
		if (!box.is(e.target) && box.has(e.target).length === 0) {
			box.hide();
		}
	});';
			}
			$js .= '
	});'.$auto.'
	$.fn.extend({
		"insert": function(myValue) {
			var $t = $(this)[0];
			if (document.selection) {
				this.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
				this.focus()
			} else if ($t.selectionStart || $t.selectionStart=="0") {
				var startPos = $t.selectionStart;
				var endPos = $t.selectionEnd;
				var scrollTop = $t.scrollTop;
				$t.value = $t.value.substring(0, startPos) + myValue + $t.value.substring(endPos, $t.value.length);
				this.focus();
				$t.selectionStart = startPos + myValue.length;
				$t.selectionEnd = startPos + myValue.length;
				$t.scrollTop = scrollTop
			} else {
				this.value += myValue;
				this.focus()
			}
		}
	}) 
});
</script>
		';
		//js模式
		} else {
			$js = '<script type="text/javascript">
//<![CDATA[
Smilies = {
	domId : function(id) {
		return document.getElementById(id);
	},
	domTag : function(id) {
		return document.getElementsByTagName(id)[0];
	},
	showBox : function () {
		this.domId("smiliesbox").style.display = "block";
	},
	closeBox : function () {
		this.domId("smiliesbox").style.display = "none";
	},
	grin : function (tag) {
		tag = \' \' + tag + \' \'; myField = this.'.$txtdom.';
		document.selection ? (myField.focus(),sel = document.selection.createRange(),sel.text = tag,myField.focus()) : this.insertTag(tag);
	},
	insertTag : function (tag) {
		myField = Smilies.'.$txtdom.';
		myField.selectionStart || myField.selectionStart=="0" ? (
			startPos = myField.selectionStart,
			endPos = myField.selectionEnd,
			cursorPos = startPos,
			myField.value = myField.value.substring(0,startPos)
				+ tag
				+ myField.value.substring(endPos,myField.value.length),
			cursorPos += tag.length,
			myField.focus(),
			myField.selectionStart = cursorPos,
			myField.selectionEnd = cursorPos
		):(
			myField.value += tag,
			myField.focus()
		);';
			if ($settings->allowpop) {
				$js .= '
		this.closeBox();';
			}
			$js .= '
	}
} 
//]]>
</script>';
		}

		if ($widget->is('single')) {
			echo ($settings->jqmode ? '<script type="text/javascript">//<![CDATA[
	window.jQuery || document.write("<script type=\"text/javascript\" src=\"http://cdn.staticfile.org/jquery/3.1.1/jquery.min.js\"><\/script>")//]]></script>' : '').$js;
		}
		if ($widget instanceof Widget_Contents_Post_Edit && $settings->postmode) {
			echo $js;
		}

	}

}