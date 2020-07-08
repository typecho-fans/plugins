<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章内容替换工具，帮助不懂sql语句的用户快速替换文章内容（该插件仅在SQL环境下测试过，其他类型数据库也许会不兼容！）
 * 
 * @package TEReplace
 * @author 泽泽
 * @version 1.3.0
 * @link http://qqdie.com
 */
class TEReplace_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */

    /** @var string 控制菜单链接 */
    public static $panel  = 'TEReplace/console.php';

  
    public static function activate()
    {
      Helper::addPanel(1, self::$panel, '内容替换', '内容替换控制台', 'administrator');
        return _t('开启成功ヽ(✿ﾟ▽ﾟ)ノ');
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
      Helper::removePanel(1, self::$panel); 
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

      
?><style>@media (max-width: 767px){.yaofan {display: none!important;}}#tixing-0-1 {display: none;}</style>
<?php
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
        $tixing = new Typecho_Widget_Helper_Form_Element_Text('tixing', NULL, NULL, _t('【控制台】→【内容替换】进入操作页面'), _t('<div class="yaofan"><br>
        <b>作者 ❤ 语：'.$say[rand(0,9)].'</b><br><br><img src="'.Helper::options()->pluginUrl.'/TEReplace/yaofan.jpg" style="max-width: 100%;">
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


}
