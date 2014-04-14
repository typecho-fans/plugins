<?php
/**
 * WeChatHelper Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
include_once 'Utils.php';
class WeChatHelper_Action extends Typecho_Widget implements Widget_Interface_Do
{
    protected $db;
    protected $options;
    protected $user;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /** 初始化数据库 */
        $this->db = Typecho_Db::get();
        /** 初始化常用组件 */
        $this->options = $this->widget('Widget_Options');
        $this->user = $this->widget('Widget_User');
    }
    public function execute() {}

    public function valid() {
        $echoStr = $this->request->get('echostr');
        if($this->checkSignature($this->options->WeChatHelper_token)){
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature($token) {
        $signature = $this->request->get('signature');
        $timestamp = $this->request->get('timestamp');
        $nonce = $this->request->get('nonce');

        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        
        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 数据
     * 
     */
    public function postAction(){
        Typecho_Widget::widget('WeChatHelper_Widget_WeChat')->action();
        /*
        $dir = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/WeChatHelper';
        $myfile = $dir.'/wechatDebug.txt';
        //echo $myfile;
        $file_pointer = @fopen($myfile,"a");
        @fwrite($file_pointer, $this->get_http_raw());
        @fclose($file_pointer);
        */
    }

    public function action(){

        if($this->request->isGet()){
            $this->on($this->request->is('valid'))->valid();
        }
        if($this->request->isPost()){
            $this->on($this->request->is('valid'))->postAction();
            if($this->request->is('config')){  //插件设置业务
                Typecho_Widget::widget('WeChatHelper_Widget_Config')->action();
            }else if($this->request->is('customreply')){  //自定义回复业务
                Typecho_Widget::widget('WeChatHelper_Widget_CustomReply')->action();
            }
        }
    }
}
?>