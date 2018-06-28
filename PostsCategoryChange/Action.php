<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class PostsCategoryChange_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $prefix;
    public function action()
    {
        $user = Typecho_Widget::widget('Widget_User');
        $user->pass('administrator');
        $this->options = Typecho_Widget::widget('Widget_Options');
        $this->db = Typecho_Db::get();
        $this->prefix = $this->db->getPrefix();
        $this->on($this->request->is('do=change-category'))->makeChange();
        $this->on($this->request->is('do=change-status'))->changeStatus();
        exit;
    }

    public function makeChange()
    {
        
        $cids = $this->request->filter('int')->getArray('cid');
        $mid = $this->request->filter('int')->get('mid');
        if(empty($cids)) {
            echo json_encode(['code'=>-1,'msg'=>'大佬，至少选择一篇文章！']);
            return;
        } else if(empty($mid)) {
            echo json_encode(['code'=>-1,'msg'=>'大佬，请选择一个分类！']);
            return;
        } else {
            $cid = implode(',',$cids);
            $select = 'SELECT cid FROM '.$this->prefix.'contents where cid in('.$cid.') and type="post"';
            $res = $this->db->fetchAll($this->db->query($select));
            if(empty($res)) {
                echo json_encode(['code'=>-1,'msg'=>'f**k,别特么瞎jb搞！']);
                return;
            } else {
                $post_cid = '';
                foreach ($res as $value) {
                    $post_cid .= $value['cid'].',';
                }
                $post_cid = trim($post_cid,',');
                
                $select = 'SELECT mid,type FROM '.$this->prefix.'metas where type="category"';

                $res = $this->db->fetchAll($this->db->query($select));
                
                $category_mid = '';
                foreach ($res as $value) {
                    $category_mid .= $value['mid'].',';
                }
                $category_mid = trim($category_mid,',');
                
                $res = $this->db->fetchAll($this->db->query($select));
                
                $update = $this->db->update($this->prefix.'relationships')->rows(array('mid'=>$mid))->where('cid in ('.$post_cid.') AND mid IN ('.$category_mid.')');
                $row = @$this->db->query($update);
                if($row) {
                    echo json_encode(['code'=>1,'msg'=>'本次成功更新'.$row.'篇文章！']);
                    return;
                } else {
                    echo json_encode(['code'=>-1,'msg'=>'更新失败']);
                    return;
                }
            }
        }
    }
    
    public function changeStatus()
    {
        $cids = $this->request->filter('int')->getArray('cid');
        if(empty($cids)) {
            echo json_encode(['code'=>-1,'msg'=>'大佬，至少选择一篇文章！']);
            return;
        } else {
            $cid = implode(',',$cids);
            $select = 'SELECT cid,status FROM '.$this->prefix.'contents where cid in('.$cid.') and type="post"';
            $res = $this->db->fetchAll($this->db->query($select));
            if(empty($res)) {
                echo json_encode(['code'=>-1,'msg'=>'f**k,别特么瞎jb搞！']);
                return;
            } else {
                $count = 0;
                foreach ($res as $value) {
                    if($value['status'] == 'publish') {
                        $update = $this->db->update($this->prefix.'contents')->rows(array('status'=>'hidden'))->where('cid in ('.$value['cid'].')');                      
                    } elseif($value['status'] == 'hidden') {
                        $update = $this->db->update($this->prefix.'contents')->rows(array('status'=>'publish'))->where('cid in ('.$value['cid'].')');
                    }
                    @$this->db->query($update) && $count ++;
                }
                if($count>0) {
                    echo json_encode(['code'=>1,'msg'=>'本次成功更新'.$count.'篇文章！']);
                    return;
                } else {
                    echo json_encode(['code'=>-1,'msg'=>'更新失败']);
                    return;
                }
            }
        }
    }
}
