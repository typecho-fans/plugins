<?php
class Utils {
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

    public static function getAddons(){
        $files = glob(__TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__ . '/' . 'WeChatHelper/Addons' . '/*.php');

        $result = array();
        foreach ($files as $file) {
            $info = Utils::parseInfo($file);
            $file = basename($file);
            
            if ('index.php' != $file && $info['name'] != '' && $info['package'] != '') {
                $result[$file] = $info['name'];
            }
        }
        return $result;
    }

    public static function parseInfo($file){
        $tokens = token_get_all(file_get_contents($file));
        $isDoc = false;

        $info = array(
            'name'   =>  '',
            'author'    =>  '',
            'package'   =>  '',
            'version'   =>  ''
        );

        foreach ($tokens as $token) {
            if (is_array($token) && T_DOC_COMMENT == $token[0]) {
                /** 分行读取 */
                $lines = preg_split("(\r|\n)", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));

                        if (!empty($line) && '@' == $line[0]) {
                            $line = trim(substr($line, 1));
                            $args = explode(' ', $line);
                            $key = array_shift($args);
                            if (isset($key)) {
                                $info[$key] = trim(implode(' ', $args));
                            }
                        }
                    }
                }
            }
        }
        return $info;
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