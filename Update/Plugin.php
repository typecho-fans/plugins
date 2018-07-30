<?php
/**
 * Typecho后台一键升级开发版
 * 
 * @package Update 
 * @author 公子
 * @version 0.0.3
 * @link https://imnerd.org
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
    	$json = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/Update/latest.version';
    	if( !file_exists($json) || (time()-filemtime($json)) > 48 * 3600 ) {
            $commonfile = file_get_contents('https://raw.githubusercontent.com/typecho/typecho/master/var/Typecho/Common.php');
            preg_match('/const VERSION = \'\s*\K[\d\.]+?\/(\s*\K[\d\.]+?)\';/', $commonfile, $latest);
            file_put_contents($json, $latest[1]);
        }else{
            $latest[1] = file_get_contents($json);
        }
        $version = explode('/', Helper::options()->version);

        if( $latest[1] > $version[1] ) {
            $url = Helper::security()->getIndex('/update/zero');
            echo '<a href="'.$url.'"><span class="message btn-warn">升级到开发版</span></a>';
            ?>
            <script>
            window.onload = function() {
                document.querySelector(".update-check strong") && (document.querySelector(".update-check strong").innerHTML += '<a href="<?php echo $url; ?>" class="update message error" style="margin-left:15px;">升级到开发版</a>');
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
