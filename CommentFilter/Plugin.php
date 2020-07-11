<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 评论过滤器 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package CommentFilter
 * @author jrotty,ghostry,Hanny
 * @version 1.2.1
 * @link https://github.com/typecho-fans/plugins/tree/master/CommentFilter
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
			_t('屏蔽IP'), _t('多条IP请用换行符隔开<br />支持用*号匹配IP段，如：192.168.*.*'));
        $form->addInput($words_ip);

        $opt_nocn = new Typecho_Widget_Helper_Form_Element_Radio('opt_nocn', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "none",
			_t('非中文评论操作'), "如果评论中不包含中文，则强行按该操作执行");
        $form->addInput($opt_nocn);

        $opt_nopl = new Typecho_Widget_Helper_Form_Element_Radio('opt_nopl', array("none" => "无动作", "waiting" => "标记为待审核", "spam" => "标记为垃圾", "abandon" => "评论失败"), "none",
			_t('首次评论操作'), "如果评论人没有评论过，则强行按该操作执行");
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

		//机器评论处理
		if ($opt == "none" && $filter_set->opt_spam != "none") {
			if ($_POST['filter_spam'] != '48616E6E79') {
				$error = "请勿使用第三方工具进行评论";
				$opt = $filter_set->opt_spam;
			}			
		}

		//屏蔽IP段处理
		if ($opt == "none" && $filter_set->opt_ip != "none") {
			if (CommentFilter_Plugin::check_ip($filter_set->words_ip, $comment['ip'])) {
				$error = "评论发布者的IP已被管理员屏蔽";
				$opt = $filter_set->opt_ip;
			}			
		}

		//纯中文评论处理
		if ($opt == "none" && $filter_set->opt_nocn != "none") {
			if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $comment['text']) == 0) {
				$error = "评论内容请不少于一个中文汉字";
				$opt = $filter_set->opt_nocn;
			}
		}

		//首次评论操作
		if($opt == "none" && $filter_set->opt_nopl != "none"){
			if($comment['mail']){
				 $db = Typecho_Db::get();
            $select=$db->select('mail')
                    ->from('table.comments')
                    ->where('mail = ?', $comment['mail']);
            $result = $db->query($select);
            $row = $db->fetchRow($result);
			if(!$row['mail']){
				$opt = $filter_set->opt_nopl;
			}
			}
		}

		//检查禁止词汇
		if ($opt == "none" && $filter_set->opt_ban != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_ban, $comment['text'])) {
				$error = "评论内容中包含禁止词汇";
				$opt = $filter_set->opt_ban;
			}
		}

		//检查敏感词汇
		if ($opt == "none" && $filter_set->opt_chk != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_chk, $comment['text'])) {
				$error = "评论内容中包含敏感词汇";
				$opt = $filter_set->opt_chk;
			}
		}

		//检查关键昵称词汇
		if ($opt == "none" && $filter_set->opt_author != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_author, $comment['author'])) {
				$error = "该类型昵称已被禁止评论";
				$opt = $filter_set->opt_author;
			}
		}

		//检查评论者链接
		if ($opt == "none" && $filter_set->opt_url != "none") {
			if (CommentFilter_Plugin::check_in($filter_set->words_url, $comment['url'])) {
				$error = "该类型评论者超链接被禁止评论";
				$opt = $filter_set->opt_url;
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
		$_SESSION['comment']=$comment;
		Typecho_Cookie::delete('__typecho_remember_text');
        return $comment;
    }

    /**
     * 检查$str中是否含有$words_str中的词汇
     * 
     */
	private static function check_in($words_str, $str)
	{
		$words = explode("\n", $words_str);
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
     * 检查$ip中是否在$words_ip的IP段中
     * 
     */
	private static function check_ip($words_ip, $ip)
	{
		$words = explode("\n", $words_ip);
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
function get_form(input) {
	var node = input;
	while (node) {
		node = node.parentNode;
		if (node.nodeName.toLowerCase() == "form") {
			return node;
		}
	}
	return null;
};
window.onload = function() {
	var inputs = document.getElementsByTagName("textarea");
	var i, input_author;
	input_author = null;
	for (i=0; i<inputs.length; i++) {
		if (inputs[i].name.toLowerCase() == "text") {
			input_author = inputs[i];
			break;
		}
	}
	var form_comment = get_form(input_author);
	if (form_comment) {
		var input_hd = document.createElement("input");
		input_hd.type = "hidden";
		input_hd.name = "filter_spam";
		input_hd.value = "48616E6E79";
		form_comment.appendChild(input_hd);
	} else {
		alert("find input author error!");
	}
}
</script>
';
		}
    }

}
