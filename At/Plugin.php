<?php
/**
 * 为评论提供当前页面@ 功能
 * 
 * @package At 
 * @author 公子
 * @version 0.0.1
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
        	$data[] = "{id: {$comment['coid']}, name: '{$comment['author']}', text: '{$comment['author']}: $text' }";
        }
        ?>
        <script type="text/javascript" src="http://lib.sinaapp.com/js/jquery/1.9.1/jquery-1.9.1.min.js"></script>
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
	        var data = [<?php echo implode(',', $data); ?>];
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
    public static function config(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
}
