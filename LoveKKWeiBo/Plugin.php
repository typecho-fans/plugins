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
 * @version 1.0.0
 * @link    https://www.lovekk.org
 */

if (!defined('__TYPECHO_ROOT_DIR__'))
    exit;

class LoveKKWeiBo_Plugin implements Typecho_Plugin_Interface
{
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
        // 获取上传文件
        if (empty($file['name'])) {
            return FALSE;
        }
        // 以.分割文件名
        $part = explode('.', $file['name']);
        // 获取扩展名
        $ext = (($length = count($part)) > 1) ? strtolower($part[$length - 1]) : '';
        // 验证扩展名
        if (!Widget_Upload::checkFileType($ext)) {
            return FALSE;
        }
        // 获取插件配置
        $plugin = Helper::options()->plugin('LoveKKWeiBo');
        // 获取临时文件名
        $filename = $file['tmp_name'];
        // 验证临时文件名
        if (!isset($filename)) {
            return FALSE;
        }
        // 是否为图片文件
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp'))) {
            // 是否载入了
            if(!class_exists('Consatan\Weibo\ImageUploader\Client')){
                require __DIR__.'/vendor/autoload.php';
            }
            // 初始化类
            $weibo = new Consatan\Weibo\ImageUploader\Client();
            // 上传图片
            $url = $weibo->upload($filename, $plugin->weiboUser, $plugin->weiboPass);
            // 返回上传结果
            return array(
                'name' => $file['name'],
                'path' => $url,
                'size' => $file['size'],
                'type' => $ext,
                'mime' => Typecho_Common::mimeContentType($filename)
            );
        } else {
            return array(
                'name' => $file['name'],
                'path' => '暂时只能上传gif、jpg、jpeg、png、bmp图片格式附件',
                'size' => $file['size'],
                'type' => $ext,
                'mime' => Typecho_Common::mimeContentType($filename)
            );
        }
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
     * @static
     * @access public
     *
     * @param $content
     *
     * @return string
     */
    static public function attachmentHandle($content)
    {
        return $content['attachment']->path;
    }
}