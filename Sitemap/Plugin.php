<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Google Sitemap 生成器 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 *
 * @package Sitemap
 * @author NHPT, 迷你日志, Hanny
 * @maintainer NHPT
 * @version 1.0.7
 * @dependence 9.9.2-*
 * @link https://github.com/NHPT
 *
 * version 1.0.7 at 2026-08-21 by NHPT
 * 保持默认动态生成，支持在插件设置中切换为静态文件
 * 动态路由与静态文件统一使用可配置的文件名
 * 静态模式停用动态路由，并在内容变更后自动刷新站点地图文件
 *
 * version 1.0.6 at 2026-08-15 by NHPT
 * 使用 Typecho 内容组件生成文章永久链接
 * 支持按标签文章数量过滤 Sitemap 中的低内容标签页
 *
 * version 1.0.5 at 2022-09-10 by 泽泽社长
 * 分类链接支持{directory}多级分类
 *
 * version 1.0.4 at 2020-07-02 by Typecho Fans (合并多人修改)
 * 调整优先级比例，增加分类页面及首页链接 by 迷你日志/羽中
 * 页面改xml后缀，加入美化样式，简化时间戳 by Suming/八云酱
 *
 * version 1.0.3 at 2017-03-28 by 禾令奇
 * 修改增加标签链接，修改页面权重分级
 *
 * 历史版本
 * version 1.0.1 at 2010-01-02
 * 修改自定义静态链接时错误的Bug
 * version 1.0.0 at 2010-01-02
 * Sitemap for Google
 * 生成文章和页面的Sitemap
 */
class Sitemap_Plugin implements Typecho_Plugin_Interface
{
	private static $refreshScheduled = false;

	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 *
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		self::registerDynamicRoute(self::currentFileName());

