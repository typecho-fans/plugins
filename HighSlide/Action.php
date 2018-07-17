<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class HighSlide_Action extends Typecho_Widget implements Widget_Interface_Do
{
	private $db;
	private $options;

	/**
	 * 添加相册图片
	 * 
	 * @access public
	 * @return void
	 */
	public function insertgallery()
	{
		if (HighSlide_Plugin::form('insert')->validate()) {
			$this->response->goBack();
		}

		$gallery = $this->request->from('thumb','image','description','sort','name');
		$gallery['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)'=>'maxOrder'))->from('table.gallery'))->maxOrder+1;
		$gallery['gid'] = $this->db->query($this->db->insert('table.gallery')->rows($gallery));

		//返回提示信息
		$this->widget('Widget_Notice')->highlight('gid-'.$gallery['gid']);
		$this->widget('Widget_Notice')->set(_t('相册组%s图片 %s 添加成功','<strong>'.$gallery['sort'].'</strong>','<strong>'.$gallery['name'].'</strong>'),'success');
		$this->response->redirect(Typecho_Common::url('extending.php?panel=HighSlide%2Fmanage-gallery.php&group='.$gallery['sort'],$this->options->adminUrl));
	}

	/**
	 * 更新相册图片
	 * 
	 * @access public
	 * @return void
	 */
	public function updategallery()
	{
		if (HighSlide_Plugin::form('update')->validate()) {
			$this->response->goBack();
		}

		$gallery = $this->request->from('gid','thumb','image','description','sort','name');
		$this->db->query($this->db->update('table.gallery')->rows($gallery)->where('gid = ?',$gallery['gid']));

		//返回提示信息
		$this->widget('Widget_Notice')->highlight('gid-'.$gallery['gid']);
		$this->widget('Widget_Notice')->set(_t('相册组%s图片 %s 修改成功','<strong>'.$gallery['sort'].'</strong>','<strong>'.$gallery['name'].'</strong>'),'success');
		$this->response->redirect(Typecho_Common::url('extending.php?panel=HighSlide%2Fmanage-gallery.php&group='.$gallery['sort'],$this->options->adminUrl));
	}

	/**
	 * 移除相册图片
	 * 
	 * @access public
	 * @return void
	 */
	public function deletegallery()
	{
		$gids = $this->request->filter('int')->getArray('gid');
		$group = isset($this->request->group) ? $this->request->filter('int')->group : HighSlide_Plugin::defaultsort();

		$deletecount = 0;
		foreach ($gids as $gid) {
			if ($this->db->query($this->db->delete('table.gallery')->where('gid = ?',$gid))) {
				++$deletecount;
			}
		}

		//返回提示信息
		$this->widget('Widget_Notice')->set($deletecount>0 ? _t('图片已从相册移除') : _t('没有图片被移除'),$deletecount>0 ? 'success' : 'notice');
		$this->response->redirect(Typecho_Common::url('extending.php?panel=HighSlide%2Fmanage-gallery.php&group='.$group,$this->options->adminUrl));
	}

	/**
	 * 排序相册图片
	 * 
	 * @access public
	 * @return void
	 */
	public function sortgallery()
	{
		$gids = $this->request->filter('int')->getArray('gid');

		foreach ($gids as $sort=>$gid) {
			$this->db->query($this->db->update('table.gallery')->rows(array('order'=>$sort+1))->where('gid = ?',$gid));
		}

		//返回提示信息
		if (!$this->request->isAjax()) {
			$this->response->goBack();
		} else {
			$this->response->throwJson(array('success'=>1,'message'=>_t('图片排序完成')));
		}
	}

	/**
	 * 执行上传图片
	 * 
	 * @access public
	 * @return void
	 */
	public function uploadimage()
	{
		if (!empty($_FILES)) {
			$file = array_pop($_FILES);
			if (0==$file['error'] && is_uploaded_file($file['tmp_name'])) {
				//xhr的send无法支持utf8
				if ($this->request->isAjax()) {
					$file['name'] = urldecode($file['name']);
				}

				$result = HighSlide_Plugin::uploadhandle($file);

				if (false!==$result) {
					$this->response->throwJson(array(array(
						'name'=>$result['name'],
						'title'=>$result['title'],
						'bytes'=>number_format(ceil($result['size']/1024)).' Kb'
					)));
				}
			}
		}

		$this->response->throwJson(false);
	}

	/**
	 * 执行删除图片
	 * 
	 * @access public
	 * @return void
	 */
	public function removeimage()
	{
		$requests = $this->request->from('imgname','url');

		if ($requests['imgname']) {
			HighSlide_Plugin::removehandle($requests['imgname'],false,$requests['url']);
		}

		$this->response->throwJson(false);
	}

	/**
	 * 执行缩略图片
	 * 
	 * @access public
	 * @return void
	 */
	public function cropthumbnail()
	{
		$requests = $this->request->from('imgname','w','h','x1','y1','url');
		$isatt = isset($this->request->tid);

		if ($requests['imgname']) {
			$result = HighSlide_Plugin::crophandle($requests['imgname'],$requests['w'],$requests['h'],$requests['x1'],$requests['y1'],$requests['url'],$isatt);
			$bytes = number_format(ceil($result['size']/1024)).' Kb';
			$tid = '';

			//附件模式归档
			if ($isatt) {
				$tid = $this->request->filter('int')->tid;
				$widget = Typecho_Widget::widget('Widget_Abstract_Contents');

				if ($tid) { //修改
					$widget->update(array('text'=>serialize($result)),$this->db->sql()->where('cid = ?',$tid));
					$this->db->fetchRow($widget->select()->where('table.contents.cid = ?',$tid)
						->where('table.contents.type = ?', 'attachment'), array($widget, 'push'));
				} else { //新建
					$struct = array(
					'title'=>$result['name'],
					'slug' =>$result['name'],
					'type' =>'attachment',
					'status'=>'publish',
					'text' =>serialize($result),
					'allowComment' =>1,
					'allowPing'=>0,
					'allowFeed'=>1
					);
					if (isset($this->request->cid)) {
						$cid = $this->request->filter('int')->cid;
						if ($widget->isWriteable($this->db->sql()->where('cid = ?', $cid))) {
							$struct['parent'] = $cid;
						}
					}

					$tid = $widget->insert($struct);
					$this->db->fetchRow($widget->select()->where('table.contents.cid = ?', $tid)
						->where('table.contents.type = ?', 'attachment'), array($widget, 'push'));
				}
			}

			$this->response->throwJson(array('cid'=>$tid,'bytes'=>$bytes,'aurl'=>$result['aurl']));
		}

		$this->response->throwJson(false);
	}

	/**
	 * 同步插件设置
	 * 
	 * @access public
	 * @return void
	 */
	public function syncsettings()
	{
		//验证规格表单
		$validator = new Typecho_Validate();
		$validator->addRule('fixedwidth','isInteger',_t('固定宽度请填写整数数字'));
		$validator->addRule('fixedheight','isInteger',_t('固定高度请填写整数数字'));
		$validator->addRule('fixedratio',array(new HighSlide_Plugin,'ratioformat'),_t('固定比例请填写:号与数字'));
		$validator->addRule('fixedwidth','required',_t('固定宽度不能为空'));
		$validator->addRule('fixedheight','required',_t('固定高度不能为空'));
		$validator->addRule('fixedratio','required',_t('固定比例不能为空'));
		if ($error = $validator->run($this->request->from('fixedwidth','fixedheight','fixedratio'))) {
			$this->widget('Widget_Notice')->set($error,'error');
			$this->response->goBack();
		}

		//保存提交数据
		Helper::configPlugin('HighSlide',$this->request->from(
			'gallery','thumbfix','fixedwidth','fixedheight','fixedratio',
			'storage','thumbapi','path','cloudtoo',
			'qiniubucket','qiniudomain','qiniuak','qiniusk',
			'scsbucket','scsdomain','scsimgx','scsak','scssk',
			'nosbucket','nosdomain','nosak','nosas','nosep',
			'cosbucket','cosdomain','cosai','cossi','cossk','cosrg'
		));

		//返回提示信息
		$this->widget('Widget_Notice')->set(_t('相册设置已保存'),'success');
		$this->response->goBack();
	}

	/**
	 * 预览内文附件
	 * 
	 * @access public
	 * @return void
	 */
	public function postpreview()
	{
		$settings = $this->options->plugin('HighSlide');
		$local = $this->options->siteUrl;

		//获取附件对象
		if (isset($this->request->cid)) {
			$cid = $this->request->filter('int')->cid;
			$attachment = Typecho_Widget::widget('Widget_Contents_Attachment_Related','parentId='.$cid);
		} else {
			$attachment = Typecho_Widget::widget('Widget_Contents_Attachment_Unattached');
		}

		//重构响应数据
		$aurl = '';
		$attachurl = '';
		$title= '';
		$name = '';
		$parse = '';
		$path = '';
		$url = '';
		$imgurl = '';
		$struct =array();
		while ($attachment->next()) {
			$aurl = $attachment->attachment->aurl;
			$attachurl = $attachment->attachment->url;
			$title= $attachment->attachment->name;
			$name = basename($attachurl);
			//获取API文件名
			if ($aurl) {
				$parse = parse_url($attachurl);
				$name = basename($parse['path']);
			}
			$path = strstr($attachment->attachment->path,$name,true);
			$url = Typecho_Common::url($path,$settings->cloudtoo ? HighSlide_Plugin::route()->site : $local);
			//探测绝对地址
			if (!$aurl) {
				$imgurl = HighSlide_Plugin::ifexist($attachurl);
				if (!$imgurl) {
					$imgurl = HighSlide_Plugin::ifexist($url.$name);
					if (!$imgurl) {
						$imgurl = $local.$path.$name;
					}
				}
			}
			$struct[] = array(
				'cid'=>$attachment->cid,
				'isimg'=>$attachment->attachment->isImage ? 1 : 0,
				'name'=>$name,
				'title'=>$title,
				'url'=>$aurl ? $aurl : $imgurl,
				'size'=>number_format(ceil($attachment->attachment->size/1024)).' KB',
				'turl'=>$settings->cloudtoo && $settings->thumbapi ? str_replace('thumb_','',$name) : $url.(0===strpos($name,'thumb_') ? $name : 'thumb_'.$name),
				'aurl'=>$aurl
			);
		}
		if (!isset($this->request->cid)) {
			sort($struct); //修正排序
		}

		$this->response->throwJson(Json::encode($struct));
	}

	/**
	 * 绑定动作
	 * 
	 * @access public
	 * @return void
	 */
	public function action()
	{
		$this->db = Typecho_Db::get();
		$this->options = Helper::options();
		$this->security = Helper::security();

		$this->security->protect();
		$this->on($this->request->is('do=insert'))->insertgallery();
		$this->on($this->request->is('do=update'))->updategallery();
		$this->on($this->request->is('do=delete'))->deletegallery();
		$this->on($this->request->is('do=sort'))->sortgallery();
		$this->on($this->request->is('do=upload'))->uploadimage();
		$this->on($this->request->is('do=remove'))->removeimage();
		$this->on($this->request->is('do=crop'))->cropthumbnail();
		$this->on($this->request->is('do=sync'))->syncsettings();
		$this->on($this->request->is('do=preview'))->postpreview();

		$this->response->redirect($this->options->adminUrl);
	}

}