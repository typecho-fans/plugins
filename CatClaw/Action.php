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
@($pass = $_GET['pass']);
$setting=Helper::options()->Plugin('CatClaw');

if(!$_GET['pg']||!$_GET['day']||!$_GET['pass']){
    echo '存在参数未填写,您需要确保网址上带有pg，day，pass<br>
    参数：<br>
    pg = 页数<br>
    day = 采集天数，可输入1,7,max（输入1就是采集最近24小时内更新的资源，7就是一周，max就是采集全部）<br>
    pass = 插件后台设置的密码<br>';
    exit;
}
elseif($pass!=$setting->pass||empty($pass)){
		echo "访问密码错误";
		exit;
	}
	
    if ($day == '1') {
        $h = '24';} elseif ($day == "7") {
        $h = '168';} elseif ($day == "max") {
        $h='';}


echo '<div style="
    max-height: 200px;
    overflow-y: auto;
    background: #000;
    color:#fff;
    padding: 12px;
">';

$detailurl=$setting->detailurl;
$aid=$setting->aid;
$bid=$setting->bid;
$listurl=$setting->listurl.'&h='.$h.'&t='.$aid.'&pg='.$pg;

$list=json_decode($this->MCurl($listurl), true);
//echo '<pre>';
//print_r($list);
//echo '</pre>';
for($i=0;$i<count($list['list']);$i++){
    
   $ids=$list['list'][$i]['vod_id'];
   $detail=json_decode($this->MCurl($detailurl.'&ids='.$ids), true);
 
$m3u8=explode('$$$',$detail['list'][0]['vod_play_url']);
if(strpos($m3u8[0],'.m3u8') != false){
$m3u8=$m3u8[0];
}else{
$m3u8=$m3u8[1];
}
$m3u8=str_ireplace("#", "\r\n", $m3u8);
 
$user=$setting->username;
$password=$setting->password;
$title=$detail['list'][0]['vod_name'];
$text=$detail['list'][0]['vod_blurb'];
$cate=$bid;
$tags=$detail['list'][0]['vod_class'];

$fn[0]='niandai';
$ft[0]='str';
$fv[0]=$detail['list'][0]['vod_year'];//年代

$zt=$detail['list'][0]['vod_remarks'];
$zhuangtai=0;if(strpos($zt,'更') != false||strpos($zt,'新') != false||strpos($zt,'至') != false)
         {$zhuangtai=1;}//含有关键字时状态改为连载状态
$fn[1]='zhuangtai';
$ft[1]='str';
$fv[1]=$zhuangtai;//状态

$fn[2]='thumb';
$ft[2]='str';
$fv[2]=$detail['list'][0]['vod_pic'];

$fn[3]='mp4';
$ft[3]='str';
$fv[3]=$m3u8;

$fn[4]='name';
$ft[4]='str';
$fv[4]=$detail['list'][0]['vod_sub'];


if(Helper::options()->Plugin('CatClaw')->autoup){
$fn[5]='autoup';
$ft[5]='str';
$fv[5]=Helper::options()->Plugin('CatClaw')->autoup.'$'.$aid;
}


$this->post_article($user,$password,$title,$text,$fn,$ft,$fv,$cate,$tags);
}
$zpg= $list['pagecount'];
        if ($pg < $zpg) {
            $pg = $pg + 1;
            $urll ='?pg='.$pg.'&day='.$day.'&pass='.$pass;
           echo '</div><p class="description"><meta http-equiv="refresh" content="3;URL='.$urll.'"><a href="'.$urll.'">3秒后跳转下一页~</a> 剩余：' . ($pg - 1) . '/' . $zpg . '页</p>';
        }else{
    echo '</div><p class="description">该类别下的内容已全部采集完毕！</p>';
}

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


if($db->fetchRow($db->select()->from ('table.fields')->where ('cid = ?',$cid)->where ('name = ?','zhuangtai'))['str_value']!=0||Helper::options()->Plugin('CatClaw')->tiao==2){

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

        //填充文章的相关字段信息。
       $contents=
            array(
                'title'=>$title,
                'text'=>$text,
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
            );

        $field['fieldNames']=$fn;
        $field['fieldTypes']=$ft;
        $field['fieldValues']=$fv;
        $this->request->markdown=$contents['markdown'];
        //设置时区，否则文章的发布时间会查8H
        date_default_timezone_set('PRC');
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
            $this->applyFields($this->getFields($field), $realId);
        }     
   }    
          

            echo '《'.$title.'》发布成功<br>';
            return 'ok';
     }
        
    }
    
    private function  getFields($field)
    {
        $fields = array();
        $fieldNames = $field['fieldNames'];

        if (!empty($fieldNames)) {
            $data = array(
                'fieldNames'    =>  $field['fieldNames'],
                'fieldTypes'    =>  $field['fieldTypes'],
                'fieldValues'   =>  $field['fieldValues']
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

    private function MCurl($url){
$headers[] = "User-Agent: Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)";
$curl = curl_init(); // 启动一个CURL会话
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
$tmpInfo = curl_exec($curl);
//关闭URL请求
curl_close($curl);
return $tmpInfo;
    }
    
    
}
