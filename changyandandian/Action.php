<?php



class changyandandian_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
 
    
   public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }

    public function execute() 
    {
    }

    public function action()
    {
        

        if($this->user->hasLogin()){
            $ret=array(  
            "is_login"=>1, //已登录，返回登录的用户信息
            "user"=>array(
            "user_id"=>$this->user->uid,
            "nickname"=>$this->user->screenName,
            "img_url"=>"https://gravatar.helingqi.com/wavatar/".md5($this->user->mail)."?d=mm",
            "profile_url"=>$this->user->url,
            "sign"=>"zezechupin", //注意这里的sign签名验证已弃用，任意赋值即可
            'reload_page'=>1,
            ));
        setcookie("cyCookie",'1');//畅言已通过站点账号自动登录，使用cookie做个标记，用于判断进行同步登出

        }else{
            $ret=array("is_login"=>0);//未登录
        }
        
        echo $_GET['callback'].'('.json_encode($ret).')';  
        
        }
        
    public function logout(){
        
if(!$this->user->hasLogin()){
    $return=array(
    'code'=>1,
    'reload_page'=>1,
    );
}else{
    $this->user->logout();
    $return=array(
    'code'=>1,
    'reload_page'=>1,
    );
}
 echo $_GET['callback'].'('.json_encode($return).')';  	 
        
        
        
    }   

    
    
}
