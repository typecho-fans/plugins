<?php

/**
 * Typecho 点赞插件
 * 
 * @package Like
 * @author skylzl
 * @version 1.0.1
 * @link http://www.phoneshuo.com
 */

class Like_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Like_Plugin', 'footer');
        Helper::addAction('like', 'Like_Action');
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        // contents 如果没有likes字段，则添加
        if (!array_key_exists('likes', $db->fetchRow($db->select()->from('table.contents'))))
            $db->query('ALTER TABLE `'. $prefix .'contents` ADD `likes` INT(10) DEFAULT 0;');

    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
    	Helper::removeAction('like');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        /** 文章页A标签点赞的class */
        $likeClass = new Typecho_Widget_Helper_Form_Element_Text(
            'likeClass',NULL ,'post-like', 
            _t('点赞A标签的class'),
            _t('点赞的自定义样式，默认为.post-like。可自定义CSS样式，无需加.')
        );
        /** 是否加载jquery */
        $jquery = new Typecho_Widget_Helper_Form_Element_Radio(
        'jquery', array('0'=> '手动加载', '1'=> '自动加载'), 0, '选择jQuery来源',
            '若选择"手动加载",则需要你手动加载jQuery到你的主题里,若选择"自动加载",本插件会自动加载jQuery到你的主题里。');
        $form->addInput($jquery);        
        $form->addInput($likeClass);           
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){     
    }

     /**
     * 输出点赞链接或者点赞次数
     *
     * 语法: Like_Plugin::theLike();
     * 输出: '<a href="javascript:;" class="post-like" data-pid="'.$cid.'">赞 (<span>'.$row['like'].'</span>)</a>'
     *
     * 语法: Like_Plugin::theLike(false);
     * 输出: 0
     *
     * @access public
     * @param bool    $link   是否输入链接 (false为显示纯数字)
     * @return string
     */  
    public static function theLike($link = true){
        $db = Typecho_Db::get();
        $cid = Typecho_Widget::widget('Widget_Archive')->cid;
        $row = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
        if($link){
            $settings = Helper::options()->plugin('Like');
            echo '<a href="javascript:;" class="'.$settings->likeClass.'" data-pid="'.$cid.'"><i class="fa-thumbs-up"></i>赞 (<span>'.$row['likes'].'</span>)</a>';
        }else{
            echo $row['likes'];
        }
    }

    /**
     * 输出点赞最多的文章
     *
     * 语法: Like_Plugin::theMostLiked();
     *
     * @access public
     * @param int     $limit  文章数目
     * @param string  $showlink 是否显示点赞链接
     * @param string  $before 前字串
     * @param string  $after  后字串
     * @return string
     */
    public static function theMostLiked($limit = 10, $showlink = true, $before = '<br/> - ( 点赞: ', $after = ' 次 ) ')
    {
        $db = Typecho_Db::get();
        $limit = is_numeric($limit) ? $limit : 10;
        $posts = $db->fetchAll($db->select()->from('table.contents')
                 ->where('type = ? AND status = ? AND password IS NULL', 'post', 'publish')
                 ->order('likes', Typecho_Db::SORT_DESC)
                 ->limit($limit)
                 );

        if ($posts) {
            foreach ($posts as $post) {
                $result = Typecho_Widget::widget('Widget_Abstract_Contents')->push($post);
                $post_likes = number_format($result['likes']);
                $post_title = htmlspecialchars($result['title']);
                $permalink = $result['permalink'];
                $settings = Helper::options()->plugin('Like');
                if($showlink == true){
                	echo "<li><a href='$permalink' title='$post_title'>$post_title</a><span style='font-size:70%'><br/><a href='javascript:;' class='$settings->likeClass' data-pid='$cid'><i class='fa-thumbs-up'></i>赞 (<span>'$post_likes</span>)</a></span></li>\n";
              	}else{
              		echo "<li><a href='$permalink' title='$post_title'>$post_title</a><span style='font-size:70%'>$before $post_likes $after</span></li>\n";
              	}
            }

        } else {
            echo "<li>N/A</li>\n";
        }
    }
    
    /**
     * 点赞相关js加载在尾部
     */
    public static function footer() {    
        include 'like-js.php';
    }    
}
