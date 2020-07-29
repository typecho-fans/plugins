<?php

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Version_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);

    }

    public function execute()
    {

    }

    public function action()
    {

    }

    public function permissionCheck()
    {
        $user = Typecho_Widget::widget('Widget_User');
        
        if(!$user->pass('editor', true))
            throw new Typecho_Widget_Exception(_t('没有编辑权限'), 403);
    }

    public function respond()
    {
        $this->response->setContentType('image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAQUAP8ALAAAAAABAAEAAAICRAEAOw==');
    }
    
    public function revert()
    {
        $this->permissionCheck();

        $vid = $this->request->get('vid');

        if(!isset($vid))
            throw new Typecho_Widget_Exception(_t('参数不正确'), 404);
        
        $vid = intval($vid);

        $db = Typecho_Db::get();
        $table = $db->getPrefix() . 'verion_plugin';
        $row = $db->fetchRow($db->select()->from($table)->where('vid = ? ', $vid));

        $cid = $row['cid'];

        // 找出文章和文章的草稿
        $raw = $db->fetchRow($db->select()->from('table.contents')->where("cid = ? ", $cid));
        $raw2 = $db->fetchRow($db->select()->from('table.contents')->where("parent = ? AND (type = 'post' OR type = 'post_draft' OR type = 'page' OR type = 'page_draft')", $cid));
        
        if(!empty($row))
        {
            $raw['text'] = $row['text'];
            $raw2['text'] = $row['text'];

            // 开始回退
            $db->query($db->update('table.contents')->rows($raw)->where('cid = ? ', $cid));
            $db->query($db->update('table.contents')->rows($raw2)->where("parent = ?  AND (type = 'post' OR type = 'post_draft' OR type = 'page' OR type = 'page_draft')", $cid));
        }else{
            throw new Typecho_Widget_Exception(_t('数据为空'), 404);
        }
        
        // $this->respond();
    }
    
    public function delete()
    {
        $this->permissionCheck();

        $vid = $this->request->get('vid');

        if(!isset($vid))
            throw new Typecho_Widget_Exception(_t('参数不正确'), 404);
        
        $vid = intval($vid);

        $db = Typecho_Db::get();
        $table = $db->getPrefix() . 'verion_plugin';

        $db->query($db->delete($table)->where('vid = ? ', $vid));
        
        // $this->respond();
    }
    
    public function preview()
    {
        $this->permissionCheck();

        $vid = $this->request->get('vid');

        if(!isset($vid))
            throw new Typecho_Widget_Exception(_t('参数不正确'), 404);
        
        $vid = intval($vid);

        $db = Typecho_Db::get();
        $table = $db->getPrefix() . 'verion_plugin';
        $row = $db->fetchRow($db->select()->from($table)->where('vid = ? ', $vid));
        
        $this->response->setContentType('text/plain');
        echo $row['text'];
    }

    public function comment()
    {
        $this->permissionCheck();

        $vid = $this->request->get('vid');
        $comment = $this->request->get('comment');

        if(!isset($vid))
            throw new Typecho_Widget_Exception(_t('参数不正确'), 404);
        
        $vid = intval($vid);

        $db = Typecho_Db::get();
        $table = $db->getPrefix() . 'verion_plugin';
        $row = $db->fetchRow($db->select()->from($table)->where('vid = ? ', $vid));

        if(!empty($row))
        {
            $row['comment'] = $comment;

            $db->query($db->update($table)->rows($row)->where('vid = ? ', $vid));
        }else{
            throw new Typecho_Widget_Exception(_t('数据为空'), 404);
        }

        // $this->respond();
    }

}