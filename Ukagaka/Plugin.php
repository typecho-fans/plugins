<?php
/**
 * 一只萌萌的春菜
 * 
 * @package 伪春菜 
 * @author Kunr
 * @version 1.0.0
 * @link http://kunr.me
 */
class Ukagaka_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('Ukagaka_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Ukagaka_Plugin', 'footer');
        $db = Typecho_Db::get();
        $db->query($db->insert('table.options')
                ->rows(array(
                    'name'  =>  'Ukagaka_starttime',
                    'value' =>  time(),
                    'user'  =>  0
                )));
        Helper::addAction('Ukagaka', 'Ukagaka_Action');
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
        $db = Typecho_Db::get();
        $db->delete('table.options')
->where('name = ?', 'Ukagaka_starttime');
        Helper::removeAction('Ukagaka');
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
        /** 称呼 */
        $nickname = new Typecho_Widget_Helper_Form_Element_Text('nickname', NULL, '主人桑', _t('春菜如何称呼主人呢..'));
        $form->addInput($nickname);
        /** 公告 */
        $notice = new Typecho_Widget_Helper_Form_Element_Textarea('notice', NULL, '主人暂时还没有写公告呢，这是主人第一次使用伪春菜吧', _t('博客有什么公告呢..'));
        $form->addInput($notice);
        /** 聊天 */
        $contact = new Typecho_Widget_Helper_Form_Element_Textarea('contact', NULL, '早上好//早上好主人sama！', _t('聊天对话'), '一行一个对话，问和答用\'//\'分割 比如: 问//答，留空使用api.');
        $form->addInput($contact);
        /** 自言自语 */
        $selftalk = new Typecho_Widget_Helper_Form_Element_Textarea('selftalk', NULL, '', _t('自言自语'), '一行一个自言自语设置，话和表情id(1~3)用\'//\'分割 比如: 你好//1，留空采用hitokoto');
        $form->addInput($selftalk);
        /** 零食 */
        $foods = new Typecho_Widget_Helper_Form_Element_Textarea('foods', NULL, '金坷垃//吃了金坷垃，一刀能秒一万八～！'."\r\n".'咸梅干//吃咸梅干，变超人！哦耶～～～', _t('零食'), '一行一个零食，零食//答语用\'//\'分割 比如: 零食//答语');
        $form->addInput($foods);
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
    public static function header()
    {
        echo '<link rel="stylesheet" type="text/css" href="'.Helper::options()->pluginUrl.'/Ukagaka/assets/Ukagaka.css" />' . "\n";
    }

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function footer()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $Ukagaka = $options->plugin('Ukagaka');
        if($Ukagaka->selftalk){
            $selftalk = explode("\r\n", $Ukagaka->selftalk);
            $vv = '[';
            foreach ($selftalk as $key => $value) {
                $vx = explode("//", $value);
                $vv .= '["'.$vx[0].'", "'.$vx[1].'"],';
            }
            $vv .= ']';
        } else {
            $vv = '\'\'';
        }
        $path = Helper::options()->pluginUrl.'/Ukagaka/assets';
        ?><script type="text/javascript">!window.jQuery && document.write('<script src="//upcdn.b0.upaiyun.com/libs/jquery/jquery-1.9.1.min.js"><\/script>');</script>
        <script src="<?php echo $path;?>/Ukagaka.js"></script>
        <script type="text/javascript">var actionurl = '<?php Helper::options()->index('action/Ukagaka');?>'; var imagewidth = '85';var imageheight = '152';</script>
        <script type="text/javascript">createFace("<?php echo $path; ?>/skin/default/face1.gif", "<?php echo $path; ?>/skin/default/face2.gif", "<?php echo $path; ?>/skin/default/face3.gif");</script>
        <script type="text/javascript">var talkself_arr = <?php echo $vv;?>;</script>
        <?php
    }
}
