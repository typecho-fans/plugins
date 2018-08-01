<?php
/**
 * WeChatHelper Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
include_once 'Utils.php';
class WeChatHelper_Widget_Menus extends Widget_Abstract implements Widget_Interface_Do {
    private $siteUrl, $_countSql, $_total = false;

    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
        $this->siteUrl = Helper::options()->siteUrl;
    }

    public function select() {
        return $this->db->select()->from('table.wch_menus');
    }
    public function insert(array $options) {
        return $this->db->query($this->db->insert('table.wch_menus')->rows($options));
    }
    public function update(array $options, Typecho_Db_Query $condition){
        return $this->db->query($condition->update('table.wch_menus')->rows($options));
    }
    public function delete(Typecho_Db_Query $condition){
        return $this->db->query($condition->delete('table.wch_menus'));
    }
    public function size(Typecho_Db_Query $condition){
        return $this->db->fetchObject($condition->select(array('COUNT(table.wch_menus.uid)' => 'num'))->from('table.wch_menus'))->num;
    }

    public function execute(){
        /** 构建基础查询 */
        $select = $this->select()->from('table.wch_menus');

        /** 给计算数目对象赋值,克隆对象 */
        $this->_countSql = clone $select;

        /** 提交查询 */
        $select->order('table.wch_menus.sort', Typecho_Db::SORT_ASC);
        $this->db->fetchAll($select, array($this, 'push'));
    }

    public function filter(array $value) {
        $value['levelVal'] = $value['level'] == 'button' ? '＃＃' : '└──';
        $value['tr'] = $value['level'] == 'button' ? 'style="background-color: #F0F0EC"' : '';
        return $value;
    }

    public function push(array $value) {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * 生成表单
     *
     * @access public
     * @param string $action 表单动作
     * @return Typecho_Widget_Helper_Form_Element
     */
    public function form($action = NULL) {
        if (isset($this->request->mid) && 'insert' != $action) {
            /** 更新模式 */
            $menu = $this->db->fetchRow($this->select()->where('mid = ?', $this->request->mid)->limit(1));

            if (!$menu) {
                $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
            }
        }
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('action/WeChat?menus'), Typecho_Widget_Helper_Form::POST_METHOD);

        $select = $this->select()->where('table.wch_menus.parent = ?', '0')->order('table.wch_menus.order', Typecho_Db::SORT_ASC);
        $buttonMenus = $this->db->fetchAll($select);

        $parent = '<select name="parent">';
        foreach ($buttonMenus as $row) {
            $selected = '';
            if (isset($menu['parent']) && $menu['parent'] === $row['mid']) {
                $selected = ' selected="true"';
            }
            $parent .= '<option value="' . $row['mid'] . '"' . $selected . '>' . $row['name'] . '</option>';
        }
        $parent .= '</select>';

        $level = new Typecho_Widget_Helper_Form_Element_Radio('level', 
            array('button' => _t('一级菜单'), 'sub_button' => _t('二级菜单 '.$parent)),
            'button', _t('消息类型'), NULL);
        $form->addInput($level->multiMode());

        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL,
        _t('标题'), _t('菜单标题，不超过16个字节。'));
        $form->addInput($name);

        $type = new Typecho_Widget_Helper_Form_Element_Radio('type', array('click' => _t('Click类型'), 'view' => _t('View类型')), 'click', _t('消息类型'), NULL);
        $form->addInput($type);

        $value = new Typecho_Widget_Helper_Form_Element_Text('value', NULL, NULL,
        _t('Key & URL值'), _t('Click类型：菜单KEY值，用于消息接口推送，不超过128字节；<br />View类型：网页链接，用户点击菜单可打开链接，不超过256字节。'));
        $form->addInput($value);

        $order = new Typecho_Widget_Helper_Form_Element_Select('order', 
            array('1' => _t('1'),
                  '2' => _t('2'),
                  '3' => _t('3'),
                  '4' => _t('4'),
                  '5' => _t('5')), '1', _t('排序'), NULL);
        $form->addInput($order);

        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, NULL);
        $form->addInput($do);

        $mid = new Typecho_Widget_Helper_Form_Element_Hidden('mid', NULL, NULL);
        $form->addInput($mid);

        $submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, NULL);
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (isset($this->request->mid) && 'insert' != $action) {
            $level->value($menu['level']);
            $name->value($menu['name']);
            $type->value($menu['type']);
            $value->value($menu['value']);
            $order->value($menu['order']);
            $mid->value($menu['mid']);
            $submit->value(_t('编辑菜单'));
            $do->value('update');
            $_action = 'update';
        } else {
            $submit->value(_t('增加菜单'));
            $do->value('insert');
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $level->addRule(array($this, 'checkLevelNum'), _t('一级标题不能超过3个或同一一级标题下的二级标题不能超过5个。'), 'mid');
            $name->addRule('required', _t('标题不能为空'));
            $name->addRule(array($this, 'checkMaxLength'), _t('菜单标题最多包含16个字符'), 'type');
            $value->addRule(array($this, 'checkKeyOrUrl'), _t('URL格式不正确'), 'type');
            $value->addRule(array($this, 'checkKeyOrUrlMaxLength'), _t('View类型最多包含128个字符，Click类型最多包含256个字符。'), 'type');
            //$name->addRule('checkMaxLength', _t('菜单标题最多包含5个字符'), 5);
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('菜单主键不存在'));
            //$mid->addRule(array($this, 'categoryExists'), _t('分类不存在'));
        }

        return $form;
    }

    public function checkMaxLength($name, $type){
        $type = $this->request->get($type);
        $length = 16;
        if(strlen($name) > $length){
            return false;
        }else{
            return true;
        }
    }

    public function checkKeyOrUrl($value, $type){
        $type = $this->request->get($type);
        if($type == 'view'){
            return Typecho_Validate::url($value);
        }else{
            return true;
        }
    }

    public function checkLevelNum($value, $mid){
        $mid = $this->request->get($mid);
        $select = $this->db->sql()->select(array('COUNT(table.wch_menus.mid)' => 'num'))->from('table.wch_menus')->where('level = ?', $value);
        if($value == 'button'){
            if($mid){
                $select->where('mid <> ?', $mid);
            }
            $num = $this->db->fetchObject($select)->num;
            if($num>=3){
                return false;
            }else{
                return true;
            }
        }else if($value == 'sub_button'){
            $parent = $this->request->get('parent');
            if($parent){
                $select->where('parent = ?', $parent);
            }
            if($mid){
                $select->where('mid <> ?', $mid);
            }
            $num = $this->db->fetchObject($select)->num;
            if($num>=5){
                return false;
            }else{
                return true;
            }
        }
    }

    public function checkKeyOrUrlMaxLength($value, $type){
        $type = $this->request->get($type);
        $length = $type == 'click' ? 256 : 128;
        if(strlen($value) > $length){
            return false;
        }else{
            return true;
        }
    }

    public function getParentOrder($menu){
        if($menu['level'] == 'sub_button'){
            $select = $this->db->sql()->select(array('table.wch_menus.order' => 'order'))->from('table.wch_menus')->where('mid = ?', $menu['parent']);
            $order = $this->db->fetchObject($select)->order;
            $menu['sort'] = ($order * 10) + $menu['order'];
        }else{
            $menu['sort'] = $menu['order'] * 10;
            $menu['parent'] = '0';
        }
        return $menu;
    }

    /**
     * 分类排序
     *
     * @access public
     * @return void
     */
    public function orderMenu() {
        $menus = $this->request->filter('int')->getArray('mid');
        $levels = $this->request->getArray('level');
        if ($menus) {
            $parent = 0;
            foreach ($menus as $sort => $mid) {
                $param = array('order' => $sort + 1);
                if($levels[$sort] == 'button'){
                    $parent = $mid;
                    $param['parent'] = '0';
                }else{
                    $param['parent'] = $parent;
                }
                $this->update($param, $this->db->sql()->where('mid = ?', $mid));
            }
        }

        if (!$this->request->isAjax()) {
            /** 转向原页 */
            $this->response->redirect(Typecho_Common::url('manage-categories.php', $this->options->adminUrl));
        } else {
            $this->response->throwJson(array('success' => 1, 'message' => _t('分类排序已经完成')));
        }
    }

    public function insertMenu() {
        if ($this->form('insert')->validate()) {
           $this->response->goBack();
        }
        /** 取出数据 */
        $menu = $this->request->from('level', 'name', 'type', 'value', 'parent', 'order');
        $menu = $this->getParentOrder($menu);
        $menu['created'] = time();

        /** 插入数据 */
        $menu['mid'] = $this->db->query($this->insert($menu));
        $this->push($menu);

        $this->widget('Widget_Notice')->highlight('menus-mid-'.$menu['mid']);
        $this->widget('Widget_Notice')->set(_t('自定义菜单添加成功'), 'success');
        $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
    }

    public function updateMenu() {
        if ($this->form('insert')->validate()) {
           $this->response->goBack();
        }
        /** 取出数据 */
        $menu = $this->request->from('level', 'name', 'type', 'value', 'parent', 'order', 'mid');
        $menu = $this->getParentOrder($menu);

        /** 插入数据 */
        $this->db->query($this->update($menu, $this->db->sql()->where('mid = ?', $this->request->filter('int')->mid)));
        if($menu['level'] == 'button'){
            $this->db->query($this->db->sql()->update('table.wch_menus')->where('parent = ?', $menu['mid'])->expression('sort', ($menu['order'] * 10) . ' + `order` '));
        }
        $this->push($menu);

        $this->widget('Widget_Notice')->highlight('menus-mid-'.$menu['mid']);
        $this->widget('Widget_Notice')->set(_t('自定义菜单修改成功'), 'success');
        $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
    }

    public function deleteMenu() {
        $menus = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;

        if ($menus && is_array($menus)) {
            foreach ($menus as $menu) {
                if ($this->delete($this->db->sql()->where('mid = ?', $menu)->orWhere('parent = ?', $menu))) {
                    $deleteCount ++;
                }
            }
        }

        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('自定义菜单已经删除') : _t('没有自定义菜单被删除'),
        $deleteCount > 0 ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
    }

    public function createMenu(){
        $accessToken = Utils::getAccessToken();
        if(!$accessToken){
            $this->widget('Widget_Notice')->set('获取Access Token异常！', 'error');
            $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
        }
        $create['button'] = array();
        $select = $this->select()->where('level = ?', 'button')->order('table.wch_menus.order', Typecho_Db::SORT_ASC);
        $buttons = $this->db->fetchAll($select);
        if (count($buttons) > 3 || !count($buttons)) {
            $this->widget('Widget_Notice')->set(_t('错误：一级菜单没有找到或超过三个.'), 'error');
            $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
        }
        foreach ($buttons as $row) {
            $button = array();
            $select = "";
            $select = $this->select()->where('level = ?', 'sub_button')->where('parent = ?', $row['mid'])->order('table.wch_menus.order', Typecho_Db::SORT_ASC);
            $subButtons = $this->db->fetchAll($select);
            if (!count($subButtons)) {  //没有二级菜单
                $button['type'] = urlencode($row['type']);
                $button['name'] = urlencode($row['name']);
                $button[$row['type'] == 'view' ? 'url' : 'key'] = urlencode($row['value']);
            }else{
                $button['name'] = urlencode($row['name']);
                $tmp = array();
                foreach ($subButtons as $row) {
                    $subButton = array();
                    $subButton['type'] = urlencode($row['type']);
                    $subButton['name'] = urlencode($row['name']);
                    $subButton[$row['type'] == 'view' ? 'url' : 'key'] = urlencode($row['value']);
                    array_push($tmp, $subButton);
                }
                $button['sub_button'] = $tmp;
            }
            array_push($create['button'], $button);
        }

        $json = json_encode($create);

        try {
            $client = Typecho_Http_Client::get();
            $params = array('access_token' => $accessToken);
            $response = $client->setQuery($params)->setData(urldecode($json))->send(Utils::MENU_CREATE_URL);
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('对不起，创建自定义菜单失败，请重试！'), 'error');
            $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
        }

        $result = json_decode($response);
        if($result->errcode){
            $this->widget('Widget_Notice')->set(_t('对不起，创建自定义菜单失败，错误原因：'.$result->errmsg), 'notice');
        }else{
            $this->widget('Widget_Notice')->set(_t('恭喜您，创建自定义菜单成功！'), 'success');
        }
        $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
    }

    public function removeMenu(){
        $accessToken = Utils::getAccessToken();
        if(!$accessToken){
            $this->widget('Widget_Notice')->set('获取Access Token异常！', 'error');
            $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
        }

        try {
            $client = Typecho_Http_Client::get();
            $params = array('access_token' => $accessToken);
            $response = $client->setQuery($params)->send(Utils::MENU_REMOVE_URL);
        } catch (Exception $e) {
            $this->widget('Widget_Notice')->set(_t('对不起，删除自定义菜单失败，请重试！'), 'error');
            $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
        }

        $result = json_decode($response);
        if($result->errcode){
            $this->widget('Widget_Notice')->set(_t('对不起，删除自定义菜单失败，错误原因：'.$result->errmsg), 'notice');
        }else{
            $this->widget('Widget_Notice')->set(_t('恭喜您，删除自定义菜单成功！'), 'success');
        }
        $this->response->redirect(Helper::url('WeChatHelper/Page/Menus.php', $this->options->adminUrl));
    }

    public function action() {
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertMenu();
        $this->on($this->request->is('do=update'))->updateMenu();
        $this->on($this->request->is('do=delete'))->deleteMenu();
        $this->on($this->request->is('do=order'))->orderMenu();
        $this->on($this->request->is('do=create'))->createMenu();
        $this->on($this->request->is('do=remove'))->removeMenu();
        $this->response->redirect($this->options->adminUrl);
    }
}
