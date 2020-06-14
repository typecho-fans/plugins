<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 读取Github上维护的专用表格实现插件仓库各项功能
 * 
 * @package TeStore
 * @author 羽中, zhulin3141
 * @version 1.1.5
 * @dependence 13.12.12-*
 * @link https://www.yzmb.me/archives/net/testore-for-typecho
 * @copyright Copyright (c) 2014-2020 Yuzhong Zheng (jzwalk)
 * @license MIT
 */
class TeStore_Plugin implements Typecho_Plugin_Interface
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
		$tempDir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/TeStore/.tmp';
		$dataDir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/TeStore/data';

		if (!class_exists('ZipArchive')) {
			return _t('主机未安装ZipArchive扩展, 无法安装插件');
		}

		if (!is_dir($tempDir) && !@mkdir($tempDir)) {
			throw new Typecho_Plugin_Exception('无法创建临时目录.');
		}

		if(!self::testWrite($tempDir)){
			throw new Typecho_Plugin_Exception('.tmp目录没有写入权限');
		}

		Typecho_Plugin::factory('admin/menu.php')->navBar = array('TeStore_Plugin','render');
		Helper::addPanel(1,'TeStore/market.php',_t('TE插件仓库'),_t('TE插件仓库'),'administrator');
		Helper::addRoute('te-store_market',__TYPECHO_ADMIN_DIR__.'te-store/market','TeStore_Action','market');
		Helper::addRoute('te-store_install',__TYPECHO_ADMIN_DIR__.'te-store/install','TeStore_Action','install');
		Helper::addRoute('te-store_uninstall',__TYPECHO_ADMIN_DIR__.'te-store/uninstall','TeStore_Action','uninstall');
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
		Helper::removePanel(1,'TeStore/market.php');
		Helper::removeRoute('te-store_market');
		Helper::removeRoute('te-store_install');
		Helper::removeRoute('te-store_uninstall');
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
		$source = new Typecho_Widget_Helper_Form_Element_Textarea('source',
		NULL,'https://github.com/typecho-fans/plugins/blob/master/TESTORE.md'.PHP_EOL.'https://github.com/typecho-fans/plugins/blob/master/README.md',_t('插件信息来源'),
		_t('应为可公开访问且包含符合本插件规定表格内容的页面地址, 每行一个, 默认: ').'<br/>
		<strong><a href="https://github.com/typecho-fans/plugins/blob/master/README.md">https://github.com/typecho-fans/plugins/blob/master/README.md</a> - <span class="warning">'._t('Typecho-Fans内部插件索引(社区维护版列表)').'</span><br/>
		<a href="https://github.com/typecho-fans/plugins/blob/master/TESTORE.md">https://github.com/typecho-fans/plugins/blob/master/TESTORE.md</a> - <span class="warning">'._t('Typecho-Fans外部插件登记表(TeStore专用)').'</span></strong><br/>
		'._t('以上Markdown语法文件在Github上由多人共同维护, 参与方式详见文件说明'));
		$source->addRule('required',_t('文件地址不能为空'));
		$form->addInput($source);

		$cache = new Typecho_Widget_Helper_Form_Element_Select('cache_time',
			array(
				'0'=>_t('不缓存'),
				'6'=>_t('6小时'),
				'12'=>_t('12小时'),
				'24'=>_t('1天'),
				'72'=>_t('3天'),
				'168'=>_t('1周')
			),
			'24',_t('数据缓存时限'),_t('设置本地缓存数据时间'));
		$form->addInput($cache);

		$proxy = new Typecho_Widget_Helper_Form_Element_Radio('proxy',
		array(''=>_t('否'),'cdn.jsdelivr.net/gh'=>_t('jsDelivr镜像'),'gitcdn.xyz/repo'=>_t('GitCDN镜像1'),'gitcdn.link/repo'=>_t('GitCDN镜像2')),'',_t('使用代理加速'),_t('GitHub连接不畅时可选'));
		$form->addInput($proxy);

		$curl = new Typecho_Widget_Helper_Form_Element_Checkbox('curl',
		array(1=>'是'),0,	_t('cURL方式下载'),_t('默认方式无效时可尝试'));
		$form->addInput($curl);

		$showNavMenu = new Typecho_Widget_Helper_Form_Element_Radio('showNavMenu',
		array(1=>_t('显示'),0=>_t('关闭')),1,_t('导航快捷按钮'));
		$form->addInput($showNavMenu);
	}

	/**
	 * 检查cURL支持
	 * 
	 * @param array $settings
	 * @return string
	 */
	public static function configCheck(array $settings)
	{
		if (!class_exists('ZipArchive')) {
			return _t('主机未安装ZipArchive扩展, 无法安装插件');
		}
		if ($settings['curl'] && !extension_loaded('curl')) {
			return _t('主机未安装cURL扩展, 无法使用此方式下载');
		}
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
	 * 输出导航按钮
	 * 
	 * @access public
	 * @return void
	 */
	public static function render()
	{
		$options = Helper::options();
		if ($options->plugin('TeStore')->showNavMenu && Typecho_Widget::widget('Widget_User')->pass('administrator',true)){
			echo '<a href="'.$options->adminUrl.'extending.php?panel=TeStore%2Fmarket.php"><span class="message notice"><i class="mime-script"></i>'._t('TE插件仓库').'</span></a>';
		}
	}

	/**
	 * 判断目录可写
	 * 
	 * @access public
	 * @return boolean
	 */
	public static function testWrite($dir)
	{
		$testFile = "_test.txt";
		$fp = @fopen($dir."/".$testFile,"w");
		if (!$fp) {
			return false;
		}
		fclose($fp);
		$rs = @unlink($dir."/".$testFile);
		if ($rs) {
			return true;
		}
		return false;
	}

}