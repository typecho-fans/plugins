<?php
/**
 * @name 每日一句
 * @package DailySentence
 * @author 冰剑
 * @link http://www.binjoo.net
 * @description 每日一句唯美的英语
 * @version 1.0.0
 *
 * @param false
 */
class AddonsDailySentence {
    private $result;
    private $postObj;
    private $params;

    function __construct($result, $postObj = NULL, $params = NULL) {
        $this->result = $result;
        $this->postObj = $postObj;
        $this->params = $params;
    }

    public function execute(){
        $client = Typecho_Http_Client::get();
        //金山词霸http://open.iciba.com/dsapi/
        //词海http://en.dict.cn/api/article/daily
        $response = $client->send('http://en.dict.cn/api/article/daily');
        $response = json_decode($response);
        $text = $response->en.chr(10).chr(10);
        $text .= $response->ch;
        $this->result->setMsgType(MessageTemplate::TEXT)->setText($text)->send();
    }
}
?>
