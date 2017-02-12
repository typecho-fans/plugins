<?php
/**
 * TeKit Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
class TeKit_Contents extends Widget_Abstract_Contents implements Widget_Interface_Do {
    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }
    /**
     * 清空堆栈信息
     */
    private function clearPush(){
        $this->row = array();
        $this->length = 0;
        $this->stack = array();
        return $this;
    }

    public function push(array $value) {
        //$value = $this->filter($value);
        return parent::push($value);
    }

    public function Random($number = 10){
        $sql = $this->select()
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created <= ?', $this->options->gmtTime)
            ->where('table.contents.type = ?', 'post')
            ->limit($number)
            ->order('RAND()');
        $this->clearPush()->db->fetchAll($sql, array($this, 'push'));
        return $this;
    }

    public function MostCommented($number = 10){
        $sql = $this->select()
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.created <= ?', $this->options->gmtTime)
            ->limit($number)
            ->order('commentsNum',Typecho_Db::SORT_DESC);
        $this->clearPush()->db->fetchAll($sql, array($this, 'push'));
        return $this;
    }
    
    public function HistoryToday($number = 10){
        $year = date('Y', $this->options->gmtTime);
        $month = date('m', $this->options->gmtTime);
        $day = date('j', $this->options->gmtTime);

        $sql = $this->select()
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created <= ?', $this->options->gmtTime)
            ->where('year(FROM_UNIXTIME(table.contents.created)) <> ?', $year)
            ->where('month(FROM_UNIXTIME(table.contents.created)) = ?', $month)
            ->where('day(FROM_UNIXTIME(table.contents.created)) = ?', $day)
            ->where('table.contents.type = ?', 'post')
            ->limit($number)
            ->order('table.contents.created', Typecho_Db::SORT_DESC);
        $this->clearPush()->db->fetchAll($sql, array($this, 'push'));
        return $this;
    }
    
    public function HistoryTomonth($number = 10){
        $year = date('Y', $this->options->gmtTime);
        $month = date('m', $this->options->gmtTime);

        $sql = $this->select()
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created <= ?', $this->options->gmtTime)
            ->where('year(FROM_UNIXTIME(table.contents.created)) <> ?', $year)
            ->where('month(FROM_UNIXTIME(table.contents.created)) = ?', $month)
            ->where('table.contents.type = ?', 'post')
            ->limit($number)
            ->order('table.contents.created', Typecho_Db::SORT_DESC);
        $this->clearPush()->db->fetchAll($sql, array($this, 'push'));
        return $this;
    }

    public function action() {}
}
