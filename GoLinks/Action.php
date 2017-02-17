<?php
/**
 * GoLinks Plugin
 *
 * @copyright  Copyright (c) 2011 DEFE (http://defe.me)
 * @license    GNU General Public License 2.0
 * 
 */

class GoLinks_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;   

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        $this->db = Typecho_Db::get();        
    }

    /**
     * 添加新的链接转换
     * 
     */
    public function add(){

        $key = $this->request->key;
        $key = $key ? $key : Typecho_Common::randString(8);
        $target = $this->request->target;
        if($target === "" || $target === "http://"){
            $this->widget('Widget_Notice')->set(_t('请输入目标链接。'), NULL, 'error');
        }
        //判断key是否被占用
        elseif($this->getTarget($key)){            ;
            $this->widget('Widget_Notice')->set(_t('该key已被使用，请更换key值。'), NULL, 'error');
        }  else {
             $links=array(
            'key' => $key,
            'target' => $this->request->target,
            'count' => 0
            );
            $insertId = $this->db->query($this->db->insert('table.golinks')->rows($links));
        }
    }
    
    /**
     * 修改链接
     * 
     */
    
    public function edit(){
        $target = $this->request->url;
        $id = $this->request->id;
        if(trim($target) == "" || $target == "http://"){     
            $this->response->throwJson('error');
        }else{
            if($id){
            $this->db->query($this->db->update('table.golinks')->rows(array('target' => $target))
                    ->where('id = ?', $id));           
            $this->response->throwJson('success');
        }}
    }

    /**
     *删除链接转换
     *
     * @param int $id
     */
    public function del($id){
        $this->db->query($this->db->delete('table.golinks')
                    ->where('id = ?', $id));
        
    }

    /**
     * 链接重定向
     * 
     */
    public function golink()
    {
        $key = $this->request->key;
        $target = $this->getTarget($key);

        if( $target){
             //增加统计
            $count = $this->db->fetchObject($this->db->select('count')
                    ->from('table.golinks')
                    ->where('key = ?', $key))->count;

            $count = $count+1;

            $this->db->query($this->db->update('table.golinks')
                    ->rows(array('count' => $count))
                    ->where('key = ?', $key));

            
            //设置nofollow属性
            $this->response->setHeader('X-Robots-Tag','noindex, nofollow');
            //301重定向
            $this->response->redirect($target,301);

        }else{            
            throw new Typecho_Widget_Exception(_t('您访问的网页不存在'), 404);
        }        
    }

    /**
     * 获取目标链接
     *
     * @param string $key
     * @return void
     */
    public function getTarget($key){
        $target = $this->db->fetchRow($this->db->select('target')
                ->from('table.golinks')
                ->where(' key = ?' , $key));
         if($target['target']){
             return  $target['target'];
         }else{
             return FALSE;
         }
    }
    
    /**
     * 重设自定义链接
     */
    public function resetLink(){
        $link = $this->request->link;
        Helper::removeRoute('go');
        Helper::addRoute('go', $link, 'GoLinks_Action', 'golink');
        $this->response->throwJson('success');
    }

    public function action(){
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->is('add'))->add();
        $this->on($this->request->is('edit'))->edit();
        $this->on($this->request->is('del'))->del($this->request->del);
        $this->on($this->request->is('resetLink'))->resetLink();
        $this->response->goBack();
    }
}
?>
