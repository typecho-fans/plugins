<?php

class AutoBackup_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
 
    
   public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }

    public function execute() 
    {
    }

    public function action()
    {
        
		$configs = Helper::options()->plugin('AutoBackup');
		if($configs->cronpass){
        if($this->request->taken==$configs->cronpass){
      	require_once 'send.php';
        $Send = new Send();
        return $Send->sender(1,1,1);  
    }else{
        echo '秘钥错误';
    }}
    else{
        echo '定时任务秘钥未设置';
    }
        
    }
    
    
}
?>