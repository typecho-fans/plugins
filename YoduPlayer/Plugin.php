<?php
/**
 * 一款清爽的BGM播放器,需要您的主题支持pjax或者instantclick才能保证页面切换依旧播放
 * 
 * @package YoduPlayer
 * @author Jrotty
 * @version 2.4.6
 * @link https://github.com/jrotty/YoduPlayer
 */
class YoduPlayer_Plugin implements Typecho_Plugin_Interface
{ 
 public static function activate()
	{
    Typecho_Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
    Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'footer');
    Helper::addRoute("yoduapi","/yoduapi","YoduPlayer_Action",'action');
    }
	/* 禁用插件方法 */
	public static function deactivate(){
     Helper::removeRoute("yoduapi");
	}
    public static function config(Typecho_Widget_Helper_Form $form){
 $random = new Typecho_Widget_Helper_Form_Element_Radio(
            'random', array('0' => '不随机播放', '1' => '随机播放'), 0, '随机播放设置',
            '随机播放顾名思义，就是页面打开后随机选择列表一首音乐播放');
        $form->addInput($random);

$d=array();$n=0;
while ($n<=200) {$d[$n] = $n.'px';$n=$n+5;}

    $set1 = new Typecho_Widget_Helper_Form_Element_Select('top', $d, '65', _t('距离顶部间距'), _t('播放器按钮显示在网页的右上角，这里的设置就是播放器组件距离顶部的间距，默认为65px'));
    $form->addInput($set1);

$sxj = new Typecho_Widget_Helper_Form_Element_Radio(
        'sxj', array('0'=> '隐藏', '1'=> '不隐藏'), 0, '手机端是/否隐藏',
            '');
        $form->addInput($sxj);
        $musicList = new Typecho_Widget_Helper_Form_Element_Textarea('musicList', NULL, NULL,_t('歌曲列表'), _t('格式: {title:"xxx", artist:"xxx", mp3:"http:xxxx",cover:"图片地址",} ，每个歌曲之间用英文,隔开。请保证歌曲列表里至少有一首歌！')); 
        $musicList->addRule('maxLength', _t('歌曲太多建议减少歌曲数量'), 60000);
        $form->addInput($musicList);
      
            $sok = new Typecho_Widget_Helper_Form_Element_Textarea('sok', NULL, 
'',_t('自定义css'), _t('直接在这里输入css即可对播放器样式进行修改'));
        $form->addInput($sok);
        
         $getype = new Typecho_Widget_Helper_Form_Element_Radio(
            'getype', array('netease' => '网易云音乐(默认)', 'tencent' => 'QQ音乐'), 'netease', '歌曲源',
            '选择好后请在下方填写对应平台的歌单id即可');
        $form->addInput($getype);
        
        $gedan = new Typecho_Widget_Helper_Form_Element_Text('gedan', NULL, 
'',_t('请输入歌单id'), _t('填写该项后，播放器将使用这里的歌曲忽略上方的歌曲列表设置'));
        $form->addInput($gedan);
        
        $t = new Typecho_Widget_Helper_Form_Element_Text(
            'auth',
            null,
            Typecho_Common::randString(32),
            _t('* 接口保护'),
            _t('加盐保护 API 接口不被滥用，自动生成无需设置。')
        );
        $form->addInput($t);
    }
    
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    public static function header(){
        $cssUrl = Helper::options()->pluginUrl . '/YoduPlayer/css/player.css?239';
        echo '<link rel="stylesheet" href="' . $cssUrl . '">';
        $css="";
if(Helper::options()->Plugin('YoduPlayer')->top){
		$css.='#bgmplayer{top: '.Helper::options()->Plugin('YoduPlayer')->top.'px;}';  
}
if(Helper::options()->Plugin('YoduPlayer')->sxj=='0'){	
		$css.='@media only screen and (max-width:767px){#bgmplayer{display:none}}';
}
    echo '<style>'.$css.Helper::options()->plugin('YoduPlayer')->sok.'</style>';
    }

    public static function footer(){
        $options = Helper::options()->plugin('YoduPlayer'); 
if(empty($options->musicList)){
    $gqlb='{title:"未设置歌曲",artist:"",mp3:"'.Helper::options()->pluginUrl . '/YoduPlayer/images/huaq.mp3",cover:"'.Helper::options()->pluginUrl . '/YoduPlayer/images/moren.jpg",},';}else{$gqlb=$options->musicList;}
		echo '
<div id="bgmplayer" class="bgmplayer">
<span class="bgmbuttom"  onClick="qiehuan();" >
<svg viewBox="0 0 20 20" fill="currentColor" id="music-note" class="ydicon"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"></path></svg>
</span>
<div id="bgmpanel">
<div class="bgmfm" onclick="playbtu();"><img id="ydfm" class="Rotation paused" src="" onerror=\'javascript:this.src="'.Helper::options()->pluginUrl . '/YoduPlayer/images/0014.jpg";this.onerror=null;\'></div>
<div class="bgmtitle"><span id="ydtitle"></span></div>
<div class="bgmtime"><span id="ytime">0:00</span></div>
<div class="bgmbtn">
<span onClick="previous();"><svg viewBox="0 0 20 20" fill="currentColor" class="ydicon"><path d="M8.445 14.832A1 1 0 0010 14v-2.798l5.445 3.63A1 1 0 0017 14V6a1 1 0 00-1.555-.832L10 8.798V6a1 1 0 00-1.555-.832l-6 4a1 1 0 000 1.664l6 4z"></path></svg></span>
<span onclick="playbtu();" id="ydmc"></span>
<span onclick="next();"><svg viewBox="0 0 20 20" fill="currentColor" class="ydicon"><path d="M4.555 5.168A1 1 0 003 6v8a1 1 0 001.555.832L10 11.202V14a1 1 0 001.555.832l6-4a1 1 0 000-1.664l-6-4A1 1 0 0010 6v2.798l-5.445-3.63z"></path></svg></span>
<span onclick="liebiao();"><svg viewBox="0 0 20 20" fill="currentColor" class="ydicon"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg></span>
</div>
</div><div id="jindu"></div>
		<ul id="playlist" class="yhidden"></ul></div>
             ';
if(empty($options->gedan)){
        echo '<script data-no-instant>
var yaudio = new Audio();
yaudio.controls = true;
yaudio.loop = false;
yaudio.volume = 0.68;
var musicArr=[
'.$gqlb.'
              ];';
	      if (Helper::options()->Plugin('YoduPlayer')->random == '1') {echo 'var a=parseInt(Math.random()*musicArr.length);'. "\n";}else{
echo 'var a=0;'. "\n";}
echo 'var sj=musicArr[a];
yaudio.src=sj.mp3;
yaudio.ti=sj.title;
yaudio.art=sj.artist;
yaudio.fm=sj.cover;
var musicApi=[];';
echo '</script>';
}else{
$rewrite='';if(Helper::options()->rewrite==0){$rewrite='index.php/';}
$apiurl=Helper::options()->rootUrl.'/'.$rewrite.'yoduapi';
    ?>
  <script data-no-instant>
var yaudio = new Audio();
yaudio.controls = true;
yaudio.loop = false;
yaudio.volume = 0.68; 
var sj="";var a=0;
var musicArr=[];
var musicApi=[
    {api:"<?php echo $apiurl;?>",type:"<?php echo $options->getype; ?>",id:"<?php echo $options->gedan; ?>",sj:"<?php echo $options->random; ?>",auth:"<?php echo $options->auth; ?>"},
    ];
</script>   
    <?php
}

        echo '<script  src="'.Helper::options()->pluginUrl . '/YoduPlayer/js/player.js?246" data-no-instant></script>' . "\n";
        echo '<script  src="'.Helper::options()->pluginUrl . '/YoduPlayer/js/prpr.js?246"></script>' . "\n";        
    }

}
