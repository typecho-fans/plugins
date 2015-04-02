<?php
/**
 * <a href="http://developer.baidu.com/bae/bcs/bucket/" target="_blank">Baidu App Engine</a> 附件上传插件
 * 
 * @category system
 * @package BaeUpload
 * @author doudou
 * @version 1.0.1
 * @link https://github.com/doudoutime
 * @date 2014-1-5
 */

class BaeUpload_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('BaeUpload_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('BaeUpload_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('BaeUpload_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('BaeUpload_Plugin', 'attachmentHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('BaeUpload_Plugin', 'attachmentDataHandle');
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
        $ak = new Typecho_Widget_Helper_Form_Element_Text('ak', 
        NULL, '',
        _t('API Key'),
        _t('<a href="http://developer.baidu.com/console" target="_blank">获取API Key</a>'));
        $form->addInput($ak);

        $sk = new Typecho_Widget_Helper_Form_Element_Text('sk', 
        NULL, '',
        _t('Secret Key'),
        _t('<a href="http://developer.baidu.com/console" target="_blank">获取Secret Key</a>'));
        $form->addInput($sk);

        $bucketName = new Typecho_Widget_Helper_Form_Element_Text('bucket',
        NULL, '',
        _t('Bucket名称'),
        _t(''));
        $form->addInput($bucketName);
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
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $fileName = preg_split("(\/|\\|:)", $file['name']);
        $file['name'] = array_pop($fileName);
        
        //获取扩展名
        $ext = '';
        $part = explode('.', $file['name']);
        if (($length = count($part)) > 1) {
            $ext = strtolower($part[$length - 1]);
        }

        if (!Widget_Upload::checkFileType($ext)) {
            return false;
        }

        $options = Typecho_Widget::widget('Widget_Options');
        $date = new Typecho_Date($options->gmtTime);

        //构建路径
        $path = Widget_Upload::UPLOAD_PATH . '/' . $date->year . '/' . $date->month;

        //获取文件名
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $path . '/' . $fileName;

        $bcs = self::bcsInit();
        $bucket = $options->plugin('BaeUpload')->bucket;

        //空日志记录函数
        function bcs_log(){}

        if (isset($file['tmp_name'])) {

            //移动上传文件
            if (!$bcs->create_object($bucket, $path, $file['tmp_name'], array('acl'=>BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_READ, BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->isOK()) {
                return false;
            }
        } else if (isset($file['bits'])) {

            //直接写入文件
            if (!$bcs->create_object_by_content($bucket, $path, $file['bits'], array('acl'=>BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_READ, BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->isOK()) {
                return false;
            }
        } else {
            return false;
        }

        //设置文件Content-Type
        $bcs->set_object_meta($bucket, $path, array('Content-Type'=>BCS_MimeTypes::get_mimetype($ext)), array(BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'));

        if (!isset($file['size'])) {
            $file['size'] = $bcs->get_object_info($bucket, $path, array(BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->header['Content-Length'];
        }

        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($path)
        );
    }

    /**
     * 修改文件处理函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function modifyHandle($content, $file)
    {
        if (empty($file['name'])) {
            return false;
        }

        $fileName = preg_split("(\/|\\|:)", $file['name']);
        $file['name'] = array_pop($fileName);
        
        //获取扩展名
        $ext = '';
        $part = explode('.', $file['name']);
        if (($length = count($part)) > 1) {
            $ext = strtolower($part[$length - 1]);
        }

        if ($content['attachment']->type != $ext) {
            return false;
        }

        //获取文件名
        $fileName = $content['attachment']->path;
        $path = $fileName;

        $bcs = self::bcsInit();
        $options = Typecho_Widget::widget('Widget_Options');
        $bucket = $options->plugin('BaeUpload')->bucket;

        //空日志记录函数
        function bcs_log(){}

        if (isset($file['tmp_name'])) {

            //移动上传文件
            if (!$bcs->create_object($bucket, $path, $file['tmp_name'], array('acl'=>BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_READ, BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->isOK()) {
                return false;
            }
        } else if (isset($file['bits'])) {

            //直接写入文件
            if (!$bcs->create_object_by_content($bucket, $path, $file['bits'], array('acl'=>BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_READ, BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->isOK()) {
                return false;
            }
        } else {
            return false;
        }

        //设置文件Content-Type
        $bcs->set_object_meta($bucket, $path, array('Content-Type'=>BCS_MimeTypes::get_mimetype($ext)), array(BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'));

        if (!isset($file['size'])) {
            $file['size'] = $bcs->get_object_info($bucket, $path, array(BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->header['Content-Length'];
        }

        //返回相对存储路径
        return array(
            'name' => $content['attachment']->name,
            'path' => $content['attachment']->path,
            'size' => $file['size'],
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
        );
    }

    /**
     * 删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function deleteHandle(array $content)
    {
        $bcs = self::bcsInit();
        $bucket = Helper::options()->plugin('BaeUpload')->bucket;
        //空日志记录函数
        function bcs_log(){}
        return $bcs->delete_object($bucket, $content['attachment']->path, array(BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->isOK();
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content)
    {
        //不采用bcs获取还签名的url
        $bucket = Helper::options()->plugin('BaeUpload')->bucket;
        return Typecho_Common::url($content['attachment']->path, 'http://bcs.duapp.com/' . $bucket);
    }

    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function attachmentDataHandle(array $content)
    {
        $bcs = self::bcsInit();
        $bucket = Helper::options()->plugin('BaeUpload')->bucket;
        //空日志记录函数
        function bcs_log(){}
        return $bcs->get_object($bucket, $content['attachment']->path, array(BaiduBCS::IMPORT_BCS_LOG_METHOD=>'bcs_log'))->body;
    }

    /**
     * bcs初始化
     *
     * @access public
     * @return object
     */
    public static function bcsInit()
    {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('BaeUpload');
        require_once 'SDK/bcs.class.php';
        return new BaiduBCS($options->ak, $options->sk);
    }
}
