<?php 
/**
 * 在网站底部插入APlayer吸底播放器<br/>
 * 开源项目：<a href="https://github.com/DIYgod/APlayer" target="_blank">APlayer</a> | 歌单获取API：<a href="https://api.ohmyga.cn/page/netease" target="_blank">Ohmyga</a><br/>
 * 保存完配置记得点 - > <a href="../usr/plugins/APlayerAtBottom/Update.php" target="_blank">点我更新歌单内容</a>
 * @package APlayerAtBottom
 * @author 小太
 * @version 1.0.6
 * @link https://713.moe/
 */
class APlayerAtBottom_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Typecho_Plugin::factory('Widget_Archive')->footer = array('APlayerAtBottom_Plugin', 'footer');
        Typecho_Plugin::factory('Widget_Archive')->header = array('APlayerAtBottom_Plugin', 'header');
      	$site = array();
      	$site['siteurl'] = Helper::options()->siteUrl;
      	$sitedata = json_encode($site);
      	file_put_contents('./usr/plugins/APlayerAtBottom/siteurl.json',$sitedata);
    	return '启用成功ヾ(≧▽≦*)o，请设置您您的歌单ID~';
    }
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
    	return '禁用成功！插件已经停用啦（；´д｀）ゞ';
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
      	echo ('<style>.buttons a{background:#467b96; color:#fff; border-radius:4px; padding:.5em .75em; display:inline-block}</style>');
      	$version = '1.0.6'; //定义此插件版本
      	$api_get = file_get_contents('https://api.713.moe/version/aab_gh.json'); //获取最新版本内容（GithubAPI部分地区无法访问就没用了）
      	$arr = json_decode($api_get, true); //json解析
      	$new_version = $arr['tag_name']; //获取版本号
      	$new_title = $arr['name'];
      	
      	//判断版本是否过时
      	if($version < $new_version) {
        	$version_tips = '该插件有<font color="#e84118">新版本</font> => '.$new_title.' => <a href="https://github.com/SatoSouta/APlayerAtBottom/releases/tag/'.$new_version.'" target="_blank">立即下载</a>';
          	$new_version_out = '<font color="#e84118">'.$new_version.'</font>';
        }else{
          	if($version > $new_version){
            	$version_tips = '你怎么回事，怎么还比最新版本高了？';
          		$new_version_out = '<font color="#e84118">'.$new_version.'</font>';
            }else{
        		$version_tips = '您的插件为最新版本，无需更新！';
          		$new_version_out = $new_version;
            }
        }
      	
      	//输出版本信息
        $public_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $public_section->html('<h4>本插件目前版本：'.$version.' | 最新版本：'.$new_version_out.'（'.$version_tips.'）</h4><h4><font color="#e84118">请注意：保存配置后一定要来点击一下下面的按钮（若歌单有新增歌曲也要来点一下哦~）一定要点！！</font></h4><p class="buttons"><a href="../usr/plugins/APlayerAtBottom/Update.php" target="_blank">点我更新歌单内容</a></p>');
        $form->addItem($public_section);
      	
      	//设置内容
      	$aplayer = new Typecho_Widget_Helper_Form_Element_Radio('aplayer', array ('0' => '有', '1' => '无'), '1','您是否有安装APlayer相关插件或CSS/JS', '这将会决定本插件是否输出设定CSS/JS');
    	$form->addInput($aplayer);
    	$id = new Typecho_Widget_Helper_Form_Element_Text('id', null, '4907097519', _t('歌单id'), '这里填写你的 <b>网易云音乐</b> 歌单id（目前仅支持网易云音乐）<br/>PS：更换后请刷新浏览器缓存！');
        $form->addInput($id);
      	$autoplay = new Typecho_Widget_Helper_Form_Element_Radio('autoplay', array ('0' => '启用', '1' => '禁用'), '1','自动播放', 'PS：部分主题或浏览器可能不支持此项。');
    	$form->addInput($autoplay);
      	$lrc = new Typecho_Widget_Helper_Form_Element_Radio('lrc', array ('0' => '启用', '1' => '禁用'), '0','歌词显示', '选择是否开启歌词显示');
    	$form->addInput($lrc);
     	$order = new Typecho_Widget_Helper_Form_Element_Radio('order', array ('0' => '列表顺序', '1' => '随机播放'), '0','音频循环顺序', '选择你的音乐播放方式~');
    	$form->addInput($order);
        $theme = new Typecho_Widget_Helper_Form_Element_Text('theme', null, '#3498db', _t('主题颜色'), '这里填写十六进制颜色代码，作为进度条和音量条的主题颜色');
        $form->addInput($theme);
        $volume = new Typecho_Widget_Helper_Form_Element_Text('volume', null, '0.7', _t('默认音量'), '这里填写不大于1的数字作为默认音量<br/>PS：播放器会记忆用户设置，用户手动设置音量后默认音量即失效');
        $form->addInput($volume);
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
    public static function render(){}
    public static function header(){
      	//获取参数
      	$config = Typecho_Widget::widget('Widget_Options')->plugin('APlayerAtBottom');
      	$aplayer = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> aplayer;
        $id = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> id;
     	$autoplay = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> autoplay;
      	$theme = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> theme;
      	$volume = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> volume;
      	$lrc = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> lrc;
      	$order = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> order;
      	
      	//判断是否有开启APlayer设置
      	if($aplayer === '1') {
        	echo '<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/aplayer@1.10.0/dist/APlayer.min.css">'; //输出APlayerCSS
        }else{}
      	
      	//判断是否打开歌词
      	if($lrc === '0') {
        	$lrc_out = 3;
        }else{
        	$lrc_out = 0;
        }
      	
      	//判断是否打开自动播放
      	if($autoplay === '0') {
          	$autoplay_out = 'true';
        }else{
        	$autoplay_out = 'false';
        }
      	
      	//判断歌曲播放方式
      	if($order === '0') {
        	$order_out = 'list';
        }else{
        	$order_out = 'random';
        }
		
      	//设置写入缓存
      	$data = array();
      	$data['id'] = $id;
		$data['lrc'] = $lrc_out;
		$data['autoplay'] = $autoplay_out;
      	$data['theme'] = $theme;
      	$data['volume'] = $volume;
      	$data['order'] = $order_out;
      	$json_string = json_encode($data);
      	file_put_contents('./usr/plugins/APlayerAtBottom/settings.json',$json_string);
    }
    public static function footer(){
      	//获取参数
      	$config = Typecho_Widget::widget('Widget_Options')->plugin('APlayerAtBottom');
      	$aplayer = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> aplayer;
      	
        echo '<div id="downplayer"></div>'; //构建播放器
      	
      	//判断是否有开启APlayer设置
      	if($aplayer === '1') {
        	echo '<script src="//cdn.jsdelivr.net/npm/aplayer@1.10.0/dist/APlayer.min.js"></script>'; //输出APlayerJS
        }else{}
        
		echo '<script src="/usr/plugins/APlayerAtBottom/downplayer.js"></script>'; //输出设定内容JS
    }
}
?>
