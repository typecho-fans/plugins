<?php
/**
 * LoveKK 找回密码功能 for Typecho
 *
 * @package LoveKKForget
 * @author 康粑粑
 * @version 1.0.1
 * @link https://www.lovekk.org
 */

if ( !defined('__TYPECHO_ROOT_DIR__') ) exit;
// 当前版本号
define('VERSION', '1.0.1');

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
		?>
        <style>.message {
                padding: 10px;
                background-color: #fff;
                box-shadow: 2px 2px 5px #888;
                font-size: 1pc;
                line-height: 1.875rem
            }

            .message span {
                display: block;
                color: #1abc9c
            }

            .message span pre {
                margin: 0;
                padding: 0;
                color: #ee5c42
            }

            .message li, .message p {
                margin: 0;
                padding: 0;
                line-height: 1.5rem
            }</style>
        <div class="message">
            <div id="update_txt">当前版本: <?php _e(VERSION); ?>, 正在检测版本更新...</div>
            <span id="update_notice"></span>
            <span id="update_body"></span>
        </div>
        <script src="//cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
        <script src="//cdn.bootcss.com/marked/0.3.12/marked.min.js"></script>
        <script>
            $(function () {
                $.getJSON(
                    'https://git.wskehao.com/api/v1/repos/ylqjgm/LoveKKForget/releases',
                    function (data) {
                        if (checkUpdater('<?php _e(VERSION);?>', data[0].tag_name)) {
                            $('#update_notice').html('有新版本可用, <a href="' + data[0].zipball_url + '" target="_blank">点此下载 ' + data[0].tag_name + ' 版本</a>');
                            $('#update_body').html('版本说明: ' + marked(data[0].body));
                        } else {
                            $('#update_txt').html('当前版本: <?php _e(VERSION);?>, 当前没有新版本');
                        }
                    }
                );
            });

            // 版本比较
            function checkUpdater(currVer, remoteVer) {
                currVer = currVer || '0.0.0';
                remoteVer = remoteVer || '0.0.0';
                if (currVer == remoteVer) return false;
                var currVerAry = currVer.split('.');
                var remoteVerAry = remoteVer.split('.');
                var len = Math.max(currVerAry.length, remoteVerAry.length);
                for (var i = 0; i < len; i++) {
                    if (~~remoteVerAry[i] > ~~currVerAry[i]) return true;
                }

                return false;
            }
        </script>
        <?php
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