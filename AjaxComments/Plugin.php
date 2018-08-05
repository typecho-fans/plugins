<?php
/**
 * Ajax 内置嵌套评论
 *
 * @package AjaxComments
 * @link http://www.byends.com
 * @author Byends, Willin Kan
 * @version 1.2.0
 * @oriAuthor willin(http://kan.willin.org/typecho/)
 *
 * 原作者是 willin (http://kan.willin.org/typecho/),请尊重版权
 *
 * 1.2.0 update by Byends at 2015-05-30
 * 升级兼容 typecho 1.0，此次升级不向下兼容，typecho 1.0 以下版本请使用 AjaxComments 1.1.1
 * 
 * 1.1.1 update by Byends at 2014-01-21
 * 修复由于 jQuery $ 对象被某些插件二货制作者无故释放导致插件无法使用的BUG
 *
 * 1.1.0 update by Byends at 2012-07-13
 * 
 *
 */
class AjaxComments_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('AjaxComments_Plugin', 'headerScript');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('AjaxComments_Plugin', 'footerScript');

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
        $options       = Helper::options();
        $wn_img        = '<span style="color:#f00;">×</span>';
        $er_img        = '<span style="color:#f00;">×</span>';
        $ok_img        = '<span style="color:green;font-weight:bold">√</span>';
        $file_contents = '';

        $db = Typecho_Db::get();
        $select = $db->fetchRow($db
				->select('cid')->from('table.comments')
				->where('table.comments.status = ?', 'approved')
				->limit(1)
		);

        if ($select){
			$select = $db->fetchRow($db
            		->select('cid', 'created', 'type', 'slug')->from('table.contents')
            		->where('table.contents.cid = ?', $select['cid'])
            );
          $select['text'] = ''; //fix php5.6 warning

          $select = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($select);
          $permalink = $select['permalink'];

          $fh = fopen($permalink, 'r');
          $file_contents =  file_get_contents($permalink);
          fclose($fh);
        } else {
          echo $wn_img, '<span style="color:#f00;"> 警告! 未能找到任何评论, 以下测试失败...</span><br/>';
        }

        $registered = $db->fetchRow($db
					->select()->from('table.options')
					->where('name = ?', 'plugin:AjaxComments')
		);

        $jq_set = new Typecho_Widget_Helper_Form_Element_Radio(
        'jq_set', array('0'=> '自己处理', '1'=> '随着本插件载入'), 1, 'jQuery 来源', '若选择 "随着本插件载入", 会从 CDN 自动载入 jQurey 1.8.3 到 header().');
        $form->addInput($jq_set);

        $trg = $registered ? Helper::options()->plugin('AjaxComments')->_comments : '#comments h4';
        $trgar = explode(' ', $trg);
        $htm_trg = strtr( $trgar[0], array( '.' => 'class="', '#' => 'id="'));
        $htm_trg .= stristr($htm_trg, 'id=') ? '"' : '';
        if (!isset($trgar[1])) {
			$trgar[1] = '';
			$chkd = stripos($file_contents, $htm_trg) ? $ok_img : $wn_img;
        } else {
			$tmp = substr($file_contents, stripos($file_contents, $htm_trg), 80);
			$chkd = (stripos($file_contents, $htm_trg) && stristr($tmp, $trgar[1])) ? $ok_img : $wn_img;
			$trgar[1] = '&lt;'. $trgar[1]. '&gt;';
        }
        if (!stristr($htm_trg, 'id=') && !stristr($htm_trg, 'class=')) $chkd = $er_img;
        
        $loadingLan = new Typecho_Widget_Helper_Form_Element_Text('loadingLan', NULL, '正在提交, 请稍候...', _t('等待提示'), _t('正在提交时的提示信息'));
        $loadingLan->input->setAttribute('style', 'float:left; width:200px;margin-right:10px');
        $form->addInput($loadingLan->addRule('required', _t('必须填写等待提示')));
        
        $subSuccess = new Typecho_Widget_Helper_Form_Element_Text('subSuccess', NULL, '评论提交成功', _t('成功提示'), _t('提交成功时的提示信息'));
        $subSuccess->input->setAttribute('style', 'float:left; width:200px;margin-right:10px');
        $form->addInput($subSuccess->addRule('required', _t('必须填写成功提示')));
        
        $errUsername = new Typecho_Widget_Helper_Form_Element_Text('errUsername', NULL, '必须填写用户名', _t('用户名提示'), _t('用户名为空时的提示信息'));
        $errUsername->input->setAttribute('style', 'float:left; width:200px;margin-right:10px');
        $form->addInput($errUsername->addRule('required', _t('必须填写用户名提示')));
        
        $errEmail = new Typecho_Widget_Helper_Form_Element_Text('errEmail', NULL, '必须填写邮箱地址', _t('电子邮箱提示'), _t('电子邮箱为空时的提示信息'));
        $errEmail->input->setAttribute('style', 'float:left; width:200px;margin-right:10px');
        $form->addInput($errEmail->addRule('required', _t('必须填写电子邮箱提示')));
        
        $errRuleEmail = new Typecho_Widget_Helper_Form_Element_Text('errRuleEmail', NULL, '邮箱地址不正确', _t('电子邮箱错误提示'), _t('电子邮箱格式错误时的提示信息'));
        $errRuleEmail->input->setAttribute('style', 'float:left; width:200px;margin-right:10px');
        $form->addInput($errRuleEmail->addRule('required', _t('必须填写电子邮箱错误提示')));
        
        $errText = new Typecho_Widget_Helper_Form_Element_Text('errText', NULL, '必须填写评论内容', _t('评论内容提示'), _t('评论内容为空时的提示信息'));
        $errText->input->setAttribute('style', 'float:left; width:200px;margin-right:10px');
        $form->addInput($errText->addRule('required', _t('评论内容提示')));
        
        $_comments = new Typecho_Widget_Helper_Form_Element_Text(
        '_comments', NULL, $trg, '评论总数', '　'. $chkd. '　&lt;'. $htm_trg. '&gt;'. $trgar[1]. '有 xx 条评论...');
        $_comments->input->setAttribute('style', 'float:left; width:200px;');
        $form->addInput($_comments);

        $trg = $registered ? Helper::options()->plugin('AjaxComments')->_comment_list : '.comment-list';
        $htm_trg = strtr( $trg, array( '.' => 'class="', '#' => 'id="'));
        $chkd = (stripos($file_contents, $htm_trg)) ? $ok_img : $wn_img;
        if (!stristr($htm_trg, 'id=') && !stristr($htm_trg, 'class=')) $chkd = $er_img;
        $_comment_list = new Typecho_Widget_Helper_Form_Element_Text(
        '_comment_list', NULL, $trg, '评论主体', '　'. $chkd. '　&lt;ol '. $htm_trg. '"&gt;');
        $_comment_list->input->setAttribute('style', 'float:left; width:200px;');
        $form->addInput($_comment_list);

        $trg = $registered ? Helper::options()->plugin('AjaxComments')->_comment_reply : '.comment-reply';
        $htm_trg = strtr( $trg, array( '.' => 'class="', '#' => 'id="'));
        $chkd = (stripos($file_contents, $htm_trg)) ? $ok_img : $wn_img;
        if (!stristr($htm_trg, 'id=') && !stristr($htm_trg, 'class=')) $chkd = $er_img;
        $_comment_reply = new Typecho_Widget_Helper_Form_Element_Text(
        '_comment_reply', NULL, $trg, '回复', '　'. $chkd. '　&lt;div '. $htm_trg. '"&gt;&lt;a href=" ...');
        $_comment_reply->input->setAttribute('style', 'float:left; width:200px;');
        $form->addInput($_comment_reply);

        $trg = $registered ? Helper::options()->plugin('AjaxComments')->_comment_form : '#comment_form';
        $htm_trg = strtr( $trg, array( '.' => 'class="', '#' => 'id="'));
        $htm_trg .= stristr($htm_trg, 'id=') ? '"' : '';
        $chkd = (stripos($file_contents, $htm_trg)) ? $ok_img : $wn_img;
        if (!stristr($htm_trg, 'id=') && !stristr($htm_trg, 'class=')) $chkd = $er_img;
        $_comment_form = new Typecho_Widget_Helper_Form_Element_Text(
        '_comment_form', NULL, $trg, '表单', '　'. $chkd. '　&lt;form .. '. $htm_trg. ' ... &gt;');
        $_comment_form->input->setAttribute('style', 'float:left; width:200px;');
        $form->addInput($_comment_form);

        $trg = $registered ? Helper::options()->plugin('AjaxComments')->_respond : '.respond';
        $htm_trg = strtr( $trg, array( '.' => 'class="', '#' => 'id="'));
        $htm_trg .= stristr($htm_trg, 'id=') ? '"' : '';
        $chkd = (stripos($file_contents, $htm_trg)) ? $ok_img : $wn_img;
        if (!stristr($htm_trg, 'id=') && !stristr($htm_trg, 'class=')) $chkd = $er_img;
        $_respond = new Typecho_Widget_Helper_Form_Element_Text(
        '_respond', NULL, $trg, '评论框', '　'. $chkd. '　&lt;div id="respond-post- xx " '. $htm_trg. ' ... &gt;');
        $_respond->input->setAttribute('style', 'float:left; width:200px;');
        $form->addInput($_respond);

        $trg = $registered ? Helper::options()->plugin('AjaxComments')->_textarea : '.textarea';
        $htm_trg = strtr( $trg, array( '.' => 'class="', '#' => 'id="'));
        $htm_trg .= stristr($htm_trg, 'id=') ? '"' : '';
        $chkd = (stripos($file_contents, $htm_trg)) ? $ok_img : $wn_img;
        if (!stristr($htm_trg, 'id=') && !stristr($htm_trg, 'class=')) $chkd = $er_img;
        $_textarea = new Typecho_Widget_Helper_Form_Element_Text(
        '_textarea', NULL, $trg, '內容', '　'. $chkd. '　&lt;textarea .. '. $htm_trg. ' ... &gt;');
        $_textarea->input->setAttribute('style', 'float:left; width:200px;');
        $form->addInput($_textarea);

        $trg = $registered ? Helper::options()->plugin('AjaxComments')->_submit : '.submit';
        $htm_trg = strtr( $trg, array( '.' => 'class="', '#' => 'id="'));
        $htm_trg .= stristr($htm_trg, 'id=') ? '"' : '';
        $chkd = (stripos($file_contents, $htm_trg)) ? $ok_img : $wn_img;
        if (!stristr($htm_trg, 'id=') && !stristr($htm_trg, 'class=')) $chkd = $er_img;
        $_submit = new Typecho_Widget_Helper_Form_Element_Text(
        '_submit', NULL, $trg, '提交', '　'. $chkd. '　&lt;input .. '. $htm_trg. ' ... &gt;<br/><br/>
        提示不正常项目请查找模板比对, 修改后先保存设置, 再回来重新检查.<br/>
        id 使用 ( # ) ;　class 使用 (<strong> . </strong>) ;　请勿直接输入 "id" 或 "class".<br/>
        ( 测试结果僅供参考, 若还有不正常, 请详细修改以上对应标签. )
        ');

        $_submit->input->setAttribute('style', 'float:left; width:200px;');
        $form->addInput($_submit);

        $wn_msg = '';
        if ($select){ $wn_msg = (stripos($file_contents, 'id="cancel-comment-reply-link"')) ? '已采用内置嵌套评论 ' . $ok_img : $er_img . '<span style="color:#f00;"> 不支持内置嵌套评论.</span>';}
        if ($options->commentsThreaded == 0) $wn_msg = $wn_img . '<span style="color:#d60;"> 嵌套回复功能未开启, 测试失败.</span>';
        echo '<span style="font-size:15px;">当前使用的外观是 [ <strong>', $options->theme, '</strong> ] ', $wn_msg, '</span>';
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
     * @param $header
     * @param $that
     * @return mixed
     */
    public static function headerScript($header, $that)
    {
        if (Helper::options()->plugin('AjaxComments')->jq_set == 1) {
			echo "<script type=\"text/javascript\" src=\"http://cdn.staticfile.org/jquery/1.8.3/jquery.min.js\"></script>\n";
        }
        
        return $header;
    }

    /**
     * 自定义 footer
     * @param $that
     */
    public static function footerScript($that)
    {
        if (Typecho_Widget::widget('Widget_Archive')->is('single'))
			include('AjaxComments/typecho-ajax-comm.php');
    }

}
