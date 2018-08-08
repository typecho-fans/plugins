<?php
/**
 *  腾讯云COS上传插件（Typecho）
 *
 * @package CosForTypecho
 * @author Charmeryl
 * @version 1.0.1
 * @link https://bigrats.net
 * @dependence 1.0-*
 * @date 2018-08-08
 */

class CosForTypecho_Plugin implements Typecho_Plugin_Interface {
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
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('CosForTypecho_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('CosForTypecho_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('CosForTypecho_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('CosForTypecho_Plugin', 'attachmentHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('CosForTypecho_Plugin', 'attachmentDataHandle');
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
                      <li>插件基于腾讯云cos-php-sdk-v5开发，若发现插件不可用，请到本插件 <a target="_blank" href="https://github.com/CharmeRyl/typecho-plugin-cosfile">GitHub发布地址</a> 检查是否有更新，或者提交Issues。<br></li>
                      <li>在腾讯云控制台 <a target="_blank" href="https://console.cloud.tencent.com/capi">个人API密钥</a> 页面里获取 APPID、SecretId、SecretKey 内容。<br></li>
                      <li>插件不会验证配置的正确性，请自行确认配置信息正确，否则不能正常使用。<br></li>
                      <li>插件会替换所有之前上传的文件的链接，若启用插件前存在已上传的数据，请自行将其上传至COS相同目录中以保证正常显示；同时，禁用插件也会导致链接恢复，也请自行将数据下载至相同目录中。<br></li>
                    </ol>'));
        $form->addInput($desc);

        $appid = new Typecho_Widget_Helper_Form_Element_Text('appid',
            null, '',
            _t('APPID：'));
        $form->addInput($appid->addRule('required', _t('APPID不能为空！')));

        $secid = new Typecho_Widget_Helper_Form_Element_Text('secid',
            NULL, '',
            _t('SecretId：'));
        $form->addInput($secid->addRule('required', _t('SecretId不能为空！')));

        $sekey = new Typecho_Widget_Helper_Form_Element_Text('sekey',
            NULL, '',
            _t('SecretKey：'));
        $form->addInput($sekey->addRule('required', _t('SecretKey不能为空！')));

        $region = new Typecho_Widget_Helper_Form_Element_Select('region',
            array('ap-beijing-1' => _t('北京一区（华北）'), 'ap-beijing' => _t('北京'), 'ap-shanghai' => _t('上海（华东）'),
                'ap-guangzhou' => _t('广州（华南）'), 'ap-chengdu' => _t('成都（西南）'), 'ap-chongqing' => _t('重庆'),
                'ap-singapore' => _t('新加坡'), 'ap-hongkong' => _t('香港'), 'na-toronto' => _t('多伦多'),'eu-frankfurt' => _t('法兰克福'),
                'ap-mumbai' => _t('孟买'), 'ap-seoul' => _t('首尔'), 'na-siliconvalley' => _t('硅谷'), 'na-ashburn' => _t('弗吉尼亚')),
            'ap-beijing-1',
            _t('Bucket地域：')
        );
        $form->addInput($region);

        $bucket = new Typecho_Widget_Helper_Form_Element_Text('bucket',
            NULL, '',
            _t('Bucket名称：'));
        $form->addInput($bucket->addRule('required', _t('Bucket名称不能为空！')));

        $domain = new Typecho_Widget_Helper_Form_Element_Text('domain',
            NULL, '',
            _t('Bucket自定义域名：'),
            _t('可使用自定义域名（留空则使用默认域名）<br>例如：http://cos.example.com（需加上前面的 http:// 或 https://）'));
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
        $options = Typecho_Widget::widget('Widget_Options')->plugin('CosForTypecho');
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

        /* 上传到COS */
        //初始化COS
        $cosClient = self::CosInit();
        try {
            $cosClient->upload(
                $bucket = $options->bucket .'-'. $options->appid,
                $key = $path,
                $body = fopen($uploadfile, 'rb'),
                $options = array(
                    "ACL"=>'public-read',
                    'CacheControl' => 'private'));
        } catch (Exception $e) {
            echo "$e\n";
            return false;
        }

        if (!isset($file['size'])){
            $fileInfo = $cosClient->headObject(array('Bucket' => $options->bucket .'-'. $options->appid, 'Key' => $path))->toArray();
            $file['size'] = $fileInfo['ContentLength'];
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
        $options = Typecho_Widget::widget('Widget_Options')->plugin('CosForTypecho');
        //获取文件路径
        $path = $content['attachment']->path;
        //获得上传文件
        $uploadfile = self::getUploadFile($file);
        //如果没有临时文件，则退出
        if (!isset($uploadfile)) {
            return false;
        }

        /* 上传到COS */
        $cosClient = self::CosInit();
        try {
            $cosClient->upload(
                $bucket = $options->bucket .'-'. $options->appid,
                $key = $path,
                $body = fopen($uploadfile, 'rb'),
                $options = array(
                    "ACL"=>'public-read',
                    'CacheControl' => 'private'));
        } catch (Exception $e) {
            echo "$e\n";
            return false;
        }

        if (!isset($file['size'])){
            $fileInfo = $cosClient->headObject(array('Bucket' => $options->bucket .'-'. $options->appid, 'Key' => $path))->toArray();
            $file['size'] = $fileInfo['ContentLength'];
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
        $options = Typecho_Widget::widget('Widget_Options')->plugin('CosForTypecho');
        //初始化COS
        $cosClient = self::CosInit();
        try {
            $result = $cosClient->deleteObject(array(
                //bucket的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
                'Bucket' => $options->bucket .'-'. $options->appid,
                'Key' => $content['attachment']->path));
        } catch (Exception $e) {
            echo "$e\n";
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
        $options = Typecho_Widget::widget('Widget_Options')->plugin('CosForTypecho');
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
        $options = Typecho_Widget::widget('Widget_Options')->plugin('CosForTypecho');
        $cosClient = self::CosInit();
        return $cosClient->headObject(array('Bucket' => $options->bucket .'-'. $options->appid, 'Key' => $content['attachment']->path));
    }

    /**
     * COS初始化
     *
     * @access public
     * @return object
     */
    public static function CosInit() {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('CosForTypecho');
        require_once 'phar://'. __DIR__ .'/cos-sdk-v5.phar/vendor/autoload.php';;
        return new Qcloud\Cos\Client(array('region' => $options->region,
            'credentials'=> array(
                'secretId' => $options->secid,
                'secretKey' => $options->sekey)));
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
        $options = Typecho_Widget::widget('Widget_Options')->plugin('CosForTypecho');
        $domain = $options->domain;
        if(empty($domain))  $domain = 'http://' . $options->bucket . '-' . $options->appid . '.cos.' . $options->region . '.myqcloud.com';
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