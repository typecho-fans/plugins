<?php



class CatClaw_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
 
    
   public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }

    public function execute() 
    {
    }

    public function action()
    {
@($pg = $_GET['pg']);
@($day = $_GET['day']);
@($type = $_GET['type']);
@($id = $_GET['id']);
@($pass = $_GET['pass']);


if ($type == "cron") {
    
   if($pass!=Helper::options()->Plugin('CatClaw')->pass||empty($pass)){
		echo "访问密码错误";
		exit;
	}
    
    
    if ($day == '1') {
        $url = Helper::options()->Plugin('CatClaw')->url . "?ac=videolist&h=24&t=" . $id . "&pg=" . $pg;
    } elseif ($day == "7") {
        $url = Helper::options()->Plugin('CatClaw')->url . "?ac=videolist&h=168&t=" . $id . "&pg=" . $pg;
    } elseif ($day == "max") {
        $url = Helper::options()->Plugin('CatClaw')->url . "?ac=videolist&h=&t=" . $id . "&pg=" . $pg;
    }
    $listcontent = $this->MloocCurl($url);
    $ruleMatchDetailInList = "~<list page=\"[^>]*\" pagecount=\"(.*?)\" pagesize=\"[^>]*\" recordcount=\"[^>]*\">~";
    #正则表达式
    preg_match($ruleMatchDetailInList, $listcontent, $cjinfo);
    $zpg = $cjinfo[1];
if($zpg>=80){
    	echo '更新数量过大,请使用手动添加模式';
    	exit();
    }
    for ($i = 0; $i < $zpg; $i++) {
    	$tt = $i + 1;
        $this->caiji($tt ,$day, $id,$pass,$type);
    }
}
if ($type == "add") {
    ?>
    
<html>
<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="renderer" content="webkit">
        <meta name="viewport" content="width=device-width, initial-scale=1">
<title>采集操作中心</title>
<style>
html,body{
    margin: 0;
    padding: 0;
    background: #fff;
}
body{
        font-family: "SF Pro SC", "SF Pro Text", "SF Pro Icons", PingFang SC, Lantinghei SC, Microsoft Yahei, Hiragino Sans GB, Microsoft Sans Serif, WenQuanYi Micro Hei, sans-serif;
    -moz-osx-font-smoothing: grayscale;
    -webkit-font-smoothing: antialiased;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #000;    width: 100%;
    min-height: 100vh;
}
div {background-color: #f4f6fb;
    margin: auto auto;
    max-width: 800px;
    padding: 20px;
} 
</style>
</head>

<body>
<div><?php 

if(!$_GET['pg']||!$_GET['day']||!$_GET['type']||!$_GET['pass']){
    echo '存在参数未填写,您需要确保网址上带有pg，type，day，id，pass<br>
    参数：<br>
    pg = 页数<br>
    type = 操作类型（add和cron，add是手动采集，cron是用于服务器定时任务的）<br>
    day = 采集天数，可输入1,7,max（输入1就是采集最近24小时内更新的资源，7就是一周，max就是采集全部）<br>
    id = 采集站上面的分类ID<br>
    pass = 插件后台设置的密码<br>';
    exit;
}
elseif($pass!=Helper::options()->Plugin('CatClaw')->pass||empty($pass)){
		echo "访问密码错误";
		exit;
	}

$this->caiji($pg,$day,$id,$pass,$type); ?></div>

</body>

</html>
<?php
}

    }


private function trimall($str)//删除空格
{
    $oldchar=array(" ","　","\t","\n","\r");
$newchar=array("","","","","");
    return str_replace($oldchar,$newchar,$str);
}

