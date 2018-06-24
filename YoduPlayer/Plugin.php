<?php
/**
 * 一款清爽的BGM播放器,需要您的主题支持pjax或者instantclick才能保证页面切换依旧播放
 * 
 * @package YoduPlayer
 * @author Jrotty
 * @version 2.2.2
 * @link http://qqdie.com/archives/typecho-yoduplayer.html
 */
class YoduPlayer_Plugin implements Typecho_Plugin_Interface
{ 
 public static function activate()
	{
        Typecho_Plugin::factory('Widget_Archive')->header = array('YoduPlayer_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('YoduPlayer_Plugin', 'footer');
    }
	/* 禁用插件方法 */
	public static function deactivate(){}
    public static function config(Typecho_Widget_Helper_Form $form){
       if(strcasecmp(Helper::options()->theme,'yodu')==0){
           echo '检测到您使用的是<b>'.Helper::options()->theme.'</b>模板，已为您启动定制策略！';
       }
 $random = new Typecho_Widget_Helper_Form_Element_Radio(
            'random', array('0' => '不随机播放', '1' => '随机播放'), 0, '随机播放设置',
            '随机播放顾名思义，就是页面打开后随机选择列表一首音乐播放');
        $form->addInput($random);

       $bof = new Typecho_Widget_Helper_Form_Element_Radio(
        'bof', array('0'=> '不自动播放', '1'=> '自动播放'), 0, '播放设置',
            '自动播放顾名思义，就是页面打开后音乐就会自动播放');
        $form->addInput($bof);

$sxj = new Typecho_Widget_Helper_Form_Element_Radio(
        'sxj', array('0'=> '隐藏', '1'=> '不隐藏'), 0, '手机端是/否隐藏',
            '');
        $form->addInput($sxj);
        $musicList = new Typecho_Widget_Helper_Form_Element_Textarea('musicList', NULL, 
'{title:"風の道",artist:"conte-de-fees.com",mp3:"'.Helper::options()->pluginUrl . '/YoduPlayer/images/contedefees_0014.mp3",cover:"'.Helper::options()->pluginUrl . '/YoduPlayer/images/0014.jpg",},
',_t('歌曲列表'), _t('格式: {title:"xxx", artist:"xxx", mp3:"http:xxxx",cover:"图片地址",} ，每个歌曲之间用英文,隔开。请保证歌曲列表里至少有一首歌！'));
        $form->addInput($musicList);

       $styleso = array_map('basename', glob(dirname(__FILE__) . '/skin/*.css'));
 $styleso = array_combine($styleso, $styleso);
$st = array('mr'=>'不使用皮肤');
$sk = array_merge($st,$styleso);
 $skin = new Typecho_Widget_Helper_Form_Element_Select('skin', $sk, 'mr',
    _t('播放器皮肤'), _t('可自写皮肤扔进skin文件夹即可'));
    $form->addInput($skin->multiMode());
      
      
            $sok = new Typecho_Widget_Helper_Form_Element_Textarea('sok', NULL, 
'',_t('自定义css'), _t('直接在这里输入css即可对播放器样式进行修改'));
        $form->addInput($sok);
    }
    
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    public static function header(){
 if(strcasecmp(Helper::options()->theme,'yodu')==0){
        $cssUrl = Helper::options()->pluginUrl . '/YoduPlayer/yodu/player.css?v=2.2.2';
    }else{
        $cssUrl = Helper::options()->pluginUrl . '/YoduPlayer/css/player.css?v=2.2.2';
}
        echo '<link rel="stylesheet" href="' . $cssUrl . '">';
if(Helper::options()->Plugin('YoduPlayer')->sxj=='0'){	
			echo '<style>@media only screen and (max-width:767px){#bgmplayer{display:none}}</style>'. "\n";
}

if(Helper::options()->plugin('YoduPlayer')->skin && 'mr'!=Helper::options()->plugin('YoduPlayer')->skin){
  echo'<link rel="stylesheet" href="'.Helper::options()->pluginUrl .'/YoduPlayer/skin/'.Helper::options()->plugin('YoduPlayer')->skin.'?v=2.2.2" data-instant-track>';
}

      
      
    echo '<style>'.Helper::options()->plugin('YoduPlayer')->sok.'</style>';
    }

    public static function footer(){
        $options = Helper::options()->plugin('YoduPlayer'); 
if($options->musicList==""){$gqlb='{title:"風の道",artist:"conte-de-fees.com",mp3:"'.Helper::options()->pluginUrl . '/YoduPlayer/images/contedefees_0014.mp3",cover:"'.Helper::options()->pluginUrl . '/YoduPlayer/images/0014.jpg",},';}else{$gqlb=$options->musicList;}
		echo '
<div id="bgmplayer" class="bgmplayer">
<span class="bgmbuttom"  onClick="qiehuan();" >
<i id="ydmusic" class="icon-music"></i>
</span>
<div id="bgmpanel">
<div class="bgmfm"><img id="ydfm" src="" onerror=\'javascript:this.src="'.Helper::options()->pluginUrl . '/YoduPlayer/images/0014.jpg";this.onerror=null;\'></div>
<div class="bgmtitle"><span id="ydtitle"></span></div>
<div class="bgmtime"><span id="ytime">0:00</span></div>
<div class="bgmbtn">
<span onClick="previous();"><i class="icon-zuo"></i></span>
<span onclick="playbtu();"><i id="ydmc"></i></span>
<span onclick="next();"><i class="icon-you"></i></span>
<span onclick="liebiao();"><i class="icon-list"></i></span>
</div>
</div><div id="jindu"></div>
		<ol id="playlist"></ol></div>
             ';

        echo '<script data-no-instant>
var yaudio = new Audio();
yaudio.controls = true;
yaudio.loop = false;
yaudio.volume = 0.18;
var musicArr=[
'.$gqlb.'
              ];';
	      if (Helper::options()->Plugin('YoduPlayer')->random == '1') {echo 'var a=parseInt(Math.random()*musicArr.length);'. "\n";}else{
echo 'var a=0;'. "\n";}
echo 'var sj=musicArr[a];
yaudio.src=sj.mp3;
yaudio.ti=sj.title;
yaudio.art=sj.artist;
yaudio.fm=sj.cover;';
if(Helper::options()->Plugin('YoduPlayer')->bof=='1'){	
			echo 'yaudio.play();'. "\n";
}
echo '</script>';

        echo '<script  src="'.Helper::options()->pluginUrl . '/YoduPlayer/js/player.js?v=2.2.2" data-no-instant></script>' . "\n";
        echo '<script  src="'.Helper::options()->pluginUrl . '/YoduPlayer/js/prpr.js?v=2.2.2"></script>' . "\n";        
    }

}
