<?php
/**
 * WeChatHelper Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
include_once 'Utils.php';
class WeChatHelper_Widget_CustomReply extends Widget_Abstract implements Widget_Interface_Do {
    private $siteUrl, $pageSize, $_currentPage, $_countSql, $_total = false;

    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
        $this->siteUrl = Helper::options()->siteUrl;
    }
    public function getCurrentPage(){
        return $this->_currentPage ? $this->_currentPage : 1;
    }
    public function select() {
        return $this->db->select()->from('table.wxh_reply');
    }
    public function insert(array $options) {
        return $this->db->query($this->db->insert('table.wxh_reply')->rows($options));
    }
    public function update(array $options, Typecho_Db_Query $condition){
        return $this->db->query($condition->update('table.wxh_reply')->rows($options));
    }
    public function delete(Typecho_Db_Query $condition){
        return $this->db->query($condition->delete('table.wxh_reply'));
    }
    public function size(Typecho_Db_Query $condition){
        return $this->db->fetchObject($condition->select(array('COUNT(table.wxh_reply.rid)' => 'num'))->from('table.wxh_reply'))->num;
    }
    public function execute(){
        $this->parameter->setDefault('pageSize=10');
        $this->_currentPage = $this->request->get('page', 1);

        /** 构建基础查询 */
        $select = $this->db->select()->from('table.wxh_reply');

        /** 过滤分类 */
        if (NULL != ($keywords = $this->request->keywords)) {
            $rids = $this->db->fetchAll($this->db->select('distinct rid')->from('table.wxh_keywords')->where('name like ?', '%' . $keywords . '%'));
            foreach ($rids as $rid) {
                $select->orWhere('rid = ?', $rid['rid']);
            }
        }

        /** 过滤类别 */
        if (NULL != ($type = $this->request->type)) {
            $select->where('type = ?', $type);
        }

        /** 给计算数目对象赋值,克隆对象 */
        $this->_countSql = clone $select;

        /** 提交查询 */
        $select->page($this->_currentPage, $this->parameter->pageSize)->order('table.wxh_reply.rid', Typecho_Db::SORT_DESC);
        $this->db->fetchAll($select, array($this, 'push'));
    }

    /**
     * 输出分页
     */
    public function pageNav() {
        $query = $this->request->makeUriByRequest('page={page}');

        /** 使用盒状分页 */
        $nav = new Typecho_Widget_Helper_PageNavigator_Box(false === $this->_total ? $this->_total = $this->size($this->_countSql) : $this->_total, $this->_currentPage, $this->parameter->pageSize, $query);
        $nav->render('&laquo;', '&raquo;');
    }
    /**
     * 生成表单
     *
     * @access public
     * @param string $action 表单动作
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function form($action = NULL) {
        if (isset($this->request->rid) && 'insert' != $action) {
            /** 更新模式 */
            $custom = $this->db->fetchRow($this->select()->where('rid = ?', $this->request->rid)->limit(1));

            if (!$custom) {
                $this->response->redirect(Helper::url('WeChatHelper/Page/CustomReply.php', $this->options->adminUrl));
            }
        }

        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->siteUrl.'action/WeChat?customreply', Typecho_Widget_Helper_Form::POST_METHOD);

        $keywords = new Typecho_Widget_Helper_Form_Element_Text('keywords', NULL, NULL,
        _t('关键字'), _t('多个关键字请用英文逗号分开，如：typecho,binjoo,冰剑'));
        $form->addInput($keywords);

        $typeOptions = Utils::getMsgType();

        $systemSelect = '<select name="syscmdSelect">';
        foreach (Utils::getSystemMsg() as $key => $value) {
            $selected = '';
            if (isset($custom['command']) && $custom['command'] === $key) {
                $selected = ' selected="true"';
            }
            $systemSelect .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
        }
        $systemSelect .= '</select>';
        $typeOptions['system'] = $typeOptions['system'] . ' ' . $systemSelect;  //系统消息

        $addonsArray = Utils::getAddons();
        if(count($addonsArray)){
            $addonsSelect = '<select name="addonsSelect">';
            foreach ($addonsArray as $key => $value) {
                $selected = '';
                if (isset($custom['command']) && $custom['command'] === $key) {
                    $selected = ' selected="true"';
                }
                $addonsSelect .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
            }
            $addonsSelect .= '</select>';
            $typeOptions['addons'] = $typeOptions['addons'] . ' ' . $addonsSelect;
        }else{
            unset($typeOptions['addons']);
        }

        $type = new Typecho_Widget_Helper_Form_Element_Radio('type', $typeOptions, 'text', '消息类型', NULL);
        $form->addInput($type->multiMode());

        $content = new Typecho_Widget_Helper_Form_Element_Textarea('content', NULL, NULL, '回复内容', '
            文本消息：填写需要回复的内容；<br />
            图片消息：填写图片绝对路径即可；<br />
            系统消息：不需要填写任何内容。');
        $form->addInput($content);

        $status = new Typecho_Widget_Helper_Form_Element_Radio('status',
            array('1' => '激活', '0' => '冻结'),
                  '1', '激活状态', NULL);
        $form->addInput($status);

        $command = new Typecho_Widget_Helper_Form_Element_Hidden('command', NULL, NULL);
        $form->addInput($command);

        $rid = new Typecho_Widget_Helper_Form_Element_Hidden('rid', NULL, NULL);
        $form->addInput($rid);

        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, NULL);
        $form->addInput($do);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存自定义回复'));
        $submit->input->setAttribute('class', 'primary');
        $form->addItem($submit);

        if (isset($this->request->rid) && 'insert' != $action) {
            if($custom['type'] == "system"){
                $content->input->setAttribute('readonly', true);
            }
            $keywords->value($custom['keywords']);
            $type->value($custom['type']);
            $command->value($custom['command']);
            $content->value($custom['content']);
            $status->value($custom['status']);
            $rid->value($custom['rid']);
            $submit->value(_t('编辑自定义回复'));
            $do->value('update');
            $_action = 'update';
        } else {
            $submit->value(_t('增加自定义回复'));
            $do->value('insert');
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $keywords->addRule('required', _t('关键字不能为空'));
            $keywords->addRule(array($this, 'keywordsExists'), _t('关键字名称已经存在'));
            //$type->addRule(array($this, 'typeExists'), _t('该系统消息类型已经存在'));
            $content->addRule('required', _t('回复内容不能为空'));
            //$content->addRule(array($this, 'contentExists'), _t('关键字名称已经存在'));
        }

        if ('update' == $action) {
            $rid->addRule('required', _t('分类主键不存在'));
            //$mid->addRule(array($this, 'categoryExists'), _t('分类不存在'));
        }

        return $form;
    }

    public function insertCustomReply(){
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $customReply = $this->request->from('keywords', 'type', 'command', 'content', 'status');

        $customReply['created'] = time();

        /** 插入数据 */
        $customReply['rid'] = $this->db->query($this->insert($customReply));
        $this->insertKeywords($customReply);
        $this->push($customReply);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('rid-customreply-'.$customReply['rid']);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('自定义回复 <strong>%s</strong> 已经被增加', $customReply['keywords']), 'success');

        /** 转向原页 */
        $this->response->redirect(Helper::url('WeChatHelper/Page/CustomReply.php', $this->options->adminUrl));
    }

    public function updateCustomReply(){
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }
        /** 取出数据 */
        $customReply = $this->request->from('keywords', 'type', 'command', 'content', 'status', 'rid');

        /** 更新数据 */
        $this->db->query($this->update($customReply, $this->db->sql()->where('rid = ?', $this->request->filter('int')->rid)));
        $this->db->query($this->db->sql()->delete('table.wxh_keywords')->where('rid = ?', $customReply['rid']));
        $this->insertKeywords($customReply);
        $this->push($customReply);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('rid-customreply-'.$customReply['rid']);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('自定义回复 <strong>%s</strong> 已经被更新', $customReply['keywords']), 'success');

        /** 转向原页 */
        $this->response->redirect(Helper::url('WeChatHelper/Page/CustomReply.php&page='.$this->_currentPage, $this->options->adminUrl));
    }

    public function deleteCustomReply(){
        $customreplys = $this->request->filter('int')->rid;
        $deleteCount = 0;

        if ($customreplys && is_array($customreplys)) {
            foreach ($customreplys as $customreply) {
                if ($this->delete($this->db->sql()->where('rid = ?', $customreply))) {
                    $this->db->query($this->db->sql()->delete('table.wxh_keywords')->where('rid = ?', $customreply));
                    $deleteCount ++;
                }
            }
        }

        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('自定义回复已经删除') : _t('没有自定义回复被删除'),
        $deleteCount > 0 ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->redirect(Helper::url('WeChatHelper/Page/CustomReply.php', $this->options->adminUrl));
    }

    public function insertKeywords($customReply){
        foreach (explode(",", $customReply['keywords']) as $key => $value) {
            if($value){
                $keyObj['name'] = $value;
                $keyObj['rid'] = $customReply['rid'];
                $this->db->query($this->db->insert('table.wxh_keywords')->rows($keyObj));
            }
        }
    }

    /**
     * 判断关键字是否存在
     */
    public function keywordsExists($keywords) {
        foreach (explode(",", $keywords) as $key => $value) {
            if($value){
                $select = $this->db->select()->from('table.wxh_keywords')->where('name = ?', $value);
                if ($this->request->rid) {
                    $select->where('rid <> ?', $this->request->rid);
                }
                $result = $this->db->fetchRow($select->limit(1));
                if($result){
                    break;
                }else{
                    continue;
                }
            }
        }
        return $result ? false : true;
    }

    /**
     * 判断消息类型是否存在
     */
    public function typeExists($type) {
        if($this->request->type === 'system'){
            $select = $this->db->select(array('COUNT(table.wxh_reply.rid)' => 'num'))->from('table.wxh_reply')->where('type = ?', 'system')->where('command = ?', $this->request->command);
            if(!is_null($this->request->rid)){
                $select->where('rid <> ?', $this->request->rid);
            }
            $result = $this->db->fetchObject($select);
        }
        return $result->num ? false : true;
    }
    public function action() {
        $this->on($this->request->is('do=insert'))->insertCustomReply();
        $this->on($this->request->is('do=update'))->updateCustomReply();
        $this->on($this->request->is('do=delete'))->deleteCustomReply();
    }
}