private function caiji($pg,$day,$id,$pass,$gettype){
    $cate=0;//分类参数
    if ($day == '1') {
        $url = Helper::options()->Plugin('CatClaw')->url . "?ac=videolist&h=24&t=" . $id . "&pg=" . $pg;
    } elseif ($day == "7") {
        $url = Helper::options()->Plugin('CatClaw')->url . "?ac=videolist&h=168&t=" . $id . "&pg=" . $pg;
    } elseif ($day == "max") {
        $url = Helper::options()->Plugin('CatClaw')->url . "?ac=videolist&h=&t=" . $id . "&pg=" . $pg;
    } 
 echo $url.'<br />';
    $listcontentx = $this->MloocCurl($url);
    
    $listcontent = str_replace("'","",$listcontentx);
    
    $ruleMatchDetailInList = "~<list page=\"[^>]*\" pagecount=\"(.*?)\" pagesize=\"[^>]*\" recordcount=\"(.*?)\">~";
    #正则表达式
    preg_match($ruleMatchDetailInList, $listcontent, $cjinfo);
    
    $listcontent = explode("</video>",$listcontent);
    
    $xunhuan = count($listcontent);
     $zpg = $cjinfo[1];
     
if(strpos($listcontentx,"</video>") == false && $zpg == 0){
    echo '该分类下无内容，上方地址为资源站对应分类的接口，你可以访问查看(查看源码)该分类下接口是否没有内容！';
    exit;
}




    for ($i = 0; $i < $xunhuan; $i++) {
        
        
    $html=$listcontent[$i];
    $ruleMatchDetailInList = "~<last>(.*?)</last>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $datex);
    //视频时间
    $ruleMatchDetailInList = "~<tid>(.*?)<\\/tid>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $tid);
    //视频分类id
    $ruleMatchDetailInList = "~<id>(.*?)<\\/id>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $title_id);
    //视频id
    $ruleMatchDetailInList = "~<type>(.*?)<\\/type>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $category);
    $ruleMatchDetailInList = "~<name>\\<\\!\\[CDATA\\[(.*?)\\]\\]\\>\\<\\/name>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $title);
    //视频名称
    $ruleMatchDetailInList = "~<des>\\<\\!\\[CDATA\\[(.*?)\\]\\]\\>\\<\\/des>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $drama);
    //视频介绍
    $ruleMatchDetailInList = "~<pic>(.*?)<\\/pic>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $pic);
    //视频图片
    $ruleMatchDetailInList = "~<area>(.*?)<\\/area>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $area);
    //视频地区分类
    $ruleMatchDetailInList = "~<dd flag=\"[^>]*\">\\<\\!\\[CDATA\\[(.*?)\\]\\]\\>\\<\\/dd>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $m3u8);
    //视频地址
    $ruleMatchDetailInList = "~<note>\\<\\!\\[CDATA\\[(.*?)\\]\\]\\>\\<\\/note>~";
    #正则表达式
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $note);
    //视频地址
    $ruleMatchDetailInList = "~<year>(.*?)<\\/year>~";
    #年代
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $year);
    //年代
    //视频地址
    $ruleMatchDetailInList = "~<tag>(.*?)<\\/tag>~";
    #标签
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $tag);
    
    $ruleMatchDetailInList = "~<type>(.*?)<\\/type>~";
    #标签
    preg_match_all($ruleMatchDetailInList, $listcontent[$i], $type);
$type=$type[1][0];$area=$area[1][0];
if(strpos($type,'动漫') !== false && strpos($type,'动漫电影') === false){
$d= $this->shuzu(Helper::options()->Plugin('CatClaw')->anime);
if(strpos($area,'中国') !== false||strpos($area,'大陆') !== false||strpos($area,'内地') !== false||strpos($area,'国漫') !== false){
$cate=$d['中国动漫'];
}

elseif(strpos($area,'日本') !== false||strpos($area,'日漫') !== false||strpos($area,'日本国') !== false||strpos($area,'日本岛') !== false){
$cate=$d['日本动漫'];
}

elseif(strpos($area,'美国') !== false||strpos($area,'欧美') !== false||strpos($area,'加拿大') !== false||strpos($area,'法国') !== false||strpos($area,'英国') !== false||strpos($area,'德国') !== false||strpos($area,'俄国') !== false||strpos($area,'俄罗斯') !== false||strpos($area,'欧洲') !== false){
$cate=$d['欧美动漫'];
}
elseif(strpos($area,'台湾') !== false||strpos($area,'香港') !== false||strpos($area,'港台') !== false){
$cate=$d['港台动漫'];
}


elseif(strpos($area,'韩国') !== false){
$cate=$d['韩国动漫'];
}
else{
$cate=$d['其他动漫'];
}
}


