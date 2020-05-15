<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class WeChatShare_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $prefix;
    private $wx_config;
    private $system_options;
    private $widget_options;
    private $user;

    public function action()
    {
        $this->init();
        $this->on($this->request->is('do=insert'))->insertWxShare();
        $this->on($this->request->is('do=ajax-get'))->ajaxGetWxShare();
    }

    /*
    * 编辑或者新增文章的时候把微信分享的数据插入到wx_share表
    */
    public function insertWxShare()
    {
        if($this->user->group != 'administrator') {
            throw new Typecho_Plugin_Exception(_t('f**k,别瞎jb搞'));
        }
        $data = [];
        //接收数据
        $data['wx_title'] = trim($_POST['wx_title']);

        $data['wx_description'] = trim($_POST['wx_description']);

        $data['wx_image'] = trim($_POST['wx_image']);

        $data['wx_url']  = !empty(trim($_POST['wx_url'])) ? trim($_POST['wx_url']) :'';

        $cid= !empty(trim($_POST['cid'])) ? trim($_POST['cid']) :'';

        if($cid) {
            //取出数据
            $wx_share_data= $this->db->fetchAll($this->db->select()->from($this->prefix.'wx_share')->where('cid = ?', $cid));
            if($wx_share_data) {
                /** 更新数据 */
                $this->db->query($this->db->update($this->prefix.'wx_share')->rows($data)->where('cid=?',$cid)); 
            } else {
                /** 插入数据 */
                $this->db->query($this->db->insert($this->prefix.'wx_share')->rows($data));               
            }

        }else {
            /** 插入数据 */
            $this->db->query($this->db->insert($this->prefix.'wx_share')->rows($data));
        }
    }

    /**
    * 前台ajax获取微信分享信息
    */
    public function ajaxGetWxShare()
    {

        if(!$this->request->isPost()) {

            throw new Typecho_Plugin_Exception(_t('f**k,别瞎jb搞'));
        }

        $params = $this->request->from(['cid','parameter_type','title','signature_url']);

        extract($params);

        if(empty($title) || empty($signature_url) || empty($parameter_type)) {

            throw new Typecho_Plugin_Exception(_t('f**k,别瞎jb搞'));
        }

        $wx_share = [];

        switch ($parameter_type) {

            case 'index':

                $wx_share['wx_title'] = $this->system_options->title;

                $wx_share['wx_description'] = $this->system_options->description;

                $wx_share['wx_url'] = $this->system_options->siteUrl;

                break;

            case 'post' == $parameter_type || 'page' == $parameter_type:

                //取出数据
                $wx_share_data= $this->db->fetchAll($this->db->select()->from($this->prefix.'wx_share')->where('cid = ?', $cid));

                if(!$wx_share_data) {

                    $wx_share['wx_title'] = $title;

                    $wx_share['wx_description'] = $this->system_options->description;

                    $wx_share['wx_url'] = $signature_url;

                }else {

                    $wx_share['wx_title'] = $wx_share_data[0]['wx_title'];

                    $wx_share['wx_description'] = $wx_share_data[0]['wx_description'];

                    $wx_share['wx_image'] = $wx_share_data[0]['wx_image'];

                    $wx_share['wx_url'] = $wx_share_data[0]['wx_url'];
                }

                break;

            default:

                $wx_share['wx_title'] = $title;

                $wx_share['wx_description'] = $this->system_options->description;

                $wx_share['wx_url'] = $signature_url;

                break;
        }

        !empty($wx_share['wx_image']) || $wx_share['wx_image'] = $this->wx_config->wx_image;

        $signPackage = $this->getSignPackage($signature_url);

        $wx_share['appId'] = $this->wx_config->wx_AppID;

        $wx_share['timestamp'] = $signPackage['timestamp'];

        $wx_share['nonceStr'] = $signPackage['nonceStr'];

        $wx_share['signature'] = $signPackage['signature'];

        _e(json_encode($wx_share));

    }

    /*
    * 获取微信分享配置SignPackage值
    */
    public function getSignPackage($url) {

        $this->getJsApiTicket();

        $timestamp = time();

        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = 'jsapi_ticket='.$this->wx_config->jsapi_ticket.'&noncestr='.$nonceStr.'&timestamp='.$timestamp.'&url='.$url;

        $signature = sha1($string);

        $signPackage = [
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
            "rawString" => $string,
        ];
        return $signPackage;
    }

    /*
    * 生成随机字符串
    */
    private function createNonceStr($length = 16) {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $str = '';

        for ($i = 0; $i < $length; $i++) {

        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);

        }

        return $str;
    }


    /*
    * 获取JsApiTicket,如果过时就重新获取
    */
    private function getJsApiTicket() {

        if ($this->wx_config->jsapi_ticket_expire_time < time()) {

            $this->getAccessToken();

            $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$this->wx_config->access_token;

            $res = json_decode($this->httpGet($url));

            $ticket = $res->ticket;

            if ($ticket) {

                $this->wx_config->jsapi_ticket_expire_time = time() + 7000;

                $this->wx_config->jsapi_ticket = $ticket;

                $this->updateWxConfig();
            }
        }
    }

    /**
    * 获取AccessToken,如果过时就重新获取
    */
    private function getAccessToken() {

        if ($this->wx_config->access_token_expire_time < time()) {

            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->wx_config->wx_AppID.'&secret='.$this->wx_config->wx_AppSecret;

            $res = json_decode($this->httpGet($url));

            $access_token = $res->access_token;

            if ($access_token) {

                $this->wx_config->access_token_expire_time = time() + 7000;

                $this->wx_config->access_token = $access_token;

            }
        }
    }

    /*
    *服务器与微信服务器通信
    */
    private function httpGet($url) {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_TIMEOUT, 500);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);

        curl_setopt($curl, CURLOPT_CAINFO,'usr/plugins/WeChatShare/cacert.pem');

        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    /*
    * 获取微信插件的配置
    */

    public function  getWxConfig()
    {

        $this->wx_config = json_decode(json_encode(unserialize(Helper::options()->plugin('WeChatShare'))));

        //判断是否配置好APPID
        if (empty($this->wx_config->wx_AppID)) {

            throw new Typecho_Plugin_Exception(_t('微信AppID未配置'));
        }
        if (empty($this->wx_config->wx_AppSecret)) {

            throw new Typecho_Plugin_Exception(_t('微信AppSecret密钥未配置'));
        }
        //判断是否设置了默认图片URL
        if(empty($this->wx_config->wx_image)) {

            $this->wx_config->wx_image = Typecho_Common::url('usr/plugins/WeChatShare/nopic.jpg', $this->system_options->siteUrl);
        }

    }

    /*
    * 更新微信插件的配置
    */
    public function updateWxConfig()
    {

        $data = ['value'=>serialize($this->wx_config)];

        $this->db->query($this->db->update($this->prefix.'options')->rows($data)->where('name = ?', 'plugin:WeChatShare'));

    }
    /*
    * 初始化
    */
    public function init()
    {
        $this->getWxConfig();

        $this->db = Typecho_Db::get();

        $this->prefix = $this->db->getPrefix();

        $this->widget_options = Typecho_Widget::widget('Widget_Options');
        
        $this->user = Typecho_Widget::widget('Widget_User');

        $this->system_options = Helper::options();

    }
}
