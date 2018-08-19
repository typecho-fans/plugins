<?php
/**
 * 小墙- 用最简单的方法墙掉垃圾评论
 * 
 * @package AntiSpam
 * @author Willin Kan
 * @version 1.0.3
 * @update: 2011.06.07
 * @link http://kan.willin.org/typecho/
 */
class AntiSpam_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive') ->beforeRender = array('AntiSpam_Plugin', 'field');
        Typecho_Plugin::factory('Widget_Feedback')->comment      = array('AntiSpam_Plugin', 'filter');

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
        $action = new Typecho_Widget_Helper_Form_Element_Radio(
          'action', array(
            0 => '直接挡掉.',
            1 => '标记为 spam, 留在资料库检查是否误判.'
         ), 0,
          '遇到 spam 的处理方法');
        $form->addInput($action);
    
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
     * 设栏位
     *
     * @access public
     * @return void
     */
    public static function field()
    {
        if (Typecho_Widget::widget('Widget_Archive')->is('single') && !Typecho_Widget::widget('Widget_User')->hasLogin()) {
          ob_start(create_function('$input','return preg_replace("#textarea(.*?)name=([\"\'])text([\"\'])(.+)/textarea>#",
          "textarea$1name=$2comment$3$4/textarea><textarea name=\"text\" cols=\"100%\" rows=\"4\" style=\"display:none\">spam</textarea>",$input);') );
        }

    }

    /**
     * 评论过滤器
     *
     * @access public
     * @return void
     */
    public static function filter($comment)
    {
        if (!Typecho_Widget::widget('Widget_User')->hasLogin()) {
            $w = Typecho_Request::getInstance()->comment;
            if (!empty($w) && $comment['text'] == 'spam') {
                $comment['text'] = $w;
            } else {
                if (Typecho_Widget::widget('Widget_Options')->plugin('AntiSpam')->action) {
                    $comment['text'] = "[ 小墙判断这是 Spam! ]\n" . $comment['text'];
                    $comment['status'] = 'spam';
                } else {
                    throw new Typecho_Exception('Spam Detected!');
                }
            }
        }
        return $comment;

    }

}
