<?php
/**
 * 搜索增强插件,支持typecho1.1及以上版本
 * 
 * @package Soso
 * @author 泽泽社长
 * @version 1.0.6
 * @link http://qqdie.com/
 */
class Soso_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->search = array('Soso_Plugin', 'soso'); 
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Soso_Plugin','keywordsl');
       Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Soso_Plugin','keywordsl');
       Typecho_Plugin::factory('Widget_Archive')->title = array('Soso_Plugin','keywordst');
        Typecho_Plugin::factory('Widget_Archive')->callExcerpts = array('Soso_Plugin', 'excerpts');
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
       $Somo = new Typecho_Widget_Helper_Form_Element_Radio('Somo',array('1' => _t('常规模式'),'2' => _t('仅搜索标题')),'1',_t('搜索模式'),_t(""));
       $form->addInput($Somo); 
       $sid = new Typecho_Widget_Helper_Form_Element_Text('sid', NULL, NULL, _t('搜索结果不显示的分类'), _t('多个请用英文逗号隔开'));
        $form->addInput($sid);
      
      
      
      
    $tuozhan = new Typecho_Widget_Helper_Form_Element_Checkbox('tuozhan', 
    array('keyred' => _t('被搜索的<font color="red">词汇</font>高亮显示'),
),
    array(), _t('拓展设置'), _t('<p style="background: #fff;margin: 2px 0;padding: 5px;">如果模板中缩略内容使用的是<code style="color: red;">$this->excerpt(140, \'...\')</code>请改为<code style="color: red;">$this->excerpts($this)</code>并且在下面的输入框中输入要截取的内容长度</p>'));
    $form->addInput($tuozhan->multiMode());
      
    $lenth = new Typecho_Widget_Helper_Form_Element_Text('lenth', NULL,'140', _t('缩略内容截取长度'), _t('<div style="background: #fff;margin: 15px 0;padding: 10px 5px;"><p style="font-weight: bold;margin-top: 0;">感谢：</p>
    <a href="http://qqdie.com/" target="_blank">泽泽</a> <font color="red">❤</font> <a href="http://siitake.cn/" target="_blank">香菇</a>，<a href="http://ysido.com/" target="_blank">Rakiy</a>
    </div>'));
    $form->addInput($lenth->addRule('isInteger', '请填数字'));
 
      
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
    public static function soso($keywords, $obj) {
  $Somo = Typecho_Widget::widget('Widget_Options')->plugin('Soso')->Somo;//获取设置参数
      
$keywords=$obj->request->keywords;//尝试越过搜索词过滤，失败！【可通过修改var/Widget/Archive.php的源码,$keywords = $this->request->filter('url', 'search')->keywords;改为$keywords = $this->request->keywords;】
      
      
  $searchQuery = '%' . str_replace(' ', '%', $keywords) . '%';
  $po = $obj->select()->join('table.relationships','table.relationships.cid = table.contents.cid','right')->join('table.metas','table.relationships.mid = table.metas.mid','right')->where('table.metas.type=?','category')
           ->where("table.contents.password IS NULL OR table.contents.password = ''")
           ->where('table.contents.title LIKE ? OR table.contents.text LIKE ?', $searchQuery, $searchQuery)
           ->where('table.contents.type = ?', 'post')->group('cid'); 
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
      
      
}
    


public static function keywordsl($con, $obj,$text) {
  $text = empty($text)?$con:$text;
  if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan) && in_array('keyred',  Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan)){
$keywords=$obj->request->keywords;

$text = preg_replace_callback('#(.*?)\[Meting\](.*?)\[\/Meting\](.*?)#', 
                              function($s)use($keywords){
return str_ireplace($keywords,'<font color="red">'.$keywords.'</font>', $s[1]).'[Meting]'.$s[2].'[/Meting]'.str_ireplace($keywords,'<font color="red">'.$keywords.'</font>', $s[3]);
}    
                              , $text);  

      }
        return $text;
      
      
}
  

public static function keywordst($titl, $obj) {
  if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan) && in_array('keyred',  Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan)){
$keywords = $obj->request->keywords;
$titl = str_ireplace($keywords,'<font color="red">'.$keywords.'</font>',$titl);
  }
        return $titl; 
}  
  
  
  public static function excerpts($obj)
    {  
$lenth = Typecho_Widget::widget('Widget_Options')->plugin('Soso')->lenth;//获取设置参数
$content = Typecho_Common::subStr(strip_tags($obj->excerpt), 0, $lenth, '...');
    if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan) && in_array('keyred',  Typecho_Widget::widget('Widget_Options')->plugin('Soso')->tuozhan)){
$keywords=$obj->request->keywords;
$content = str_ireplace($keywords,'<font color="red">'.$keywords.'</font>', $content);
    }
        echo $content;
    }
}
