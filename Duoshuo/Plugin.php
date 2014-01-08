<?php
/**
 * 多说实时同步插件
 * 
 * @package Duoshuo
 * @author Rakiy
 * @version 1.1.2
 * @link http://ysido.com/duoshuo.htm
 *
 *
 */
class Duoshuo_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活duoshuo方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		try {
			Duoshuo_Plugin::duoshuoInstall();
			$err = '建立多说信息数据表，duoshuo启用成功';
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if (1050 == $code || 1062 == $code || 1060 == $code) {
				$err = '多说信息数据表数据表已经存在，duoshuo启用成功';
			} else {
				return _t('多说实时同步duoshuo启用失败'.$code);
			}
		}
		Helper::addPanel(3, 'Duoshuo/manage-duoshuo.php', '多说评论', '多说评论管理', 'administrator');
		Helper::addAction('duoshuo-edit', 'Duoshuo_Action');
        Helper::addRoute('DuoShuoSync', '/DuoShuoSync', 'Duoshuo_Action', 'api');
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('Duoshuo_Plugin', 'comments_deny');  //关闭站内留言
		return _t($err);
    }
    
    /**
     * 禁用duoshuo方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
	{
		Helper::removeAction('duoshuo-edit');
        Helper::removeRoute('DuoShuoSync');
		Helper::removePanel(3, 'Duoshuo/manage-duoshuo.php');

	}
    
    /**
     * 获取duoshuo配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
   public static function config(Typecho_Widget_Helper_Form $form){
        $end_point = new Typecho_Widget_Helper_Form_Element_Radio('end_point', array('0'=>_t('api.duoshuo.com  [国内]')."\r\n", '1'=>_t('api.duoshuo.org  [国外]'),'2'=>_t('118.144.80.201 [特殊]')), '1', _t('多说同步API节点'),_t('如果你的博客服务器DNS坏掉了，才会用到第3个，其它情况慎用'));
        $form->addInput($end_point);
        $seo_enabled = new Typecho_Widget_Helper_Form_Element_Radio('seo_enabled', array('1'=>_t('开启'), '0'=>_t('关闭')), '1', _t('是否动态改变文章评论数'),_t('若开启，则在评论操作时，同时改变本地文章评论数，不必修改模板中文章评论数量'));
        $form->addInput($seo_enabled);
        $sync_to_local = new Typecho_Widget_Helper_Form_Element_Radio('sync_to_local', array('1'=>_t('开启'), '0'=>_t('关闭')), '1', _t('是否开启主动同步功能'),_t('若开启，则不进行主动同步，手动同步不受影响'));
        $form->addInput($sync_to_local);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){

    }

	public static function duoshuoInstall()
	{
		$installDb = Typecho_Db::get();
		$scripts = file_get_contents('usr/plugins/Duoshuo/install.sql');
		$scripts = str_replace(array('%charset%','typecho_'), array('utf8',$installDb->getPrefix()), $scripts);
		$scripts = explode(';', $scripts);
		foreach ($scripts as $script)
		{
			$script = trim($script);
			if ($script)
			{
				$installDb->query($script, Typecho_Db::WRITE);
			}
		}
	}
    /**
     * 关闭站点原来的留言
     * 
     * @access public
     * @return void
     */
    public static function comments_deny($comment, $post){
        throw new Typecho_Widget_Exception('留言提交已关闭');
        return false;
    }
}
