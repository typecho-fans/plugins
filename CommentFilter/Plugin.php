<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 评论过滤器 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package CommentFilter
 * @author jrotty,ghostry,Hanny
 * @version 1.3.0
 * @link https://github.com/typecho-fans/plugins/tree/master/CommentFilter
 *
 * version 1.3.0 at 2026-08-16
 * 兼容 Typecho 1.3 和 PHP 8，修复空行规则、IP 误匹配及评论表单初始化
 *
 * version 1.2.1 at 2020-06-27[typecho-fans合并2012-12-31 ghostry修改版]
 * 增加首次评论过滤，评论者可以在评论底部看到自己的未审核评论
 *
 * version 1.2.0 at 2017-10-10[非原作者更新修改，jrotty魔改更新]
 * 增加评论者昵称/超链接过滤功能
 *
 * 历史版本
 * version 1.1.0 at 2014-01-04
 * 增加机器评论过滤
 * version 1.0.2 at 2010-05-16
 * 修正发表评论成功后，评论内容Cookie不清空的Bug
 * version 1.0.1 at 2009-11-29
 * 增加IP段过滤功能
 * version 1.0.0 at 2009-11-14
 * 实现评论内容按屏蔽词过滤功能
 * 实现过滤非中文评论功能
 */
class CommentFilter_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('CommentFilter_Plugin', 'filter');
		Typecho_Plugin::factory('Widget_Archive')->header = array('CommentFilter_Plugin', 'add_filter_spam_input');
		return _t('评论过滤器启用成功，请配置需要过滤的内容');
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
        $opt_spam = new Typecho_Widget_Helper_Form_Element_Radio('opt_spam', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "none",
			_t('屏蔽机器人评论'), "如果为机器人评论，将执行该操作。如果需要开启该过滤功能，请尝试进行评论测试，以免不同模板造成误判。");
        $form->addInput($opt_spam);

        $opt_ip = new Typecho_Widget_Helper_Form_Element_Radio('opt_ip', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "none",
			_t('屏蔽IP操作'), "如果评论发布者的IP在屏蔽IP段，将执行该操作");
        $form->addInput($opt_ip);

        $words_ip = new Typecho_Widget_Helper_Form_Element_Textarea('words_ip', NULL, "0.0.0.0",
			_t('屏蔽IP'), _t('多条IP请用换行符隔开<br />支持通配符和 IPv4/IPv6 CIDR，如：192.168.*.*、192.168.0.0/16、2001:db8::/32'));
        $form->addInput($words_ip);

        $opt_nocn = new Typecho_Widget_Helper_Form_Element_Radio('opt_nocn', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "none",
			_t('非中文评论操作'), "如果评论中不包含中文，则强行按该操作执行");
        $form->addInput($opt_nocn);

        $opt_nopl = new Typecho_Widget_Helper_Form_Element_Radio('opt_nopl', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "none",
			_t('首次评论操作'), "如果评论者没有同昵称、同邮箱且已通过审核的历史评论，则执行该操作");
        $form->addInput($opt_nopl);

        $opt_ban = new Typecho_Widget_Helper_Form_Element_Radio('opt_ban', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "abandon",
			_t('禁止词汇操作'), "如果评论中包含禁止词汇列表中的词汇，将执行该操作");
        $form->addInput($opt_ban);

        $words_ban = new Typecho_Widget_Helper_Form_Element_Textarea('words_ban', NULL, "fuck\n操你妈\n[url\n[/url]",
			_t('禁止词汇'), _t('多条词汇请用换行符隔开'));
        $form->addInput($words_ban);

        $opt_chk = new Typecho_Widget_Helper_Form_Element_Radio('opt_chk', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "waiting",
			_t('敏感词汇操作'), "如果评论中包含敏感词汇列表中的词汇，将执行该操作");
        $form->addInput($opt_chk);

        $words_chk = new Typecho_Widget_Helper_Form_Element_Textarea('words_chk', NULL, "http://",
			_t('敏感词汇'), _t('多条词汇请用换行符隔开<br />注意：如果词汇同时出现于禁止词汇，则执行禁止词汇操作'));
        $form->addInput($words_chk);
      
       $opt_author = new Typecho_Widget_Helper_Form_Element_Radio('opt_author', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "spam",
			_t('关键昵称操作'), "如果评论中包含关键昵称词汇列表中的词汇，将执行该操作");
        $form->addInput($opt_author);

        $words_author = new Typecho_Widget_Helper_Form_Element_Textarea('words_author', NULL, "澳门银座\n自动化软件\n量化交易",
			_t('关键昵称词汇'), _t('多条词汇请用换行符隔开'));
        $form->addInput($words_author);
      
       $opt_url = new Typecho_Widget_Helper_Form_Element_Radio('opt_url', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "spam",
			_t('垃圾链接过滤操作'), "如果评论中包含垃圾链接列表中字符串，将执行该操作");
        $form->addInput($opt_url);

        $words_url = new Typecho_Widget_Helper_Form_Element_Textarea('words_url', NULL, "www.vps521.cn",
			_t('垃圾链接'), _t('多条词汇请用换行符隔开，链接格式请参考上边输入框默认的链接'));
        $form->addInput($words_url);
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
     * 评论过滤器
     * 
     */
    public static function filter($comment, $post)
    {
        $options = Typecho_Widget::widget('Widget_Options');
		$filter_set = $options->plugin('CommentFilter');
		$opt = "none";
		$error = "";
		$comment_ip = isset($comment['ip']) ? (string) $comment['ip'] : '';
		$comment_text = isset($comment['text']) ? (string) $comment['text'] : '';
		$comment_author = isset($comment['author']) ? (string) $comment['author'] : '';
		$comment_mail = isset($comment['mail']) ? (string) $comment['mail'] : '';
		$comment_url = isset($comment['url']) ? (string) $comment['url'] : '';

		//机器评论处理
		if ($opt == "none" && $filter_set->opt_spam != "none") {
			$spam_token = isset($_POST['filter_spam']) ? (string) $_POST['filter_spam'] : '';
			if ($spam_token !== '48616E6E79') {
				$error = "请勿使用第三方工具进行评论";
				$opt = $filter_set->opt_spam;
			}			
		}

		//屏蔽IP段处理
		if ($opt == "none" && $filter_set->opt_ip != "none") {
			if (CommentFilter_Plugin::check_ip($filter_set->words_ip, $comment_ip)) {
				$error = "评论发布者的IP已被管理员屏蔽";
				$opt = $filter_set->opt_ip;
			}			
		}

		//纯中文评论处理
		if ($opt == "none" && $filter_set->opt_nocn != "none") {
			if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $comment_text) == 0) {
				$error = "评论内容请不少于一个中文汉字";
				$opt = $filter_set->opt_nocn;
			}
		}

		//首次评论操作
		if($opt == "none" && $filter_set->opt_nopl != "none"){
			$row = false;
			if($comment_mail !== ''){
				$db = Typecho_Db::get();
				$row = $db->fetchRow($db->select('coid')
					->from('table.comments')
					->where(
						'author = ? AND mail = ? AND type = ? AND status = ?',
						$comment_author,
						$comment_mail,
						'comment',
						'approved'
					)
					->limit(1));
			}
			if(!$row){
				$error = "首次评论已被限制";
				$opt = $filter_set->opt_nopl;
			}
		}

		//检查禁止词汇
		if ($opt == "none" && $filter_set->opt_ban != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_ban, $comment_text)) {
				$error = "评论内容中包含禁止词汇";
				$opt = $filter_set->opt_ban;
			}
		}

		//检查敏感词汇
		if ($opt == "none" && $filter_set->opt_chk != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_chk, $comment_text)) {
				$error = "评论内容中包含敏感词汇";
				$opt = $filter_set->opt_chk;
			}
		}

		//检查关键昵称词汇
		if ($opt == "none" && $filter_set->opt_author != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_author, $comment_author)) {
				$error = "该类型昵称已被禁止评论";
				$opt = $filter_set->opt_author;
			}
		}

		//检查评论者链接
		if ($opt == "none" && $filter_set->opt_url != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_url, $comment_url)) {
				$error = "该类型评论者超链接被禁止评论";
				$opt = $filter_set->opt_url;
			}
		}

		//执行操作
		if ($opt == "abandon") {
			Typecho_Cookie::set('__typecho_remember_text', $comment_text);
            throw new Typecho_Widget_Exception($error);
		}
		else if ($opt == "spam") {
			$comment['status'] = 'spam';
		}
		else if ($opt == "waiting") {
			$comment['status'] = 'waiting';
		}
		if (function_exists('session_status') && PHP_SESSION_ACTIVE === session_status()) {
			$_SESSION['comment'] = $comment;
		}
        return $comment;
    }

    /**
     * 将多行配置整理为非空规则列表
     *
     */
	private static function parse_words($words_str)
	{
		$lines = preg_split('/\r\n|\r|\n/', (string) $words_str);
		$words = array();
		foreach ($lines as $line) {
			$word = trim($line);
			if ($word !== '') {
				$words[] = $word;
			}
		}
		return $words;
	}

    /**
     * 检查$str中是否含有$words_str中的词汇
     * 
     */
	private static function check_in($words_str, $str)
	{
		foreach (self::parse_words($words_str) as $word) {
            if (false !== strpos((string) $str, $word)) {
                return true;
            }
		}
		return false;
	}

    /**
     * 检查$ip中是否在$words_ip的IP段中
     * 
     */
	private static function check_ip($words_ip, $ip)
	{
		$ip = trim((string) $ip);
		if (false === filter_var($ip, FILTER_VALIDATE_IP)) {
			return false;
		}
		foreach (self::parse_words($words_ip) as $word) {
			if (self::match_ip_rule($word, $ip)) {
				return true;
			}
		}
		return false;
	}

    /**
     * 匹配单个精确、通配符或 CIDR IP 规则
     *
     */
	private static function match_ip_rule($rule, $ip)
	{
		if (false !== strpos($rule, '/')) {
			return self::ip_in_cidr($ip, $rule);
		}

		if (false !== strpos($rule, '*')) {
			if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				return false;
			}

			$rule_parts = explode('.', $rule);
			$ip_parts = explode('.', $ip);
			if (count($rule_parts) !== 4) {
				return false;
			}

			foreach ($rule_parts as $index => $part) {
				if ($part === '*') {
					continue;
				}
				if (!ctype_digit($part) || (int) $part > 255
					|| (int) $part !== (int) $ip_parts[$index]) {
					return false;
				}
			}
			return true;
		}

		$rule_binary = @inet_pton($rule);
		$ip_binary = @inet_pton($ip);
		return false !== $rule_binary && false !== $ip_binary
			&& $rule_binary === $ip_binary;
	}

    /**
     * 判断 IP 是否位于 IPv4 或 IPv6 CIDR 网段
     *
     */
	private static function ip_in_cidr($ip, $cidr)
	{
		$parts = explode('/', $cidr, 2);
		if (count($parts) !== 2 || !ctype_digit($parts[1])) {
			return false;
		}

		$ip_binary = @inet_pton($ip);
		$network_binary = @inet_pton($parts[0]);
		if (false === $ip_binary || false === $network_binary
			|| strlen($ip_binary) !== strlen($network_binary)) {
			return false;
		}

		$prefix = (int) $parts[1];
		$max_bits = strlen($ip_binary) * 8;
		if ($prefix < 0 || $prefix > $max_bits) {
			return false;
		}

		$bytes = (int) floor($prefix / 8);
		$remaining_bits = $prefix % 8;
		if ($bytes > 0
			&& substr($ip_binary, 0, $bytes) !== substr($network_binary, 0, $bytes)) {
			return false;
		}
		if ($remaining_bits === 0) {
			return true;
		}

		$mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
		return (ord($ip_binary[$bytes]) & $mask)
			=== (ord($network_binary[$bytes]) & $mask);
	}

    /**
     * 在表单中增加 filter_spam 隐藏域
     * 
     */
    public static function add_filter_spam_input($header, $archive)
    {
		$options = Typecho_Widget::widget('Widget_Options');
		$filter_set = $options->plugin('CommentFilter');
		if ($filter_set->opt_spam != "none" && $archive->is('single') && $archive->allow('comment')) {
			echo '<script type="text/javascript">
(function () {
	function add_filter_spam_input() {
		var input = document.querySelector("textarea[name=text]");
		if (!input || !input.form
			|| input.form.querySelector("input[name=filter_spam]")) {
			return;
		}
		var hidden = document.createElement("input");
		hidden.type = "hidden";
		hidden.name = "filter_spam";
		hidden.value = "48616E6E79";
		input.form.appendChild(hidden);
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", add_filter_spam_input);
	} else {
		add_filter_spam_input();
	}
}());
</script>
';
		}
    }

}
