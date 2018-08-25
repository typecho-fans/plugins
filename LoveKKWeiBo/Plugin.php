<?php
// +----------------------------------------------------------------------
// | LoveKKWeiBo [ Plugin.php ]
// +----------------------------------------------------------------------
// | Create: 08/24/2018 15:06:00
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://www.lovekk.org All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/MIT )
// +----------------------------------------------------------------------
// | Author: 康粑粑 <admin@lovekk.org>
// +----------------------------------------------------------------------

/**
 * 把新浪微博作为附件图床 for Typecho
 *
 * @package LoveKKWeiBo
 * @author  康粑粑
 * @version 1.0.1
 * @link    https://www.lovekk.org
 */

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

class LoveKKWeiBo_Plugin implements Typecho_Plugin_Interface
{
    // 默认上传目录
    const UPLOAD_DIR = '/usr/uploads';

    /**
     * 插件激活
     *
     * @static
     * @access public
     */
    static public function activate()
    {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array(__CLASS__, 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array(__CLASS__, 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array(__CLASS__, 'attachmentHandle');
    }

    static public function deactivate()
    {
    }

    /**
     * 插件配置
     *
     * @static
     * @access public
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    static public function config(Typecho_Widget_Helper_Form $form)
    {
        $weiboUser = new Typecho_Widget_Helper_Form_Element_Text('weiboUser', NULL, '', _t('微博账号'), _t('建议尽量使用平时不用的小号'));
        $form->addInput($weiboUser->addRule('required', _t('微博账号必须填写')));
        $weiboPass = new Typecho_Widget_Helper_Form_Element_Password('weiboPass', NULL, '', _t('登录密码'));
        $form->addInput($weiboPass->addRule('required', _t('登录密码必须填写')));
    }

    static public function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 图片上传方法
     *
     * @static
     * @access public
     *
     * @param $file
     *
     * @return array|bool
     * @throws Typecho_Exception
     * @throws Typecho_Plugin_Exception
     */
    static public function uploadHandle($file)
    {
        // 检查上传文件名
        if (empty($file['name'])) {
            return FALSE;
        }
        // 获取扩展名
        $ext = self::getSafeName($file['name']);
        // 检查文件类型
        if (!Widget_Upload::checkFileType($ext)) {
            return FALSE;
        }
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKWeiBo');
        // 验证临时文件名
        if (!isset($file['tmp_name'])) {
            return FALSE;
        }
        // 是否有文件大小
        if (!isset($file['size'])) {
            $file['size'] = filesize($file['tmp_name']);
        }
        // 复制一份数据
        $files = $file['tmp_name'];
        // 附件地址
        $uri = '';
        // 是否为图片文件
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp'))) {
            // 是否载入了
            if (!class_exists('Consatan\Weibo\ImageUploader\Client')) {
                require __DIR__ . '/vendor/autoload.php';
            }
            // 初始化类
            $weibo = new Consatan\Weibo\ImageUploader\Client();
            // 上传图片
            $uri = $weibo->upload($files, $plugin->weiboUser, $plugin->weiboPass);
        } else {
            // 初始化一个时间对象
            $date = new Typecho_Date();
            // 初始化保存路径
            $path = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR, defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__) . '/' . $date->year . '/' . $date->month;
            // 目录是否存在
            if (!is_dir($path)) {
                // 创建目录
                if (!self::makeUploadDir($path)) {
                    return FALSE;
                }
            }
            // 生成存储文件名
            $fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
            // 组合路径
            $path = $path . '/' . $fileName;
            // 移动并保存文件
            if (!@move_uploaded_file($file['tmp_name'], $path)) {
                return FALSE;
            }
            // 附件地址
            $uri = (defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR) . '/' . $date->year . '/' . $date->month . '/' . $fileName;
            // 实际地址
            $files = $path;
        }
        return array(
            'name' => $file['name'],
            'path' => $uri,
            'size' => $file['size'],
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($files)
        );
    }

    /**
     * 图片修改
     *
     * @static
     * @access public
     *
     * @param $content
     * @param $file
     *
     * @return array|bool
     * @throws Typecho_Exception
     * @throws Typecho_Plugin_Exception
     */
    static public function modifyHandle($content, $file)
    {
        return self::uploadHandle($file);
    }

    /**
     * 获取附件路径
     *
     * @param array $content
     *
     * @return string
     * @throws Typecho_Exception
     */
    static public function attachmentHandle(array $content)
    {
        // 获取附件地址
        $path = $content['attachment']->path;
        // 是否为远程附件
        if ('http://' == substr($path, 0, 7) || 'https://' == substr($path, 0, 8)) {
            return $path;
        }
        // 获取系统配置
        $options = Typecho_Widget::widget('Widget_Options');
        // 返回附件路径
        return Typecho_Common::url($content['attachment']->path, defined('__TYPECHO_UPLOAD_URL__') ? __TYPECHO_UPLOAD_URL__ : $options->siteUrl);
    }

    /**
     * 获取安全的文件名
     *
     * @param string $name
     *
     * @static
     * @access private
     * @return string
     */
    static private function getSafeName(&$name)
    {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = FALSE === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);

        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

    /**
     * 创建上传路径
     *
     * @access private
     *
     * @param string $path 路径
     *
     * @return boolean
     */
    static private function makeUploadDir($path)
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && FALSE !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return TRUE;
        }

        if (!@mkdir($last)) {
            return FALSE;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::makeUploadDir($path);
    }
}