<?php
class Utils {
    const MENU_CREATE_URL = 'https://api.weixin.qq.com/cgi-bin/menu/create';
    const MENU_REMOVE_URL = 'https://api.weixin.qq.com/cgi-bin/menu/delete';
    public static function getDefaultMessage($msg){
        $tmp = array(
            'welcome'       => '哟，客官，您来啦！'.chr(10).'发送\'h\'让小的给您介绍一下！',
            'notfound'      => '对不起，我完全不明白你在说什么！'
        );
        return $tmp[$msg];
    }
    public static function getMsgType($type = NULL){
        $result = array('text' => '文本消息',
                        //'image' => '图片消息',
                        'system' => '系统消息',
                        'addons' => '插件扩展');
        if($type){
            return $result[$type];
        }else{
            return $result;
        }
    }

    public static function getSystemMsg(){
        $result = array('sys_random' => '随机日志',
                        'sys_recent' => '最新日志',
                        'sys_hot_comment' => '热评日志');
        return $result;
    }

    public static function getAccessToken(){
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        if(isset($options->WCH_appid) && isset($options->WCH_appsecret)){
            if(isset($options->WCH_access_token) && isset($options->WCH_expires_in) && $options->WCH_expires_in > time()){
                return $options->WCH_access_token;
            }else{
                $client = Typecho_Http_Client::get();
                $params = array('grant_type' => 'client_credential',
                                'appid' => $options->WCH_appid, 'secret' => $options->WCH_appsecret);
                $response = $client->setQuery($params)->send('https://api.weixin.qq.com/cgi-bin/token');
                $response = json_decode($response);
                if(isset($response->errcode)){
                    //throw new Typecho_Plugin_Exception(_t('对不起，请求错误。ErrCode：'.$response->errcode.' - ErrMsg：'.$response->errmsg));
                    return NULL;
                }else{
                    $db->query($db->update('table.options')->rows(array('value' => $response->access_token))->where('name = ?', 'WeChatHelper_access_token'));
                    $db->query($db->update('table.options')->rows(array('value' => time() + $response->expires_in))->where('name = ?', 'WeChatHelper_expires_in'));
                    return $response->access_token;
                }
            }
        }else{
            //throw new Typecho_Plugin_Exception(_t('对不起, 请先在高级功能中填写正确的APP ID和APP Secret。'));
            return NULL;
        }
    }
}

class MessageTemplate {
    const TEXT = 'text';    //文本
    const IMAGE = 'image';  //图片
    const VOICE = 'voice';  //语音
    const VIDEO = 'video';  //视频
    const MUSIC = 'music';  //音乐
    const NEWS = 'news';    //图文
    const THIRD = 'third';  //第三方
    private $toUserName;
    private $fromUserName;
    /**
     * 消息时间
     */
    private $createTime;
    /**
     * 消息类型
     */
    private $msgType;
    /**
     * 最终输出内容
     */
    private $result;
    /**
     * 设置消息内容
     */
    private $content;
    /**
     * 
     */
    private $item = array();
    private $sendContent;
    private $_textTpl;
    private $_newsTpl;
    private $_itemTpl;

    public function __construct($postObj) {
        $this->toUserName = $postObj->ToUserName;
        $this->fromUserName = $postObj->FromUserName;
        $this->createTime = time();
        $this->_textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content><FuncFlag>0</FuncFlag></xml>"; 
        $this->_newsTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>%s</Articles><FuncFlag>0</FuncFlag></xml>";
        $this->_itemTpl = "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>";
    }
    /**
     * 设置直接发送的消息内容，适用于第三方平台
     */
    public function setSendContent($sendContent){
        $this->sendContent = $sendContent;
        return $this;
    }
    /**
     * 设置消息类型
     */
    public function setMsgType($msgType){
        $this->msgType = $msgType;
        return $this;
    }
    /**
     * 设置文本内容
     */
    public function setText($content) {
        $this->content = $content;
        return $this;
    }
    /**
     * 组合发送消息
     */
    public function addItem($item){
        array_push($this->item, $item);
    }
    public function send(){
        switch ($this->msgType) {
            case MessageTemplate::TEXT:
                $this->result = sprintf($this->_textTpl, $this->fromUserName, $this->toUserName, $this->createTime, $this->content);
                break;
            case MessageTemplate::THIRD:
                $this->result = $this->sendContent;
                break;
            case MessageTemplate::IMAGE:
                $this->result = '图片类型';
                break;
            case MessageTemplate::NEWS:
                foreach ($this->item as $key => $value) {
                    $this->result .= sprintf($this->_itemTpl, $value['title'], $value['description'], $value['picurl'], $value['url']);
                }
                $this->result = sprintf($this->_newsTpl, $this->fromUserName, $this->toUserName, $this->createTime, count($this->item), $this->result);
                break;
            default:
                $this->result = 'error';
        }
        echo $this->result;
    }
}
?>