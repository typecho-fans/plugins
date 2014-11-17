<?php
/**
 * Sync Post
 * 
 * @category system
 * @package SyncPost
 * @author 冰剑
 * @version 1.0.0
 * @link http://www.binjoo.net
 */
require_once 'Constant.php';
require_once 'Http.php';
class SyncPost_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/write-post.php')->option = array('SyncPost_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->option = array('SyncPost_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('SyncPost_Plugin', 'postRender');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('SyncPost_Plugin', 'postRender');
        Helper::addAction('SyncPost', 'SyncPost_Action');
    }
    public static function deactivate(){
        Helper::removeAction('SyncPost');
    }
    public static function config(Typecho_Widget_Helper_Form $form){
        $options = '';
        $siteUrl = Helper::options()->siteUrl;
        try {
            $options = Helper::options()->plugin('SyncPost');
        } catch (Exception $e) {
        }
        echo '<style type="text/css">form{width: 480px}.rigthUl{float: right;margin-left: 10px;width: 260px}</style>';
        echo '<div class="rigthUl">';
        echo '<ul class="typecho-option"><li><label class="typecho-label">腾讯微博</label><p class="description">';
        echo '授权状态：';
        if($options->tqq_access_token){
            if($options->tqq_last_time < time()){
                echo '<span style="color: #0000FF">已过期</span>（<a href="'.$siteUrl.'/action/SyncPost?tqq">重新授权</a>）';
            }else{
                echo '<span style="color: #BD6800">已授权</span>';
            }
            echo '<br />有效时间：'. date("Y-m-d H:i", $options->tqq_last_time);
        }else{
            echo '<span style="color: #FF0000">未授权</span>（<a href="'.$siteUrl.'action/SyncPost?tqq">申请授权</a>）';
        }
        echo '</p></li></ul>';
        echo '<ul class="typecho-option"><li><label class="typecho-label">新浪微博</label><p class="description">';
        echo '授权状态：';
        if($options->sina_access_token){
            if($options->sina_last_time < time()){
                echo '<span style="color: #0000FF">已过期</span>（<a href="'.$siteUrl.'/action/SyncPost?sina">重新授权</a>）';
            }else{
                echo '<span style="color: #BD6800">已授权</span>';
            }
            echo '<br />有效时间：'. date("Y-m-d H:i", $options->sina_last_time);
        }else{
            echo '<span style="color: #FF0000">未授权</span>（<a href="'.$siteUrl.'action/SyncPost?sina">申请授权</a>）';
        }
        echo '</p></li></ul>';
        echo '<ul class="typecho-option"><li><label class="typecho-label">豆瓣广播</label><p class="description">';
        echo '授权状态：';
        if($options->douban_access_token){
            if($options->douban_last_time < time()){
                echo '<span style="color: #0000FF">已过期</span>（<a href="'.$siteUrl.'/action/SyncPost?douban">重新授权</a>）';
            }else{
                echo '<span style="color: #BD6800">已授权</span>';
            }
            echo '<br />有效时间：'. date("Y-m-d H:i", $options->douban_last_time);
        }else{
            echo '<span style="color: #FF0000">未授权</span>（<a href="'.$siteUrl.'action/SyncPost?douban">申请授权</a>）';
        }
        echo '</p></li></ul>';
        echo '</div>';

        $postContent = new Typecho_Widget_Helper_Form_Element_Textarea('postContent', NULL, '我发表了一篇新的日志《%title%》，地址是：%permalink%，快来坐沙发吧！', _t('发送内容'), _t('标题：%title%<br />地址：%permalink%'));
        $form->addInput($postContent);

        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('tqq_access_token'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('tqq_expires_in'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('tqq_openid'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('tqq_openkey'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('tqq_last_time'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('sina_access_token'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('sina_expires_in'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('sina_last_time'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('douban_access_token'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('douban_refresh_token'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('douban_expires_in'));
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Hidden('douban_last_time'));
    }
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    public static function render()
    {
        echo '<section class="typecho-post-option category-option"><label class="typecho-label">Sync Post</label>
<ul><li><input type="checkbox" id="syncpost-tqq" value="syncpost-tqq" name="syncpost[]">
<label for="syncpost-tqq">腾讯微博</label></li>
<li><input type="checkbox" id="syncpost-sina" value="syncpost-sina" name="syncpost[]">
<label for="syncpost-sina">新浪微博</label></li>
<li><input type="checkbox" id="syncpost-douban" value="syncpost-douban" name="syncpost[]">
<label for="syncpost-douban">豆瓣广播</label></li></ul></section>';
    }

    public static function postRender($contents, $class){
        //echo '<xmp>';
        //var_dump($contents);
        //exit();
        if($class->request->is('do=publish')/* && !$class->request->get('cid')*/){
            $opstions = Helper::options()->plugin('SyncPost');
            $syncpost = $class->request->get('syncpost');

            foreach ((array)$syncpost as $key => $val) {
                if($val == 'syncpost-tqq'){
                    $params = array(
                        'oauth_consumer_key' => TQQ_CLIENT_ID,
                        'access_token' => $opstions->tqq_access_token,
                        'openid' => $opstions->tqq_openid,
                        'oauth_version' => '2.a',
                        'format' => 'json',
                        'content' => str_replace(array('%title%', '%permalink%'), array($contents['title'], $class->permalink), $opstions->postContent)
                    );
                    $result = HTTP::request(TQQ_API_URL, $params, 'POST');
                }else if($val == 'syncpost-sina'){
                    $params = array(
                        'access_token' => $opstions->sina_access_token,
                        'status' => str_replace(array('%title%', '%permalink%'), array($contents['title'], $class->permalink), $opstions->postContent)
                    );
                    $result = HTTP::request(SINA_API_URL, $params, 'POST');
                }else if($val == 'syncpost-douban'){
                    $params = array(
                        'source' => $opstions->douban_access_token,
                        'text' => str_replace(array('%title%', '%permalink%'), array($contents['title'], $class->permalink), $opstions->postContent)
                    );
                    $result = HTTP::request(DOUBAN_API_URL, $params, 'POST', false, array('Authorization: Bearer ' .$opstions->douban_access_token));
                }
            }
        }
        return $contents;
    }
}
