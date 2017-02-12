<?php
/**
 * TeKit Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
class TeKit_Comments extends Widget_Abstract_Comments implements Widget_Interface_Do {
    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }
    private function clearPush(){
        $this->row = array();
        $this->length = 0;
        $this->stack = array();
        return $this;
    }

    public function MostCommentors($days = NULL, $number = 10, $ignore = true){
        $sql = $this->db->select('table.comments.author, table.comments.mail, table.comments.url, table.comments.created, count(1) as cnt')->from('table.comments')
            ->where('table.comments.status = ?','approved')
            ->group('table.comments.author, table.comments.mail')
            ->limit($number)
            ->order('cnt', Typecho_Db::SORT_DESC);
        if($days){
            $sql->where('table.comments.created >= ?', $this->options->gmtTime - (24 * 60 * 60 * $days));
        }
        if($ignore){
            $sql->where('table.comments.ownerId <> table.comments.authorId');
        }
        $this->clearPush()->db->fetchAll($sql, array($this, 'push'));
        return $this;
    }

    public function MostSofaCommentors($days = NULL, $number = 10, $ignore = true){
        $prefix = $this->db->getPrefix();
        $sql = "select author, url, mail, created, count(*) as cnt from (SELECT a.author, a.url, a.mail, a.created, min(a.created) FROM " . $prefix . "comments a, " . $prefix . "contents b WHERE b.status = 'publish' and b.type = 'post' and b.commentsNum <> 0 ";
        if($days){
            $sql = $sql."and a.created >= '". ($this->options->gmtTime - (24 * 60 * 60 * $days)) ."' ";
        }
        if($ignore){
            $sql = $sql."and a.ownerId <> a.authorId";
        }
        $sql = $sql." and a.cid = b.cid group by a.cid) c group by c.author order by cnt desc limit " . $number;
        $this->clearPush()->db->fetchAll($sql, array($this, 'push'));
        return $this;
    }

    public function CommentorNumber($author, $mail, $days = 30){
        $sql = $this->db->select(array('count(1)' => 'cnt'))->from('table.comments')
            ->where('table.comments.status = ?','approved')
            ->where('table.comments.author = ?', $author)
            ->where('table.comments.mail = ?', $mail)
            ->order('table.comments.created', Typecho_Db::SORT_DESC);
        if($days){
            $sql->where('table.comments.created >= ?', $this->options->gmtTime - (24 * 60 * 60 * $days));
        }
        return $this->clearPush()->db->fetchObject($sql)->cnt;
    }

    public function CommentorComments($author, $mail, $days = 30){
        $sql = $this->select()
            ->where('table.comments.status = ?','approved')
            ->where('table.comments.author = ?', $author)
            ->where('table.comments.mail = ?', $mail)
            ->order('table.comments.created', Typecho_Db::SORT_DESC);
        if($days){
            $sql->where('table.comments.created >= ?', $this->options->gmtTime - (24 * 60 * 60 * $days));
        }
        $this->clearPush()->db->fetchAll($sql, array($this, 'push'));
        return $this;
    }

    public function action() {}
}