elseif(strpos($type,'片') !== false||strpos($type,'动漫电影') !== false){
 $f=$this->shuzu(Helper::options()->Plugin('CatClaw')->film);
$cate=$f[$type]; 
}

elseif(strpos($type,'剧') !== false){
  $t=$this->shuzu(Helper::options()->Plugin('CatClaw')->tv);  
if(strpos($area,'美国') !== false||strpos($area,'欧美') !== false||strpos($area,'加拿大') !== false||strpos($area,'法国') !== false||strpos($area,'英国') !== false||strpos($area,'德国') !== false||strpos($area,'俄国') !== false||strpos($area,'俄罗斯') !== false||strpos($area,'欧洲') !== false){
$cate=$t['欧美剧'];
}
elseif(strpos($area,'中国') !== false||strpos($area,'大陆') !== false||strpos($area,'国产') !== false||strpos($area,'内地') !== false){
$cate=$t['国产剧'];
}
else {
 $area=$area.'剧';
if(empty($t[$area])){$cate=$t['其他剧'];}else{$cate=$t[$area];}
 }
}
elseif(strpos($type,'综艺') !== false){
$z=$this->shuzu(Helper::options()->Plugin('CatClaw')->zy);  
$cate=$z[$type]; 
}





else{
    $cate=0;
}
  
if ($title[1][0] != "" && !empty($cate) && $cate!=0) {
         $name = $title[1][0];//名字
         @($dramas = strip_tags($drama[1][0]));
         @$dramas = $this->trimall($dramas);//简介
         $years = substr($year[1][0], 0, 4);//年份
         $tags = $tag[1][0];if(empty($tags)){$tags='';}//标签
         $pic=$pic[1][0];//封面id
         $vodurl = "";
         $vodurl = str_replace("#", "\r\n", $m3u8[1][0]);//视频地址
         $zhuangtai=0;if(strpos($note[1][0],'更') != false||strpos($note[1][0],'新') != false||strpos($note[1][0],'至') != false)
         {$zhuangtai=1;}//含有关键字时状态改为连载状态

         
         $aid = $title_id[1][0];//资源站资源id


$user=Helper::options()->Plugin('CatClaw')->username;
$password=Helper::options()->Plugin('CatClaw')->password;


$fn[0]='niandai';
$ft[0]='str';
$fv[0]=$years;//年代


$fn[1]='zhuangtai';
$ft[1]='str';
$fv[1]=$zhuangtai;//状态

$fn[2]='thumb';
$ft[2]='str';
$fv[2]=$pic;

$fn[3]='mp4';
$ft[3]='str';
$fv[3]=$vodurl;

if(Helper::options()->Plugin('CatClaw')->autoup){
$fn[4]='autoup';
$ft[4]='str';
$fv[4]=Helper::options()->Plugin('CatClaw')->autoup.'$'.$aid;
}



$cate[0]=$cate;//写入分类


$this->post_article($user,$password,$name,$dramas,$fn,$ft,$fv,$cate,$tags);







     }

}
if($pg==$zpg){
    echo '该类别下的内容已全部采集完毕！';
}
       
        if ($pg < $zpg && $gettype !== "cron") {
            $pg = $pg + 1;
            $urll = '?pg=' . $pg . '&type=add&day=' . $day . '&pass=' . $pass . '&id=' . $id;
           echo '<hr /><meta http-equiv="refresh" content="3;URL='.$urll.'"><a href="'.$urll.'">3秒后跳转下一页~</a> 剩余：' . ($pg - 1) . '/' . $zpg . '页';
        }

}



