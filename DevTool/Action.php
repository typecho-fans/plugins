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

    public function post(){
        $rssfile = array(
            "http://news.ifeng.com/rss/index.xml",
            "http://news.ifeng.com/mil/rss/index.xml",
            "http://news.ifeng.com/history/rss/index.xml",
            "http://news.ifeng.com/rss/mainland.xml",
            "http://news.ifeng.com/rss/taiwan.xml",
            "http://news.ifeng.com/rss/world.xml",
            "http://news.ifeng.com/rss/society.xml",
            "http://news.ifeng.com/rss/photo.xml",
            "http://news.ifeng.com/sports/rss/index.xml"
        );

        $rssfile = $rssfile[rand(0, count($rssfile) - 1)];

        $xml = simplexml_load_file($rssfile);

        $postObj = new Widget_Abstract_Contents($this->request, $this->response);
        $insertId = array();

        $posts = array();
        foreach ($xml->channel->item as $item) {
            $post = array();
            $post['title'] = (string)$item->title[0];
            $post['text'] = (string)$item->description[0];
            $posts[] = $post;
        }

        if( ! empty($posts) ){
            foreach ($posts as $post) {
                $insertId[] = $postObj->insert($post);
            }
        }

        include_once 'views/post.php';
    }
}
