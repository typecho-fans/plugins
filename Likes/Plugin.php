<?php

/**
 * Typecho 点赞插件
 * 
 * @package Likes
 * @author skylzl
 * @version 1.0.0
 * @link http://www.phoneshuo.com
 */

class Likes_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Likes_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Likes_Plugin', 'footer');
        Helper::addAction('likes', 'Likes_Action');
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
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        /** 列表A标签点赞的class */
        $listLikeClass = new Typecho_Widget_Helper_Form_Element_Text(
            'listClass', NULL,'list-like', 
            _t('列表点赞A标签的class'),
            _t('列表页点赞的自定义样式，默认为.list-like可自写CSS样式')
        );
        $form->addInput($listLikeClass);
        /** 文章页A标签点赞的class */
        $postLikeClass = new Typecho_Widget_Helper_Form_Element_Text(
            'postClass',NULL ,'post-like', 
            _t('文章点赞A标签的class'),
            _t('文章页点赞的自定义样式，默认为.post-like可自写CSS样式')
        );
        $form->addInput($postLikeClass);           
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
     * 语法: Likes_Plugin::theLikes();
     * 输出: '<a href="javascript:;" class="post-like" data-pid="'.$cid.'">赞 (<span>'.$row['likes'].'</span>)</a>'
     *
     * 语法: Likes_Plugin::theLikes(false);
     * 输出: 0
     *
     * @access public
     * @param bool    $link   是否输入链接 (false为显示纯数字，用于自定义显示样式)
     * @return string
     */  
    public static function theLikes($link = true){
        $db = Typecho_Db::get();
        $cid = Typecho_Widget::widget('Widget_Archive')->cid;
        $row = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
        if($link){
            echo '<a href="javascript:;" class="post-like" data-pid="'.$cid.'"><i class="fa-thumbs-up"></i>赞 (<span>'.$row['likes'].'</span>)</a>';
        }else{
            return $row['likes'];
        }
    }

    /**
     * 输出点赞最多的文章
     *
     * 语法: Likes_Plugin::theMostLiked();
     *
     * @access public
     * @param int     $limit  文章数目
     * @param string  $shownum 是否显示点赞数量
     * @param string  $before 前字串
     * @param string  $after  后字串
     * @return string
     */
    public static function theMostLiked($limit = 10, $shownum = true, $before = '<br/> - ( 访问: ', $after = ' 次 ) ')
    {
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
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
                if($shownum == true){
                	echo "<li><a href='$permalink' title='$post_title'>$post_title</a><span style='font-size:70%'>$before $post_likes $after</span></li>\n";
              	}else{
              		echo "<li><a href='$permalink' title='$post_title'>$post_title</a></li>\n";
              	}
            }

        } else {
            echo "<li>N/A</li>\n";
        }
    }
    /**
     * 点赞相关css加载在头部
     */
    public static function header() {
        $cssUrl = Helper::options()->pluginUrl . '/Likes/css/style.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }   
    
    /**
     * 点赞相关js加载在尾部
     */
    public static function footer() {    
        include 'likes-js.php';
    }    
}