private function shuzu($f){
$f=explode("
",$f);
$shu=count($f);

for($i=0;$i<$shu;$i++){
$t=explode("：",$f[$i]);
$j=$t[0];
$list[$j]=$t[1];
}
return $list;
}
 

    
private function post_article($user,$password,$title,$text,$fn,$ft,$fv,$cate,$tags)
    {
        

if (!$this->user->login($user, $password, true)) { //使用特定的账号登陆
echo '插件配置中的用户名或密码错误！';exit;
            }
            $uid=$this->user->uid;

$titlex=str_replace("&","&amp;",$title); //解决&符号导致的番剧存在与否的判断bug
	
$db = Typecho_Db::get();
if($db->fetchRow($db->select()->from ('table.contents')->where ('title = ?',$titlex))){
$cid=$db->fetchRow($db->select()->from ('table.contents')->where ('title = ?',$titlex))['cid'];


if($db->fetchRow($db->select()->from ('table.fields')->where ('cid = ?',$cid)->where ('name = ?','zhuangtai'))['str_value']!=0){

$zhuangtai=$fv[1];
$list=$fv[3];
$nowtime=time();
$prefix = $db->getPrefix();
$data_name=$prefix.'fields';//字段表
$data_tname=$prefix.'contents';//文章表
$db->query("UPDATE `{$data_name}` SET `str_value`='{$list}' WHERE `cid`='{$cid}' and name='mp4'");//更新列表
$db->query("UPDATE `{$data_name}` SET `str_value`='{$zhuangtai}' WHERE `cid`='{$cid}' and name='zhuangtai'");//更新状态0完结1连载-1待定
$db->query("UPDATE `{$data_tname}` SET `modified`='{$nowtime}' WHERE `cid`='{$cid}'");//更新时间
  
 
echo '更新连载《'.$title.'》<br>';
}else{
echo '跳过重复项《'.$title.'》<br>';    
}
return 'ok';
     }else{
     
        $request = Typecho_Request::getInstance();

        //填充文章的相关字段信息。
        $request->setParams(
            array(
                'title'=>$title,
                'text'=>$text,
                'fieldNames'=>$fn,
                'fieldTypes'=>$ft,
                'fieldValues'=>$fv,
                'cid'=>'',
                'do'=>'publish',
                'markdown'=>'1',
                'date'=>'',
                'category'=>$cate,
                'tags'=>$tags,
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
        
        $contents = $this->request->from('password', 'allowComment',
            'allowPing', 'allowFeed', 'slug', 'tags', 'text', 'visibility','created');

        $contents['category'] = $this->request->getArray('category');
        $contents['title'] = $this->request->get('title', _t('未命名文档'));
        $contents['type'] = 'post';
        $content['authorId']=$user;
        
        if ($this->request->markdown && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }
        
            $realId=$this->insert($contents);//插入文章
            $widgetName = 'Widget_Contents_Post_Edit';
        $reflectionWidget = new ReflectionClass($widgetName);
   if ($reflectionWidget->implementsInterface('Widget_Interface_Do')) {
       
  if ($realId > 0) {
            /** 插入分类 */
            if (array_key_exists('category', $contents)) {
                $this->widget($widgetName)->setCategories($realId, !empty($contents['category']) && is_array($contents['category']) ?
                $contents['category'] : array($this->options->defaultCategory), false, true);
            }

            /** 插入标签 */
            if (array_key_exists('tags', $contents)) {
                $this->widget($widgetName)->setTags($realId, $contents['tags'], false, true);
            }

            /** 同步附件虽然本插件并不涉及附件 */
           $this->widget($widgetName)->attach($this->cid);
            
            /** 保存自定义字段 */
            $this->applyFields($this->getFields(), $realId);
        }     
   }    
          

            echo '《'.$title.'》发布成功<br>';
            return 'ok';
     }
        
    }
    
    private function  getFields()
    {
        $fields = array();
        $fieldNames = $this->request->getArray('fieldNames');

        if (!empty($fieldNames)) {
            $data = array(
                'fieldNames'    =>  $this->request->getArray('fieldNames'),
                'fieldTypes'    =>  $this->request->getArray('fieldTypes'),
                'fieldValues'   =>  $this->request->getArray('fieldValues')
            );
            foreach ($data['fieldNames'] as $key => $val) {
                if (empty($val)) {
                    continue;
                }

                $fields[$val] = array($data['fieldTypes'][$key], $data['fieldValues'][$key]);
            }
        }

        $customFields = $this->request->getArray('fields');
        if (!empty($customFields)) {
            $fields = array_merge($fields, $customFields);
        }

        return $fields;
    }

    private function MloocCurl($url){

        $UserAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';#设置UserAgent

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);

        #关闭SSL

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        #返回数据不直接显示

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }
    
    
}
