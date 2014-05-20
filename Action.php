<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Avatars_Action extends Typecho_Widget implements Widget_Interface_Do
{
	private $options;

	/**
	 * 执行清空缓存
	 *
	 * @access public
	 * @return void
	 */
	public function deleteFile()
	{
		$path = __TYPECHO_ROOT_DIR__ .'/usr/plugins/Avatars/cache/';

		foreach (glob($path.'*') as $filename) {
			unlink($filename);
		}

		$this->widget('Widget_Notice')->set('读者墙头像缓存已清空!',NULL,'success');

		$this->response->redirect(Typecho_Common::url('options-plugin.php?config=Avatars',$this->options->adminUrl));
	}

	/**
	 * 绑定动作
	 *
	 * @access public
	 * @return void
	 */
	public function action()
	{
		$this->options = Typecho_Widget::widget('Widget_Options');

		$this->on($this->request->is('do=delete'))->deleteFile();

		$this->response->redirect($this->options->adminUrl);
	}

}