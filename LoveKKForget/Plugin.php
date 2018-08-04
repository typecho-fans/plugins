<?php
/**
 * LoveKK 找回密码功能 for Typecho
 *
 * @package LoveKKForget
 * @author 康康
 * @version 1.0.0
 * @link https://www.lovekk.org
 */

if ( !defined('__TYPECHO_ROOT_DIR__') ) exit;

class LoveKKForget_Plugin implements Typecho_Plugin_Interface {
	/**
	 * 插件激活方法
	 *
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate() {
		// 绑定动作
		Helper::addAction('lovekkforget', 'LoveKKForget_Action');
		// 添加找回密码申请路由
		Helper::addRoute('lovekkforget_forget', '/lovekkforget/forget', 'LoveKKForget_Action', 'forget');
		// 添加充值密码路由
		Helper::addRoute('lovekkforget_reset', '/lovekkforget/reset', 'LoveKKForget_Action', 'reset');
		// 绑定后台页面接口
		Typecho_Plugin::factory('admin/footer.php')->end = array(__CLASS__, 'render');
	}

	/**
	 * 插件禁用方法
	 *
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate() {
		// 删除绑定动作
		Helper::removeAction('lovekkforget');
		// 删除申请路由
		Helper::removeRoute('lovekkforget_forget');
		// 删除重置路由
		Helper::removeRoute('lovekkforget_reset');
	}

	/**
	 * 插件配置方法
	 *
	 * @static
	 * @access public
	 * @param  Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function config(Typecho_Widget_Helper_Form $form) {
		// API_USER
		$api_user = new Typecho_Widget_Helper_Form_Element_Text('api_user', NULL, NULL, _t('SendCloud发信API USER'), _t('请填入在SendCloud生成的API_USER'));
		$form->addInput($api_user);
		// API_KEY
		$api_key = new Typecho_Widget_Helper_Form_Element_Text('api_key', NULL, NULL, _t('SendCloud发信API KEY'), _t('请填入在SendCloud生成的API_KEY'));
		$form->addInput($api_key);
		// 发件人信箱
		$send_form = new Typecho_Widget_Helper_Form_Element_Text('send_form', NULL, NULL, _t('发件人邮件地址'), _t('请尽量保证与SendCloud中配置的发信域名一致'));
		$form->addInput($send_form);
		// 模板名称
		$template = new Typecho_Widget_Helper_Form_Element_Text('template', NULL, NULL, _t('模板名称'), _t('请填入在SendCloud配置的模板名称'));
		$form->addInput($template);
		// 过期时间
		$expire = new Typecho_Widget_Helper_Form_Element_Text('expire', NULL, NULL, _t('过期时间'), _t('设置一个链接过期时间, 超过时间后点击链接将无效, 单位为分钟, 默认10分钟'));
		$form->addInput($expire);
	}

	public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

	/**
	 * 插件实现方法, 显示找回密码链接
	 *
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function render() {
		// 初始化request对象
		$request = Typecho_Request::getInstance();
		// 获取当前请求
		$pathinfo = $request->getRequestUrl();
		// 如果是登录页面则添加忘记密码链接
		if ( preg_match('/\/login\.php/i', $pathinfo) ) {
?>
<script>
var forget = document.createElement('a');
forget.href = '<?php echo Typecho_Common::url('/action/lovekkforget?forget', Helper::options()->index);?>';
var text = document.createTextNode('<?php _e('忘记密码');?>');
forget.appendChild(text);
document.getElementsByClassName('more-link')[0].appendChild(forget);
</script>
<?php
		}
	}
}