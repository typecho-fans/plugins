<?php
/**
 * @name IP查询
 * @package IpTaoBao
 * @author 冰剑
 * @link http://www.binjoo.net
 * @description 淘宝IP地址库IP查询
 * @version 1.0.0
 *
 * @param true
 */
class AddonsIpTaoBao {
    private $result;
    private $postObj;
    private $params;
    private $query;
    private $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=';

    function __construct($result, $postObj = NULL, $params = NULL) {
        $this->result = $result;
        $this->postObj = $postObj;
        $this->params = $params;

        $this->query = array('ip' => '');
    }

    public function execute(){
        if($this->params){
            $this->query['ip'] = $this->params['param'];
        }
        $this->result->setMsgType(MessageTemplate::TEXT);
        $client = Typecho_Http_Client::get();
        $response = $client->setQuery($this->query)->send($this->url);
        $response = json_decode($response);
        if(!$response->code){
            $text = 'IP：'.$response->data->ip.chr(10);
            $text .= '国家：'.$response->data->country.chr(10);
            $text .= '地区：'.$response->data->area.chr(10);
            $text .= '省份：'.$response->data->region.chr(10);
            $text .= '城市：'.$response->data->city.chr(10);
            $text .= '服务商：'.$response->data->isp;
        }else{
            $text = 'IP地址错误，请输入正确地址！';
        }
        $this->result->setText($text)->send();
    }
}
?>
