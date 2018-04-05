<?php
/**
 *  阿里云OSS上传插件（Typecho）
 *
 * @package OssForTypecho
 * @author Charmeryl
 * @version 1.0
 * @link https://bigrats.net
 * @dependence 1.0-*
 * @date 2018-04-05
 */

class OssForTypecho_Plugin implements Typecho_Plugin_Interface {
    //上传文件目录
    const UPLOAD_DIR = '/usr/uploads' ;

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('OssForTypecho_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('OssForTypecho_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('OssForTypecho_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('OssForTypecho_Plugin', 'attachmentHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('OssForTypecho_Plugin', 'attachmentDataHandle');
        return _t('插件已激活，请前往设置');
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
        return _t('插件已禁用');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        $desc = new Typecho_Widget_Helper_Form_Element_Text('desc', NULL, '', _t('插件使用说明：'),
            _t('<ol>
                      <li>插件基于阿里云aliyun-oss-php-sdk开发，若发现插件不可用，请到本插件 <a target="_blank" href="https://github.com/CharmeRyl/typecho-plugin-ossfile">GitHub发布地址</a> 检查是否有更新，或者提交Issues。<br></li>
                      <li>在阿里云 <a target="_blank" href="https://ak-console.aliyun.com/#/accesskey">AccessKey管理控制台</a> 页面里获取AccessKeyID与AccessKeySecret。<br></li>
                      <li>插件不会验证配置的正确性，请自行确认配置信息正确，否则不能正常使用。<br></li>
                      <li>插件会替换所有之前上传的文件的链接，若启用插件前存在已上传的数据，请自行将其上传至COS相同目录中以保证正常显示；同时，禁用插件也会导致链接恢复，也请自行将数据下载至相同目录中。<br></li>
                    </ol>'));
        $form->addInput($desc);

        $acid = new Typecho_Widget_Helper_Form_Element_Text('acid',
            NULL, '',
            _t('AccessKeyId：'));
        $form->addInput($acid->addRule('required', _t('AccessId不能为空！')));

        $ackey = new Typecho_Widget_Helper_Form_Element_Text('ackey',
            NULL, '',
            _t('AccessKeySecret：'));
        $form->addInput($ackey->addRule('required', _t('AccessKey不能为空！')));

        $region = new Typecho_Widget_Helper_Form_Element_Select('region',
            array('oss-cn-hangzhou' => '华东 1', 'oss-cn-shanghai' => '华东 2', 'oss-cn-qingdao' => '华北 1',
                'oss-cn-beijing' => '华北 2', 'oss-cn-zhangjiakou' => '华北 3', 'oss-cn-huhehaote' => '华北 5',
                'oss-cn-shenzhen' => '华南 1', 'oss-cn-hongkong' => '香港', 'oss-us-west-1' => '美国西部 1 （硅谷）',
                'oss-us-east-1' => '美国东部 1 （弗吉尼亚）', 'oss-ap-southeast-1' => '亚太东南 1 （新加坡）',
                'oss-ap-southeast-2' => '亚太东南 2 （悉尼）', 'oss-ap-southeast-3' => '亚太东南 3 （吉隆坡）',
                'oss-ap-southeast-5' => '亚太东南 5 （雅加达）', 'oss-ap-northeast-1' => '亚太东北 1 （日本）',
                'oss-ap-south-1' => '亚太南部 1 （孟买）', 'oss-eu-central-1' => '欧洲中部 1 （法兰克福）',
                'oss-me-east-1' => '中东东部 1 （迪拜）'),
            'oss-cn-hangzhou', _t('存储区域：')
        );
        $form->addInput($region);

        $suffix = new Typecho_Widget_Helper_Form_Element_Radio('suffix', array('.aliyuncs.com' => '外网', '-internal.aliyuncs.com' => '内网'),
            '.aliyuncs.com', _t('节点访问方式：'), _t('阿里云主机选择内网方式可节省上传流量'));
        $form->addInput($suffix);

        $bucket = new Typecho_Widget_Helper_Form_Element_Text('bucket',
            NULL, '',
            _t('Bucket名称：'));
        $form->addInput($bucket->addRule('required', _t('Bucket名称不能为空！')));

        $domain = new Typecho_Widget_Helper_Form_Element_Text('domain',
            NULL, '',
            _t('Bucket自定义域名：'),
            _t('可使用自定义域名（留空则使用默认域名）<br>例如：http://oss.example.com（需加上前面的 http:// 或 https://）'));
        $form->addInput($domain);

        echo '<script>
          window.onload = function() 
          {
            document.getElementsByName("desc")[0].type = "hidden";
          }
        </script>';
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) { }

    /**
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file) {
        if (empty($file['name'])) {
            return false;
        }
        //获取扩展名
        $ext = self::getSafeName($file['name']);
        //判定是否是允许的文件类型
        if (!Widget_Upload::checkFileType($ext) || Typecho_Common::isAppEngine()) {
            return false;
        }
        //获取设置参数
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OssForTypecho');
        //获取文件名
        $date = new Typecho_Date($options->gmtTime);
        $fileDir = self::getUploadDir() . '/' . $date->year . '/' . $date->month;
        $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $path = $fileDir . '/' . $fileName;
        //获得上传文件
        $uploadfile = self::getUploadFile($file);
        //如果没有临时文件，则退出
        if (!isset($uploadfile)) {
            return false;
        }

        /* 上传到OSS */
        //初始化OSS
        $ossClient = self::OssInit();
        try {
            $result = $ossClient->uploadFile($options->bucket, substr($path,1), $uploadfile);
        } catch (Exception $e) {
            print_r($e);
            return false;
        }

        if (!isset($file['size'])){
            $fileInfo = $result['info'];
            $file['size'] = $fileInfo['size_upload'];
        }

        //返回相对存储路径
        return array(
            'name' => $file['name'],
            'path' => $path,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => @Typecho_Common::mimeContentType($path)
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
    public static function modifyHandle($content, $file) {
        if (empty($file['name'])) {
            return false;
        }

        //获取扩展名
        $ext = self::getSafeName($file['name']);
        //判定是否是允许的文件类型
        if ($content['attachment']->type != $ext || Typecho_Common::isAppEngine()) {
            return false;
        }
        //获取设置参数
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OssForTypecho');
        //获取文件路径
        $path = $content['attachment']->path;
        //获得上传文件
        $uploadfile = self::getUploadFile($file);
        //如果没有临时文件，则退出
        if (!isset($uploadfile)) {
            return false;
        }

        /* 上传到OSS */
        //初始化OSS
        $ossClient = self::OssInit();
        try {
            $result = $ossClient->uploadFile($options->bucket, substr($path,1), $uploadfile);
        } catch (Exception $e) {
            return false;
        }

        if (!isset($file['size'])){
            $fileInfo = $result['info'];
            $file['size'] = $fileInfo['size_upload'];
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
    public static function deleteHandle(array $content) {
        //获取设置参数
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OssForTypecho');
        //初始化COS
        $ossClient = self::OssInit();
        try {
            $ossClient->deleteObject($options->bucket, $content['attachment']->path);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content) {
        //获取设置参数
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OssForTypecho');
        return Typecho_Common::url($content['attachment']->path, self::getDomain());
    }

    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function attachmentDataHandle($content) {
        //获取设置参数
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OssForTypecho');
        $ossClient = self::OssInit();
        return $ossClient->getObjectMeta($options->bucket, substr($content['attachment']->path,1));
    }

    /**
     * Upyun初始化
     *
     * @access public
     * @return object
     */
    public static function OssInit() {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OssForTypecho');
        $endpoint = 'https://' . $options->region . $options->suffix;
        require_once 'autoload.php';
        return new OSS\OssClient($options->acid, $options->ackey, $endpoint);
    }

    /**
     *获取文件上传目录
     * @access private
     * @return string
     */
    private static function getUploadDir() {
        if(defined('__TYPECHO_UPLOAD_DIR__'))
        {
            return __TYPECHO_UPLOAD_DIR__;
        }
        else{
            return self::UPLOAD_DIR;
        }
    }

    /**
     * 获取上传文件
     *
     * @param array $file 上传的文件
     * @access private
     * @return string
     */
    private static function getUploadFile($file) {
        return isset($file['tmp_name']) ? $file['tmp_name'] : (isset($file['bytes']) ? $file['bytes'] : (isset($file['bits']) ? $file['bits'] : ''));
    }

    /**
     *获取文件上传目录
     * @access private
     * @return string
     */
    private static function getDomain() {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('OssForTypecho');
        $domain = $options->domain;
        if(empty($domain))  $domain = 'https://' . $options->bucket . '.' . $options->region . '.aliyuncs.com';
        return $domain;
    }

    /**
     * 获取安全的文件名
     *
     * @param string $name
     * @static
     * @access private
     * @return string
     */
    private static function getSafeName(&$name) {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);
        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

}