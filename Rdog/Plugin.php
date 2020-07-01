<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 修改注册时默认用户组，贡献者可直接发布文章无需审核,前台注册支持用户输入密码,支持模板开发者设置前台注册后的跳转地址，设置前台文章发布后的跳转地址
 * 
 * @package 权限狗
 * @author 泽泽
 * @version 1.4.0
 * @link https://qqdie.com/archives/typecho-Rdog.html
 */
class Rdog_Plugin extends Widget_Abstract_Users implements Typecho_Plugin_Interface
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
      Typecho_Plugin::factory('Widget_Register')->register = array('Rdog_Plugin', 'zhuce'); 
	  Typecho_Plugin::factory('Widget_Register')->finishRegister = array('Rdog_Plugin', 'zhucewan');
	  Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('Rdog_Plugin', 'fabu');
	  Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('Rdog_Plugin', 'fabuwan');
      Typecho_Plugin::factory('admin/footer.php')->end = array('Rdog_Plugin', 'footerjs');
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

    $yonghuzu = new Typecho_Widget_Helper_Form_Element_Radio('yonghuzu',array(
      'visitor' => _t('访问者'),
      'subscriber' => _t('关注者'),
      'contributor' => _t('贡献者'),
      'editor' => _t('编辑'),
      'administrator' => _t('管理员')
    ),'subscriber',_t('注册用户默认用户组设置'),_t('<p class="description">
不同的用户组拥有不同的权限，具体的权限分配表请<a href="http://docs.typecho.org/develop/acl" target="_blank" rel="noopener noreferrer">参考这里</a>.</p>'));
    $form->addInput($yonghuzu); 

    $tuozhan = new Typecho_Widget_Helper_Form_Element_Checkbox('tuozhan', 
    array('contributor-nb' => _t('勾选该选项让【贡献者】直接发布文章无需审核'),
          'register-nb' => _t('勾选该选项后台注册功能将可以直接设置注册密码'),
),
    array(), _t('拓展设置'), _t(''));
    $form->addInput($tuozhan->multiMode());
      
      
//    $tcat = new Typecho_Widget_Helper_Form_Element_Text('tcat', NULL, NULL, _t('特例分类'), _t('在这里填入一个分类mid，分类间用英文的半角逗号隔开如【1,2】，这些分类贡献者发布文章必须需要经过审核！'));
//    $form->addInput($tcat);
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
public static function zhuce($v) {
  /*获取插件设置*/
   $yonghuzu = Typecho_Widget::widget('Widget_Options')->plugin('Rdog')->yonghuzu;
  $hasher = new PasswordHash(8, true);
  /*判断注册表单是否有密码*/
  if(isset(Typecho_Widget::widget('Widget_Register')->request->password)){
    /*将密码设定为用户输入的密码*/
    $generatedPassword = Typecho_Widget::widget('Widget_Register')->request->password;
  }else{
    /*用户没输入密码，随机密码*/
    $generatedPassword = Typecho_Common::randString(7);
  }
  /*将密码设置为常量，方便下个函数adu()直接获取*/
  define('passd', $generatedPassword);
  /*将密码加密*/
  $wPassword = $hasher->HashPassword($generatedPassword);
  /*设置用户密码*/
  $v['password']=$wPassword;
  /*将注册用户默认用户组改为插件设置的用户组*/
  $v['group']=$yonghuzu;
  /*返回注册参数*/
  return $v;
}
public static function zhucewan($obj) {
 /*获取密码*/
 $wPassword=passd;
 /*登录账号*/
 $obj->user->login($obj->request->name,$wPassword);
 /*删除cookie*/
 Typecho_Cookie::delete('__typecho_first_run');
 Typecho_Cookie::delete('__typecho_remember_name');
 Typecho_Cookie::delete('__typecho_remember_mail');
 /*发出提示*/
 $obj->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $obj->screenName, $wPassword), 'success');
 /*跳转地址(后台)*/
 if (NULL != $obj->request->referer) {
 $obj->response->redirect($obj->request->referer);
 }else if(NULL != $obj->request->tz){
   if (Helper::options()->rewrite==0){$authorurl=Helper::options()->rootUrl.'/index.php/author/';}else{$authorurl=Helper::options()->rootUrl.'/author/';}
  $obj->response->redirect($authorurl.$obj->user->uid);
 }else{
 $obj->response->redirect($obj->options->adminUrl);
 }
}
  
  
public static function fabu($con,$obj) {
  /*插件用户设置是否勾选*/    
  if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('Rdog')->tuozhan) && in_array('contributor-nb',  Typecho_Widget::widget('Widget_Options')->plugin('Rdog')->tuozhan)){
 /*获取插件设置的分类id*/      
//$tcat = Typecho_Widget::widget('Widget_Options')->plugin('Rdog')->tcat;
/*求插件设置的分类id数据与用户勾选的分类数据交集*/
//$result=array_intersect($tcat,$con['category']);   && count($result)==0
  /*如果用户是贡献者临时给予编辑权限，并且非特例分类*/
  if($obj->author->group=='contributor'||$obj->user->group=='contributor'){
  $obj->user->group='editor';
  }}
  return $con;
}
  
  
 public static function fabuwan($con,$obj) {
           /** 跳转验证后地址 */
        if($obj->request->referer=='return'){
          exit;
        }
        elseif (NULL != $obj->request->referer) {
            /** 发送ping */
            $trackback = array_unique(preg_split("/(\r|\n|\r\n)/", trim($obj->request->trackback)));
            $obj->widget('Widget_Service')->sendPing($obj->cid, $trackback);
            /** 设置提示信息 */
            $obj->widget('Widget_Notice')->set('post' == $obj->type ?
            _t('文章 "<a href="%s">%s</a>" 已经发布', $obj->permalink, $obj->title) :
            _t('文章 "%s" 等待审核', $obj->title), 'success');
            /** 设置高亮 */
            $obj->widget('Widget_Notice')->highlight($obj->theId);
            /** 获取页面偏移 */
            $pageQuery = $obj->getPageOffsetQuery($obj->cid);
            /** 页面跳转 */
            $obj->response->redirect($obj->request->referer);
             
        } else{
            return $con;
        }
 }
 public static function footerjs(){
   if (!empty(Typecho_Widget::widget('Widget_Options')->plugin('Rdog')->tuozhan) && in_array('register-nb',  Typecho_Widget::widget('Widget_Options')->plugin('Rdog')->tuozhan)){
?>
<script>
var rdoghtml='<p><label for="password" class="sr-only">密码</label><input type="password"  id="password" name="password" placeholder="输入密码" class="text-l w-100" autocomplete="off" required></p><p><label for="confirm" class="sr-only">确认密码</label><input type="password"  id="confirm" name="confirm" placeholder="再次输入密码" class="text-l w-100" autocomplete="off" required></p>';
$("#mail").parent().after(rdoghtml);
</script>
<?php
   }
 }
}
