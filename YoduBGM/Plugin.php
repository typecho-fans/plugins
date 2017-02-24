<?php
/**
 * 一款简洁BGM播放器,需要您的主题支持pjax或者instantclick才能保证页面切换依旧播放
 * 
 * @package YoduBGM
 * @author Jrotty
 * @version 0.6.0
 * @link http://qqdie.com/archives/typecho-yodubgm.html
 */
class YoduBGM_Plugin implements Typecho_Plugin_Interface
{ 
 public static function activate()
	{
        Typecho_Plugin::factory('Widget_Archive')->header = array('YoduBGM_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('YoduBGM_Plugin', 'footer');
    }
	/* 禁用插件方法 */
	public static function deactivate(){}
    public static function config(Typecho_Widget_Helper_Form $form){
       $bof = new Typecho_Widget_Helper_Form_Element_Radio(
        'bof', array('0'=> '不自动播放', '1'=> '自动播放'), 0, '播放设置',
            '自动播放顾名思义，就是页面打开后音乐就会自动播放');
        $form->addInput($bof);

$sxj = new Typecho_Widget_Helper_Form_Element_Radio(
        'sxj', array('0'=> '隐藏', '1'=> '不隐藏'), 0, '手机端是/否隐藏',
            '');
        $form->addInput($sxj);
        $musicList = new Typecho_Widget_Helper_Form_Element_Textarea('musicList', NULL, 
'{title:"一半一半",artist:"洛天依",mp3:"//p2.music.126.net/LsZAfVP2SgdqoemJpYmOnw==/5670181464502469.mp3",},{title:"I Love U",artist:"洛天依",mp3:"//p2.music.126.net/IbwygfnvC-f7hEuU_7WKbA==/5985741301956163.mp3",},
',_t('歌曲列表'), _t('格式: {title:"xxx", artist:"xxx", mp3:"http:xxxx"} ，每个歌曲之间用英文,隔开。请保证歌曲列表里至少有一首歌！<br><br><div style="background-color:#56A5CE;padding:5px 8px;max-width:250px;border-radius: 2px;"><a href="'.Helper::options()->pluginUrl.'/YoduBGM/IDExplain.php" target="_blank" style="font-size:14px;color:#fff;outline:none;text-decoration:none;">网易云音乐id解析(主机需支持curl扩展)</a>
        	</div>请自行去网易云音乐网页版获取音乐id(具体在每个音乐项目的网址最后会有个id)。<b>将解析出的音乐链接复制到上面歌曲列表里(注意检查与现有歌曲是否用英文,隔开)</b>'));
        $form->addInput($musicList);
    }
    
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    public static function header(){
        $cssUrl = Helper::options()->pluginUrl . '/YoduBGM/css/player.css';
        echo '<link rel="stylesheet" href="' . $cssUrl . '">';
if(Typecho_Widget::widget('Widget_Options')->Plugin('YoduBGM')->sxj=='0'){	
			echo '<style>@media only screen and (max-width:766px){.ymusic{display:none}}</style>'. "\n";
}
    }

    public static function footer(){
        $options = Typecho_Widget::widget('Widget_Options')->plugin('YoduBGM'); 

		echo '
<bgm>			
<a class="ymusic" onclick="playbtu();" target="_blank"><i id="ydmc"></i></a><a class="ymusic" onclick="next();" id="ydnext" target="_blank"><i class="iconfont icon-you"></i></a>
</bgm>
             ';
       
        echo '<script data-no-instant>
var yaudio = new Audio();
yaudio.controls = true;
var musicArr=[
              '.$options->musicList.'
              ];
/*首次随机播放*/
var a=parseInt(Math.random()*musicArr.length);
var sj=musicArr[a];
yaudio.src=sj.mp3;
yaudio.ti=sj.title;
yaudio.art=sj.artist;
 ';
if(Typecho_Widget::widget('Widget_Options')->Plugin('YoduBGM')->bof=='1'){	
			echo 'yaudio.play();</script>'. "\n";
		}else{	echo '</script>'. "\n";
}
        echo '<script  src="'.Helper::options()->pluginUrl . '/YoduBGM/js/player.js" data-no-instant></script><script  src="'.Helper::options()->pluginUrl . '/YoduBGM/js/prbug.js"></script>' . "\n";        
    }

}