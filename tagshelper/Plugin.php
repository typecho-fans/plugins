<?php
/**
 * 后台编辑文章时增加标签选择列表
 * 
 * @package tagshelper
 * @author 泽泽社长
 * @version 1.1
 * @link http://blog.zezeshe.com
 */
class tagshelper_Plugin implements Typecho_Plugin_Interface
{ 
 public static function activate()
	{
Typecho_Plugin::factory('admin/write-post.php')->bottom = array('tagshelper_Plugin', 'tagslist');
    }
	/* 禁用插件方法 */
	public static function deactivate(){}
    public static function config(Typecho_Widget_Helper_Form $form){

    }
    
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}


    public static function tagslist()
    {

      
?><style>.tagshelper a{cursor: pointer; padding: 0px 6px; margin: 2px 0;display: inline-block;border-radius: 2px;text-decoration: none;}
.tagshelper a:hover{background: #ccc;color: #fff;}
</style>
<script> $(document).ready(function(){
    $('#tags').after('<div style="margin-top: 35px;" class="tagshelper"><ul style="list-style: none;border: 1px solid #D9D9D6;padding: 6px 12px; max-height: 240px;overflow: auto;background-color: #FFF;border-radius: 2px;"><?php
$i=0;
Typecho_Widget::widget('Widget_Metas_Tag_Cloud', 'sort=count&desc=1&limit=200')->to($tags);
while ($tags->next()) {
echo "<a id=".$i." onclick=\"$(\'#tags\').tokenInput(\'add\', {id: \'".$tags->name."\', tags: \'".$tags->name."\'});\">".$tags->name."</a>";
$i++;
}
?></ul></div>');
  });</script>
<?php

    }
}
