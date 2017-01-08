<?php
/**
 * IQapTcha 评论滑动解锁
 * 
 * @package IQapTcha
 * @author Byends
 * @version 1.1.2
 * @link http://www.byends.com
 */
class IQapTcha_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('IQapTcha_Plugin', 'filter');
        Typecho_Plugin::factory('Widget_Archive')->header = array('IQapTcha_Plugin', 'headerScript');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('IQapTcha_Plugin', 'footerScript');
        
        Helper::addRoute('iQapTcha4tyepcho', '/'.self::getCheckStr('slug'), 'IQapTcha_Plugin');
        
		return _t('评论滑动解锁插件启用成功，请配置 评论滑动解锁 相关项');
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
    	Helper::removeRoute('iQapTcha4tyepcho');
    }
    
    public static function execute()
    {
    	$aResponse['code'] = false;
    	$options = Helper::options();
    	$iQapTchaOpt = $options->plugin('IQapTcha');
    	$action = self::getCheckStr('action');
    	$req = Typecho_Request::getInstance();
    	
    	if ( !$req->isAjax() || !$req->isPost() || !$req->is('action='.$action) ) {
    		$msg = $iQapTchaOpt->opt_lock_txt;
    		$aResponse['msg'] = $msg;
    	}
    	else{
    		$aResponse['code'] = true;
    		$msg = $iQapTchaOpt->opt_unlock_txt;
    		$aResponse['msg'] = $msg;
    		
    		@session_start();
    		$_SESSION[$action] = $req->get('iQaptcha');
    	}
    	
    	echo json_encode($aResponse);
    	exit;
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
		//jquery 设置
		$opt_jq_set = new Typecho_Widget_Helper_Form_Element_Radio('opt_jq_set', array('0'=> '自己处理', '1'=> '自动载入'), 1, 'jQuery 来源', '如果选择 【自动载入】，会从开放静态文件CDN自动载入 jQurey 1.8.3 到 header<br />http://cdn.staticfile.org/jquery/1.8.3/jquery.min.js');
		$form->addInput($opt_jq_set);
		
		//IQapTcha 配置
		$opt_admin_unlock = new Typecho_Widget_Helper_Form_Element_Radio('opt_admin_unlock', array("true" => "是", "false" => "否"), "false", _t('博主是否无须上锁'), '若选择 【是】，博主登录后 IQapTcha 将不再显示，且不进行相应的 session 检验');
		$form->addInput($opt_admin_unlock);
		
		$opt_autoSubmit = new Typecho_Widget_Helper_Form_Element_Radio('opt_autoSubmit', array("true" => "是", "false" => "否"), "false", _t('解锁后立即提交评论'), '若选择 【是】，IQapTcha 解锁后将立即自动提交评论');
		$form->addInput($opt_autoSubmit);
		
		$opt_lock_txt = new Typecho_Widget_Helper_Form_Element_Text('opt_lock_txt', NULL, '发表评论前，请滑动滚动条解锁', _t('IQapTcha 解锁前的提示'));
		$form->addInput( $opt_lock_txt->addRule('required', _t('IQapTcha 解锁前的提示不能为空')) );
		
		$opt_unlock_txt = new Typecho_Widget_Helper_Form_Element_Text('opt_unlock_txt', NULL, '已解锁，可以发表评论了', _t('IQapTcha 解锁后的提示'));
		$form->addInput( $opt_unlock_txt->addRule('required', _t('IQapTcha 解锁后的提示不能为空')) );
		
		$opt_autoRevert = new Typecho_Widget_Helper_Form_Element_Radio('opt_autoRevert', array("true" => "是", "false" => "否"), "true", _t('滚动条是否自动回滚'), '在拖动 IQapTcha 滚动条中途释放时，滚动条是否自动回滚');
		$form->addInput($opt_autoRevert);
		
		$opt_disabledSubmit = new Typecho_Widget_Helper_Form_Element_Radio('opt_disabledSubmit', array("false" => "是", "true" => "否"), "false", _t('提交按钮是否可用'), 'IQapTcha 未解锁时，评论提交按钮是否可用');
		$form->addInput($opt_disabledSubmit);
		
		//屏蔽IP操作
        $opt_ip = new Typecho_Widget_Helper_Form_Element_Radio('opt_ip', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('屏蔽IP操作'), "如果评论发布者的IP在屏蔽IP段，将执行该操作");
        $form->addInput($opt_ip);
        
        $opt_ip_txt = new Typecho_Widget_Helper_Form_Element_Text('opt_ip_txt', NULL, '你的IP已被管理员屏蔽！', _t('屏蔽IP后的提示'), _t('仅当屏蔽IP操作为 【评论失败】 时有效'));
        $form->addInput( $opt_ip_txt->addRule('required', _t('屏蔽IP后的提示不能为空')) );

        $wordsIp = new Typecho_Widget_Helper_Form_Element_Textarea('words_ip', NULL, "0.0.0.0",
			_t('屏蔽IP'), _t('多条IP请用换行符隔开<br />支持用*号匹配IP段，如：192.168.*.*'));
        $form->addInput($wordsIp);
        
		//非中文评论操作
        $opt_nocn = new Typecho_Widget_Helper_Form_Element_Radio('opt_nocn', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('非中文评论操作'), "如果评论中不包含中文，则强行按该操作执行");
        $form->addInput($opt_nocn);
        
        $opt_nocn_txt = new Typecho_Widget_Helper_Form_Element_Text('opt_nocn_txt', NULL, '评论内容不能少于2个汉字！', _t('非中文评论的提示'), _t('仅当非中文评论操作为 【评论失败】 时有效'));
        $form->addInput( $opt_nocn_txt->addRule('required', _t('非中文评论的提示不能为空')) );
        
		//禁止词汇操作
        $opt_ban = new Typecho_Widget_Helper_Form_Element_Radio('opt_ban', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('禁止词汇操作'), "如果评论中包含禁止词汇列表中的词汇，将执行该操作");
        $form->addInput($opt_ban);
		
        $opt_ban_txt = new Typecho_Widget_Helper_Form_Element_Text('opt_ban_txt', NULL, '评论内容包含禁止词汇！', _t('禁止词汇后的提示'), _t('仅当禁止词汇操作为 【评论失败】 时有效'));
        $form->addInput( $opt_ban_txt->addRule('required', _t('禁止词汇后的提示不能为空')) );
        
        $words_ban = new Typecho_Widget_Helper_Form_Element_Textarea('words_ban', NULL, "fuck\n操你妈\n[url\n[/url]",
			_t('禁止词汇'), _t('多条词汇请用换行符隔开'));
        $form->addInput($words_ban);
		
        //感词汇操作
        $opt_chk = new Typecho_Widget_Helper_Form_Element_Radio('opt_chk', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('敏感词汇操作'), "如果评论中包含敏感词汇列表中的词汇，将执行该操作");
        $form->addInput($opt_chk);

        $opt_chk_txt = new Typecho_Widget_Helper_Form_Element_Text('opt_chk_txt', NULL, '评论内容包含敏感词汇！', _t('包含敏感词汇的提示'), _t('仅当敏感词汇操作为 【评论失败】 时有效'));
        $form->addInput( $opt_chk_txt->addRule('required', _t('包含敏感词汇的提示不能为空')) );
        
        $words_chk = new Typecho_Widget_Helper_Form_Element_Textarea('words_chk', NULL, "http://",
			_t('敏感词汇'), _t('多条词汇请用换行符隔开<br />注意：如果词汇同时出现于禁止词汇，则执行禁止词汇操作'));
        $form->addInput($words_chk);
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
     * 自定义 header
     *
     * @access public
     * @return void
     */
    public static function headerScript()
    {
    	if (Typecho_Widget::widget('Widget_Archive')->is('single')) {
    		
    		$user = Typecho_Widget::widget('Widget_User');
    		$options = Helper::options();
    		$iQapTchaOpt = $options->plugin('IQapTcha');
    		
    		if( $iQapTchaOpt->opt_admin_unlock == 'false' || !($user->hasLogin() && $user->pass('administrator'))) {
    			
    			$pluginUrl = Typecho_Common::url('IQapTcha/static', $options->pluginUrl);
		    	if ($iQapTchaOpt->opt_jq_set == 1) {
		    		echo "<script type=\"text/javascript\" src=\"http://cdn.staticfile.org/jquery/1.8.3/jquery.min.js\"></script>\n";
		    	}
		    	echo '<link rel="stylesheet" type="text/css" media="all" href="'.$pluginUrl.'/QapTcha.jquery.css" />'."\n";
    		}
    	}
    }
    
    /**
     * 自定义 footer
     *
     * @access public
     * @return void
     */
    public static function footerScript()
    {
    	if (Typecho_Widget::widget('Widget_Archive')->is('single')) {
    		
    		$user = Typecho_Widget::widget('Widget_User');
    		$options = Helper::options();
    		$iQapTchaOpt = $options->plugin('IQapTcha');
    		
    		if( $iQapTchaOpt->opt_admin_unlock == 'false' || !($user->hasLogin() && $user->pass('administrator'))) {
    		
	    		$pluginUrl = Typecho_Common::url('IQapTcha/static', $options->pluginUrl);
	    		$url = Typecho_Router::url('iQapTcha4tyepcho', array(), $options->index);
	    		$action = self::getCheckStr('action');
    		
	    		$script = <<<EOT
\n<script type="text/javascript" src="{$pluginUrl}/jquery-ui.js"></script>
<script type="text/javascript" src="{$pluginUrl}/jquery.ui.touch.js"></script>
<script type="text/javascript" src="{$pluginUrl}/QapTcha.jquery.js"></script>
<script>
(function($){
    $(document).ready(function() {
        if ( ! $('#QapTcha').is('div')) {
            $('#comment_form textarea').parent().before('<div id="QapTcha"></div>\\n');
        }
        $('#QapTcha').QapTcha({
            txtLock : '{$iQapTchaOpt->opt_lock_txt}',
            txtUnlock : '{$iQapTchaOpt->opt_unlock_txt}',
            disabledSubmit : {$iQapTchaOpt->opt_disabledSubmit},
            autoRevert: {$iQapTchaOpt->opt_autoRevert},
            autoSubmit : {$iQapTchaOpt->opt_autoSubmit},
            url : '{$url}',
            action  : '{$action}'
        });
    });
})(jQuery)
</script>
EOT;
    			echo $script;
    		}
    	}
    }
    
    /**
     * 评论过滤器
     * 
     */
    public static function filter($comment, $post)
    {
    	$user = Typecho_Widget::widget('Widget_User');
        $options = Helper::options();
		$iQapTchaOpt = $options->plugin('IQapTcha');
		$opt = 'none';
		$error = '';
		$action = self::getCheckStr('action');
		$req = Typecho_Request::getInstance();
		
		//登录检测，session 检测
		if( $iQapTchaOpt->opt_admin_unlock == 'false' || !($user->hasLogin() && $user->pass('administrator'))) {
			@session_start();
			if (!isset($_SESSION[$action]) || !$_SESSION[$action] || !$req->is('iQapTcha='.$_SESSION[$action]) ) {
				throw new Typecho_Widget_Exception($iQapTchaOpt->opt_lock_txt);
			}
		}

		//屏蔽IP段处理
		if ($iQapTchaOpt->opt_ip != "none") {
			if (IQapTcha_Plugin::checkIp($iQapTchaOpt->words_ip, $comment['ip'])) {
				$error = $iQapTchaOpt->opt_ip_txt;
				$opt = $iQapTchaOpt->opt_ip;
			}			
		}
		//纯英文评论处理
		if ($iQapTchaOpt->opt_nocn != "none") {
			$result = preg_match_all("/[\x{4e00}-\x{9fa5}]/u", $comment['text'], $txt);
			if ($result == false || $result < 2) {
				$error = $iQapTchaOpt->opt_nocn_txt;
				$opt = $iQapTchaOpt->opt_nocn;
			}
		}
		//检查禁止词汇
		if ($iQapTchaOpt->opt_ban != "none") {
			if (IQapTcha_Plugin::checkIn($iQapTchaOpt->words_ban, $comment['text'])) {
				$error = $iQapTchaOpt->opt_ban_txt;
				$opt = $iQapTchaOpt->opt_ban;
			}
		}
		//检查敏感词汇
		if ($iQapTchaOpt->opt_chk != "none") {
			if (IQapTcha_Plugin::checkIn($iQapTchaOpt->words_chk, $comment['text'])) {
				$error = $iQapTchaOpt->opt_chk_txt;
				$opt = $iQapTchaOpt->opt_chk;
			}
		}

		//执行操作
		if ($opt == "abandon") {
			Typecho_Cookie::set('__typecho_remember_text', $comment['text']);
            throw new Typecho_Widget_Exception($error);
		}
		else if ($opt == "spam") {
			$comment['status'] = 'spam';
		}
		else if ($opt == "waiting") {
			$comment['status'] = 'waiting';
		}
		
		Typecho_Cookie::delete('__typecho_remember_text');
        return $comment;
    }
	
    /**
     * 获取 安全检测 字符串
     * @param string $type
     * @return string
     */
    private static function getCheckStr($type)
    {
    	$options = Helper::options();
    	switch ($type) {
    		case 'slug':
    			$chkStr = md5($options->siteUrl.'_slug_iQapTcha4tyepcho_admin_');
    			break;
    		case 'action':
    			$chkStr = md5($options->siteUrl.'_action_iQapTcha4tyepcho_');
    			break;
    		default:
    			$chkStr = '';
    			break;
    	}
    	
    	return $chkStr;
    }
    
    /**
     * 检查$str中是否含有$wordsStr中的词汇
     * 
     */
	private static function checkIn($wordsStr, $str)
	{
		$words = explode("\n", $wordsStr);
		if (empty($words)) {
			return false;
		}
		foreach ($words as $word) {
            if (false !== strpos($str, trim($word))) {
                return true;
            }
		}
		return false;
	}

    /**
     * 检查$ip中是否在$wordsIp的IP段中
     * 
     */
	private static function checkIp($wordsIp, $ip)
	{
		$words = explode("\n", $wordsIp);
		if (empty($words)) {
			return false;
		}
		foreach ($words as $word) {
			$word = trim($word);
			if (false !== strpos($word, '*')) {
				$word = "/^".str_replace('*', '\d{1,3}', $word)."$/";
				if (preg_match($word, $ip)) {
					return true;
				}
			} else {
				if (false !== strpos($ip, $word)) {
					return true;
				}
			}
		}
		return false;
	}
}
