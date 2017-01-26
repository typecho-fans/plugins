<?php

class Contribute_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /* 数据库对象 */
    private $_db;

    /* 获取配置 */
    private $_options;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /* 获取数据库对象、配置及用户 */
        $this->_db = Typecho_Db::get();
        $this->_options = Typecho_Widget::widget('Widget_Options');
    }

    /**
     * 审核投稿
     *
     * @access public
     * @return void
     */
    public function approved()
    {
        $cid = $this->request->getArray('cid');
        $approvedCount = 0;
        if ($cid) {
            /* 格式化文章主键 */
            $posts = is_array($cid) ? $cid : array($cid);
            foreach ($posts as $post) {
                $resource = $this->_db->query($this->_db->select()
                    ->from('table.contribute')->where('cid = ?', $post));

                /* 取出草稿 */
                $draft = $this->_db->fetchRow($resource);

                /* 去除草稿中的cid、分类及标签, 并生成文章数组 */
                $content = $draft;
                unset($content['cid']);
                unset($content['author']);
                unset($content['category']);
                unset($content['tags']);
                /* 从草稿中取出分类及标签 */
                $category = unserialize($draft['category']);
                $tags = $draft['tags'];

                /* 注销草稿变量 */
                unset($draft);

                /* 发布内容 */
                $realId = $this->_publish($content);

                if ($realId > 0) {
                    /* 更新分类 */
                    $this->_setCategories($realId, $category);

                    /* 更新标签 */
                    $this->_setTags($realId, $tags);

                    /* 通过后删除草稿 */
                    $this->_deleteDraft($post);

                    $approvedCount ++;
                }
            }
        }

        $this->widget('Widget_Notice')->set($approvedCount > 0 ? _t('稿件已经被审核通过') : _t('没有稿件被审核通过'),
            $approvedCount > 0 ? 'success' : 'notice');
        $this->response->goBack();
    }

    /**
     * 删除投稿
     *
     * @access public
     * @return void
     */
    public function delete()
    {
        $cid = $this->request->getArray("cid");
        $deleteCount = 0;

        if ($cid) {
            $drafts = is_array($cid) ? $cid : array($cid);
            foreach ($drafts as $draft) {
                $this->_deleteDraft($draft);

                $deleteCount ++;
            }
        }

        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('稿件已经被删除') : _t('没有稿件被删除'),
            $deleteCount > 0 ? 'success' : 'notice');
        $this->response->goBack();
    }

    /**
     * 撰写稿件
     *
     * @access public
     * @return void
     */
    public function write()
    {
        /* XSS过滤 */
        $content['title'] = $this->request->filter('strip_tags', 'trim', 'xss')->title;
        $content['slug'] = $this->request->filter('strip_tags', 'trim', 'xss')->slug;
        $content['text'] = $this->request->filter('strip_tags', 'trim', 'xss')->text;
        $content['author'] = $this->request->filter('strip_tags', 'trim', 'xss')->author;
        $content['category'] = $this->request->filter('strip_tags', 'trim', 'xss')->category;
        $content['tags'] = $this->request->filter('strip_tags', 'trim', 'xss')->tags;
        $created = $this->request->filter('strip_tags', 'trim', 'xss')->date;

        /* 处理空值 */
        $content['title'] = empty($content['title']) ? _t('未命名文档') : $content['title'];
        $content['category'] = empty($content['category'])
            ? array($this->_options->defaultCategory) : $content['category'];

        if (!empty($created)) {
            $content['created'] = strtotime($created)
                - $this->_options->timezone + $this->_options->serverTimezone;
        } else {
            $content['created'] = $this->_options->gmtTime;
        }

        /* 判断内容是否为markdown格式, 设置与后台同步 */
        if ($this->request->markdown && $this->_options->markdown) {
            $content['text'] = '<!--markdown-->' . $content['text'];
        }

        /* 添加撰稿人信息 */
        $config = Helper::options()->plugin('Contribute');
        if ($config->author == 'y') {
            $content['text'] .= "\n\n" . '撰稿人: <u>' . $content['author'] . '</u>';
        }

        $realId = $this->_insert($content);

        $this->widget('Widget_Notice')->set($realId > 0 ? _t('提交稿件成功') : _t('提交稿件失败'),
            $realId > 0 ? 'success' : 'notice');
        $this->response->goBack();
    }

    /**
     * 稿件预览
     *
     * @access public
     * @return void
     */
    public function preview()
    {
        $cid = $this->request->filter('int')->cid;
        $widget = $this->widget('Widget_Abstract_Contents');

        $resource = $this->_db->query($this->_db->select('text')
        ->from('table.contribute')
        ->where('cid = ?', $cid)
        ->limit(1));

        $result = $this->_db->fetchRow($resource);
        $content = $result['text'];
        $isMarkdown = strpos($content, '<!--markdown-->');

        if (false !== $isMarkdown) {
            $content = str_replace('<!--markdown-->', '', $content);
            $content = $widget->markdown($content);
        } else {
            $content = $widget->autoP($content);
        }

        echo $content;
    }

    /**
     * 插入内容
     *
     * @access private
     * @param array $content 内容数组
     * @return integer
     */
    private function _insert(array $content)
    {

        /** 构建插入结构 */
        $insertStruct = array(
            'title'         =>  htmlspecialchars($content['title']),
            'slug'          =>  $content['slug'],
            'created'       =>  $content['created'],
            'modified'      =>  $content['created'],
            'text'          =>  empty($content['text']) ? NULL : $content['text'],
            'order'         =>  0,
            'authorId'      =>  1,
            'template'      =>  empty($content['template']) ? NULL : $content['template'],
            'type'          =>  'post',
            'status'        =>  'publish',
            'password'      =>  NULL,
            'commentsNum'   =>  0,
            'allowComment'  =>  $this->_options->defaultAllowComment,
            'allowPing'     =>  $this->_options->defaultAllowPing,
            'allowFeed'     =>  $this->_options->defaultAllowFeed,
            'parent'        =>  0,
            'author'        =>  empty($content['text']) ? NULL : $content['author'],
            'category'      =>  serialize($content['category']),
            'tags'          =>  $content['tags']
        );

        $insertId = $this->_db->query($this->_db->insert('table.contribute')->rows($insertStruct));

        return $insertId;
    }

    /**
     * 发布到文章
     *
     * @access private
     * @param array $contents 内容结构
     * @return void
     */
    private function _publish(array $content)
    {
        /* 默认审核设置 */
        $config = Helper::options()->plugin('Contribute');
        if ('draft' == $config->approved) {
            $content['type'] = 'post_draft';
        }

        /* 获取最大的cid, 并生成审核文章的slug */
        $cid = $this->_db->fetchObject($this->_db->select('cid')
            ->from('table.contents')->limit(1)
            ->order('cid', Typecho_Db::SORT_DESC))->cid;
        $content['slug'] = $this->_applySlug($content['slug'], $cid);
        
        $insertId = $this->_db->query($this->_db->insert('table.contents')->rows($content));

        return $insertId;
    }

    /**
     * 删除草稿
     *
     * @access private
     * @param string $draft 草稿cid
     * @return void
     */
    private function _deleteDraft($draft) {
        $this->_db->query($this->_db->delete('table.contribute')
            ->where('cid = ?', $draft));
    }

    /**
     * 生成缩略名
     *
     * @access private
     * @param string $slug 投稿缩略名
     * @return void
     */
    private function _applySlug($slug, $cid = NULL) {
        $slug = Typecho_Common::slugName($slug, $cid);
        $result = $slug;
        $count = 1;

        while ($this->_db->fetchObject($this->_db->select(array('COUNT(cid)' => 'num'))
        ->from('table.contents')->where('slug = ?', $result))->num > 0) {
            $result = $slug . '-' . $count;
            $count ++;
        }

        return $result;
    }

    /**
     * 设置分类
     *
     * @access private
     * @param integer $cid 内容id
     * @param array $categories 分类id的集合数组
     * @return integer
     */
    private function _setCategories($cid, array $categories)
    {
        $categories = array_unique(array_map('trim', $categories));

        foreach ($categories as $category) {
            /* 更新关系表 */
            $this->_db->query($this->_db->insert('table.relationships')
            ->rows(array(
                'mid' => $category,
                'cid' => $cid
            )));

            /* 更新分类表 */
            $this->_db->query($this->_db->update('table.metas')
            ->expression('count', 'count + 1')->where('mid = ?', $category));
        }
    }

    /**
     * 设置内容标签
     *
     * @access private
     * @param integer $cid
     * @param string $tags
     * @return string
     */
    private function _setTags($cid, $tags)
    {
        $tags = str_replace('，', ',', $tags);
        $tags = array_unique(array_map('trim', explode(',', $tags)));

        /** 取出插入tag */
        $insertTags = $this->widget('Widget_Abstract_Metas')->scanTags($tags);

        /** 插入tag */
        if ($insertTags) {
            foreach ($insertTags as $tag) {
                $this->_db->query($this->_db->insert('table.relationships')
                ->rows(array(
                    'mid' => $tag,
                    'cid' => $cid
                )));

                $this->_db->query($this->_db->update('table.metas')
                ->expression('count', 'count + 1')->where('mid = ?', $tag));
            }
        }
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        if (!$this->request->is('write')) {
            $this->widget('Widget_User')->pass('administrator');
            $this->on($this->request->is('approved'))->approved();
            $this->on($this->request->is('delete'))->delete();
            $this->on($this->request->is('preview'))->preview();
        }
        $this->on($this->request->is('write'))->write();
    }
}
