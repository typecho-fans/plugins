<?php
if ( !defined('__TYPECHO_ROOT_DIR__') ) exit;

class LoveKKForget_Action extends Widget_Abstract_Users implements Widget_Interface_Do {
	/**
	 * 插件配置
	 *
	 * @access private
	 * @var mixed
	 */
	private $_plugin = null;

	/**
	 * 构造方法
	 */
	public function __construct($request, $response, $params = NULL) {
		parent::__construct($request, $response, $params);
		// 获取插件配置信息
		$this->_plugin = $this->options->plugin('LoveKKForget');
	}

	/**
	 * 页面输出代码
	 *
	 * @access private
	 * @param  string $act 当前操作
	 * @param  mixed  $form 表单对象
	 * @return void
	 */
	private function html($act = 'forget', $form = null) {
?>
<!DOCTYPE html>
<html class="no-js">
<head>
<meta charset="<?php $this->options->charset();?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="renderer" content="webkit">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php _e('%s - %s - Powered by Typecho', 'reset' == $act ? _t('重置密码') : _t('找回密码'), $this->options->title); ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?php echo Typecho_Common::url('normalize.css', $this->options->adminStaticUrl('css'));?>">
<link rel="stylesheet" href="<?php echo Typecho_Common::url('grid.css', $this->options->adminStaticUrl('css'));?>">
<link rel="stylesheet" href="<?php echo Typecho_Common::url('style.css', $this->options->adminStaticUrl('css'));?>">
<!--[if lt IE 9]>
<script src="<?php echo Typecho_Common::url('html5shiv.js', $this->options->adminStaticUrl('js'));?>"></script>
<script src="<?php echo Typecho_Common::url('respond.js', $this->options->adminStaticUrl('js'));?>"></script>
<![endif]-->
</head>
<body class="body-100">
<!--[if lt IE 9]>
<div class="message error browsehappy" role="dialog"><?php _e('当前网页 <strong>不支持</strong> 你正在使用的浏览器. 为了正常的访问, 请 <a href="http://browsehappy.com/">升级你的浏览器</a>'); ?>.</div>
<![endif]-->
<div class="typecho-login-wrap">
	<div class="typecho-login">
		<h1><a href="http://typecho.org" class="i-logo">Typecho</a></h1>
		<?php $form->render();?>
	</div>
</div>
<script src="<?php $this->options->adminStaticUrl('js', 'jquery.js'); ?>"></script>
<script src="<?php $this->options->adminStaticUrl('js', 'jquery-ui.js'); ?>"></script>
<script src="<?php $this->options->adminStaticUrl('js', 'typecho.js'); ?>"></script>
<script>
(function () {
	$(document).ready(function() {
		(function () {
			var prefix = '<?php echo Typecho_Cookie::getPrefix();?>',
			cookies = {
				notice      :   $.cookie(prefix + '__typecho_notice'),
				noticeType  :   $.cookie(prefix + '__typecho_notice_type'),
				highlight   :   $.cookie(prefix + '__typecho_notice_highlight')
			},
			path = '<?php echo Typecho_Cookie::getPath(); ?>';
			if (!!cookies.notice && 'success|notice|error'.indexOf(cookies.noticeType) >= 0) {
				var head = $('.typecho-head-nav'),
				p = $('<div class="message popup ' + cookies.noticeType + '">'
					+ '<ul><li>' + $.parseJSON(cookies.notice).join('</li><li>') 
					+ '</li></ul></div>'), offset = 0;
				if (head.length > 0) {
					p.insertAfter(head);
					offset = head.outerHeight();
				} else {
					p.prependTo(document.body);
				}
				function checkScroll () {
					if ($(window).scrollTop() >= offset) {
						p.css({
							'position'  :   'fixed',
							'top'       :   0
						});
					} else {
						p.css({
							'position'  :   'absolute',
							'top'       :   offset
						});
					}
				}
				$(window).scroll(function () {
					checkScroll();
				});
				checkScroll();
				p.slideDown(function () {
					var t = $(this), color = '#C6D880';
					if (t.hasClass('error')) {
						color = '#FBC2C4';
					} else if (t.hasClass('notice')) {
						color = '#FFD324';
					}
					t.effect('highlight', {color : color})
						.delay(5000).fadeOut(function () {
							$(this).remove();
					});
				});
				$.cookie(prefix + '__typecho_notice', null, {path : path});
				$.cookie(prefix + '__typecho_notice_type', null, {path : path});
			}
			if (cookies.highlight) {
				$('#' + cookies.highlight).effect('highlight', 1000);
				$.cookie(prefix + '__typecho_notice_highlight', null, {path : path});
			}
		})();
		(function () {
			$('#typecho-nav-list').find('.parent a').focus(function() {
				$('#typecho-nav-list').find('.child').hide();
				$(this).parents('.root').find('.child').show();
			});
			$('.operate').find('a').focus(function() {
				$('#typecho-nav-list').find('.child').hide();
			});
		})();
		if ($('.typecho-login').length == 0) {
			$('a').each(function () {
				var t = $(this), href = t.attr('href');
				if ((href && href[0] == '#')
					|| /^<?php echo preg_quote($this->options->adminUrl, '/'); ?>.*$/.exec(href) 
					|| /^<?php echo substr(preg_quote(Typecho_Common::url('s', $this->options->index), '/'), 0, -1); ?>action\/[_a-zA-Z0-9\/]+.*$/.exec(href)) {
					return;
				}
				t.attr('target', '_blank');
			});
		}
	});
})();
</script>
<?php if ( 'forget' == $act ) :?><script>$(document).ready(function () {$('#mail').focus();});</script><?php endif;?>
</body>
</html>
<?php
	}

