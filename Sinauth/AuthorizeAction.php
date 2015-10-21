<?php
/**
 * Sinauth Plugin
 *
 * @copyright  Copyright (c) 2015 jimmy.chaw (http://x3d.cnblogs.com)
 * @license    GNU General Public License 2.0
 * 
 */

class Sinauth_AuthorizeAction extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $config;
    private static $pluginName = 'Sinauth';
    private static $tableName = 'users_oauth';

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->config = Helper::options()->plugin(self::$pluginName);
        $this->db = Typecho_Db::get();        
    }

    public function action(){
        //跳转
        if (!class_exists('SaeTOAuthV2')) {
            require_once './saetv2.ex.class.php';
        }
        $saeto_client = new SaeTOAuthV2($this->config->client_id, $this->config->client_secret);
        $authorize_url = $saeto_client->getAuthorizeURL($this->config->callback_url, 'code');
        $this->response->redirect($authorize_url);

        exit;
    }
    
    /**
     * 授权回调地址
     */
    public function callback(){
        if(empty($_GET['code'])) {
            throw new Typecho_Exception(_t('无效请求！'));
        }
        
        //跳转
        if (!class_exists('SaeTOAuthV2')) {
            require_once './saetv2.ex.class.php';
        }
        
        $saeto_client = new SaeTOAuthV2($this->config->client_id, $this->config->client_secret);
        //取access_token
        $access_token = $saeto_client->getAccessToken('code', array('code' => trim($_GET['code']), 'redirect_uri' => $this->config->callback_url));
        
        if (empty($access_token) || !is_array($access_token) || empty($access_token['uid'])) {
        	throw new Typecho_Exception(_t('获取access_token失败，请返回重新授权！'));
        }
        
        $table = $this->db->getPrefix() . self::$tableName;
        $query = $this->db->query("SELECT * FROM {$table} WHERE openid='{$access_token['uid']}' AND plateform='sina'");
		$users_oauth = $this->db->fetchRow($query);
        
        if (!empty($users_oauth['uid'])) { //该新浪帐号已经绑定了用户
        	if (Typecho_Widget::widget('Widget_User')->hasLogin()) { /** 直接返回 */
                
            	$this->response->redirect(Typecho_Widget::widget('Widget_Options')->index);
            } else { //让其直接登陆
                $this->setUserLogin($users_oauth['uid']);
                
                if (!Typecho_Widget::widget('Widget_User')->pass('contributor', true)) {
                    /** 不允许普通用户直接跳转后台 */
                    $this->response->redirect(Typecho_Widget::widget('Widget_Options')->profileUrl);
                } else {
                    $this->response->redirect(Typecho_Widget::widget('Widget_Options')->adminUrl);
                }
            }
            
            exit;
        }
        
        //该新浪帐号未绑定过
        
        /** 如果已经登录 */
        if (Typecho_Widget::widget('Widget_User')->hasLogin()) {
            /** 直接绑定 */
            $cookieUid = Typecho_Cookie::get('__typecho_uid');
            $this->bindOauthUser($cookieUid, $access_token['uid'], 'sina', $access_token['expires_in']);
            
            $this->response->redirect(Typecho_Widget::widget('Widget_Options')->index);
        } else {
        	//取用户信息
            $saetc_client = new SaeTClientV2($this->config->client_id, $this->config->client_secret, $access_token['access_token']);
            
            $weibo_user = $saetc_client->show_user_by_id($access_token['uid']);
            
            //创建用户
            $uid = $this->registerFromWeiboUser($weibo_user);
            
            if (!$uid) {
                throw new Typecho_Exception(_t('创建帐号失败，请联系管理员！'));
            }
            
            $this->setUserLogin($uid);
            
            $this->bindOauthUser($uid, $access_token['uid'], 'sina', $access_token['expires_in']);
            
            $this->response->redirect(Typecho_Widget::widget('Widget_Options')->profileUrl);
            
        }
        
        
        //构造用户帐号
        
        exit;
    }
    
    /**
     * 根据微博用户信息创建帐号
     */
    protected function registerFromWeiboUser(&$weibo_user) {
    	$hasher = new PasswordHash(8, true);
        $generatedPassword = Typecho_Common::randString(7);
        
        //TODO 用户名重复的问题
        
        $uname = $weibo_user['name'];
        
        $i = 0;
        if (!Typecho_Widget::widget('Widget_Abstract_Users')->nameExists($uname)) { //用户名存在
            echo 'here';
        	for ($i = 1; $i < 999; $i++) {
                echo $i;
                if (Typecho_Widget::widget('Widget_Abstract_Users')->nameExists($uname . '_' . $i)) {
                    $uname = $uname . '_' . $i;
                    break;
                }
            }
        }
        
        $dataStruct = array(
            'name'      =>  $uname,
            'mail'      =>  $weibo_user['idstr'] . ($i ? '_' . $i : '') . '@localhost.local',
            'screenName'=>  $weibo_user['screen_name'] . ($i ? '_' . $i : ''),
            'password'  =>  $hasher->HashPassword($generatedPassword),
            'created'   =>  time(),
            'url'		=>	$weibo_user['url'],
            'group'     =>  'subscriber'
        );
        
        $insertId = Typecho_Widget::widget('Widget_Abstract_Users')->insert($dataStruct);

        return $insertId;
    }
    
    public function nameExists($name)
    {
        $select = $this->db->select()
        ->from('table.users')
        ->where('name = ?', $name)
        ->limit(1);

        $user = $this->db->fetchRow($select);
        
        return $user ? false : true;
    }
    
    /**
     * 设置用户登陆状态
     */
    protected function setUserLogin($uid, $expire = 30243600) {
    	Typecho_Widget::widget('Widget_User')->simpleLogin($uid);
        
        $authCode = function_exists('openssl_random_pseudo_bytes') ?
            bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
        
        Typecho_Cookie::set('__typecho_uid', $uid, time() + $expire);
        Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), time() + $expire);
        
        //更新最后登录时间以及验证码
        $this->db->query($this->db
                         ->update('table.users')
                         ->expression('logged', 'activated')
                         ->rows(array('authCode' => $authCode))
                         ->where('uid = ?', $uid));
    }
    
    public function bindOauthUser($uid, $openid, $plateform = 'sina', $expires_in = 0) {
        $rows = array(
        	'openid' => $openid,
            'uid' => $uid,
            'plateform' => $plateform,
            'bind_time' => time(),
            'expires_in' => $expires_in
        );
    	return $this->db->query($this->db->insert('table.users_oauth')->rows($rows));
    }
}
