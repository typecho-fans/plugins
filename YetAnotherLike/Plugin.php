<?php

namespace TypechoPlugin\YetAnotherLike;
 
use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Utils\Helper;
use Typecho\Widget\Helper\Form\Element\Radio;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Typecho 点赞插件
 *
 * @package YetAnotherLike
 * @author Ect07
 * @version 1.0.0
 * @since 1.3.0
 * @link https://ect.fyi/
 */
class Plugin implements PluginInterface
{
    /**
     * 激活插件方法
     */
    public static function activate()
    {
        try {
            Helper::addAction('like', 'TypechoPlugin\YetAnotherLike\Like_Action');

        } catch (\Exception $e) {
            throw new \Typecho\Plugin\Exception('插件激活失败: ' . $e->getMessage());
        }
    }
 
    /**
     * 禁用插件方法  
     */
    public static function deactivate()
    {
        Helper::removeAction('like');
    }
 
    /**
     * 获取插件配置面板
     */
    public static function config(Form $form)
    {
        $login = new Radio('login', array('0'=> '任何用户', '1'=> '仅登录用户'), 0, '对哪些用户启用',
            '设置哪些用户可以点赞。如果选择“任何用户”，对于未登录的情况，每个IP地址只能点赞一次。');
        $form->addInput($login);
    }
 
    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Form $form)
    {
    }


    public static function like() {
        include "like-button.php";
    }

    public static function likeCount() {
        $db = \Typecho\Db::get();
        $LIKE_FIELD_NAME = "likes";
        $cid = \Widget\Archive::alloc()->cid;
        $userlist = ",";
        $already_exists = $db->fetchRow(
            $db->select()->from("table.fields")->where("cid = ?", $cid)->where("name = ?", $LIKE_FIELD_NAME)
        );
        if (!is_null($already_exists)) {
            $userlist = $already_exists["str_value"];
        }
        $like_count = substr_count($userlist, ",") - 1;
        echo $like_count;
    }
}
