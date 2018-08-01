<?php
/**
 * WeChatHelper Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
include_once 'Utils.php';
class WeChatHelper_Widget_Config extends Widget_Abstract_Options implements Widget_Interface_Do {
    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }
    public function execute(){}

    /**
     * 基础设置
     */
    public function baseForm() {
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/WeChat?config&do=base'), Typecho_Widget_Helper_Form::POST_METHOD);

        $token = new Typecho_Widget_Helper_Form_Element_Text('token', NULL, NULL,
        _t('TOKEN'), _t('TOKEN内容自定义，需要与开发模式服务器配置中填写一致，推荐使用GUID。'));
        $token->value(isset($this->options->WCH_token) ? $this->options->WCH_token : '');
        $form->addInput($token);

        $welcome = new Typecho_Widget_Helper_Form_Element_Textarea('welcome', NULL, NULL, _t('欢迎提示语'), _t('用户在关注公众号时，会发送欢迎的提示消息。'));
        $welcome->value(isset($this->options->WCH_welcome) ? $this->options->WCH_welcome : Utils::getDefaultMessage('welcome'));
        $form->addInput($welcome);

        $notfound = new Typecho_Widget_Helper_Form_Element_Textarea('notfound', NULL, NULL, _t('找不到提示语'), _t('没开启第三方平台搜索功能时，会发送找不到的提示消息。'));
        $notfound->value(isset($this->options->WCH_notfound) ? $this->options->WCH_notfound : Utils::getDefaultMessage('notfound'));
        $form->addInput($notfound);

        $dropTable = new Typecho_Widget_Helper_Form_Element_Radio('dropTable',
            array('1' => _t('开启'), '0' => _t('关闭')),
                  NULL,  _t('<span style="color:#B94A48">数据删除</span>'), _t('<span style="color:#B94A48">开启后，禁用插件会删除插件设置数据和数据表。</span>'));
        $dropTable->value(isset($this->options->WCH_dropTable) ? $this->options->WCH_dropTable : '0');
        $form->addInput($dropTable);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 高级功能
     */
    public function deluxeForm() {
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/WeChat?config&do=deluxe'), Typecho_Widget_Helper_Form::POST_METHOD);

        $appid = new Typecho_Widget_Helper_Form_Element_Text('appid', NULL, NULL,
        _t('APP ID'), _t('TOKEN内容自定义，需要与开发模式服务器配置中填写一致，推荐使用GUID。'));
        $appid->value(isset($this->options->WCH_appid) ? $this->options->WCH_appid : '');
        $form->addInput($appid);

        $appsecret = new Typecho_Widget_Helper_Form_Element_Text('appsecret', NULL, NULL,
        _t('APP Secret'), _t('TOKEN内容自定义，需要与开发模式服务器配置中填写一致，推荐使用GUID。'));
        $appsecret->value(isset($this->options->WCH_appsecret) ? $this->options->WCH_appsecret : '');
        $form->addInput($appsecret);

        $access_token = new Typecho_Widget_Helper_Form_Element_Hidden('access_token', NULL, NULL);
        $access_token->value(isset($this->options->WCH_access_token) ? $this->options->WCH_access_token : '');
        $form->addInput($access_token);

        $expires_in = new Typecho_Widget_Helper_Form_Element_Hidden('expires_in', NULL, NULL);
        $expires_in->value(isset($this->options->WCH_expires_in) ? $this->options->WCH_expires_in : '0');
        $form->addInput($expires_in);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 第三方
     */
    public function thirdPartyForm() {
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/WeChat?config&do=thirdParty'), Typecho_Widget_Helper_Form::POST_METHOD);

        $thirdPartyUrl = new Typecho_Widget_Helper_Form_Element_Text('thirdPartyUrl', NULL, $this->options->WCH_thirdPartyUrl,
        _t('第三方平台链接'), _t('推荐平台：<a href="http://cloud.xiaoi.com/">小i机器人</a>'));
        $form->addInput($thirdPartyUrl);

        $thirdPartyToken = new Typecho_Widget_Helper_Form_Element_Text('thirdPartyToken', NULL, $this->options->WCH_thirdPartyToken, _t('第三方平台Token'), NULL);
        $form->addInput($thirdPartyToken);

        $thirdPartySearch= new Typecho_Widget_Helper_Form_Element_Radio('thirdPartySearch',
            array('1' => _t('开启'), '0' => _t('关闭')),
                  NULL,  _t('第三方平台搜索'),
                   _t('所有在系统中找不到的关键字是否提交给第三方平台处理。'));
        $thirdPartySearch->value(isset($this->options->WCH_thirdPartySearch) ? $this->options->WCH_thirdPartySearch : '0');
        $form->addInput($thirdPartySearch);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 积分设置
     */
    public function creditForm() {
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/WeChat?config&do=credit'), Typecho_Widget_Helper_Form::POST_METHOD);

        $subscribe_credit = new Typecho_Widget_Helper_Form_Element_Text('subscribe_credit', NULL, NULL,
        _t('订阅积分'), _t('订阅后，初始赠送积分。'));
        $subscribe_credit->value(isset($this->options->WCH_subscribe_credit) ? $this->options->WCH_subscribe_credit : '0');
        $form->addInput($subscribe_credit);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    public function updateConfig(){
        $baseForm = array('token', 'welcome', 'notfound', 'dropTable');
        $deluxeForm = array('appid', 'appsecret', 'access_token', 'expires_in');
        $thirdPartyForm = array('thirdPartyUrl', 'thirdPartyToken', 'thirdPartySearch');
        $creditForm = array('subscribe_credit');
        $do = $this->request->get('do').'Form';

        $settings = $this->request->from(${$do});   //动态传递表单参数
        foreach ($settings as $key => $value) {
            //if(!is_null($settings[$key])){    //判断参数是否为NULL
                $row['name'] = 'WCH_'.$key;
                $row['value'] = $value;
                if($this->db->fetchRow($this->select()->where('name = ?', $row['name'])->limit(1))){
                    $this->update($row, $this->db->sql()->where('name = ?', $row['name']));
                }else{
                    $this->insert($row);
                }
            //}
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('设置已经保存'), 'success');

        /** 转向原页 */
        $this->response->goBack();
    }

    public function action() {
        $this->on($this->request->is('do'))->updateConfig();
    }
}
