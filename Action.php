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
class DownloadFile_Action extends Typecho_Widget
{

    /**
     * 构造函数
     *
     * @param mixed $request
     * @param mixed $response
     * @param null $params
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
    }

    /**
     * 下载文件
     *
     * @cid 文件ID
     * @throws Typecho_Db_Exception
     */
    public function downloadFile(){

        //throw new Typecho_Widget_Exception(_t('文件不存在'), 404);
        //Typecho_Widget::widget('Widget_Contents_Attachment_Edit')->to($attachment);
        if($this->request->filter('int')->cid) {
            Typecho_Db::get()->fetchRow(Typecho_Db::get()->select()->from('table.contents')
                ->where('table.contents.type = ?', 'attachment')
                ->where('table.contents.cid = ?', $this->request->filter('int')->cid)
                ->limit(1), array($this, 'push'));
            if (!$this->have()) {
                throw new \InvalidArgumentException("文件不存在");
            }
            $info = unserialize($this->text);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Type: {$info['mime']}");
            header("Content-Disposition: attachment; filename=\"{$info['name']}\";" );
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: {$info['size']}");
            // download
            $file = @fopen($_SERVER['DOCUMENT_ROOT'].$info['path'],"rb");
            if ($file) {
                while(!feof($file)) {
                    print(fread($file, 1024*8));
                    flush();
                    if (connection_status()!=0) {
                        @fclose($file);
                        die();
                    }
                }
                @fclose($file);
            }
        }
    }
}
