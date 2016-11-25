<?php
/**
 * 首页过滤指定分类
 * 
 * @package CateFilter
 * @author Rakiy
 * @version 1.1.0
 * @link http://ysido.com/
 *
 * 历史版本
 *
 * version 1.1.0 -- 2016-11-25
 * 修复过滤分类时同时会过滤FEED的BUG
 *
 * version 1.0.0 -- 2013-12-22
 * 实现功能
 */
class CateFilter_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->indexHandle = array('CateFilter_Plugin', 'CateFilter'); 
        return _t('插件已激活，现在可以对插件进行设置！');
    }
    
    /**
     * 禁用duoshuo方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取duoshuo配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
   public static function config(Typecho_Widget_Helper_Form $form){
        $CateId = new Typecho_Widget_Helper_Form_Element_Text('CateId', NULL, '0', _t('首页不显示的分类'), _t('多个请用英文逗号隔开'));
        $form->addInput($CateId);
        
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

	public static function CateFilter($this, $select){
        if('/feed' != strtolower(Typecho_Router::getPathInfo())){
            $CateIds = Typecho_Widget::widget('Widget_Options')->plugin('CateFilter')->CateId;
            if($CateIds){
                $select = $select->join('table.relationships','table.relationships.cid = table.contents.cid','right')->join('table.metas','table.relationships.mid = table.metas.mid','right')->where('table.metas.type=?','category');
                $CateIds = explode(',', $CateIds);
                $CateIds = array_unique($CateIds);  //去除重复值
                foreach ($CateIds as $k => $v) {
                    $select = $select->where('table.relationships.mid != '.intval($v));//确保每个值都是数字
                }          
            }
        }
        return $select;
    }
}
