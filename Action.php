<?php
class YangCong_Action extends Typecho_Widget implements Widget_Interface_Do {
    const URL_QRCODE_FOR_BINDING = "https://api.yangcong.com/v2/qrcode_for_binding";
    const URL_QRCODE_FOR_AUTH = "https://api.yangcong.com/v2/qrcode_for_auth";
    const URL_EVENT_RESULT = "https://api.yangcong.com/v2/event_result";

    protected $db, $options, $plugin, $user, $yc_id, $yc_key;

    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
        /** 初始化数据库 */
        $this->db = Typecho_Db::get();
        /** 初始化常用组件 */
        $this->options = $this->widget('Widget_Options');
        $this->user = $this->widget('Widget_User');
        $this->plugin = $this->options->plugin('YangCong');

        $this->yc_id = $this->plugin->yc_id;
        $this->yc_key = $this->plugin->yc_key;
        $this->yc_auth_type = $this->plugin->yc_auth_type;
    }
    public function execute() {}

    public function login(){
        $params = array('app_id' => $this->yc_id,
                        'auth_type' => $this->yc_auth_type,
                        'signature' => md5("app_id=" . $this->yc_id . "auth_type=" . $this->yc_auth_type . $this->yc_key));
        $client = Typecho_Http_Client::get();
        $response = $client->setQuery($params)->send(self::URL_QRCODE_FOR_AUTH);
        $response = json_decode($response, true);
        $this->response->throwJson($response);
    }

    public function bind(){
        if ($this->db->fetchObject($this->db->select(array('COUNT(*)' => 'num'))->from('table.options')->where('name = ? AND user = ?', 'yc_bind_uid', $this->user->uid))->num <= 0) {
            $params = array('app_id' => $this->yc_id,
                            'auth_type' => $this->yc_auth_type,
                            'signature' => md5("app_id=" . $this->yc_id . "auth_type=" . $this->yc_auth_type . $this->yc_key));
            $client = Typecho_Http_Client::get();
            $response = $client->setQuery($params)->send(self::URL_QRCODE_FOR_BINDING);
            $response = json_decode($response, true);
            $this->response->throwJson($response);
        }else{
            $this->response->throwJson(null);
        }
    }

    public function auth(){
        $eventId = $this->request->get("event_id");
        $params = array('app_id' => $this->yc_id,
                        'event_id' => $eventId,
                        'signature' => md5("app_id=" . $this->yc_id . "event_id=" . $eventId . $this->yc_key));
        $client = Typecho_Http_Client::get();
        $response = $client->setQuery($params)->send(self::URL_EVENT_RESULT);
        $response = json_decode($response, true);
        $result = array('status' => $response['status']);
        if($response['status'] == 200){
            $action = $this->request->get("action");
            if($action == 'bind'){
                if ($this->db->fetchObject($this->db->select(array('COUNT(*)' => 'num'))->from('table.options')->where('name = ? AND user = ?', 'yc_bind_uid', $this->user->uid))->num > 0) {
                    $this->widget('Widget_Abstract_Options')->update(array('value' => $response['uid']), $this->db->sql()->where('name = ? AND user = ?', 'yc_bind_uid', $this->user->uid));
                } else {
                    $this->widget('Widget_Abstract_Options')->insert(array(
                        'name'  =>  'yc_bind_uid',
                        'value' =>  $response['uid'],
                        'user'  =>  $this->user->uid
                    ));
                }
            }else if($action == 'login'){
                $user = $this->db->fetchRow($this->db->select()->from('table.options')->where('name = ? and value = ?', 'yc_bind_uid', $response['uid'])->limit(1));

                if (!empty($user)) {
                    $authCode = function_exists('openssl_random_pseudo_bytes') ?
                        bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
                    $user['authCode'] = $authCode;

                    Typecho_Cookie::set('__typecho_uid', $user['user'], 0);
                    Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), $expire);

                    //更新最后登录时间以及验证码
                    $this->db->query($this->db->update('table.users')->expression('logged', 'activated')->rows(array('authCode' => $authCode))->where('uid = ?', $user['user']));
                    $this->user->simpleLogin($user['user']);
                }

                /** 跳转验证后地址 */
                if (NULL != $this->request->referer) {
                    $result['redirect'] = $this->request->referer;
                } else if (!$this->user->pass('contributor', true)) {
                    /** 不允许普通用户直接跳转后台 */
                    $result['redirect'] = $this->options->profileUrl;
                } else {
                    $result['redirect'] = $this->options->adminUrl;
                }
            }
        }
        $this->response->throwJson($result);
    }


    public function action(){
        // $this->security->protect();
        $this->on($this->request->is('do=auth'))->auth();
        $this->on($this->request->is('do=bind'))->bind();
        $this->on($this->request->is('do=login'))->login();
    }
}
?>