<?php
/**
 * MostCache Plugin
 *
 * @copyright  Copyright (c) 2014 skylzl (http://www.woyoudian.com)
 * @license    GNU General Public License 2.0
 * 
 */

class MostCache_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $config;
    private static $pluginName = 'MostCache';
    private static $tableName = 'most_cache';

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->config = Helper::options()->plugin(self::$pluginName);
        $this->db = Typecho_Db::get();        
    }

    /**
     *删除指定缓存
     *
     * @param string $hash
     */
    public function del($hash){
        $this->db->query($this->db->delete('table.'.self::$tableName)
                    ->where('hash = ?', $hash));
        
    }
    
    /**
     * 修改缓存规则
     */
    public function edit(){
      if($_POST['preg']){
         $preg = $_POST['preg'];
	$select = $this->db->query("SELECT * FROM ".$this->db->getPrefix()."options WHERE name ='plugin:MostCache'");
	$oldConfig = $this->db->fetchAll($select);				
	$newConfig = unserialize($oldConfig[0]['value']);
        unset($newConfig['cacheType']);
        $newConfig['cacheType'] = $preg;
        $this->db->query($this->db->update('table.options')->rows(array('value' => serialize($newConfig)))->where('name = ?', 'plugin:MostCache'));
      }
    }
    
    /**
     * 重设缓存
     */
    public function resetCache(){
        
	if($this->config->cacheMode=='Mysql'){#1.Mysql模式 				
            $table = $this->db->getPrefix().self::$tableName;
            $this->db->query("TRUNCATE TABLE $table ");			
	}else{#2.SAE模式
		$mc = memcache_init();
		memcache_flush($mc);
	}        
        $this->request->throwJson('success');
    }

    public function action(){
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->is('del'))->del($this->request->del);
        $this->on($this->request->is('resetCache'))->resetCache();
        $this->on($this->request->is('edit'))->edit();
        $this->response->goBack();
    }
}
?>
