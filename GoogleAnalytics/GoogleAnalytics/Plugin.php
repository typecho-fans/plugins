<?php
/**
 * Typecho 的 Google Analytics 加速器
 *
 * @package GoogleAnalytics
 * @author WeiCN
 * @version 1.0.0
 * @link https://cuojue.org/read/typecho_plugin_ga.html
 */
 class GoogleAnalytics_Plugin implements Typecho_Plugin_Interface
 {

    /** @var bool 请求适配器 */
	private static $_adapter    = false;

	 /**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 *
	 * @access public
	 * @return String
	 * @throws Typecho_Plugin_Exception
	 */
	 public static function activate()
	{
        if (false == self::isAvailable()) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机没有打开 allow_url_fopen 功能而且不支持 php-curl 扩展, 无法正常使用此功能'));
        }
		Helper::addRoute('Analytics', '/Analytics', 'GoogleAnalytics_Action', 'Action');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('GoogleAnalytics_Plugin', 'footer');
	}
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return String
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate()
	{
		Helper::removeRoute('Analytics');
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
		$gaid =  new Typecho_Widget_Helper_Form_Element_Text('gaid', NULL, _t(''), _t('GoogleAnalytics ID'), _t('UA-XXXXXX-1'));
		$form->addInput($gaid);
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
     * 检测 适配器
     * @return string
     */
    public static function isAvailable()
    {
        function_exists('ini_get') && ini_get('allow_url_fopen') && (self::$_adapter = 'Socket');
        false == self::$_adapter && function_exists('curl_version') && (self::$_adapter = 'Curl');
        
        return self::$_adapter;
	}

	public static function footer(){
		$options = Typecho_Widget::widget('Widget_Options');
		$pluginOption = Typecho_Widget::widget('Widget_Options')->Plugin('GoogleAnalytics');
		$pluginOption = unserialize($pluginOption);
		$gaid = $pluginOption['gaid'];
		$url = ($options->rewrite) ? $options->siteUrl : $options->siteUrl . 'index.php';
		$url = rtrim($url, '/') . '/Analytics';
	?>
		<script>
		function ga(c, d, e) {
		var f = c.screen,
			g = encodeURIComponent,
			h = ["ga=<?=$gaid?>", "dt=" + g(d.title), "dr=" + g(d.referrer), "ul=" + (e.language || e.browserLanguage || e.userLanguage), "sd=" + f.colorDepth + "-bit", "sr=" + f.width + "x" + f.height, "vp=" + Math.max(d.documentElement.clientWidth, c.innerWidth || 0) + "x" + Math.max(d.documentElement.clientHeight, c.innerHeight || 0), "z=" + +new Date];
			c.__ga_img = new Image, c.__ga_img.src = "<?=$url?>?" + h.join("&")
		}
		ga(window, document, navigator, location);
		</script>
		
	<?
	}
 }
