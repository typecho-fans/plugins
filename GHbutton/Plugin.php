<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 在文中嵌入GitHub项目按钮
 * 
 * @package GHbutton
 * @author 羽中
 * @version 1.0.3
 * @dependence 14.10.10
 * @link http://www.yzmb.me/archives/net/github-btn-typecho
 */
class GHbutton_Plugin implements Typecho_Plugin_Interface
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
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('GHbutton_Plugin','btn_parse');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('GHbutton_Plugin','btn_parse');
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
'<div style="color:#999;font-size:13px"><p>'
._t('编辑文章或页面写入%s用户名%s项目名%s即可显示按钮状图标, 支持标签内指定各项参数<br/>例:','<span style="color:#467B96;font-weight:bold">&lt;gb&gt;</span><span style="color:#444;font-weight:bold">','<span style="color:#467B96">/</span>','</span><span style="color:#467B96;font-weight:bold">&lt;/gb&gt;</span>').
' <span style="color:#467B96;font-weight:bold">&lt;gb user="<span style="color:#444">typecho-fans</span>"  type="<span style="color:#444">star</span>" count="<span style="color:#444">1</span>" size="<span style="color:#444">1</span>" width="<span style="color:#444">200</span>"&gt;</span><span style="color:#444;font-weight:bold">plugin</span><span style="color:#467B96;font-weight:bold">&lt;/gb&gt;</span>
</p></div>';
		$btn_user = new Typecho_Widget_Helper_Form_Element_Text('btn_user',
		NULL,'',_t('GitHub用户名称'),_t('缺省调用username, 可在标签内指定参数user="-"覆盖'));
		$btn_user->input->setAttribute('class','w-10');
		$form->addInput($btn_user);

		$btn_type = new Typecho_Widget_Helper_Form_Element_Select('btn_type',
		array('watch'=>_t('Watch(跟进项目)'),'star'=>_t('Star(收藏项目)'),'fork'=>_t('Fork(拷贝项目)'),'follow'=>_t('Follow(关注作者)'),'download'=>_t('Download(下载项目)'),'issue'=>_t('Issue(提交问题)')),'fork',_t('GitHub按钮种类'),_t('缺省按钮, 可用参数type="watch/star/fork/follow/download/issue"覆盖'));
		$form->addInput($btn_type);

		$btn_width = new Typecho_Widget_Helper_Form_Element_Text('btn_width',
		NULL,'170',_t('iframe调用宽度'),_t('缺省宽度(单位px不用写), 标签内可用参数width="-"覆盖'));
		$btn_width->input->setAttribute('style','width:47px');
		$form->addInput($btn_width->addRule('isInteger','请填写整数数字'));

		$btn_size = new Typecho_Widget_Helper_Form_Element_Checkbox('btn_size',
		array(1=>_t('大尺寸')),NULL,_t('GitHub按钮大小'),_t('缺省是否使用大按钮, 可在标签内用参数size="0/1"覆盖'));
		$form->addInput($btn_size);

		$btn_count = new Typecho_Widget_Helper_Form_Element_Checkbox('btn_count',
		array(1=>_t('显示')),NULL,_t('GitHub按钮计数'),_t('缺省是否显示计数, 可在标签内用参数count="0/1"覆盖'));
		$form->addInput($btn_count);

		$btn_lang = new Typecho_Widget_Helper_Form_Element_Radio('btn_lang',
		array('en'=>_t('英文'),'cn'=>_t('中文')),'en',_t('GitHub按钮语言'),_t('缺省按钮文本语言, 可在标签内用参数lang="en/cn"覆盖'));
		$form->addInput($btn_lang);
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
	 * 输出标签替换
	 * 
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function btn_parse($content,$widget,$lastResult)
	{
		$content = empty($lastResult) ? $content : $lastResult;

		if ($widget instanceof Widget_Archive && false!==stripos($content,'</gb>')) {
			$content = preg_replace_callback('/<(gb)([^>]*)>(.*?)<\/\\1>/si',array('GHbutton_Plugin',"parseCallback"),$content);
		}

		return $content;
	}

	/**
	 * 参数回调解析
	 * 
	 * @param array $matche
	 * @return string
	 */
	public static function parseCallback($matche)
	{
		$options = Helper::options();
		$settings = $options->plugin('GHbutton');
		$param = trim($matche['2']);
		$btn_repo = trim($matche['3']);

		//获取设置参数
		$btn_user = $settings->btn_user;
		if (strpos($btn_repo,'/')) {
			$pair = explode('/',$btn_repo);
			$btn_user = $pair['0'];
			$btn_repo = $pair['1'];
		}
		$btn_type = $settings->btn_type;
		$btn_count = $settings->btn_count ? '&amp;count=true' : '';
		$btn_size = $settings->btn_size ? '&amp;size=large' : '';
		$btn_height = $settings->btn_size ? '30' : '20';
		$btn_width = $settings->btn_width;
		$html = $settings->btn_lang=='cn' ? '/GHbutton/source/github-btn-cn.html' : '/GHbutton/source/github-btn.html';

		//匹配标签参数
		if ($param) {
			if (preg_match('/user=["\']([\w-]*)["\']/i',$param,$out)) {
				$btn_user = trim($out['1']) ? trim($out['1']) : $btn_user;
			}
			if (preg_match('/type=["\'](watch|star|fork|follow|download|issue)["\']/i',$param,$out)) {
				$btn_type = trim($out['1']) ? trim($out['1']) : $btn_type;
			}
			if (preg_match('/count=["\'](0|1)["\']/i',$param,$out)) {
				$btn_count = trim($out['1'])=='0' ? '' : '&amp;count=true';
			}
			if (preg_match('/size=["\'](0|1)["\']/i',$param,$out)) {
				$btn_size = trim($out['1'])=='0' ? '' : '&amp;size=large';
				$btn_height = trim($out['1'])=='0' ? '20' : '30';
			}
			if (preg_match('/lang=["\'](cn|en)["\']/i',$param,$out)) {
				$html = trim($out['1'])=='cn' ? '/GHbutton/source/github-btn-cn.html' : '/GHbutton/source/github-btn.html';
			}
			if (preg_match('/width=["\']([\w-]*)["\']/i',$param,$out)) {
				$btn_width = trim($out['1']) ? str_replace('px','',trim($out['1'])) : $btn_width;
			}
		}

		$replace = '<iframe src="'.$options->pluginUrl.$html.'?user='.$btn_user.'&amp;repo='.$btn_repo.'&amp;type='.($btn_type=='watch' ? $btn_type.'&amp;v=2' : $btn_type).$btn_count.$btn_size.'" width="'.$btn_width.'px" height="'.$btn_height.'px" frameborder="0" scrolling="0"></iframe>';

		return $replace;
	}

}