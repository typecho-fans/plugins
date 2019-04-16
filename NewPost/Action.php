<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class NewPost_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }

    public function action() {
      $options = Typecho_Widget::widget('Widget_Options')->plugin('NewPost');
        $user=$options->username;
        $password=$options->password;
        if (!$this->user->hasLogin()) {
            if (!$this->user->login($user, $password, true)) { //使用特定的账号登陆
                die('登录失败');
            }
        }

        $request = Typecho_Request::getInstance();
        $title = $request->get('title');
        $text = $request->get('text');
        $key= $request->get('key');
        $signkey = $options->sign;
        $mid = $options->mid;
      	if($key!=$signkey){
        die("验证失败");
        }
        //填充文章的相关字段信息。
        $request->setParams(
            array(
                'title'=>$title,
                'text'=>$text,
                'fieldNames'=>array(),
                'fieldTypes'=>array(),
                'fieldValues'=>array(),
                'cid'=>'',
                'do'=>'publish',
                'markdown'=>'1',
                'date'=>'',
                'category'=>array($mid),
                'tags'=>'',
                'visibility'=>'publish',
                'password'=>'',
                'allowComment'=>'1',
                'allowPing'=>'1',
                'allowFeed'=>'1',
                'trackback'=>'',
            )
        );

        //设置token，绕过安全限制
        $security = $this->widget('Widget_Security');
        $request->setParam('_', $security->getToken($this->request->getReferer()));
        //设置时区，否则文章的发布时间会查8H
        date_default_timezone_set('PRC');

        //执行添加文章操作
        $widgetName = 'Widget_Contents_Post_Edit';
        $reflectionWidget = new ReflectionClass($widgetName);
        if ($reflectionWidget->implementsInterface('Widget_Interface_Do')) {
            $this->widget($widgetName)->action();
            echo 'Successful';
            return;
        }
    }
}
