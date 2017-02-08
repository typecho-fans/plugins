<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文件下载插件，使下载的文件保持上传时的文件名。
 * 
 * @package DownloadFile
 * @author DT27
 * @version 1.0.0
 * @link https://dt27.org/
 */
class DownloadFile_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('DownloadFile_Plugin', 'bottomJS');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('DownloadFile_Plugin', 'bottomJS');
        // 创建路由
        Helper::addRoute('download.file', '/download/file', 'DownloadFile_Action', 'downloadFile');
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
        Helper::removeRoute('download.file');
    }

    public static function config(Typecho_Widget_Helper_Form $form){}
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function bottomJS()
    {
            ?>
            <script type="text/javascript">
                $(document).ready(function(){
                    $('#file-list li').each(function () {
                        if($(this).data('image') == 0)
                            $(this).data('url', window.location.protocol + '//' + window.location.host + '/download/file?cid='+$(this).data('cid'));
                    });
                    Typecho.uploadComplete = function (file) {
                        if(file.isImage == 0)
                            Typecho.insertFileToEditor(file.title, window.location.protocol + '//' + window.location.host + '/download/file?cid='+file.cid, file.isImage);
                        else
                            Typecho.insertFileToEditor(file.title, file.url, file.isImage);

                        $('#file-list li').each(function () {
                            if($(this).data('image') == 0)
                                $(this).data('url', window.location.protocol + '//' + window.location.host + '/download/file?cid='+$(this).data('cid'));
                        });
                    };
                });
            </script>
            <?php
    }
}
