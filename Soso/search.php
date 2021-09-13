<?php
  $po = $obj->select()->join('table.relationships','table.relationships.cid = table.contents.cid','right')->join('table.metas','table.relationships.mid = table.metas.mid','right')->where('table.metas.type=?','category')
    ->where("table.contents.password IS NULL OR table.contents.password = ''")
    ->where('table.contents.status = ?', 'publish')
    ->where('table.contents.title LIKE ? OR table.contents.text LIKE ?', $searchQuery, $searchQuery)
    ->where('table.contents.type = ?', 'post')->group('cid'); 
//定制功能，用来根据分类id搜索内容，需模板代码配合才会启用
if($cat>0){
 $po = $po->where('table.relationships.mid = ? OR table.metas.parent = ?',$cat,$cat);
}
      
//常规搜索
 if($Somo==2){
 $po = $po->where('table.contents.title LIKE ?', $searchQuery);//只允许搜索文章标题
 }
       $sid = Typecho_Widget::widget('Widget_Options')->plugin('Soso')->sid;
      if(!$sid){}else{
 $sid = explode(',', $sid);
        $sid = array_unique($sid);  //去除重复值
        foreach ($sid as $k => $v) {
             $po = $po->where('table.relationships.mid != '.intval($v));//确保每个值都是数字
        } 
      }
      
  $se = clone $po;
  $obj->setCountSql($se);  
      
  $page=$obj->request->get('page');
  $po = $po->order('table.contents.created', Typecho_Db::SORT_DESC)
         ->page($page, $obj->parameter->pageSize);
  $obj->query($po);
      
    return   $keywords;

?>