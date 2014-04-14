<?php
/**
 * @name 手机归属地
 * @package Phone
 * @author 冰剑
 * @version 1.0.0
 *
 * @param true
 */
class AddonsPhone {
    private $result;
    private $postObj;
    private $params;
    private $data;
    private $query;
    private $url = 'http://api.k780.com:88';

    function __construct($result, $postObj = NULL, $params = NULL) {
        $this->result = $result;
        $this->postObj = $postObj;
        $this->params = $params;

        $this->query = array('app' => 'phone.get',
                              'format' => 'json',
                              'appkey' => '10755',
                              'sign' => 'ce865fa86edc8cdbfe59b3cc27fe621b',
                              'phone' => '');
    }

    public function execute(){
        $this->get();
    }

    private function get(){
        if($this->params){
            $this->query['phone'] = $this->params['param'];
        }
        $this->result->setMsgType(MessageTemplate::TEXT);
        $client = Typecho_Http_Client::get();
        $response = $client->setQuery($this->query)->send($this->url);
        $response = json_decode($response);

        if($response->success){
            $text = '号码：'.$response->result->phone.chr(10);
            $text .= '地区：'.$response->result->att.chr(10);
            $text .= '区号：'.$response->result->area.chr(10);
            $text .= '类型：'.$response->result->ctype;
        }else{
            $text = $response->msg;
        }
        $this->result->setText($text)->send();
    }
}
?>
