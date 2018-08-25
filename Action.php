<?php

class ChangyanCallback_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function action()
    {
        if (!$this->request->isPost() ||
            empty($this->request->get('data')) ||
            empty($requestContent = json_decode($this->request->get('data'), true)) ||
            empty($requestContent['sourceid'])
        ) {
            throw new Typecho_Widget_Exception(_t('页面不存在'), 404);
        }

        $db       = Typecho_Db::get();
        $replyId  = empty($requestContent['comments'][0]['replyid']) ? 0 : $requestContent['comments'][0]['replyid'];
        $parentId = 0;

        if (!empty($replyId)) {
            $parentId = (int)$db->fetchObject(
                $db->select('coid')->from('table.comments')->where('cid = ?', $requestContent['sourceid'])->where('cmtid = ?', $replyId)->limit(1)
            )->coid;
        }

        $db->query(
            $db->insert('table.comments')->rows(
                [
                    'cid'      => $requestContent['sourceid'],
                    'created'  => empty($requestContent['comments'][0]['ctime']) ? time() : $requestContent['comments'][0]['ctime'] / 1000,
                    'author'   => empty($requestContent['comments'][0]['user']['nickname']) ? '' : $requestContent['comments'][0]['user']['nickname'],
                    'authorId' => empty($requestContent['comments'][0]['user']['userid']) ? 0 : $requestContent['comments'][0]['user']['userid'],
                    'ownerId'  => empty($requestContent['comments'][0]['user']['userid']) ? 0 : $requestContent['comments'][0]['user']['userid'],
                    'url'      => empty($requestContent['comments'][0]['user']['userurl']) ? '' : $requestContent['comments'][0]['user']['userurl'],
                    'ip'       => empty($requestContent['comments'][0]['ip']) ? '' : $requestContent['comments'][0]['ip'],
                    'agent'    => empty($requestContent['comments'][0]['useragent']) ? '' : $requestContent['comments'][0]['useragent'],
                    'text'     => empty($requestContent['comments'][0]['content']) ? '' : $requestContent['comments'][0]['content'],
                    'type'     => 'comment',
                    'status'   => 'approved',
                    'parent'   => $parentId,
                    'cmtid'    => empty($requestContent['comments'][0]['cmtid']) ? 0 : $requestContent['comments'][0]['cmtid'],
                ]
            )
        );

        $num = $db->fetchObject(
            $db->select(['COUNT(coid)' => 'num'])->from('table.comments')->where('status = ? AND cid = ?', 'approved', $requestContent['sourceid'])
        )->num;

        $db->query(
            $db->update('table.contents')->rows(['commentsNum' => $num])->where('cid = ?', $requestContent['sourceid'])
        );
    }
}
