<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 将 Typecho 的附件上传至七牛云存储中。【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package QiniuFile
 * @author LiCxi, 冰剑, abelyao
 * @version 1.3.3
 * @link https://github.com/typecho-fans/plugins/tree/master/QiniuFile
 * @date 2020-06-24
 */

class QiniuFile_Plugin implements Typecho_Plugin_Interface {
    public static function activate() {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('QiniuFile_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('QiniuFile_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('QiniuFile_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('QiniuFile_Plugin', 'attachmentHandle');
        return _t('插件已经激活，需先配置七牛的信息！');
    }
    public static function deactivate() {}
    public static function config(Typecho_Widget_Helper_Form $form) {
        $bucket = new Typecho_Widget_Helper_Form_Element_Text('bucket', null, null, _t('空间名称：'));
        $form->addInput($bucket->addRule('required', _t('“空间名称”不能为空！')));

        $accesskey = new Typecho_Widget_Helper_Form_Element_Text('accesskey', null, null, _t('AccessKey：'));
        $form->addInput($accesskey->addRule('required', _t('AccessKey 不能为空！')));

        $secretkey = new Typecho_Widget_Helper_Form_Element_Text('secretkey', null, null, _t('SecretKey：'));
        $form->addInput($secretkey->addRule('required', _t('SecretKey 不能为空！')));

        $domain = new Typecho_Widget_Helper_Form_Element_Text('domain', null, 'http://', _t('绑定域名：'), _t('以 http:// 开头，结尾不要加 / ！'));
        $form->addInput($domain->addRule('required', _t('请填写空间绑定的域名！'))->addRule('url', _t('您输入的域名格式错误！')));

        $savepath = new Typecho_Widget_Helper_Form_Element_Text('savepath', null, '{year}/{month}/', _t('保存路径格式：'), _t('附件保存路径的格式，默认为 Typecho 的 {year}/{month}/ 格式，注意<strong style="color:#C33;">前面不要加 / </strong>！<br />可选参数：{year} 年份、{month} 月份、{day} 日期'));
        $form->addInput($savepath->addRule('required', _t('请填写保存路径格式！')));

        $list = array('关闭', '开启');
        $element = new Typecho_Widget_Helper_Form_Element_Radio('is_save', $list, 0, _t('是否在本服务器保留备份'),_t('开启后会先上传至服务器一份，然后再同步到七牛，如果同步七牛失败则使用服务器地址'));
        $form->addInput($element);

        $imgview = new Typecho_Widget_Helper_Form_Element_Radio('imgview', 
            array('-1' => '不使用缩略图',
                  '0' => '限定缩略图的长边最多为<code style="color:#d14">LongEdge</code>，短边最多为<code style="color:#d14">ShortEdge</code>，进行等比缩放，不裁剪。',
                  '1' => '限定缩略图的宽最少为<code style="color:#d14">Width</code>，高最少为<code style="color:#d14">Height</code>，进行等比缩放，居中裁剪。',
                  '2' => '限定缩略图的宽最多为<code style="color:#d14">Width</code>，高最多为<code style="color:#d14">Height</code>，进行等比缩放，不裁剪。',
                  '3' => '限定缩略图的宽最少为<code style="color:#d14">Width</code>，高最少为<code style="color:#d14">Height</code>，进行等比缩放，不裁剪。',
                  '4' => '限定缩略图的长边最少为<code style="color:#d14">LongEdge</code>，短边最少为<code style="color:#d14">ShortEdge</code>，进行等比缩放，不裁剪。',
                  '5' => '限定缩略图的长边最少为<code style="color:#d14">LongEdge</code>，短边最少为<code style="color:#d14">ShortEdge</code>，进行等比缩放，居中裁剪。',)
            , '-1', '缩略图模式', NULL);
        $form->addInput($imgview->multiMode());

        $imgparam = new Typecho_Widget_Helper_Form_Element_Text('imgparam', null, '400|300|400|300', '缩略图参数', '参数格式：<code style="color:#d14">Width|Height|LongEdge|ShortEdge</code>，|前后都不要留空格');
        $form->addInput($imgparam);

        $imgstyle = new Typecho_Widget_Helper_Form_Element_Text('imgstyle', null, '', _t('样式分隔符+图片样式名称：'), _t('填写<a href="https://portal.qiniu.com/kodo/bucket" target="_blank">空间设置</a>里建立的图片样式名(前面加分隔符)如-test，该项有值时禁用缩略图模式'));
        $form->addInput($imgstyle);
    }
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    // 获得插件配置信息
    public static function getConfig() {
        return Typecho_Widget::widget('Widget_Options')->plugin('QiniuFile');
    }

    // 新版SDK调用(php5.3-7.0可用)
    public static function initAuto($accesskey, $secretkey) {
        require_once('autoload.php');
        return new Qiniu\Auth($accesskey, $secretkey);
    }

    public static function deleteFile($filepath) {
        // 获取插件配置
        $option = self::getConfig();

        if($option->is_save){
            @unlink(__TYPECHO_ROOT_DIR__. '/usr/uploads/' . $filepath);
        }

        // 新版SDK删除(php5.3-7.0可用)
        $qiniu = self::initAuto($option->accesskey, $option->secretkey);
        $bucketMgr = new Qiniu\Storage\BucketManager($qiniu);
        return $bucketMgr->delete($option->bucket, $filepath);
    }

    public static function uploadFile($file, $content = null) {
        // 获取上传文件
        if (empty($file['name'])) return false;

        // 校验扩展名
        $part = explode('.', $file['name']);
        $ext = (($length = count($part)) > 1) ? strtolower($part[$length-1]) : '';
        if (!Widget_Upload::checkFileType($ext)) return false;

        // 获取插件配置
        $option = self::getConfig();
        $date = new Typecho_Date(Typecho_Widget::widget('Widget_Options')->gmtTime);

        // 保存位置
        $savepath = preg_replace(array('/\{year\}/', '/\{month\}/', '/\{day\}/'), array($date->year, $date->month, $date->day), $option->savepath);
        $_name=sprintf('%u', crc32(uniqid())) . '.' . $ext;
        $savename = $savepath . $_name;
        if (isset($content))
        {
            $savename = $content['attachment']->path;
            self::deleteFile($savename);
        }

        // 上传文件
        $filename = $file['tmp_name'];
        //if (!isset($filename)) return false;

        //是否保存在本地
        if($option->is_save){
            $options = Typecho_Widget::widget('Widget_Options');
            $date = new Typecho_Date($options->gmtTime);
            $path = __TYPECHO_ROOT_DIR__. '/usr/uploads/' . $savepath;
            if(!file_exists($path)){
                mkdir($path,0777,true);
            }
            $put = isset($file['bytes']) ? file_put_contents($path.$_name, $file['bytes']) : move_uploaded_file($filename, $path.$_name);
            if($put){
                $filename=$path.$_name;
                $data=array(
                    'name'  =>  $file['name'],
                    'path'  =>  $savename,
                    'size'  =>  $file['size'],
                    'type'  =>  $ext,
                    'mime'  =>  Typecho_Common::mimeContentType($filename)
                );
            }
        }

        // 新版SDK上传(php5.3-7.0可用)
        $token = self::initAuto($option->accesskey, $option->secretkey)->uploadToken($option->bucket);
        $uploadMgr = new Qiniu\Storage\UploadManager();
        //兼容byte流方式写入
        if (isset($file['bytes'])) {
            list($result, $error) = $uploadMgr->put($token, $savename, $file['bytes']);
        } else {
            list($result, $error) = $uploadMgr->putFile($token, $savename, $filename);
        }

        if ($error == null)
        {
            return array
            (
                'name'  =>  $file['name'],
                'path'  =>  $savename,
                'size'  =>  $file['size'],
                'type'  =>  $ext,
                'mime'  =>  isset($file['bytes']) ? $file['mime'] : Typecho_Common::mimeContentType($filename) // fix php5.6 requires absolute path
            );
        }else{
            return $data?$data:false;
        }
    }

    // 上传文件处理函数
    public static function uploadHandle($file) {
        return self::uploadFile($file);
    }
    // 修改文件处理函数
    public static function modifyHandle($content, $file) {
        return self::uploadFile($file, $content);
    }
    // 删除文件处理函数
    public static function deleteHandle(array $content) {
        self::deleteFile($content['attachment']->path);
    }
    // 获取实际文件绝对访问路径
    public static function attachmentHandle(array $content) {
        $option = self::getConfig();
        $view = '';
        if($option->imgview > -1 && strpos($content['attachment']->mime, 'image/') !== false && $option->imgstyle == ''){
            $array = explode('|', $option->imgparam);
            $param = array('Width' => isset($array['0']) ? $array['0'] : 400,
                           'Height' => isset($array['1']) ? $array['1'] : 300,
                           'LongEdge' => isset($array['2']) ? $array['2'] : 400,
                           'ShortEdge' => isset($array['3']) ? $array['3'] : 300);
            if(in_array($option->imgview, array('1', '2', '3'))){
                $view = '/%type%/w/%Width%/h/%Height%';
            }else if(in_array($option->imgview, array('0', '4', '5'))){
                $view = '/%type%/w/%LongEdge%/h/%ShortEdge%';
            }
            $view = '?imageView2'.str_replace(array('%type%', '%Width%', '%Height%', '%LongEdge%', '%ShortEdge%'), array($option->imgview, $param['Width'], $param['Height'], $param['LongEdge'], $param['ShortEdge']), $view);
        }
        return Typecho_Common::url($content['attachment']->path, $option->domain).$view.$option->imgstyle;
    }
}