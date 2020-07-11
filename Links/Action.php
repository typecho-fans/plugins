<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Links_Action extends Typecho_Widget implements Widget_Interface_Do
{
	private $db;
	private $options;
	private $prefix;
			
	public function insertLink()
	{
		/** 取出数据 */
		if ($this->request->OEM == 'tf') {
			$link = array(
				'name' => 'Typecho Fans',
				'url' => 'https://typecho-fans.github.io',
				'email' => 'typechoer@126.com',
				'image' => 'https://typecho-fans.github.io/text-logo.svg',
				'description' => 'Typecho非官方粉丝群开源作品社区'
			);
		} else {
			if (Links_Plugin::form('insert')->validate()) {
				$this->response->goBack();
			}
			$link = $this->request->from('lid', 'email', 'image', 'url', 'state');
			/** 过滤XSS */
			$link['name'] = $this->request->filter('xss')->name;
			$link['sort'] = $this->request->filter('xss')->sort;
			$link['description'] = $this->request->filter('xss')->description;
			$link['user'] = $this->request->filter('xss')->user;
		}
		$link['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))->from($this->prefix.'links'))->maxOrder + 1;

		/** 插入数据 */
		$link['lid'] = $this->db->query($this->db->insert($this->prefix.'links')->rows($link));

		/** 设置高亮 */
		$this->widget('Widget_Notice')->highlight('link-'.$link['lid']);

		/** 提示信息 */
		$this->widget('Widget_Notice')->set(_t('友链%s已经被增加',
		' <a href="'.$link['url'].'">'.$link['name'].'</a> '), null, 'success');

		/** 转向原页 */
		$this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
	}

	public function updateLink()
	{
		if (Links_Plugin::form('update')->validate()) {
			$this->response->goBack();
		}

		/** 取出数据 */
		$link = $this->request->from('lid', 'email', 'image', 'url', 'state');
		/** 过滤XSS */
		$link['name'] = $this->request->filter('xss')->name;
		$link['sort'] = $this->request->filter('xss')->sort;
		$link['description'] = $this->request->filter('xss')->description;
		$link['user'] = $this->request->filter('xss')->user;

		/** 更新数据 */
		$this->db->query($this->db->update($this->prefix.'links')->rows($link)->where('lid = ?', $link['lid']));

		/** 设置高亮 */
		$this->widget('Widget_Notice')->highlight('link-'.$link['lid']);

		/** 提示信息 */
		$this->widget('Widget_Notice')->set(_t('友链%s已经被更新',
		' <a href="'.$link['url'].'">'.$link['name'].'</a> '), null, 'success');

		/** 转向原页 */
		$this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
	}

    public function deleteLink()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $deleteCount = 0;
        if ($lids && is_array($lids)) {
            foreach ($lids as $lid) {
                if ($this->db->query($this->db->delete($this->prefix.'links')->where('lid = ?', $lid))) {
                    $deleteCount ++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('友链已经删除') : _t('没有友链被删除'), null,
        $deleteCount > 0 ? 'success' : 'notice');
        
        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function enableLink()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $enableCount = 0;
        if ($lids && is_array($lids)) {
            foreach ($lids as $lid) {
                if ($this->db->query($this->db->update($this->prefix.'links')->rows(array('state' => '1'))->where('lid = ?', $lid))) {
                    $enableCount ++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set($enableCount > 0 ? _t('友链已经启用') : _t('没有友链被启用'), null,
        $enableCount > 0 ? 'success' : 'notice');
        
        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function prohibitLink()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $prohibitCount = 0;
        if ($lids && is_array($lids)) {
            foreach ($lids as $lid) {
                if ($this->db->query($this->db->update($this->prefix.'links')->rows(array('state' => '0'))->where('lid = ?', $lid))) {
                    $prohibitCount ++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set($prohibitCount > 0 ? _t('友链已经禁用') : _t('没有友链被禁用'), null,
        $prohibitCount > 0 ? 'success' : 'notice');
        
        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function sortLink()
    {
        $links = $this->request->filter('int')->getArray('lid');
        if ($links && is_array($links)) {
			foreach ($links as $sort => $lid) {
				$this->db->query($this->db->update($this->prefix.'links')->rows(array('order' => $sort + 1))->where('lid = ?', $lid));
			}
        }
    }

    public function getMd5()
    {
        $this->response->throwJson(md5($this->request->email));
    }

	public function action()
	{
		Helper::security()->protect();
		Typecho_Widget::widget('Widget_User')->pass('administrator');
		$this->db = Typecho_Db::get();
		$this->prefix = $this->db->getPrefix();
		$this->options = Typecho_Widget::widget('Widget_Options');
		$this->on($this->request->is('do=insert'))->insertLink();
		$this->on($this->request->is('do=update'))->updateLink();
		$this->on($this->request->is('do=delete'))->deleteLink();
		$this->on($this->request->is('do=enable'))->enableLink();
		$this->on($this->request->is('do=prohibit'))->prohibitLink();
		$this->on($this->request->is('do=sort'))->sortLink();
		$this->on($this->request->is('do=md5'))->getMd5();
		$this->response->redirect($this->options->adminUrl);
	}
}
