<?php
/**
 * 多说 for Typecho 插件 api处理
 *
 * @version		$Id: api.php 0 12:21 2013-12-18
 * @author 		rakiy [xux851@gmail.com] 
 * @copyright	Copyright (c) 2012 - , duoshuo, Inc.
 * @link		http://dev.duoshuo.com / http://ysido.com
 */
if (!extension_loaded('json'))	include_once 'libs/compat_json.php';

class Duoshuo_Action extends Typecho_Widget implements Widget_Interface_Do{
	private $db;
	private $options;
    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
        //将插件选项用于多说表中
        //var_dump($this->plugins);
    }

    public function action(){
    	$this->widget('Widget_User')->pass('administrator');
    	$this->db = Typecho_Db::get();
    	$this->options = Typecho_Widget::widget('Widget_Options');
		$this->fileload();
		$this->plugin = Duoshuo_Typecho::getInstance();
    	$this->on($this->request->is('do=theme'))->theme();
    	$this->on($this->request->is('do=fireExport'))->fireExport();
    	$this->on($this->request->is('do=Duoshuo_sync_log'))->Duoshuo_sync_log();
    	$this->on($this->request->is('do=Duoshuo_reset'))->Duoshuo_reset();
    	$this->on($this->request->is('do=delete_comments'))->delete_comments();
    	$this->on($this->request->is('do=exportjson'))->exportjson();
    	$this->on($this->request->is('do=writecomments'))->writecomments();
    	//$this->on($this->request->is('do=setting'))->Setting();			//暂停功能，完善再发
    }

    //更新主题
    public function theme(){
    	if($theme = $this->request->get('theme')){
    		$this->db->query($this->db->update('table.options')->rows(array('value'=>$theme))->where('name=?','Duoshuo_theme'));
    		$this->response->redirect(Typecho_Common::url('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-theme', $this->options->adminUrl));
    	}
    }

    public function fireExport(){
    	return $this->plugin->export();
    }

    public function Duoshuo_sync_log(){
    	return $this->plugin->syncLogAction();
    }

    public function exportjson(){
    	return $this->plugin->export2json();
    }

    public function writecomments(){
    	$code = '<div id="comments"> '."\r".'<?php if($this->allow("comment")): ?>'."\r".'<!-- Duoshuo Comment BEGIN -->'."\r".'	<div class="ds-thread" data-thread-key="<?php echo $this->cid;?>" '."\r".'	data-title="<?php echo $this->title;?>" data-author-key="<?php echo $this->authorId;?>" data-url=""></div>'."\r".'	<script type="text/javascript">'."\r".'	var duoshuoQuery = {short_name:"'.self::getOption('short_name').'",theme:"<?php echo ($this->options->Duoshuo_theme) ? $this->options->Duoshuo_theme : \'default\'?>"};'."\r".'	(function() {'."\r".'		var ds = document.createElement("script");'."\r".'		ds.type = "text/javascript";ds.async = true;'."\r".'		ds.src = "http://static.duoshuo.com/embed.js";'."\r".'		ds.charset = "UTF-8";'."\r".'		(document.getElementsByTagName("head")[0] '."\r".'		|| document.getElementsByTagName("body")[0]).appendChild(ds);'."\r".'	})();'."\r".'	</script>'."\r".'<!-- Duoshuo Comment END -->'."\r".'<?php else: ?>'."\r".'<h4><?php _e("评论已关闭"); ?></h4> '."\r".'<?php endif; ?> '."\r".'</div>';
    	try{
    		$path = __TYPECHO_ROOT_DIR__ . __TYPECHO_THEME_DIR__ . '/' . $this->options->theme;
    		if(!file_exists($path.'/comments.bak')) rename($path.'/comments.php',$path.'/comments.bak');
    		file_put_contents($path.'/comments.php', $code);
    		$response = array(
    			'code' => 0,
    			'progress' => '写入成功',
    		);
    	}catch(Exception $e){
    		$response = array(
				'code'	=>	$e->getCode(),
				'errorMessage'=>$e->getMessage(),
			);
    	}
    	$this->plugin->sendJsonResponse($response);
    }

    public function Setting(){
    	try{
    		//读取最后一篇评论的POST_ID
    		$post_id = $this->db->fetchRow($this->db->select('post_id')->from('table.comments')->order('post_id',Typecho_Db::SORT_DESC));
    		if($post_id) {
    			//若存在且大于0，则开始更新设置
    			self::updateOption('last_log_id',$post_id);
    			self::updateOption('synchronized',time());
    			$response = array(
    				'code' => 0,
    				'progress' => 'success'
    			);
    		}else{
    			$response = array(
    				'code' => 0,
    				'progress' => 'error'
    			);
    		}
    	}catch(Exception $e){
    		$response = array(
    			'code'	=>	$e->getCode(),
				'errorMessage'=>$e->getMessage(),
    		);
    	}
    	$this->plugin->sendJsonResponse($response);
    }

    public function Duoshuo_reset(){
    	$map = array(
    			'short_name' => ' ',
    			'secret' => ' ',
    			'last_log_id' => 0,
    			'synchronized' => 0,
    	);
    	try{
    		foreach ($map as $k => $v) {
	    		self::updateOption($k,$v);
    		}
    		$response = array(
				'progress'	=>	time(),
				'code'	=>	0
			);
    	}catch(Exception $e){
    		$response = array(
				'code'	=>	$e->getCode(),
				'errorMessage'=>$e->getMessage(),
			);
    	}
    	$this->plugin->sendJsonResponse($response);
    }
    public function delete_comments(){
    	$t = $this->db->query($this->db->delete('table.comments')->where('status=?','delete'));
    	if($t !== NULL){
    		$response = array(
				'progress'	=>	$t,
				'code'	=>	0
			);
			$this->plugin->sendJsonResponse($response);
    	}
    }

    public function fileload(){
		class_exists('Duoshuo_Client') or require('libs/Client.php');
		class_exists('Duoshuo_Abstract') or require('libs/Abstract.php');
		class_exists('Duoshuo_Typecho') or require('libs/Typecho.php');
		class_exists('Duoshuo_LocalServer') or require('libs/LocalServer.php');
		class_exists('Duoshuo_Exception') or require('libs/Exception.php');
    }

    public function nocache_headers(){
		header("Pragma:no-cache\r\n");
		header("Cache-Control:no-cache\r\n");
		header("Expires:0\r\n");
	}

	//封装API	
    public function api(){
    	$this->fileload();
		if (!headers_sent()) {
			$this->nocache_headers();//max age TODO:
			header('Content-Type: text/javascript; charset=utf8');
		}
		if (!class_exists('Duoshuo_Typecho')){
			$response = array(
				'code'			=>	30,
				'errorMessage'	=>	'duoshuo plugin hasn\'t been activated.'
			);
			echo json_encode($response);
			exit;
		}
		$plugin = Duoshuo_Typecho::getInstance();

		try{
			if($plugin->getPlugOption('sync_to_local') !== NULL){
	    		if($plugin->getPlugOption('sync_to_local') == 0) {
	    			$response = array(
						'code'			=>	31,
						'errorMessage'	=>	'duoshuo plugin hasn\'t been closed'
					);
					echo json_encode($response);
					exit;
	    		}
			}
			if ($_SERVER['REQUEST_METHOD'] == 'POST'){
				$server = new Duoshuo_LocalServer($plugin);
				$input = $_POST;
				if (get_magic_quotes_gpc()){
					foreach($input as $key => $value)
						$input[$key] = stripslashes($value);
				}
				$server->dispatch($input);
			}
		}
		catch (Exception $e){
			Duoshuo_LocalServer::sendException($e);
			exit;
		}
    }
    static function encodeJWT($payload, $key){
		$header = array('typ' => 'JWT', 'alg' => 'HS256');
		$segments = array(
			str_replace('=', '', strtr(base64_encode(json_encode($header)), '+/', '-_')),
			str_replace('=', '', strtr(base64_encode(json_encode($payload)), '+/', '-_')),
		);
		$signing_input = implode('.', $segments);
	
		$signature = self::hmacsha256($signing_input, $key);
	
		$segments[] = str_replace('=', '', strtr(base64_encode($signature), '+/', '-_'));
	
		return implode('.', $segments);
	}
	static function hmacsha256($data, $key) {
		if (function_exists('hash_hmac'))
			return hash_hmac('sha256', $data, $key, true);
		
		if (!class_exists('nanoSha2', false))
			require 'nanoSha2.php';
		
		$nanoSha2 = new nanoSha2();
		
	    $blocksize=64;
	    if (strlen($key)>$blocksize)
	        $key=pack('H*', $nanoSha2->hash($key, true));
	    $key=str_pad($key,$blocksize,chr(0x00));
	    $ipad=str_repeat(chr(0x36),$blocksize);
	    $opad=str_repeat(chr(0x5c),$blocksize);
	    $hmac = pack(
	                'H*',$nanoSha2->hash(
	                    ($key^$opad).pack(
	                        'H*', $nanoSha2->hash(($key^$ipad).$data, true)
	                    ),
	                	true
	                )
	            );
	    return $hmac;
	}
	static function getOption($key){
		global $db;
		$value = $db->fetchRow($db->select('value')->from('table.duoshuo')->where('name=?',$key));
		if(is_array($value)) return $value['value'];
		return NULL;
	}
	static function updateOption($key, $value){
		global $db;
		$oldvalue = self::getOption($key);
		if($oldvalue!==NULL){
			$info['value'] = $value;
			$t = $db->query($db->update('table.duoshuo')->rows($info)->where('name=?',$key));
			return $t;
		}
	}
	static function _engine(){
    	return !empty($_SERVER['HTTP_APPNAME']) // SAE
       	 	|| !!getenv('HTTP_BAE_ENV_APPID')   // BAE
        	|| !!getenv('HTTP_BAE_LOGID')   // BAE 3.0
        	|| (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false); // GAE
	}
	static function ActMap($act){
		if (!isset($act)) return 'comments';
		$Map = array(
			'manage-comments' 		=>		'comments',
			'manage-theme'			=>		'theme',
			'manage-profile'		=>		'profile',
			'manage-preferences'	=>		'preferences',
			'manage-plugin'			=>		'plugin',
			'manage-statistics'		=>		'statistics',
			'manage-getcode'		=>		'getcode',
			'manage-about'			=>		'about',
		);
		if(isset($Map[$act])) return $Map[$act];
		return 'comments';
	}
}