		$refresh = array('Sitemap_Plugin', 'scheduleStaticRefresh');
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = $refresh;
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishMark = $refresh;
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishDelete = $refresh;
		Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = $refresh;
		Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishMark = $refresh;
		Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishDelete = $refresh;
	}

	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return void|string
	 */
	public static function deactivate()
	{
		$fileName = self::currentFileName();
		self::removeDynamicRoute();
		require_once __DIR__ . '/Action.php';

		if (!Sitemap_Action::removeStaticFile($fileName)) {
			return _t('插件已禁用，但无法删除其生成的站点地图文件，请手动检查');
		}
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
		require_once __DIR__ . '/Action.php';
		$generateMode = 'dynamic';
		$minTagCountValue = '1';
		$fileNameValue = Sitemap_Action::DEFAULT_FILE_NAME;

		try {
			$options = Typecho_Widget::widget('Widget_Options')->plugin('Sitemap');
			if (in_array($options->generateMode, array('dynamic', 'static'), true)) {
				$generateMode = $options->generateMode;
			}
			if ((int) $options->minTagCount > 0) {
				$minTagCountValue = (string) (int) $options->minTagCount;
			}
			$fileName = Sitemap_Action::normalizeFileName($options->sitemapFileName);
			if (false !== $fileName) {
				$fileNameValue = $fileName;
			}
		} catch (Throwable $error) {
		}

		$mode = new Typecho_Widget_Helper_Form_Element_Radio(
			'generateMode',
			array(
				'dynamic' => _t('动态生成（默认）'),
				'static' => _t('静态文件')
			),
			$generateMode,
			_t('生成模式'),
			_t('动态模式实时生成且不写文件；静态模式保存时立即生成，并在文章或页面发布、状态变更及删除后自动更新。修改分类、标签或由外部程序直接改库后，需要重新保存本设置')
		);
		$form->addInput($mode);

		$fileName = new Typecho_Widget_Helper_Form_Element_Text(
			'sitemapFileName',
			NULL,
			$fileNameValue,
			_t('站点地图文件名'),
			_t('动态路由和根目录静态文件共用此名称；只能包含字母、数字、下划线和横线，并以 .xml 结尾')
		);
		$fileName->addRule(
			array('Sitemap_Plugin', 'validateFileName'),
			_t('文件名格式不正确')
		);
		$form->addInput($fileName);

		$minTagCount = new Typecho_Widget_Helper_Form_Element_Text(
			'minTagCount',
			NULL,
			$minTagCountValue,
			_t('标签最少文章数'),
			_t('仅输出文章数达到该值的标签页，默认 1 表示保留全部非空标签；建议设置为 2 或更高')
		);
		$form->addInput($minTagCount);
	}

	public static function validateFileName($fileName)
	{
		require_once __DIR__ . '/Action.php';
		return false !== Sitemap_Action::normalizeFileName($fileName);
	}

	public static function configHandle($settings, $isInit)
	{
		require_once __DIR__ . '/Action.php';
		$oldFileName = self::currentFileName();
		$fileName = isset($settings['sitemapFileName'])
			? Sitemap_Action::normalizeFileName($settings['sitemapFileName'])
			: false;
		if (false === $fileName) {
			throw new Typecho_Plugin_Exception(
				_t('站点地图文件名不合法，只能使用字母、数字、下划线、横线，并以 .xml 结尾')
			);
		}
		if (!Sitemap_Action::canUseFileName($fileName)) {
			throw new Typecho_Plugin_Exception(_t('站点根目录已存在同名的非本插件文件，请更换文件名'));
		}

		$settings['generateMode'] = isset($settings['generateMode'])
			&& 'static' === $settings['generateMode'] ? 'static' : 'dynamic';
		$settings['minTagCount'] = max(1, (int) $settings['minTagCount']);
		$settings['sitemapFileName'] = $fileName;

		if ('static' === $settings['generateMode']) {
			if (!Sitemap_Action::generateStatic($settings['minTagCount'], $fileName)) {
				throw new Typecho_Plugin_Exception(
					_t('无法生成站点地图文件，请检查目录权限或移除同名的非本插件文件')
				);
			}

			if ($oldFileName !== $fileName && !Sitemap_Action::removeStaticFile($oldFileName)) {
				Sitemap_Action::removeStaticFile($fileName);
				throw new Typecho_Plugin_Exception(_t('无法删除插件生成的旧站点地图文件，请检查文件权限'));
			}
			self::removeDynamicRoute();
		} else {
			if (!Sitemap_Action::removeStaticFile($oldFileName)) {
				throw new Typecho_Plugin_Exception(_t('无法删除插件生成的站点地图文件，请检查文件权限'));
			}
			if ($oldFileName !== $fileName && !Sitemap_Action::removeStaticFile($fileName)) {
				throw new Typecho_Plugin_Exception(_t('无法清理新路由对应的站点地图文件，请检查文件权限'));
			}
			self::registerDynamicRoute($fileName);
		}

		if (class_exists('\\Widget\\Plugins\\Edit')) {
			\Widget\Plugins\Edit::configPlugin('Sitemap', $settings);
		} else {
			Widget_Plugins_Edit::configPlugin('Sitemap', $settings);
		}
	}

	public static function scheduleStaticRefresh()
	{
		if (self::$refreshScheduled || !self::isStaticMode()) {
			return;
		}

		self::$refreshScheduled = true;
		register_shutdown_function(array('Sitemap_Plugin', 'refreshStatic'));
	}

	public static function refreshStatic()
	{
		self::$refreshScheduled = false;

		try {
			$options = Typecho_Widget::widget('Widget_Options')->plugin('Sitemap');
			require_once __DIR__ . '/Action.php';
			$fileName = Sitemap_Action::normalizeFileName($options->sitemapFileName);
			if (
				false === $fileName
				|| !Sitemap_Action::generateStatic(max(1, (int) $options->minTagCount), $fileName)
			) {
				error_log('Sitemap: failed to refresh static sitemap file');
			}
		} catch (Throwable $error) {
			error_log('Sitemap: ' . $error->getMessage());
		}
	}

	private static function isStaticMode()
	{
		try {
			return 'static' === Typecho_Widget::widget('Widget_Options')
				->plugin('Sitemap')->generateMode;
		} catch (Throwable $error) {
			return false;
		}
	}

	private static function currentFileName()
	{
		require_once __DIR__ . '/Action.php';

		try {
			$fileName = Typecho_Widget::widget('Widget_Options')
				->plugin('Sitemap')->sitemapFileName;
			$fileName = Sitemap_Action::normalizeFileName($fileName);
			if (false !== $fileName) {
				return $fileName;
			}
		} catch (Throwable $error) {
		}

		return Sitemap_Action::DEFAULT_FILE_NAME;
	}

	private static function registerDynamicRoute($fileName)
	{
		$fileName = Sitemap_Action::normalizeFileName($fileName);
		if (false === $fileName) {
			$fileName = Sitemap_Action::DEFAULT_FILE_NAME;
		}

		Helper::addRoute('sitemap', '/' . $fileName, 'Sitemap_Action', 'action');
	}

	private static function removeDynamicRoute()
	{
		Helper::removeRoute('sitemap');
	}

	/**
	 * 个人用户的配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form)
	{
	}
}
