<?php
/**
 * WeChatHelper Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
include_once 'Utils.php';
class WeChatHelper_Widget_WeChat extends Widget_Abstract implements Widget_Interface_Do {
    private $postObj, $result;

    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }
    public function select() {}
    public function insert(array $options) {}
    public function update(array $options, Typecho_Db_Query $condition){}
    public function delete(Typecho_Db_Query $condition){}
    public function size(Typecho_Db_Query $condition){}
    public function execute(){}
    /**
     * 事件推送
     */
    public function isEvent(){
        if($this->postObj->Event == "subscribe"){
            $info = Typecho_Widget::widget('WeChatHelper_Widget_Users')->subscribe($this->postObj);
            $this->result->setText(isset($this->options->WCH_welcome) ? $this->options->WCH_welcome : '')->setMsgType(MessageTemplate::TEXT)->send();
        }else if($this->postObj->Event == "unsubscribe"){
            $info = Typecho_Widget::widget('WeChatHelper_Widget_Users')->unsubscribe($this->postObj);
        }
    }
    /**
     * 文本消息
     */
    public function isText(){
        $val = $this->postObj->Content;
        $params = $this->isParam();
        if($params){
            $val = $params['cmd'];
        }

        $select = $this->db->select()->from('table.wch_reply')->join('table.wch_keywords', 'table.wch_keywords.rid = table.wch_reply.rid', Typecho_Db::LEFT_JOIN)->where('table.wch_keywords.name = ?', $val)->where('table.wch_reply.status = ?', 1)->limit(1);
        $custom = $this->db->fetchObject($select);
        if(isset($custom->content)){    //正常文本处理
            if($custom->type === 'text'){
                $this->result->setText($custom->content)->setMsgType(MessageTemplate::TEXT)->send();
            }else if($custom->type === 'image'){
                $this->result->setText('助手暂时还不支持图片回复！')->setMsgType(MessageTemplate::TEXT)->send();
            }else if($custom->type === 'system'){
                $this->blogPost($custom->command);
            }else if($custom->type === 'addons'){
                $this->addonsAction($custom->command, $this->postObj, $params);
            }
        }else if(isset($this->options->WCH_thirdPartySearch) && $this->options->WCH_thirdPartyUrl && $this->options->WCH_thirdPartyToken && $this->options->WCH_thirdPartySearch) { //第三方处理
            $this->thirdParty();
        }else{
            $this->result->setText(isset($this->options->WCH_notfound) ? $this->options->WCH_notfound : '完全不明白你在说什么！')->setMsgType(MessageTemplate::TEXT)->send();
        }
    }
    /**
     * 图片消息
     */
    public function isImage(){
        $this->result->setText('助手暂时还不支持图片消息！')->setMsgType(MessageTemplate::TEXT)->send();
    }
    /**
     * 语音消息
     */
    public function isVoice(){
        $this->result->setText('助手暂时还不支持语音消息！')->setMsgType(MessageTemplate::TEXT)->send();
    }
    /**
     * 视频消息
     */
    public function isVideo(){
        $this->result->setText('助手暂时还不支持视频消息！')->setMsgType(MessageTemplate::TEXT)->send();
    }
    /**
     * 地理位置消息
     */
    public function isLocation(){
        $this->result->setText('助手暂时还不支持地理位置消息！')->setMsgType(MessageTemplate::TEXT)->send();
    }
    /**
     * 链接消息
     */
    public function isLink(){
        $this->result->setText('助手暂时还不支持链接消息！')->setMsgType(MessageTemplate::TEXT)->send();
    }

    /**
     * 是否带有参数
     */
    public function isParam(){
        $select = $this->db->select()->from('table.wch_reply')->join('table.wch_keywords', 'table.wch_keywords.rid = table.wch_reply.rid', Typecho_Db::LEFT_JOIN)->where('table.wch_reply.type = ?', 'addons')->where('table.wch_reply.status = ?', 1);
        $allAddons = $this->db->fetchAll($select);
        $result = NULL;
        foreach ($allAddons as $row) {
            $len = Typecho_Common::strLen($row['name']);
            $cmd = Typecho_Common::substr($this->postObj->Content, 0, $len, '');
            if(Typecho_Common::strLen($this->postObj->Content) > $len && $cmd === $row['name'] && $row['param']){
                $result['value'] = $this->postObj->Content;
                $result['cmd'] = $cmd;
                $result['param'] = Typecho_Common::substr($this->postObj->Content, $len, Typecho_Common::strLen($this->postObj->Content, ''));
                break;
            }
        }
        return $result;
    }

    /**
     * 第三方平台
     */
    public function thirdParty(){
        $postStr = file_get_contents("php://input");
        $params['signature'] = $this->options->WCH_thirdPartyToken;
        $params['timestamp'] = time();
        $params['nonce'] = rand(100000000, 999999999);

        $client = Typecho_Http_Client::get();
        $response = $client->setHeader('Content-Type', 'text/xml')
        ->setHeader('User-Agent', $this->useragent)
        ->setQuery($params)
        ->setData($postStr)
        ->send($this->options->WCH_thirdPartyUrl);
        $this->result->setSendContent($response)->setMsgType(MessageTemplate::THIRD)->send();
    }

    /**
     * 博客日志数据
     */
    public function blogPost($action){
        $result = $this->$action();
        foreach ($result as $row) {
            $row = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($row);
            $item['title'] = $row['title'];
            $item['description'] = Typecho_Common::subStr(str_replace("\n", '', trim(strip_tags($row['text']))), 0, 100, '...');
            //$item['description'] = Typecho_Common::subStr(trim(strip_tags($this->excerpt)), 0, 100, '...');
            //$item['picurl'] = 'https://www.google.com/images/srpr/logo11w.png';
            
            $img = array();
            $image = '';
            if(0 === strpos($row['text'], '<!--markdown-->')){
                echo 'yes';
            }else{
                preg_match('/("|\'|\(|(:\s)|=)(http:\/\/(.*?)\.(jpg|gif|png|bmp))/i', $row['text'], $img);
                if(count($img)){
                    preg_match('/^(.*?)h/is', $img[0], $prefix);
                    if(!isset($prefix[1])) $prefix[1] = '';
                    $image = str_replace($prefix[1], '', $img[0]);
                }
            }

            //print_r($image);

            $item['picurl'] = $image;
            $item['url'] = $row['permalink'];
            $this->result->addItem($item);
        }
        $this->result->setMsgType(MessageTemplate::NEWS)->send();
    }

    private function sysGeneralSql(){
        $sql = $this->db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $this->options->gmtTime)
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.password IS NULL');
        return $sql;
    }

    /**
      * 最新日志
      */ 
    private function sys_recent(){
        $sql = $this->sysGeneralSql()->order('table.contents.created', Typecho_Db::SORT_DESC)->limit(5);
        $result = $this->db->fetchAll($sql);
        return $result;
    }

    /**
      * 随机日志
      */ 
    private function sys_random(){
        $sql = $this->sysGeneralSql()->limit(5)->order('RAND()');
        $result = $this->db->fetchAll($sql);
        return $result;
    }

    /**
      * 热评日志
      */ 
    private function sys_hot_comment(){
        $sql = $this->sysGeneralSql()->order('table.contents.commentsNum', Typecho_Db::SORT_DESC)->limit(5);
        $result = $this->db->fetchAll($sql);
        return $result;
    }

    public function addonsAction($action, $postObj, $params = NULL){
        $file = __TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__ . '/WeChatHelper/Addons/'.$action.'/Addon.php';
        include_once $file;
        $info = Typecho_Widget::widget('WeChatHelper_Widget_Addons')->parseInfo($file);
        $class = 'Addons'.$info['package'];
        $addons = new $class($this->result, $postObj, $params);
        $addons->execute();
    }

    public function action() {
        $postStr = file_get_contents("php://input");//$this->request->get("HTTP_RAW_POST_DATA");
        if (!empty($postStr)){
            $this->response->setContentType("text/xml");
            $this->postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->result = new MessageTemplate($this->postObj);
            $exe = 'is'.ucwords($this->postObj->MsgType);
            if(method_exists($this, $exe)){
                $this->$exe();
            }
        }
    }
}
