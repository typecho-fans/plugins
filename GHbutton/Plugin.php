<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 使用iframe方式嵌入GitHub项目多功能按钮
 * @package GHbutton
 * @author 羽中
 * @version 1.0.2
 * @dependence 13.12.12-*
 * @link http://www.jzwalk.com/
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
		echo "<div style='color:#999;font-size:0.92857em;font-weight:bold;'><p>编辑文章或页面写入如<span style='color:#467B96;'>&lt;gb&gt;</span><span style='color:#E47E00;'>用户名<span style='color:#467B96;'>/</span>项目名</span><span style='color:#467B96;'>&lt;/gb&gt;</span>发布即可. 支持标签参数, 详见各选项说明.<br/>示例: <span style='color:#467B96;'>&lt;gb user=\"<span style='color:#E47E00;'>typecho-fans</span>\"  type=\"<span style='color:#E47E00;'>star</span>\" count=\"<span style='color:#E47E00;'>1</span>\" size=\"<span style='color:#E47E00;'>1</span>\" width=\"<span style='color:#E47E00;'>200</span>\"&gt;</span><span style='color:#E47E00;'>plugins</span><span style='color:#467B96;'>&lt;/gb&gt;</span></p></div>";
		$btn_user = new Typecho_Widget_Helper_Form_Element_Text('btn_user',
			NULL,'',_t('GitHub用户名'),_t('默认调用的username, 可在标签中用参数user="*"覆盖'));
		$btn_user->input->setAttribute('class','w-20');
		$form->addInput($btn_user);

		$btn_type = new Typecho_Widget_Helper_Form_Element_Select('btn_type',
	 	array('watch'=>_t('Watch(跟进项目)'),'star'=>_t('Star(收藏项目)'),'fork'=>_t('Fork(拷贝项目)'),'follow'=>_t('Follow(关注作者)'),'download'=>_t('Download(版本发布)')),'fork',_t('GitHub按钮种类'),_t('默认调用的按钮种类, 可在标签中用参数type="watch/star/fork/follow/download"覆盖'));
		$form->addInput($btn_type);

		$btn_count = new Typecho_Widget_Helper_Form_Element_Checkbox('btn_count',
	 	array('1'=>_t('显示')),NULL,_t('GitHub按钮数字'),_t('默认是否显示按钮数字, 可在标签中用参数count="0/1"覆盖'));
		$form->addInput($btn_count);

		$btn_size = new Typecho_Widget_Helper_Form_Element_Checkbox('btn_size',
	 	array('1'=>_t('大尺寸')),NULL,_t('GitHub按钮大小'),_t('默认是否使用大尺寸按钮, 可在标签中用参数size="0/1"覆盖'));
		$form->addInput($btn_size);

		$btn_lang = new Typecho_Widget_Helper_Form_Element_Radio('btn_lang',
	 	array('en'=>_t('英文'),'cn'=>_t('中文')),'en',_t('GitHub按钮语言'),_t('选择按钮文字语言, 可在标签中用参数lang="en/zh"覆盖'));
		$form->addInput($btn_lang);

		$btn_width = new Typecho_Widget_Helper_Form_Element_Text('btn_width',
	 		NULL,'170',_t('iframe框架宽度'),_t('默认iframe调用宽度, 单位px(不用写), 可在标签中用参数width="*"覆盖'));
		$btn_width->input->setAttribute('class','w-10');
		$form->addInput($btn_width->addRule('isInteger','请输入整数数字'));
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
		$content = empty($lastResult)?$content:$lastResult;

		//替换gb标签
		if ($widget instanceof Widget_Archive) {
			$content = preg_replace_callback('/<(gb)([^>]*)>(.*?)<\/\\1>/si',array('GHbutton_Plugin',"parseCallback"),$content);
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
		$options = Helper::options();
		$settings = $options->plugin('GHbutton');
		$url = $options->pluginUrl;
		$param = trim($matches[2]);
		$btn_repo = trim($matches[3]);

		//获取设置参数
		$btn_user = $settings->btn_user;
		$btn_type = $settings->btn_type;
		$btn_count = ($settings->btn_count)?'&amp;count=true':'';
		$btn_size = ($settings->btn_size)?'&amp;size=large':'';
		$btn_width = $settings->btn_width;
		
		//判断语言版本
		$html = ($settings->btn_lang=='cn')?$url.'/GHbutton/source/github-btn-cn.html':$url.'/GHbutton/source/github-btn.html';

		//匹配输出参数
		if (!empty($param)) {
			if (preg_match("/user=[\"']([\w-]*)[\"']/i",$param,$out)) {
				$btn_user = trim($out[1])==''?$btn_user:trim($out[1]);
			}
			if (preg_match("/type=[\"'](watch|star|fork|follow|download)[\"']/i",$param,$out)) {
				$btn_type = trim($out[1])==''?$btn_type:trim($out[1]);
			}
			if (preg_match("/count=[\"']1[\"']/i",$param)) {
				$btn_count = '&amp;count=true';
			}
			if (preg_match("/size=[\"']1[\"']/i",$param)) {
				$btn_size = '&amp;size=large';
			}
			if (preg_match("/lang=[\"']cn[\"']/i",$param)) {
				$html= $url.'/GHbutton/source/github-btn-cn.html';
			}
			if (preg_match("/width=[\"']([\w-]*)[\"']/i",$param,$out)) {
				$btn_width = trim($out[1])==''?$btn_width:str_replace('px','',trim($out[1]));
			}
		}

		//兼容格式
		if (strpos($btn_repo,'/')) {
			$pair = explode('/',$btn_repo);
			$btn_user = $pair[0];
			$btn_repo = $pair[1];
		}

		//替换为iframe
		$replace = '<iframe src="'.$html.'?user='.$btn_user.'&amp;repo='.$btn_repo.'&amp;type='.$btn_type.$btn_count.$btn_size.'" height="30" width="'.$btn_width.'" frameborder="0" scrolling="no" style="width:'.$btn_width.'px;height:30px;"></iframe>';

		return $replace;
	}

}