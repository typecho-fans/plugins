<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 该插件会让文章加密功能只加密文章内容！而不影响标题，标签还有评论数！【兼容情况：typecho1.1，开发板】
 * 
 * @package Titleshow
 * @author 泽泽
 * @version 1.1.2
 * @link http://qqdie.com
 */
class Titleshow_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('Titleshow_Plugin', 'tshow');
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
?><style>@media (max-width: 767px){.yaofan {display: none!important;}}</style><?php
      $say=array(
        "看到下面那个大大的二维码了吗，想不想用你大大的手机扫扫它！",
        "好几天没吃早饭了，打赏下开发者吧！",
        "小伙子，插件好用么，打赏下作者好吗？",
        "如果觉得好用，可以扫描下方二维码进行打赏，支持作者！",
        "你知道吗，我特别喜欢听人民币到账的提示音！",
        "听说，打赏我的人最后都找到了真爱。",
        "打赏的都是天使。",
        "打赏了的人都会变美~",
        "打赏3块钱，帮我买杯肥宅快乐水，继续创作，谢谢大家！",
        "阔乐，我想和大阔乐，就差3块钱了！",
                         );
        $tixing = new Typecho_Widget_Helper_Form_Element_Text('tixing', NULL, NULL, _t('密码文字提醒'), _t('不填写则默认为【请输入密码访问】<div class="yaofan"><br>
        <b>作者 ❤ 语：'.$say[rand(0,9)].'</b><br><br><img src="'.Helper::options()->pluginUrl.'/Titleshow/yaofan.jpg" style="max-width: 100%;">
        </div>'));
        $form->addInput($tixing);
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
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
public static function tshow($v, $obj) {
$tixing = Typecho_Widget::widget('Widget_Options')->plugin('Titleshow')->tixing;//获取设置参数
if(empty($tixing)){$tixing='请输入密码访问';} //如果未设置则设置默认文字
$v['titleshow'] = false;
/** 如果访问权限被禁止【就是如果需要密码】 */
if ($v['hidden']){
$v['text'] = '
!!!
<form class="protected" action="' . Typecho_Widget::widget('Widget_Security')->getTokenUrl($v['permalink']). '" method="post">'.'<p class="word">'.$tixing.'</p>'.'<p><input type="password" class="text" name="protectPassword" /><input type="hidden" name="protectCID" value="' . $v['cid'] . '" />&nbsp;<input type="submit" class="submit" value="' . _t('提交') . '" /></p>'.'</form>
!!!
';
/** 跳过系统默认 */
$v['hidden'] = false;
/** 用于模板判断插件 */
$v['titleshow'] = true;
}
/** 返回数据 */
return $v; 
}

}
