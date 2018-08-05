<?php
/**
 * 文章置顶 
 * 
 * Mod by <a href="http://doufu.ru">Ryan</a>
 * @package Sticky
 * @author Ryan, Willin Kan
 * @version 1.0.1
 * @update: 2017.07.32
 * @link http://kan.willin.org/typecho/
 */
class Sticky_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->indexHandle = array('Sticky_Plugin', 'sticky');
		Typecho_Plugin::factory('Widget_Archive')->categoryHandle = array('Sticky_Plugin', 'stickyC');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $sticky_cids = new Typecho_Widget_Helper_Form_Element_Text(
          'sticky_cids', NULL, '',
          '置顶文章的 cid', '按照排序输入, 请以半角逗号或空格分隔 cid.');
        $form->addInput($sticky_cids);

        $sticky_html = new Typecho_Widget_Helper_Form_Element_Textarea(
          'sticky_html', NULL, "<span style='color:red'>[置顶]</span>",
          '置顶标题的 html', '这里的代码会自动插入置顶文章的标题后');
        $sticky_html->input->setAttribute('rows', '7')->setAttribute('cols', '80');
        $form->addInput($sticky_html);
		
		$sticky_cat = new Typecho_Widget_Helper_Form_Element_Radio('sticky_cat' , array('1'=>_t('开启'),'0'=>_t('关闭')),'1',_t('分类置顶'),_t('开启分类页面也会把文章置顶'));
		$form->addInput($sticky_cat);
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
     * 选取置顶文章
     * 
     * @access public
     * @param object $archive, $select
     * @return void
     */
    public static function sticky($archive, $select)
    {
        $config  = Typecho_Widget::widget('Widget_Options')->plugin('Sticky');
        $sticky_cids = $config->sticky_cids ? explode(',', strtr($config->sticky_cids, ' ', ',')) : '';
        if (!$sticky_cids) return;

        $db = Typecho_Db::get();
        $paded = $archive->request->get('page', 1);

        foreach($sticky_cids as $cid) {
          if ($cid && $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', $cid))) {
              if ($paded == 1) {                               // 首頁 page.1 才會有置頂文章
                $sticky_post['title'] = $sticky_post['title'] . $config->sticky_html;
                $archive->push($sticky_post);                  // 選取置頂的文章先壓入
              }
              $select->where('table.contents.cid != ?', $cid); // 使文章不重覆
          }
        }
    }
	public static function stickyC($archive, $select)
	{
		$config  = Typecho_Widget::widget('Widget_Options')->plugin('Sticky');
		if (!$config->sticky_cat) return;
        $config  = Typecho_Widget::widget('Widget_Options')->plugin('Sticky');
        $sticky_cids = $config->sticky_cids ? explode(',', strtr($config->sticky_cids, ' ', ',')) : '';
        if (!$sticky_cids) return;

        $db = Typecho_Db::get();
        $paded = $archive->request->get('page', 1);

        foreach($sticky_cids as $cid) {
          if ($cid && $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', $cid))) {
              if ($paded == 1) {                               // 首頁 page.1 才會有置頂文章
                $sticky_post['title'] = $sticky_post['title'] . $config->sticky_html;
                $archive->push($sticky_post);                  // 選取置頂的文章先壓入
              }
              $select->where('table.contents.cid != ?', $cid); // 使文章不重覆
          }
        }
	}
}