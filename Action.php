<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class HighSlide_Action extends Typecho_Widget implements Widget_Interface_Do
{
	private $db;
	private $options;
	private $prefix;

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
		$gallery['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)'=>'maxOrder'))->from($this->prefix.'gallery'))->maxOrder+1;
		$gallery['gid'] = $this->db->query($this->db->insert($this->prefix.'gallery')->rows($gallery));

		//返回原页并提示信息
		$this->widget('Widget_Notice')->highlight('gallery-'.$gallery['gid']);
		$this->widget('Widget_Notice')->set(_t('相册组%s: 图片%s 添加成功',
			$gallery['sort'],$gallery['name']),NULL,'success');

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
		$this->db->query($this->db->update($this->prefix.'gallery')->rows($gallery)->where('gid=?',$gallery['gid']));

		//返回原页并提示信息
		$this->widget('Widget_Notice')->highlight('gallery-'.$gallery['gid']);
		$this->widget('Widget_Notice')->set(_t('相册组%s: 图片%s 更新成功',
			$gallery['sort'],$gallery['name']),NULL,'success');

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

		$deletecount = 0;
		if ($gids) {
			foreach ($gids as $gid) {
				if ($this->db->query($this->db->delete($this->prefix.'gallery')->where('gid=?',$gid))) {
					$deletecount ++;
				}
			}
		}

		//返回原页并提示信息
		$this->widget('Widget_Notice')->set($deletecount>0?_t('图片已从相册移除'):_t('没有图片被移除'),NULL,
			$deletecount>0?'success':'notice');

		$this->response->goBack();
	}

	/**
	 * 排序相册图片
	 *
	 * @access public
	 * @return void
	 */
	public function sortgallery()
	{
		$galleries = $this->request->filter('int')->getArray('gid');

		if ($galleries) {
			foreach ($galleries as $sort=>$gid) {
				$this->db->query($this->db->update($this->prefix.'gallery')->rows(array('order'=>$sort+1))->where('gid=?',$gid));
			}
		}

		//返回原页并提示信息
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
			if (0==$file['error']&&is_uploaded_file($file['tmp_name'])) {

				// xhr的send无法支持utf8
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
		$imgname = $this->request->from('imgname');

		//获取附件源参数
		$path = $this->request->from('path');
		$url = $this->request->from('url');

		if ($imgname) {
			HighSlide_Plugin::removehandle($imgname['imgname'],$path['path'],$url['url']);
		}

		$this->response->throwJson(false);
	}

	/**
	 * 执行裁切图片
	 *
	 * @access public
	 * @return void
	 */
	public function cropthumbnail()
	{
		$imgname = $this->request->from('imgname');
		$w = $this->request->from('w');
		$h = $this->request->from('h');
		$x1 = $this->request->from('x1');
		$y1 = $this->request->from('y1');

		//获取附件源参数
		$path = $this->request->from('path');
		$url = $this->request->from('url');

		if ($imgname) {
			$result = HighSlide_Plugin::crophandle($imgname['imgname'],$w['w'],$h['h'],$x1['x1'],$y1['y1'],$path['path'],$url['url']);
			$this->response->throwJson(array(
				'bytes'=>number_format(ceil($result/1024)).' Kb'
				));
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
		//验证组合表单
		$requests = array_merge($this->request->from('fixedwidth'),
								$this->request->from('fixedheight'),
								$this->request->from('fixedratio')
								);

		$validator = new Typecho_Validate();
		$validator->addRule('fixedwidth','isInteger',_t('固定宽度请输入整数数字'));
		$validator->addRule('fixedheight','isInteger',_t('固定高度请输入整数数字'));
		$validator->addRule('fixedratio',array(new HighSlide_Plugin,'ratioformat'),_t('固定比例请输入:与数字'));
		$validator->addRule('fixedwidth','required',_t('固定宽度不能为空'));
		$validator->addRule('fixedheight','required',_t('固定高度不能为空'));
		$validator->addRule('fixedratio','required',_t('固定比例不能为空'));

		if ($error = $validator->run($requests)) {
			$this->widget('Widget_Notice')->set($error,'error');
			$this->response->goBack();
		}

		//构建同步数组
		$syncsets = array('qiniubucket','qiniudomain','qiniuaccesskey','qiniusecretkey','qiniuprefix',
							'upyunbucket','upyundomain','upyunuser','upyunpwd','upyunkey','upyunprefix',
							'bcsbucket','bcsapikey','bcssecretkey','bcsprefix',
							'storage','local','thumbfix','fixedwidth','fixedheight','fixedratio','gallery');
		foreach ($syncsets as $syncset) {
			$result = $this->request->from($syncset);
			$datas[$syncset] = $result[$syncset];
		}

		//返回原页并提示信息
		Widget_Plugins_Edit::configPlugin('HighSlide',$datas);
		$this->widget('Widget_Notice')->set(_t('相册设置已保存'),NULL,'success');

		$this->response->goBack();
	}

	/**
	 * 异步附件预览
	 *
	 * @access public
	 * @return void
	 */
	public function postpreview()
	{
		$cid = $this->request->filter('int')->cid;

		//调用对象
		if ($cid) {
			Typecho_Widget::widget('Widget_Contents_Attachment_Related','parentId='.$cid)->to($attachment);
		} else {
			Typecho_Widget::widget('Widget_Contents_Attachment_Unattached')->to($attachment);
		}

		//还原为数组
		while ($attachment->next()) {
			$datas[] = unserialize($attachment->attachment);
		}

		foreach ($datas as $data) {
			if (!$data['isImage']) {
				return false;
			}

			$name = basename($data['path']);
			$prefix = dirname($data['url']);
			$thumb = $prefix.'/thumb_'.$name;

			//直接验证缩略图
			$headers = @get_headers($thumb,true);
			$open = (@fopen($thumb,'r'))?1:0;

			$parse[] = array('title'=>$data['name'],
							'name'=>$name,
							'url'=>$data['url'],
							'prefix'=>$prefix,
							'path'=>str_replace($name,'',$data['path']),
							'size'=>number_format(ceil($data['size']/1024)).' KB',
							'thumb'=>$thumb,
							'tsize'=>number_format(ceil($headers['Content-Length']/1024)).' KB',
							'tstat'=>$open);
		}
		$parse = json_encode($parse);

		$this->response->throwJson($parse);
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
		$this->prefix = $this->db->getPrefix();
		$this->options = Typecho_Widget::widget('Widget_Options');

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