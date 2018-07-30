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
            "http://rss.news.sohu.com/rss/pfocus.xml",
            "http://rss.news.sohu.com/rss/focus.xml",
            "http://rss.news.sohu.com/rss/guonei.xml",
            "http://rss.news.sohu.com/rss/guoji.xml",
            "http://rss.news.sohu.com/rss/junshi.xml",
            "http://rss.news.sohu.com/rss/sports.xml",
            "http://rss.news.sohu.com/rss/business.xml",
            "http://rss.news.sohu.com/rss/it.xml",
            "http://rss.news.sohu.com/rss/learning.xml",
            "http://rss.news.sohu.com/rss/yule.xml"
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
