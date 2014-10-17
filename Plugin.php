<?php
/**
 * Typecho 后台自动升级
 * 
 * @package Update 
 * @author 公子
 * @version 0.0.1
 * @link http://zh.eming.li#update
 */
class Update_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/menu.php')->navBar = array('Update_Plugin', 'show');
        Helper::addRoute('update', '/update/[step]', 'Update_Action', NULL);
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
        Helper::removeRoute('update');
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
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function show() {
        $curl = curl_init(Helper::options()->index."/action/ajax?do=checkVersion");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $version = json_decode(curl_exec($curl), true);
        curl_close($curl);

        if(!$version['avaliable']) {
            $url = Helper::options()->index."/update/zero";
            ?>
            <script>
            window.onload = function() {
                document.querySelector(".update-check strong") && (document.querySelector(".update-check strong").innerHTML += '<a href="<?php echo $url; ?>" class="update message error" style="margin-left:15px;">升级到新版!</a>');
            }
            </script>
            <style>
            .update-check a.update {
                padding:5px 10px;
            }
            .update-check a.update:hover {
                color:#8A1F11!important;
                text-decoration:underline;
            }
            </style>
            <?php
        }
    }
}
