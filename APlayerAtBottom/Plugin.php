<?php 
/**
 * 在网站底部插入APlayer吸底播放器<br/>
 * 开源项目：<a href="https://github.com/DIYgod/APlayer" target="_blank">APlayer</a>
 * 
 * @package APlayerAtBottom
 * @author 小太
 * @version 1.1.1
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
		unlink(__DIR__ .'/settings.json'); //删除settings.json
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
		//输出后台设置页面样式
      	echo ('<style>.buttons a{background:#467b96; color:#fff; border-radius:4px; padding:.5em .75em; display:inline-block}</style>');
      	
		//定义此插件版本
		$version = '1.1.1'; 
		
		//GithubAPI内容获取UA设定
		ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');
		
		//从GithubAPI拉取最新内容
      	$arr = json_decode(@file_get_contents('https://api.github.com/repos/satosouta/APlayerAtBottom/releases/latest'), true);
		
		//定义最新版本号
		if(empty($arr['tag_name'])){
			$new_version = '获取失败！';
		}else{
			$new_version = $arr['tag_name'];
		}
      	
      	//判断版本是否过时
      	if($new_version === '获取失败！'){
			$version_tips = '获取失败！请自行前往github获取更新！';
			$new_version_out = '<font color="#e84118">获取失败！</font>';
		}elseif($version < $new_version) {
        	$version_tips = '该插件有<font color="#e84118">新版本</font> => <a href="'.$arr['assets'][0]['browser_download_url'].'" target="_blank">点击下载</a>';
			$new_version_out = '<font color="#e84118">'.$new_version.'</font>';
		}elseif($version > $new_version){
            $version_tips = '你怎么回事，怎么还比最新版本高了？';
          	$new_version_out = '<font color="#e84118">'.$new_version.'</font>';
        }elseif($version = $new_version){
			$version_tips = '您的插件为最新版本，无需更新！';
          	$new_version_out = $new_version;
		}
      	
      	//输出版本信息
        $public_section = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $public_section->html('<h4>本插件目前版本：'.$version.' | 最新版本：'.$new_version_out.'（'.$version_tips.'）</h4>');
        $form->addItem($public_section);
      	
      	//设置内容
      	$aplayer = new Typecho_Widget_Helper_Form_Element_Radio('aplayer', array ('0' => '本地', '1' => 'jsDelivr（推荐）', '2' => '我已经安装了APlayer的CSS&JS或者相关的插件'), '0','APlayer 静态资源加载', '防止APlayer版本不同导致问题，若您没有安装相关插件或者自行添加CSS/JS，您只需要选择前面两个选项中的一个即可');
    	$form->addInput($aplayer);
    	$id = new Typecho_Widget_Helper_Form_Element_Text('id', null, '4907097519', _t('歌单id'), '这里填写你的 <b>网易云音乐</b> 或 <b>QQ音乐</b> 歌单ID，请不要填写成为UserID');
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
		$hide = new Typecho_Widget_Helper_Form_Element_Radio('hide', array ('0' => '否', '1' => '是'), '0','是否默认收起播放器', '选择“是”后则会默认收起播放器');
		$form->addInput($hide);
		$cachetime = new Typecho_Widget_Helper_Form_Element_Text('cachetime', null, '86400', _t('缓存时间（秒）'), '这里填写自动缓存的时间，默认为24小时');
		$form->addInput($cachetime);
		$server = new Typecho_Widget_Helper_Form_Element_Radio('server', array ('0' => '网易云音乐', '1' => 'QQ音乐'), '0','选择音乐来源', '您可以选择使用网易云音乐或者QQ音乐的歌单');
		$form->addInput($server);
		$netease = new Typecho_Widget_Helper_Form_Element_Radio('netease', array ('0' => '自定义API', '1' => 'Shota\'s API', '2' => 'O\'s API', '3' => '犬\'s API' ,'4' => 'Meto API'), '1','网易云音乐解析服务器选择', '您可以自行选择音乐歌单解析服务器');
		$form->addInput($netease);
		$tencent = new Typecho_Widget_Helper_Form_Element_Radio('tencent', array ('0' => '自定义API', '1' => 'Meto API'), '1','QQ音乐解析服务器选择', '您可以自行选择音乐歌单解析服务器');
    	$form->addInput($tencent);
		$api = new Typecho_Widget_Helper_Form_Element_Text('iapi', null, null, _t('自定义API'), '若您上一个设置选择了自定义API，请您按照下面的方式填写，若没有选择则可以空着<br/>示例：https://api.713.moe/netease?type=playlist&id=');
        $form->addInput($api);
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
		$hide = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> hide;
		$cachetime = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> cachetime;
		$server = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> server;
		$netease = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> netease;
		$tencent = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> tencent;
		$api = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> api;
      	
      	//静态文件设置
      	if($aplayer === '0') {
        	echo '<link rel="stylesheet" href="'.Helper::options()->pluginUrl.'/APlayerAtBottom/static/APlayer.min.css">';
        }elseif($aplayer === '1'){
			echo '<link rel="stylesheet" href="//cdn.jsdelivr.net/gh/SatoSouta/APlayerAtBottom/static/APlayer.min.css">';
		}

		//展开设定
		if($hide === '1'){
			echo '<style>.aplayer.aplayer-fixed.aplayer-narrow .aplayer-body{left:-68px}.aplayer.aplayer-fixed.aplayer-narrow .aplayer-body:hover{left:0}</style>';
		}
      	
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
		
		//判断设置的API
		if($server === '0'){
			if($netease === '0'){
				$api_out = $api;
				$apid = '0';
			}elseif($netease === '1'){
				$api_out = 'https://api.9jojo.cn/netease/?type=playlist&id=';
				$apid = '1';
			}elseif($netease === '2'){
				$api_out = 'https://api.ohmyga.cn/netease/?use=1&type=playlist&id=';
				$apid = '2';
			}elseif($netease === '3'){
				$api_out = 'https://api.fczbl.vip/163/?&type=playlist&id=';
				$apid = '3';
			}elseif($netease === '4'){
				$api_out = 'https://api.i-meto.com/meting/api?server=netease&type=playlist&id=';
				$apid = '4';
			}
		}elseif($server === '1'){
			if($tencent === '0'){
				$api_out = $api;
				$apid = '0';
			}elseif($tencent === '1'){
				$api_out = "https://api.i-meto.com/meting/api?server=tencent&type=playlist&id=";
				$apid = '1';
			}
		}
		
		//更新方法
		if (file_exists(__DIR__ .'/settings.json') == false) {
			$data = [
				'last_update' => time(),
				'settings' => [],
				'data' => []
			];
			$data['settings'] = [
				'id' => $id,
				'lrc' => $lrc_out,
				'autoplay' => $autoplay_out,
				'theme' => $theme,
				'volume' => $volume,
				'order' => $order_out,
				'server' => $server,
				'api' => $apid
			];
			$data['data'] = @file_get_contents($api_out.$id."&rand=".mt_rand(0,999999));
			file_put_contents(__DIR__ .'/settings.json',json_encode($data));
		}else{
			$decode = json_decode(file_get_contents(__DIR__ .'/settings.json'), true);
			$oldserver = $decode['settings']['server'];
			$oldapi = $decode['settings']['api'];
			$olddata = $decode['data'];
			//检测缓存是否过期
			if ((time() - $data['last_update']) < $cachetime) {
				$data = [
					'last_update' => time(),
					'settings' => [],
					'data' => []
				];
				$data['settings'] = [
					'id' => $id,
					'lrc' => $lrc_out,
					'autoplay' => $autoplay_out,
					'theme' => $theme,
					'volume' => $volume,
					'order' => $order_out,
					'server' => $server,
					'api' => $apid
				];
				$data['data'] = @file_get_contents($api_out.$id."&rand=".mt_rand(0,999999));
				file_put_contents(__DIR__ .'/settings.json',json_encode($data));
			}else{
				//若缓存不过期则重新获取设置内容以防用户设置更新
				$data = [
					'last_update' => $decode['last_update'],
					'settings' => [],
					'data' => []
				];
				$data['settings'] = [
					'id' => $id,
					'lrc' => $lrc_out,
					'autoplay' => $autoplay_out,
					'theme' => $theme,
					'volume' => $volume,
					'order' => $order_out,
					'server' => $server,
					'api' => $apid
				];
				if($api != $oldapi || $server != $oldserver){
					$data['data'] = @file_get_contents($api_out.$id."&rand=".mt_rand(0,999999));
				}else{
					$data['data'] = $olddata;
				}
				file_put_contents(__DIR__ .'/settings.json',json_encode($data));
			}
		}
    }
    public static function footer(){
      	//获取参数
      	$config = Typecho_Widget::widget('Widget_Options')->plugin('APlayerAtBottom');
      	$aplayer = Typecho_Widget::widget('Widget_Options') -> Plugin('APlayerAtBottom') -> aplayer;
      	
        //构建播放器
		echo '<div id="downplayer"></div>';
      	
      	//静态文件设置
      	if($aplayer === '0'){
			echo '<script src="'.Helper::options()->pluginUrl.'/APlayerAtBottom/static/APlayer.min.js"></script>';
		}elseif($aplayer === '1') {
        	echo '<script src="//cdn.jsdelivr.net/gh/SatoSouta/APlayerAtBottom/static/APlayer.min.js"></script>';
        }
        
		//输出配置js
		echo '<script src="'.Helper::options()->pluginUrl.'/APlayerAtBottom/Downplayer.php"></script>';
    }
}
?>