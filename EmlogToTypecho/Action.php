<?php

class EmlogToTypecho_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function doImport()
    {
        /* 获取配置 */
        $options = $this->widget('Widget_Options');
        $dbConfig = $options->plugin('EmlogToTypecho');

        /* 初始化一个db */
        if (Typecho_Db_Adapter_Mysql::isAvailable()) {
            $db = new Typecho_Db('Mysql', $dbConfig->prefix);
        } else {
            $db = new Typecho_Db('Pdo_Mysql', $dbConfig->prefix);
        }

        /* 只读即可 */
        $db->addServer(array(
            'host' => $dbConfig->host,
            'user' => $dbConfig->user,
            'password' => $dbConfig->password,
            'charset' => 'utf8',
            'port' => $dbConfig->port,
            'database' => $dbConfig->database
        ), Typecho_Db::READ);

        /* 删除当前内容 */
        $masterDb = Typecho_Db::get();
        $this->widget('Widget_Abstract_Contents')->to($contents)->delete($masterDb->sql()->where('1 = 1'));
        $this->widget('Widget_Abstract_Comments')->to($comments)->delete($masterDb->sql()->where('1 = 1'));
        $this->widget('Widget_Contents_Post_Edit')->to($edit);
        $masterDb->query($masterDb->delete('table.relationships')->where('1 = 1'));

        /* 获取 emlog 管理员信息 */
        $emUser = $db->fetchRow($db->query($db->select()->from('table.user')));
        $emUsername = $emUser['username'];
        $emNickname = $emUser['nickname'];

        /* 转换评论表 */
        $i = 1;

        while (true) {
            $result = $db->query($db->select()->from('table.comment')
                ->order('cid', Typecho_Db::SORT_ASC)->page($i, 100));
            $j = 0;

            while ($row = $db->fetchRow($result)) {
                $status = '';
                if ('y' == $row['hide']) {
                    $status = 'waiting';
                } else {
                    $status = 'approved';
                }

                if (($emUsername == $row['poster'])
                    || ($emNickname == $row['poster'])
                ) {
                    $authorId = 1;
                } else {
                    $authorId = 0;
                }

                $row['comment'] = preg_replace(array(
                    "/\s*<p>/is",
                    "/\s*<\/p>\s*/is",
                    "/\s*<br\s*\/>\s*/is",
                    "/\s*<(div|blockquote|pre|table|ol|ul)>/is",
                    "/<\/(div|blockquote|pre|table|ol|ul)>\s*/is"
                ), array(
                    '',
                    "\n\n",
                    "\n",
                    "\n\n<\\1>",
                    "</\\1>\n\n"
                ), $row['comment']);

                $comments->insert(array(
                    'coid'      =>  $row['cid'],
                    'cid'       =>  $row['gid'],
                    'created'   =>  $row['date'],
                    'author'    =>  $row['poster'],
                    'authorId'  =>  $authorId,
                    'ownerId'   =>  1,
                    'mail'      =>  $row['mail'],
                    'url'       =>  $row['url'],
                    'ip'        =>  $row['ip'],
                    'agent'     =>  NULL,
                    'text'      =>  $row['comment'],
                    'type'      =>  'comment',
                    'status'    =>  $status,
                    'parent'    =>  $row['pid']
                ));
                $j ++;
                unset($row);
            }

            if ($j < 100) {
                break;
            }

            $i ++;
            unset($result);
        }

        /* 转换文章表 */
        $i = 1;

        while (true) {
            $result = $db->query($db->select()->from('table.blog')
                ->order('gid', Typecho_Db::SORT_ASC)->page($i, 100));
            $j = 0;

            while ($row = $db->fetchRow($result)) {
                $type = '';
                if ('page' == $row['type']) {
                    $type = 'page';
                } else {
                    if ('y' == $row['hide']) {
                        $type = 'post_draft';
                    } else {
                        $type = 'post';
                    }
                }

                $contents->insert(array(
                    'cid'           =>  $row['gid'],
                    'title'         =>  $row['title'],
                    'slug'          =>  Typecho_Common::slugName(urldecode($row['alias']), $row['gid']),
                    'created'       =>  $row['date'],
                    'modified'      =>  $row['date'],
                    'text'          =>  $row['content'],
                    'order'         =>  0,
                    'authorId'      =>  $row['author'],
                    'template'      =>  NULL,
                    'type'          =>  $type,
                    'status'        =>  'publish',
                    'password'      =>  $row['password'],
                    'commentsNum'   =>  $row['comnum'],
                    'allowComment'  =>  ('n' == $row['allow_remark']) ? '0' : '1',
                    'allowPing'     =>  0,
                    'allowFeed'     =>  '1'
                ));

                $j ++;
                unset($row);
            }

            if ($j < 100) {
                break;
            }

            $i ++;
            unset($result);
        }

        /* 转换 metas 表 */
        $sorts = $db->fetchAll($db->select()->from('table.sort'));
        foreach ($sorts as $sort) {
            $blogs = $db->fetchAll($db->select()->from('table.blog')
                ->where('sortid = ?', $sort['sid']));

            $masterDb->query($masterDb->insert('table.metas')->rows(array(
                'mid'           =>  $sort['sid'] + 1,
                'name'          =>  $sort['sortname'],
                'slug'          =>  $sort['alias'],
                'type'          =>  'category',
                'description'   =>  $sort['description'],
                'count'         =>  count($blogs),
                'parent'        =>  (0 != $sort['pid']) ? $sort['pid'] + 1 : 0
            )));
        }
        unset($sorts);

        $emtags = $db->fetchAll($db->select()->from('table.tag'));
        foreach ($emtags as $emtag) {
            $gid = trim($emtag['gid'], ',');
            $gids = explode(',', $gid);

            $masterDb->query($masterDb->insert('table.metas')->rows(array(
                'name'          =>  $emtag['tagname'],
                'slug'          =>  Typecho_Common::slugName($emtag['tagname']),
                'type'          =>  'tag',
                'description'   =>  NULL,
                'count'         =>  count($gids)
            )));
        }

        /* 转换关系表 */
        $emblogs = $db->fetchAll($db->select()->from('table.blog'));
        foreach ($emblogs as $emblog) {
            $masterDb->query($masterDb->insert('table.relationships')
                ->rows(array(
                    'cid' => $emblog['gid'],
                    'mid' => (-1 == $emblog['sortid']) ? 1 : ($emblog['sortid'] + 1)
                )));
        }
        unset($emblogs);

        $tags = $masterDb->fetchAll($masterDb->select()->from('table.metas')->where('type = ?', 'tag'));
        foreach ($tags as $tag) {
            foreach ($emtags as $emtag) {
                if ($tag['name'] == $emtag['tagname']) {
                    $gid = trim($emtag['gid'], ',');
                    $gids = explode(',', $gid);

                    foreach ($gids as $cid) {
                        $masterDb->query($masterDb->insert('table.relationships')->rows(array(
                            'cid' => $cid,
                            'mid' => $tag['mid']
                        )));
                    }
                }
            }
        }
        unset($emtags);

        /* 更新附件地址 */
        $emOptions = $db->fetchAll($db->select()->from('table.options'));
        $static = array();
        foreach ($emOptions as $emOption) {
            $static[$emOption['option_name']] = $emOption['option_value'];
        }
        unset($emOptions);
        $static['blogurl'];
        $oldUrl = rtrim($static['blogurl'], '/') . '/content/uploadfile';
        $path = defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : '/usr/uploads';
        $newUrl = rtrim($options->siteUrl, '/') . $path . '/emlog';
        $sql = "UPDATE `" . $masterDb->getPrefix()
            . "contents` SET `text` = REPLACE(`text`,'"
            . $oldUrl . "','" . $newUrl . "');";
        $masterDb->query($sql);

        $this->widget('Widget_Notice')->set(_t('数据已经转换完成'), NULL, 'success');
        $this->response->goBack();
    }

    public function action()
    {
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->isPost())->doImport();
    }
}
