<?php
/**
 * 首页过滤指定分类
 * 
 * @package CateFilter
 * @author Rakiy
 * @version 1.2.4
 * @link 
 */
class CateFilter_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->indexHandle = array(__CLASS__, 'filter'); 
        return _t('插件已激活，现在可以对插件进行设置！');
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
    public static function config(Typecho_Widget_Helper_Form $form){
        
    Typecho_Widget::widget('Widget_Metas_Category_List')->to($categories);
    while($categories->next()){$cate[$categories->mid]=$categories->name;}//获取分类列表
    
    $CateId = new Typecho_Widget_Helper_Form_Element_Checkbox('CateId', 
    $cate,[], _t('勾选首页不想显示的分类'), NULL);
    $form->addInput($CateId->multiMode());
 
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
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
 
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function filter($obj, $select){
        //if('/feed' == strtolower(Typecho_Router::getPathInfo()) || '/feed/' == strtolower(Typecho_Router::getPathInfo())) return $select;
        $CateIds = Typecho_Widget::widget('Widget_Options')->plugin('CateFilter')->CateId;
        if(empty($CateIds)) return $select;       //数组为空，则直接返回
        $select = $select->select('table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.created', 'table.contents.authorId','table.contents.modified', 'table.contents.type', 'table.contents.status', 'table.contents.text', 'table.contents.commentsNum', 'table.contents.order','table.contents.template', 'table.contents.password', 'table.contents.allowComment', 'table.contents.allowPing', 'table.contents.allowFeed','table.contents.parent')->join('table.relationships','table.relationships.cid = table.contents.cid','right')->join('table.metas','table.relationships.mid = table.metas.mid','right')->where('table.metas.type=?','category');
        $CateIds = array_unique($CateIds);  //去除重复值
        foreach ($CateIds as $k => $v) {
            $select = $select->where('table.relationships.mid != '.intval($v))->group('cid');//确保每个值都是数字；排除重复文章
        } 
        return $select;
    }   
 
}