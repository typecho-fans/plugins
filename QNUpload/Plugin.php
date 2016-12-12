<?php
/**
 * 七牛云附件上传 
 * 
 * @package QNUpload
 * @author rakiy
 * @version 1.3.1
 * @link http://ysido.com
 * @date 2016-12-09
 */

class QNUpload_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        //上传
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('QNUpload_Plugin', 'uploadHandle');
        //修改
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('QNUpload_Plugin', 'modifyHandle');
        //删除
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('QNUpload_Plugin', 'deleteHandle');
        //路径参数处理
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('QNUpload_Plugin', 'attachmentHandle');
        //文件内容数据
        Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('QNUpload_Plugin', 'attachmentDataHandle');
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
        $ak = new Typecho_Widget_Helper_Form_Element_Text('ak', 
        NULL, '',
        _t('Access Key'),
        _t('<a href="https://portal.qiniu.com/setting/key" target="_blank">获取Access Key</a>'));
        $form->addInput($ak);

        $sk = new Typecho_Widget_Helper_Form_Element_Text('sk', 
        NULL, '',
        _t('Secure Key'),
        _t('<a href="https://portal.qiniu.com/setting/key" target="_blank">获取Secure Key</a>'));
        $form->addInput($sk);

        $bucketName = new Typecho_Widget_Helper_Form_Element_Text('bucket',
        NULL, 'bucketName',
        _t('Bucket名称'),
        _t(''));
        $form->addInput($bucketName);

        $server = new Typecho_Widget_Helper_Form_Element_Radio('server', 
            array('0'=>_t('华东'), '1'=>_t('华北'), '2'=>_t('华南'), '3'=>_t('北美')), 
            '0', 
            _t('选择bucket节点'),
            _t('一般在七牛面板右下角显示')
        );
        $form->addInput($server);

        $domain = new Typecho_Widget_Helper_Form_Element_Text('domain',
        NULL, 'http://',
        _t('使用的域名,必填,请带上http://'),
        _t('一般在七牛存储面板右上角，形如 xxx.bkt.clouddn.com/xxx.u.qiniu.com'));
        $form->addInput($domain);
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
    public static function uploadHandle($file){
        if (empty($file['name'])) return false;
        //获取扩展名
        $ext = self::getSafeName($file['name']);
        //判定是否是允许的文件类型
        if (!Widget_Upload::checkFileType($ext)) return false;
        //获取文件名
        $filePath   =   date('Y') . '/' . date('m') . '/';
        $fileName   =   sprintf('%u', crc32(uniqid())) . '.' . $ext;
        //如果没有临时文件，则退出
        if(!isset($file['tmp_name'])) return false;
        //获取插件参数
        $options = Typecho_Widget::widget('Widget_Options')->plugin('QNUpload');
        //初始化七牛
        self::__qninit__($options);
        $putPolicy  =   new Qiniu_RS_PutPolicy($options->bucket);
        $upToken    =   $putPolicy->Token(null);
        $putExtra   =   new Qiniu_PutExtra();
        list($ret, $err) = Qiniu_PutFile($upToken, $filePath . $fileName, $file['tmp_name'], $putExtra);
        if($err !== null) return false;
        return array(
            'name' => $file['name'],
            'path' => $filePath . $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @Typecho_Common::mimeContentType(rtrim($options->domain,'/') . '/' . $filePath . $fileName),
        );
    }
     /**
     * 文件修改处理函数
     *
     * @access public
     * @param array $content 当前文件信息
     * @param array $file    新上传文件信息
     * @return mixed
     */
    public static function modifyHandle($content, $file){
        if (empty($file['name'])) return false;
        //获取扩展名
        $ext = self::getSafeName($file['name']);
        if ($content['attachment']->type != $ext)  return false;
        //获取文件名
        $fileName = $content['attachment']->path;
        //获取插件参数
        $options = Typecho_Widget::widget('Widget_Options')->plugin('QNUpload');
        $bucket  = $options->bucket;
        //初始化七牛
        self::__qninit__($options);
        //如果没有临时文件，则退出
        if(!isset($file['tmp_name'])) return false;
        //上传前删除旧文件
        $client = new Qiniu_MacHttpClient(null);
        $err = Qiniu_RS_Delete($client, $bucket, $fileName);
        //重新上传
        $putPolicy  =   new Qiniu_RS_PutPolicy($options->bucket);
        $upToken    =   $putPolicy->Token(null);
        $putExtra   =   new Qiniu_PutExtra();
        list($ret, $err) = Qiniu_PutFile($upToken, $fileName, $file['tmp_name'], $putExtra);
        if($err !== null) return false;
        return array(
            'name' => $file['name'],
            'path' => $fileName,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @Typecho_Common::mimeContentType(rtrim($options->domain,'/') . '/' . $fileName),
        );
    }
     /**
     * 文件删除
     *
     * @access public
     * @param array $content 当前文件信息
     * @return mixed
     */
    public static function deleteHandle($content){
        $options = Typecho_Widget::widget('Widget_Options')->plugin('QNUpload');
        self::__qninit__($options);
        $client = new Qiniu_MacHttpClient(null);
        $err = Qiniu_RS_Delete($client, $options->bucket, $content['attachment']->path);
        return !$err;
    }
    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function attachmentDataHandle($content){
        $options = Typecho_Widget::widget('Widget_Options')->plugin('QNUpload');
        self::__qninit__($options);
        list($ret, $err)    =   Qiniu_RS_Stat($client, $options->bucket, $fileName);
        return $err === null ? $ret : false;
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content){
        $domain = Typecho_Widget::widget('Widget_Options')->plugin('QNUpload')->domain;
        //因为七牛放弃了默认的 xxx.u.qiniudn.com,且api中并无域名返回，所以域名为必填
        $tmp    = preg_match('/http(s)?:\/\/[\w\d\.\-\/]+$/is', $domain);    //粗略验证域名
        if(!$tmp) return false;
        return Typecho_Common::url($content['attachment']->path, $domain);
    }

    /**
     * 七牛初始化
     *
     * @access public
     * @return object
     */
    private static function __qninit__($options){
        $server = intval($options->server);
        require_once("Qiniu/rs.php");
        require_once("Qiniu/io.php");
        $accessKey = $options->ak;
        $secretKey = $options->sk;
        Qiniu_SetKeys($accessKey, $secretKey);
    }

    /**
     * 获取安全的文件名 
     * 
     * @param string $name 
     * @static
     * @access private
     * @return string
     */
    private static function getSafeName(&$name){
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);
        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    /**
     * 创建上传路径
     *
     * @access private
     * @param string $path 路径
     * @return boolean
     */
    private static function makeUploadDir($path){
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;
        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }
        if ($last == $current) {
            return true;
        }
        if (!@mkdir($last)) {
            return false;
        }
        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);
        return self::makeUploadDir($path);
    }
}
