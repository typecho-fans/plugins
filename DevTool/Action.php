<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();

class DevTool_Action extends Typecho_Widget {

    public function __construct($request, $response, $params = NULL){
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
        
        define('TYPEHO_ADMIN_PATH', __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__ );
    }

    public function index(){
        include_once 'views/index.php';
    }

    public function options(){
        $options = Helper::options();

        $optData = $this->db->fetchAll($this->db->select('name', 'user', 'value')
        ->from('table.options'));
        
        include_once 'views/options.php';
    }
}
