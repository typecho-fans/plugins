<?php
/**
 * SyncPost Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
require_once 'Constant.php';
require_once 'Http.php';

class SyncPost_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $_siteUrl;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->_siteUrl = Helper::options()->siteUrl;
    }

    public function tqq(){
        if ($this->request->get("code")) {//已获得code
            $code = $this->request->get("code");
            $openid = $this->request->get("openid");
            $openkey = $this->request->get("openkey");
            $params = array(
                'client_id' => TQQ_CLIENT_ID,
                'client_secret' => TQQ_CLIENT_SECRET,
                'redirect_uri' => $this->_siteUrl . TQQ_REDIRECT_URI,
                'grant_type' => 'authorization_code',
                'code' => $code
            );
            parse_str(HTTP::request(TQQ_ACCESS_TOKEN_URL, $params, 'GET'), $result);
            if(!isset($result['errorCode'])){
                $set = array(
                    'tqq_access_token' => $result['access_token'],
                    'tqq_expires_in' => $result['expires_in'],
                    'tqq_openid' => $openid,
                    'tqq_openkey' => $openkey,
                    'tqq_last_time' => time() + $result['expires_in']
                );
                Widget_Plugins_Edit::configPlugin("SyncPost", $set);
                $this->widget('Widget_Notice')->set(_t('腾讯微博授权成功！'), 'success');
            }else{
                $this->widget('Widget_Notice')->set(_t('腾讯微博授权失败，异常信息：'.$result['errcode'].' - '.$result['errorMsg']), 'error');
            }
            header('Location: ' . $this->_siteUrl . __TYPECHO_ADMIN_DIR__ . 'options-plugin.php?config=SyncPost');//刷新页面
        } else {//获取授权code
            $params = array(
                'client_id' => TQQ_CLIENT_ID,
                'redirect_uri' => $this->_siteUrl . TQQ_REDIRECT_URI,
                'response_type' => 'code'
            );
            header('Location: ' . TQQ_AUTHORIZATION_CODE_URL.'?'.http_build_query($params));//刷新页面
        }
    }

    public function sina(){
        if ($this->request->get("code")) {//已获得code
            $code = $this->request->get("code");
            $params = array(
                'client_id' => SINA_CLIENT_ID,
                'client_secret' => SINA_CLIENT_SECRET,
                'redirect_uri' => $this->_siteUrl . SINA_REDIRECT_URI,
                'grant_type' => 'authorization_code',
                'code' => $code
            );
            $result = json_decode(HTTP::request(SINA_ACCESS_TOKEN_URL, $params, 'POST'));
            if(!isset($result->error_code)){
                $set = array(
                    'sina_access_token' => $result->access_token,
                    'sina_expires_in' => $result->expires_in,
                    'sina_last_time' => time() + $result->expires_in
                );
                Widget_Plugins_Edit::configPlugin("SyncPost", $set);
                $this->widget('Widget_Notice')->set(_t('新浪微博授权成功！'), 'success');
            }else{
                $this->widget('Widget_Notice')->set(_t('新浪微博授权失败，异常信息：'.$result->error_code.' - '.$result->error_description), 'error');
            }
            header('Location: ' . $this->_siteUrl . __TYPECHO_ADMIN_DIR__ . 'options-plugin.php?config=SyncPost');//刷新页面
        } else {//获取授权code
            $params = array(
                'client_id' => SINA_CLIENT_ID,
                'redirect_uri' => $this->_siteUrl . SINA_REDIRECT_URI,
                'response_type' => 'code'
            );
            header('Location: ' . SINA_AUTHORIZATION_CODE_URL.'?'.http_build_query($params));//刷新页面
        }
    }

    public function douban(){
        if ($this->request->get("code")) {//已获得code
            $code = $this->request->get("code");
            $params = array(
                'client_id' => DOUBAN_CLIENT_ID,
                'client_secret' => DOUBAN_CLIENT_SECRET,
                'redirect_uri' => $this->_siteUrl . DOUBAN_REDIRECT_URI,
                'grant_type' => 'authorization_code',
                'code' => $code
            );
            $result = json_decode(HTTP::request(DOUBAN_ACCESS_TOKEN_URL, $params, 'POST'));
            if(!isset($result->code)){
                $set = array(
                    'douban_access_token' => $result->access_token,
                    'douban_refresh_token' => $result->refresh_token,
                    'douban_expires_in' => $result->expires_in,
                    'douban_last_time' => time() + $result->expires_in
                );
                Widget_Plugins_Edit::configPlugin("SyncPost", $set);
                $this->widget('Widget_Notice')->set(_t('豆瓣广播授权成功！'), 'success');
            }else{
                $this->widget('Widget_Notice')->set(_t('豆瓣广播授权失败，异常信息：'.$result->code.' - '.$result->msg), 'error');
            }
            header('Location: ' . $this->_siteUrl . __TYPECHO_ADMIN_DIR__ . 'options-plugin.php?config=SyncPost');//刷新页面
        } else {//获取授权code
            $params = array(
                'client_id' => DOUBAN_CLIENT_ID,
                'redirect_uri' => $this->_siteUrl . DOUBAN_REDIRECT_URI,
                'response_type' => 'code'
            );
            header('Location: ' . DOUBAN_AUTHORIZATION_CODE_URL.'?'.http_build_query($params));//刷新页面
        }
    }

    public function action(){
        if(!$this->widget('Widget_User')->pass('administrator')){
            throw new Typecho_Widget_Exception(_t('禁止访问'), 403);
        }
        $this->on($this->request->is('tqq'))->tqq();
        $this->on($this->request->is('sina'))->sina();
        $this->on($this->request->is('douban'))->douban();
    }
}
?>