	/**
	 * 忘记密码表单
	 *
	 * @access private
	 * @return void
	 */
	private function forgetForm() {
		// 创建表单
		$form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/lovekkforget'), Typecho_Widget_Helper_Form::POST_METHOD);
		// 邮箱地址
		$mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL, _t('邮箱地址'), _t('请输入您注册时的邮箱地址'));
		// 添加class
		$mail->input->setAttribute('class', 'text-l w-100');
		// 添加规则
		$mail->addRule('required', _t('必须输入您的邮箱地址'));
		$mail->addRule('email', _t('请输入正确的邮箱格式'));
		$form->addInput($mail);
		// 动作
		$do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'forget');
		$form->addItem($do);
		// 提交按钮
		$submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('提交'));
		// 添加class
		$submit->input->setAttribute('class', 'btn btn-l w-100 primary');
		$form->addItem($submit);

		return $form;
	}

	/**
	 * 重置密码表单
	 *
	 * @access private
	 * @param  integer $uid 用户编号
	 * @return void
	 */
	private function resetForm($uid = 0) {
		// 创建表单
		$form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/lovekkforget'), Typecho_Widget_Helper_Form::POST_METHOD);
		// 登录密码
		$password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL, _t('用户密码'), _t('建议使用特殊字符与字母、数字的混编样式,以增加系统安全性.'));
		// 设置class
		$password->input->setAttribute('class', 'text-l w-100');
		// 添加规则
		$password->addRule('required', _t('必须输入您的邮箱地址'));
		$password->addRule('minLength', _t('为了保证账户安全, 请设置最少8位数的密码'), 8);
		$form->addInput($password);
		// 密码确认
		$confirm = new Typecho_Widget_Helper_Form_Element_Password('confirm', NULL, NULL, _t('用户密码确认'), _t('请确认您的密码, 与上面输入的密码保持一致.'));
		// 设置class
		$confirm->input->setAttribute('class', 'text-l w-100');
		// 添加规则
		$confirm->addRule('confirm', _t('您两次输入的密码不一致, 请重新输入'), 'password');
		$form->addInput($confirm);
		// 动作
		$do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'reset');
		$form->addItem($do);
		// uid
		$uid = new Typecho_Widget_Helper_Form_Element_Hidden('uid', NULL, $uid);
		$form->addItem($uid);
		// 提交按钮
		$submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('提交'));
		// 添加class
		$submit->input->setAttribute('class', 'btn btn-l w-100 primary');
		$form->addItem($submit);

		return $form;
	}

	/**
	 * 忘记密码提交动作
	 *
	 * @access private
	 * @return void
	 */
	private function doForget() {
		// 验证表单
		if ( $error = $this->forgetForm()->validate() ) {
			// 显示错误信息
			$this->widget('Widget_Notice')->set($error, 'error');
			// 返回上一页
			$this->response->goBack();
		}
		// 查询用户数据
		$user = $this->db->fetchRow($this->select()->where('mail = ?', $this->request->mail));
		// 没有用户
		if ( !$user ) {
			// 输出错误
			$this->widget('Widget_Notice')->set(_t('邮箱地址错误, 请核对后重新输入'), 'error');
			// 返回上一页
			$this->response->goBack();
		}
		// 过期时间
		$expire = $this->_plugin->expire ? $this->_plugin->expire : 10;
		// 转换为秒数
		$time = time() + $expire * 60;
		// 构造参数
		$query = array(
			'reset' => 'true',
			't' => md5($user['uid'] . $user['name'] . $user['mail'] . $time),
			'm' => $user['mail'],
			'e' => $time
		);
		// 生成链接地址
		$uri = Typecho_Common::url('/action/lovekkforget?' . http_build_query($query), $this->options->index);
		// SendCloud请求扩展字段
		$xsmtpapi = json_encode(
			array(
				'to' => array($user['mail']),
				'sub' => array(
					'%blogname%' => array(trim($this->options->title)),
					'%blogurl%' => array(trim($this->options->siteUrl)),
					'%mail%' => array(trim($user['mail'])),
					'%sendtime%' => array(trim(date('Y-m-d H:i:s', time()))),
					'%resetlink%' => array(trim($uri)),
					'%expire%' => array(trim($expire))
				)
			)
		);
		// 请求参数
		$param = array(
			'apiUser' => $this->_plugin->api_user,
			'apiKey' => $this->_plugin->api_key,
			'from' => $this->_plugin->send_form,
			'fromName' => $this->options->title,
			'subject' => '您在 [' . $this->options->title . '] 提交的密码找回申请!',
			'xsmtpapi' => $xsmtpapi,
			'templateInvokeName' => $this->_plugin->template
		);
		// 使用curl发送
		$ch = curl_init();
		// 请求地址
		curl_setopt($ch, CURLOPT_URL, 'http://api.sendcloud.net/apiv2/mail/sendtemplate');
		// 返回
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 提交方式
		curl_setopt($ch, CURLOPT_POST, 1);
		// 提交参数
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		// 执行请求
		$result = curl_exec($ch);
		// 获取错误代码
		$errno = curl_errno($ch);
		// 获取错误信息
		$error = curl_error($ch);
		// 关闭curl
		curl_close($ch);
		// 请求错误
		if ( $errno ) {
			// 输出错误信息
			$this->widget('Widget_Notice')->set(_t('邮件发送失败, 错误提示: ' . $error . ', 请联系管理员解决!'), 'error');
			// 跳转回去
			$this->response->goBack();
		}
		// 如果请求成功
		if ( $json = json_decode($result) ) {
			// 如果请求成功
			if ( 200 == $json->statusCode ) {
				// 输出提示
				$this->widget('Widget_Notice')->set(_t('已将重置密码信息发送至您的注册邮箱中, 请注意查收!'), 'success');
				// 跳转回去
				$this->response->goBack();
			}
			// 输出错误信息
			$this->widget('Widget_Notice')->set(_t('邮件发送失败, 错误提示: ' . $json->message . ', 请联系管理员解决!'), 'error');
			// 跳转回去
			$this->response->goBack();
		}
		// 输出提示
		$this->widget('Widget_Notice')->set(_t('已将重置密码信息发送至您的注册邮箱中, 请注意查收!'), 'success');
		// 跳转回去
		$this->response->goBack();
	}

	/**
	 * 重置密码界面
	 *
	 * @access private
	 * @return void
	 */
	private function reset() {
		// 获取过期时间
		$expire = $this->request->filter('int')->e;
		// 如果链接过期则输出错误
		if ( time() > $expire ) {
			// 输出错误
			$this->widget('Widget_Notice')->set(_t('抱歉, 您所提交的重置密码链接已过期, 请重新获取'), 'notice');
			// 跳转到找回密码界面
			$this->response->redirect(Typecho_Common::url('/action/lovekkforget?forget', $this->options->index));
		}
		// 查询用户数据
		$user = $this->db->fetchRow($this->select()->where('mail = ?', $this->request->m));
		// 没有用户
		if ( !$user ) {
			// 输出错误
			$this->widget('Widget_Notice')->set(_t('抱歉, 您的请求有误'), 'error');
			// 返回登录界面
			$this->resopnse->redirect($this->options->loginUrl);
		}
		// 取出数据
		$token = $this->request->filter('strip_tags', 'trim', 'xss')->t;
		// 如果验证不通过
		if ( $token != md5($user['uid'] . $user['name'] . $user['mail'] . $expire) ) {
			// 输出错误
			$this->widget('Widget_Notice')->set(_t('抱歉, 您的请求验证错误'), 'error');
			// 返回登录界面
			$this->resopnse->redirect($this->options->loginUrl);
		}
		// 显示重置界面
		$this->html('reset', $this->resetForm($user['uid']));
	}

	/**
	 * 重置密码动作
	 *
	 * @access private
	 * @return void
	 */
	private function doReset() {
		// 验证表单
		if ( $error = $this->resetForm()->validate() ) {
			// 显示错误信息
			$this->widget('Widget_Notice')->set($error, 'error');
			// 返回上一页
			$this->response->goBack();
		}
		// 获取用户uid
		$uid = $this->request->filter('integer')->uid;
		// 验证uid
		if ( !$uid ) {
			// 显示错误信息
			$this->widget('Widget_Notice')->set(_t('抱歉, 您的请求验证失败'), 'error');
			// 返回上一页
			$this->response->goBack();
		}
		echo '2';
		// 初始化passwordhash
		$hasher = new PasswordHash(8, true);
		// 密码加密
		$password = $hasher->HashPassword($this->request->password);
		// 更新密码
		if ( $this->update(array('password' => $password), $this->db->sql()->where('uid = ?', $uid)) ) {
			// 显示成功信息
			$this->widget('Widget_Notice')->set(_t('密码重置成功'), 'success');
			// 跳转登录页面
			$this->response->redirect($this->options->loginUrl);
		}
		echo '3';
		// 显示错误信息
		$this->widget('Widget_Notice')->set(_t('密码重置失败, 请联系管理员'), 'error');
		// 跳转登录页面
		$this->response->redirect($this->options->loginUrl);
	}

	/**
	 * 操作动作方法
	 *
	 * @access public
	 * @return void
	 */
	public function action() {
		// 如果用户是登录状态则直接跳转至个人信息界面
		if ( $this->user->hasLogin() ) $this->response->redirect($this->options->profileUrl);
		// 动作必须是POST提交
		if ( $this->request->isPost() ) {
			// 忘记密码请求
			$this->on($this->request->is('do=forget'))->doForget();
			// 重置密码请求
			$this->on($this->request->is('do=reset'))->doReset();
		}
		// 忘记密码界面
		if ( $this->request->is('forget') ) $this->html('forget', $this->forgetForm());
		// 重置密码界面
		if ( $this->request->is('reset') ) $this->reset();
	}
}