<?php
/**
 * OptimizeDB Plugin
 *
 * @copyright  Copyright (c) 2014 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */

class OptimizeDB_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $opstions;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
        $this->opstions = Helper::options()->plugin('OptimizeDB');
    }
    public function optimize(){
        $config = Typecho_Db::get()->getConfig();
        $dblist = $this->db->fetchAll("SHOW TABLE STATUS FROM " . $config[0]->database);
        foreach($dblist as $row){
            $result = $this->db->fetchAll('OPTIMIZE TABLE ' . $row['Name']);
        }
        $this->widget('Widget_Notice')->set(_t('数据库优化成功！'), 'success');
        $this->response->goBack();
    }

    public function action(){
        if(!$this->widget('Widget_User')->pass('administrator')){
            throw new Typecho_Widget_Exception(_t('禁止访问'), 403);
        }
        $this->on($this->request->is('optimize'))->optimize();
    }
}
?>
