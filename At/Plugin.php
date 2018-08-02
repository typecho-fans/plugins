<?php
/**
 * 为评论提供当前页面@ 功能
 * 
 * @package At 
 * @author 公子
 * @version 0.1.1
 * @link http://zh.eming.li
 */
class At_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->footer = array('At_Plugin', 'footer');

        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('At_Plugin', 'toMail');
        return _t('主人我还需要您到配置页面中手动初始化一下才能正常工作');
    }
    
    /**
     * 插件实现方法
     *
     * @access public
     * @param $widget
     * @return false
     */
    public static function footer($widget) 
    {
    	//~ 非post, page页以及不允许评论页插件不做处理
        if(!$widget->is('post') && !$widget->is('page')) return false;

        //~ 获取插件路径
        $options = Helper::options();
        $baseUrl = $options->pluginUrl;

        //~ 获取当前页面所有评论并格式化输出
     	$db = Typecho_Db::get();
        $comments = $db->fetchAll( $db->select('coid', 'author', 'text')->from('table.comments')->where('cid = ?', $widget->cid));
        $data = array();
        foreach($comments as $comment)
        {
        	$text = mb_strimwidth(strip_tags($comment['text']), 0, 23, '...','UTF-8');
        	$text = str_replace(array("\r\n", "\n", "\r"), ' ', $text);
        	$data[] = array('id'=>$comment['coid'], 'name'=>$comment['author'], 'text'=>$comment['author'].$text);
        }
        if($options->plugin('At')->jquery) {
        	echo '<script type="text/javascript" src="http://lib.sinaapp.com/js/jquery/1.9.1/jquery-1.9.1.min.js"></script>';
        }
        ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $baseUrl; ?>/At/res/css/jquery.atwho.css"/>
        <script type="text/javascript" src="<?php echo $baseUrl; ?>/At/res/js/jquery.atwho.min.js"></script>
        <script>
		//<![CDATA[
		TypechoComment.reply = function (cid, coid) {
		        var comment = TypechoComment.dom(cid), parent = comment.parentNode,
		            response = TypechoComment.dom('respond-post-<?php echo $widget->cid; ?>'), input = TypechoComment.dom('comment-parent'),
		            form = 'form' == response.tagName ? response : response.getElementsByTagName('form')[0],
		            textarea = response.getElementsByTagName('textarea')[0];

		        if (null == input) {
		            input = TypechoComment.create('input', {
		                'type' : 'hidden',
		                'name' : 'parent',
		                'id'   : 'comment-parent'
		            });

		            form.appendChild(input);
		        }
		        
		        input.setAttribute('value', coid);

		        if (null == TypechoComment.dom('comment-form-place-holder')) {
		            var holder = TypechoComment.create('div', {
		                'id' : 'comment-form-place-holder'
		            });
		            
		            response.parentNode.insertBefore(holder, response);
		        }

		        comment.appendChild(response);
		        TypechoComment.dom('cancel-comment-reply-link').style.display = '';
		        
		        if (null != textarea && 'text' == textarea.name) {
		            textarea.focus();
					var res = {
						'id': response.parentNode.id,
						'name':response.parentNode.getElementsByClassName('fn')[0].innerText
						}
					textarea.innerHTML = '@<a href="#'+res.id+'">'+res.name+'</a>'+textarea.innerHTML;
		        }
		        
		        return false;
		   	}
		//]]>
        $(function() {
	        var data = <?php echo json_encode($data); ?>;
	        $('textarea').atwho('run').atwho({
	            at: "@",
	            data: data,
	            max_len: 8,
	            search_key: 'text',
	            tpl: '<li data-value=\'@<a href=\"#comment-${id}\">${name}</a>\'>${text}</li>'
	        });
        });
        </script>
        <?php
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @param $comment
     * @return false
     */
    public static function toMail($post) {
        /**
         *  可自定义的全局变量
         *  标签  含义  说明
         *  {site}  博客站点名称  在博客后台设置中设定的
         *  {title} 评论文章标题  
         *  {author}    作者名称
         *  {mail}  评论者的邮箱
         *  {permalink} 评论链接
         *  {text}  发评论的正文
         *  {time}  发布评论时间
         *  {author_m}    被提及的用户名称
         *  {text_m}    被提及的用户评论
         *
         */ 

        /* 获取$preg */
        $preg = array('/{site}/', '/{title}/', '/{author}/', '/{mail}/', '/{permalink}/', '/{text}/', '/{time}/', '/{author_m}/', '/{text_,}/');       
        
        /* 获取$settings */
        $config = Helper::options()->plugin('At');
        $host = $config->host;
        $port = $config->port;
        $user = $config->user;
        $password = $config->pass;
        $validate = $config->validate;
        $sender = $config->sender;
        $settings = compact('config', 'host', 'port', 'user', 'password', 'validate', 'sender');

        require '.'. __TYPECHO_PLUGIN_DIR__.'/At/PHPMailer/PHPMailerAutoload.php';

        /* 获取$replace */
        $options = Typecho_Widget::widget('Widget_Options');
        $site = $options->title;
        $settings['site'] = $site;
        $title = $post->title;
        $author = $post->author;
        $mail = $post->mail;
        $permalink = $post->permalink;
        $text = $post->text;
        $time = date($options->timezone, $post->created);
        $replace = compact('site', 'title', 'author', 'mail', 'permalink', 'text', 'time');

        $mentions = self::match($text);
        $db = Typecho_Db::get();
        foreach($mentions as $id => $user) {
            $res = $db->fetchRow( $db->select('*')->from('table.comments')->where('coid = ?', $id) );
            $replace['author_m'] = $user;
            $replace['text_m'] = $res['text'];
            $settings['mentioned'] = $user;
            $settings['address'] = $res['mail'];
            $settings['subject'] = preg_replace($preg, $replace, $config->subject);
            $settings['body'] = preg_replace($preg, $replace, $config->body);

            self::send($settings);
        }
    }

    public static function match($text) {
        $preg = '|@<a href="(.*?)">(.*?)</a>|i';
        preg_match_all($preg, $text, $match);

        $arr = array();
        foreach($match[0] as $k => $v) {
            $id = explode('-', $match[1][$k]);
            $id = end($id);
            $name = $match[2][$k];
            $arr[$id] = $name;
        }

        return array_unique($arr, SORT_STRING);
    }

    public static function send($settings) {
        $mail = new PHPMailer;

        $mail->isSMTP();       
        $mail->CharSet = "UTF-8";                               
        $mail->Host = $settings['host'];  
        $mail->port = $settings['port'];
        $mail->SMTPAuth = ( array_search('validate', $settings['validate']) === false ) ? false : true;
        if( !( array_search('ssl', $settings['validate']) === false) )   $mail->SMTPSecure = 'ssl';
        $mail->Username = $settings['user'];                        
        $mail->Password = $settings['password'];  
        $mail->From =  $settings['user'];
        $mail->FromName = "=?utf-8?B?".base64_encode($settings['site'])."?=";
        $mail->addAddress($settings['address'], "=?utf-8?B?".base64_encode(strip_tags($settings['mentioned']))."?=");  

        $mail->WordWrap = 50;                                 
        $mail->isHTML(true);                                  

        $mail->Subject =  "=?utf-8?B?".base64_encode($settings['subject'])."?=";
        $mail->Body    = $settings['body'];
        $mail->AltBody = strip_tags($settings['body']);

        if(!$mail->send()) {
            $file = '.'.__TYPECHO_PLUGIN_DIR__.'/At/error_log.txt';
            $fp = @fopen($file,'a+');
            fwrite($fp,date('Y-m-d H:i:s').'    '.$mail->ErrorInfo.PHP_EOL);
            fclose($fp);
            exit;
        }
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
    public static function config(Typecho_Widget_Helper_Form $form){
        /**
         * 可自定义的全局变量
         *  标签  含义  说明
         *  {site}  博客站点名称  在博客后台设置中设定的
         *  {title} 评论文章标题  
         *  {author}    作者名称
         *  {mail}  评论者的邮箱
         *  {permalink} 评论链接
         *  {text}  发评论的正文
         *  {time}  发布评论时间
         *  {author_m}    被提及的用户名称
         *  {text_m}    被提及的用户评论
         *
         */

        $text = "<p>{author}在<a href=\"{permalink}\">《{title}》</a>中发布的评论提到了你，以下是他的评论正文:</p>
<blockquote>{text}</blockquote>
<p>快去看看到底是怎么回事吧！</p>";

		$jquery = new Typecho_Widget_Helper_Form_Element_Radio('jquery', 
			array('0' => _t('不加载'), '1' => _t('加载')), 
			'1', _t('是否加载外部jQuery库文件'), _t('插件需要jQuery库文件的支持，如果主题已经加载了可以选择不加载'));
		$form->addInput($jquery);

        $host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL, 'smtp.', _t('SMTP地址'), _t('请填写 SMTP 服务器地址'));
        $form->addInput($host->addRule('required', _t('必须填写一个SMTP服务器地址')));

        $port = new Typecho_Widget_Helper_Form_Element_Text('port', NULL, '25', _t('SMTP端口'), _t('SMTP服务端口,一般为25。'));
        $port->input->setAttribute('class', 'mini');
        $form->addInput($port->addRule('required', _t('必须填写SMTP服务端口'))->addRule('isInteger', _t('端口号必须是纯数字')));

        $user = new Typecho_Widget_Helper_Form_Element_Text('user', NULL, NULL, _t('SMTP用户'),_t('SMTP服务验证用户名,一般为邮箱名如：youname@domain.com'));
        $form->addInput($user->addRule('required', _t('SMTP服务验证用户名')));

        $pass = new Typecho_Widget_Helper_Form_Element_Password('pass', NULL, NULL, _t('SMTP密码'));
        $form->addInput($pass->addRule('required', _t('SMTP服务验证密码')));

        $validate=new Typecho_Widget_Helper_Form_Element_Checkbox('validate', array('validate'=>'服务器需要验证', 'ssl'=>'ssl加密'), array('validate'),'SMTP验证');
        $form->addInput($validate);
/*
        $limit = new Typecho_Widget_Helper_Form_Element_Text('limit', NULL, '5', _t('最大单次提及邮件提醒数'), _t('一次@最多能发送多少封邮件提醒，默认为5封'));
        $limit->input->setAttribute('class', 'mini');
        $form->addInput($limit->addRule('required', _t('请设置一个上限数字'))->addRule('isInteger', _t('上限必须是纯数字')));
*/
        $subject = new Typecho_Widget_Helper_Form_Element_Text('subject', NULL, _t('有人在《{title}》中召唤你'), _t('提醒邮件标题'));
        $form->addInput($subject->addRule('required', _t('提醒邮件的标题是必须要设置的')));

        $body = new Typecho_Widget_Helper_Form_Element_Textarea('body', NULL, $text, _t('提醒邮件正文'), _t('可使用变量：{site}, {title}, {author}, {mail}, {permalink}, {text}, {time}, {author_m}, {text_m}'));
        $form->addInput($body->addRule('required', _t('提醒邮件的正文是必须要设置的')));
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
