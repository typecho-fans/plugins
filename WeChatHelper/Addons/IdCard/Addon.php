<?php
/**
 * @name 身份证信息查询
 * @package IdCard
 * @author 冰剑
 * @link http://www.binjoo.net
 * @version 1.0.0
 *
 * @param true
 */
class AddonsIdCard {
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

        $this->query = array('app' => 'idcard.get',
                              'format' => 'json',
                              'appkey' => '10755',
                              'sign' => 'ce865fa86edc8cdbfe59b3cc27fe621b',
                              'idcard' => '');
    }

    public function execute(){
        $this->get();
    }

    private function get(){
        if($this->params){
            $this->query['idcard'] = $this->params['param'];
        }
        $this->result->setMsgType(MessageTemplate::TEXT);
        $client = Typecho_Http_Client::get();
        $response = $client->setQuery($this->query)->send($this->url);
        $response = json_decode($response);

        if($response->success){
            $text = '号码：'.$response->result->idcard.chr(10);
            $text .= '地区：'.$response->result->att.chr(10);
            $text .= '出生：'.$response->result->born.chr(10);
            $text .= '性别：'.$response->result->sex;
        }else{
            $text = $response->msg;
        }
        $this->result->setText($text)->send();
    }
}
?>
