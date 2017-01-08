<?php
class Zan_Action extends Typecho_Widget implements Widget_Interface_Do {
    protected $db;

    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
    }

    public function showZan($cid){
        $html = '<a href="javascript:;" class="post-zan" data-cid="';
        $html .= $cid;
        $html .= '">赞 (<span>';
        $html .= self::getZan($cid);
        $html .= '</span>)</a>';
        echo $html;
    }

    private function getZan($cid){
        $exist = $this->db->fetchRow($this->db->select('int_value')->from('table.fields')->where('cid = ? AND name = ?', $cid, 'zan'));
        if (empty($exist)) {
            return 0;
        }else{
            return $exist['int_value'];
        }
    }

    private function addZan($cid){
        $exist = $this->db->fetchRow($this->db->select('int_value')->from('table.fields')->where('cid = ? AND name = ?', $cid, 'zan'));
        $zan = array('cid'=>$cid, 'name'=>'zan', 'type'=>'int', 'str_value'=>NULL, 'int_value'=>1, 'float_value'=>0);
        $result = null;
        if (empty($exist)) {
            $result = $this->db->query($this->db->insert('table.fields')->rows($zan));
        } else {
            $zan['int_value'] = $exist['int_value'] + 1;
            $result = $this->db->query($this->db->update('table.fields')->rows($zan)->where('cid = ? AND name = ?', $cid, 'zan'));
        }

        if($result){
            $cookie = Typecho_Cookie::get("__zan_cids");
            $cids = null;
            if($cookie){
                $cids = Json::decode($cookie, true);
                $cids[$cid] = isset($cids[$cid]) ? $cids[$cid] + 1 : 0;
            }else{
                $cids = array($cid=>0);
            }
            Typecho_Cookie::set("__zan_cids", Json::encode($cids));
        }
    }

    public function action() {
        //if($this->request->isGet() && $this->request->is('cid')){
        if($this->request->isPost() && $this->request->is('cid')){
            $this->addZan($this->request->get('cid'));
            $this->response->throwJson(array('result' => 1, 'message' => _t('感谢您的赞！')));
        }
    }
}
?>