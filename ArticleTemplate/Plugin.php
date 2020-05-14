<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章类型插件 For Typecho
 *
 * @package ArticleTemplate
 * @author benzBrake
 * @version 1.0.0
 * @link http://blog.iplayloli.com/typecho-plugin-articletemplate.html
 */
class ArticleTemplate_Plugin implements Typecho_Plugin_Interface {
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 *
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		$info = self::sqlInstall();
		Typecho_Plugin::factory('admin/write-post.php')->option = array(__CLASS__, 'setTemplate');
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array(__CLASS__, "updateTemplate");
		Typecho_Plugin::factory('Widget_Archive')->select = array(__CLASS__, 'selectHandle');
		return _t($info);
	}
	/**
	 * 把文章类型设置设置装入文章编辑页
	 *
	 * @access public
	 * @return void
	 */
	public static function setTemplate($post) {
		$db = Typecho_Db::get();
		$row = $db->fetchRow($db->select('template')->from('table.contents')->where('cid = ?', $post->cid));
		$template = isset($row['template']) ? $row['template'] : '';
		$typeFields = Typecho_Widget::widget('Widget_Options')->plugin(str_replace("_Plugin","",__CLASS__))->typeFields;
		$typeList = explode("\r\n",$typeFields);
		if (!in_array("standard",$typeList)) { 
			$typeList[] = "standard"; 
		}
		if ($template == "" || !in_array($template,$typeList)) { $template = "standard"; }
		$html = '';
		foreach($typeList as $type) {
			if ($type == $template) {
				$html = $html .'<option value="'.$type.'"selected>'.$type.'</option>';
			} else {
				$html = $html .'<option value="'.$type.'">'.$type.'</option>';
			}
		}
		$html = '<section class="typecho-post-option"><label for="template" class="typecho-label">文章类型</label><p><select id="template" name="template">'.$html.'</select></p></section>';
		_e($html);
	}
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate(){
		$delFields = Typecho_Widget::widget('Widget_Options')->plugin(str_replace("_Plugin","",__CLASS__))->delFields;
		if($delFields){
			$db = Typecho_Db::get();
			$prefix = $db->getPrefix();
			$db->query('UPDATE `' . $prefix . 'contents` SET `template` = NULL WHERE `type` = "post";');
		}
	}
	/**
	 * 获取插件配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form){
		$typeFields = new Typecho_Widget_Helper_Form_Element_Textarea('typeFields', NULL, _t('standard'), _t('文章类型'), _t('在这里设置可用的文章类型'));
		$form->addInput($typeFields);
		$delFields = new Typecho_Widget_Helper_Form_Element_Radio('delFields', array(0=>_t('保留数据'),1=>_t('删除数据'),), '0', _t('卸载设置'),_t('卸载插件后数据是否保留'));
		$form->addInput($delFields);
	}
	/**
	 * 数据库初始化
	 *
	 * @access private
	 * @return void
	 */
	public static function sqlInstall(){
		$db = Typecho_Db::get();
		$type = explode('_', $db->getAdapterName());
		$type = array_pop($type);
		$prefix = $db->getPrefix();
		try {
			$select = $db->select('table.contents.template')->from('table.contents');
			$db->query($select, Typecho_Db::READ);
			return '检测到模板字段(template)，插件启用成功';
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql' == $type && (1054 == $code || '42S22' == $code)) || ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
				try {
					if ('Mysql' == $type) {
						$db->query("ALTER TABLE `".$prefix."contents` ADD `template` VARCHAR(32) NOT NULL  DEFAULT NULL;");
					} else if ('SQLite' == $type) {
						$db->query("ALTER TABLE `".$prefix."contents` ADD `template` VARCHAR(255) NOT NULL  DEFAULT NULL");
					} else {
						throw new Typecho_Plugin_Exception('不支持的数据库类型：'.$type);
					}
					return '成功建立模板字段(template)，插件启用成功';
				} catch (Typecho_Db_Exception $e) {
					$code = $e->getCode();
					if(('Mysql' == $type && 1060 == $code) ) {
						return '模板字段(template)已经存在，插件启用成功';
					}
					throw new Typecho_Plugin_Exception('插件启用失败。错误号：'.$code);
				}
			}
			throw new Typecho_Plugin_Exception('数据表检测失败，插件启用失败。错误号：'.$code);
		}
	}
	/**
	* 个人用户的配置面板
	*
	* @access public
	* @param Typecho_Widget_Helper_Form $form
	* @return void
	*/
	public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	/**
	 * 发布文章同时更新文章类型
	 *
	 * @access public
	 * @return void
	 */
	public static function updateTemplate($contents, $post){
		$template = $post->request->get('template', NULL);
		if ($template == "standard") { $template = NULL; }
		$db = Typecho_Db::get();
		$sql = $db->update('table.contents')->rows(array('template' => $template))->where('cid = ?', $post->cid);
		$db->query($sql);
	}
    /**
     * 把增加的字段添加到查询中，以便在模版中直接调用
     *
     * @access public
     * @return void
     */
	public static function selectHandle($archive){
		$user = Typecho_Widget::widget('Widget_User');
		if ('post' == $archive->parameter->type || 'page' == $archive->parameter->type) {
			if ($user->hasLogin()) {
				$select = $archive->select()->where('table.contents.status = ? OR table.contents.status = ? OR
						(table.contents.status = ? AND table.contents.authorId = ?)',
						'publish', 'hidden', 'private', $user->uid);
			} else {
				$select = $archive->select()->where('table.contents.status = ? OR table.contents.status = ?',
						'publish', 'hidden');
			}
		} else {
			if ($user->hasLogin()) {
				$select = $archive->select()->where('table.contents.status = ? OR
						(table.contents.status = ? AND table.contents.authorId = ?)', 'publish', 'private', $user->uid);
			} else {
				$select = $archive->select()->where('table.contents.status = ?', 'publish');
			}
		}
		$select->where('table.contents.created < ?', Typecho_Date::gmtTime());
		$select->cleanAttribute('fields');
		return $select;
	}
}
