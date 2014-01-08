<?php
/**
 * DuoshuoSDK 本地服务类定义
 *
 * @version		$Id: LocalServer.php 0 16:28 2013-4-11
 * @author 		xiaowu
 * @copyright	Copyright (c) 2012 - , Duoshuo, Inc.
 * @link		http://dev.duoshuo.com
 */
class Duoshuo_Typecho extends Duoshuo_Abstract{
	
	const VERSION = '1.1.3';
	
	public static $errorMessages = array();

	public static $EMBED = false;

	static $PointMap = array('http://api.duoshuo.com/','http://api.duoshuo.org/','http:///118.144.80.201/');	

	public function __construct(){
		Typecho_Widget::widget('Widget_Init');		//TE初始化
		$this->db = Typecho_Db::get();	
	}

	public static function getInstance(){
		if (self::$_instance === null)
			self::$_instance = new self();
		return self::$_instance;
	}
	
	public static function timezone(){
		global $cfg_cli_time;
		return $cfg_cli_time;
	}
	
	public function sendJsonResponse($response){
		if (!headers_sent()) {
			header("Pragma:no-cache\r\n");
			header("Cache-Control:no-cache\r\n");
			header("Expires:0\r\n");
			header('Content-type: application/json; charset=UTF-8');
		}
		
        echo json_encode($response);
        exit;
	}
	/**
	 * 保存多说设置
	 * @param 键 $key
	 * @param 值 $value
	 * @param 键名 $info
	 * @param 类型 $type
	 * @param 组别 $groupid
	 */
	public function updateOption($key, $value){//, $info = NULL,$type = NULL,$groupid = NULL){
		$oldvalue = $this->getOption($key);
		if($oldvalue!=NULL){
			$info['value'] = $value;
			$t = $this->db->query($this->db->update('table.duoshuo')->rows($info)->where('name=?',$key));
			$this->options[$key] = $value;
			return $t;
		}
	}
	
	
	public function getOption($key){
		$this->db = Typecho_Db::get();	
		$this->options = array();
		if(isset($this->options[$key])){
			return $this->options[$key];
		}else{
			try{
				$sql = $this->db->select('value')->from('table.duoshuo')->where('name=?',$key);
				$value = $this->db->fetchRow($sql);
				if(is_array($value)){
					$this->options[$key] = $value['value'];
					return $value['value'];
				}else{
					return NULL;
				}
			}catch(Exception $e){
				return NULL;
			}
		}
	}

	//获取系统插件参数
	public function getPlugOption($key){
		$options = Typecho_Widget::widget('Widget_Options');
		$value = $options->plugin('Duoshuo')->$key;
    	if($value){
    		return $value;
		}else{
			return NULL;
		}
	}
	
	public static function currentUrl(){
		$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
		$php_self	 = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
		$path_info	= isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
		$relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
		return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
	}
	
	static function sendException($e){
		$response = array(
			'code'	=>	$e->getCode(),
			'errorMessage'=>$e->getMessage(),
		);
		echo json_encode($response);
		exit;
	}
	
	public function createPost($meta){
		$approvedMap = array(
			'pending' => 'waiting',
			'approved' => 'approved',
			'deleted' => 'deleted',
			'spam' => 'spam',
			'thread-deleted'=>'spam'
		);
		if(!empty($meta['thread_key'])){
			$cid = $meta['thread_key'];
			$thread = $this->db->fetchRow($this->db->select('cid')->from('table.contents')->where('cid=?',$cid));
			if(is_array($thread)){
				$info = array(
					'post_id' 	=>  intval($meta['post_id']),
					'cid'		=>	intval($meta['thread_key']),
					'created'	=>	strtotime($meta['created_at']),
					'author'	=>	addslashes(trim(strip_tags($meta['author_name']))),
					'authorId'	=>	intval($meta['author_key']) ? intval($meta['author_key']) : intval($meta['author_id']),
					'ownerId' 	=>	1,
					'mail' 		=> 	addslashes(trim(strip_tags($meta['author_email']))),
					'url' 		=> 	trim(strip_tags($meta['author_url'])),
					'ip' 		=>	trim(strip_tags($meta['ip'])),
					'agent' 	=> 	'DUOSHUO/'.trim(strip_tags($meta['agent'])),
					'text'		=>	addslashes(strip_tags($meta['message'])),
					'type'		=>	'comment',
					'status' 	=>	$approvedMap[$meta['status']],
					'parent' 	=>	0,
				);
				if($meta['parent_id']){
					$parent = $this->db->fetchRow($this->db->select('coid')->from('table.comments')->where('post_id=?',$meta['parent_id']));
					if(is_array($parent)) $info['parent'] = intval($parent['coid']);
				}
				$sql = $this->db->insert('table.comments')->rows($info);
				$this->db->query($sql);
				//增加文章评论数
				return array($cid);	
			}
		}
		return null;
	}
	
	public function approvePost($postIdArray){
		foreach ($postIdArray as $key => $value) {
			$info['status'] = 'approved';
			$this->db->query($this->db->update('table.comments')->rows($info)->where('post_id=?',$value));
		}
		
	}

	public function spamPost($postIdArray){
		foreach ($postIdArray as $key => $value) {
			$info['status'] = 'spam';
			$sql = $this->db->update('table.comments')->rows($info)->where('post_id=?',$value);
			$this->db->query($sql);
		}
		
	}
	public function deleteForeverPost($postIdArray){
		foreach ($postIdArray as $key => $value) {
			$this->db->query($this->db->delete('table.comments')->where('post_id=?',$value));
		}
		
	}
	public function deletePost($postIdArray){
		foreach ($postIdArray as $key => $value) {
			$info['status'] = 'deleted';
			$this->db->query($this->db->update('table.comments')->rows($info)->where('post_id=?',$value));
		}
		
	}

	
	public function refreshThreads($cidList){
		foreach($cidList as $cid){
			if(!empty($cid)){
				$sql = $this->db->select('COUNT(coid) AS num')->from('table.comments')->where('status=?','approved')->where('cid=?',$cid);

				$cids = $this->db->fetchRow($sql);
				if(isset($cids['num'])){
					$info['commentsNum'] = $cids['num'];
					$this->db->query($this->db->update('table.contents')->rows($info)->where('cid=?',$cid));
				}
			}
		}
	}
	
	/**
	 * 将文章和评论内容同步到多说，用于以前的评论显示和垃圾评论过滤
	 */
	public function export(){
		@set_time_limit(0);
		@ini_set('memory_limit', '256M');
		$progress = $this->getOption('synchronized');
		if (!$progress || is_numeric($progress)) $progress = 'user/0';    //之前已经完成了导出流程
		list($type, $offset) = explode('/', $progress);
		try{
			switch($type){
				case 'user':
					$limit = 10;
					$columns = array('uid', 'name', 'mail', 'url', 'created', 'screenName','group');
					$sql = $this->db->select(implode(',', $columns))->from('table.users')->order('uid',Typecho_Db::SORT_DESC)->offset($offset)->limit($limit);
					$users = $this->db->fetchAll($sql);
					$count = $this->exportUsers($users);
					break;
				case 'post':
					$limit = 10;
					$columns = array('cid', 'title', 'slug', 'created', 'type', 'status' , 'authorId' , 'modified' , 'allowComment' , 'allowPing');
					$sql = $this->db->select(implode(',', $columns))->from('table.contents')->where('status = ?', 'publish')->where('type != ?', 'attachment')->offset($offset)->limit($limit);
					$posts = $this->db->fetchAll($sql);
					$count = $this->exportPosts($posts);
					break;
				case 'comment':
					$limit = 50;
					$columns = array('coid', 'cid', 'parent', 'author', 'authorId', 'mail', 'url', 'agent', 'ip', 'text', 'status', 'type', 'created');
					$sql = $this->db->select(implode(',', $columns))->from('table.comments')->where('type = ?', 'comment')->offset($offset)->limit($limit);
					$comments = $this->db->fetchAll($sql);
					$count = $this->exportComments($comments);
					break;
				default:
			}
			if ($count == $limit){
				$progress = $type . '/' . ($offset + $limit);
			}elseif($type == 'user'){
				$progress = 'post/0';
			}elseif($type == 'post'){
				$progress = 'comment/0';
			}elseif($type == 'comment'){
				$progress = time();
			}
			$this->updateOption('synchronized', $progress);
			$response = array(
				'progress'=>$progress,
			   	'code'	=>	0
			);
			$this->sendJsonResponse($response);
		}catch(Duoshuo_Exception $e){
			$this->sendException($e);
		}
	}
	
	public function packageUser($user){
		static $roleMap = array(
				'administrator'	=>	'administrator',
				'editor'		=>	'editor',
				'contributor'	=>	'user',
				'subscriber'	=>	'user',
				'vister'		=>	'user',
		);
		
		$data = array(
				'user_key'	=>	$user['uid'],
				'name'		=>	$user['name'],
				'email'		=>	$user['mail'],
				'url'		=>	$user['url'],
				'created_at'=>	$user['created'],
				'meta'		=>	$user['screenName'],
		);
		
		$data['role'] = $roleMap[$user['group']];
		return $data;
	}

	//打包文章
	public function packageThread($post){
		$post['text'] = '';
		$cii = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($post);
		$params = array(
			'thread_key'=>	$post['cid'],
			'author_key'=>	$post['authorId'],
			'title'		=>	html_entity_decode($post['title']),
			'content'	=>	'',	//不向多说发送文章内容
			'excerpt'	=>	'',	//不向多说发送文章摘要
			'created_at'=>	date('Y-m-d\TH:i:s+00:00', $post['created']),
			'updated_at'=>	date('Y-m-d\TH:i:s+00:00', $post['modified']),
			'ip'		=>	$_SERVER['REMOTE_ADDR'],
			'url'		=>	$cii['permalink'],
			'slug'		=>	$post['slug'],
			'status'	=>	$post['status'],
			'comment_status'=>	$post['allowComment'],
			'ping_status'=>	$post['allowPing'],
			'type'		=>	$post['type'],
			'source'	=>	'typecho',
		);
	
		return $params;
	}


	//打包评论
	public function packageComment($comment){
		$statusMap = array(
			'waiting'	=>	'pending',
			'approved'	=>	'approved',
			'trash'		=>	'deleted',	//TE没有此类别，但是为了兼容，保留
			'spam'		=>	'spam',
		);

		$meta['comment_date'] = date('Y-m-d H:i:s',$comment['created']);

		$data = array(
			'thread_key'	=>	$comment['cid'],
			'post_key'		=>	$comment['coid'],
			'author_key'	=>	$comment['authorId'],
			'author_name'	=>	htmlspecialchars_decode($comment['author'], ENT_QUOTES),
			'author_email'	=>	$comment['mail'] ? $comment['mail'] : '',
			'author_url'	=>	$comment['url'],
			'created_at'	=>	date('Y-m-d\TH:i:s+00:00',$comment['created']),
			'message'		=>	empty($comment['text']) ? '' : $comment['text'],
			'agent'			=>	$comment['agent'],
			//'type'			=>	'',				//不能加，加了会有问题
			'ip'			=>	$comment['ip'],
			'status'		=>	$statusMap[$comment['status']],
			'parent_key'	=>	$comment['parent'],	// TODO 接收的地方要处理一下
			'meta'			=>	$meta,
		);

		//'source'		=>	'import',
		return $data;
	}

	/*
	public function myUnset($data, $keys) {
		if(!is_array($data)) return array();
		foreach($keys as $key) {
			if(isset($data[$key]))
				unset($data[$key]);
		}
		return $data;
	}
	*/
	public function syncLogAction(){
		@set_time_limit(0);
		@ini_set('memory_limit', '256M');
		
		try{
			$response = array(
				'count'	=>	$this->syncLog(),
				'code'	=>	0
			);
			$this->sendJsonResponse($response);
		}
		catch(Duoshuo_Exception $e){
			if ($e->getCode() == Duoshuo_Exception::REQUEST_TIMED_OUT){
				//$this->connectFailed();
				$this->updateOption('sync_lock',  0);
			}
			
			$this->sendException($e);
		}
	}
	public function export2json(){
		@set_time_limit(0);
		@ini_set('memory_limit', '256M');
		$params = array("generator" => "duoshuo","version" => "0.1");
		//导出文章
		$columns = array('cid', 'title', 'slug', 'created', 'type', 'status' , 'authorId' , 'modified' , 'allowComment' , 'allowPing');
		$sql = $this->db->select(implode(',', $columns))->from('table.contents')->where('status = ?', 'publish')->where('type != ?', 'attachment');
		$posts = $this->db->fetchAll($sql);
		if(!$posts) return false;
		foreach ($posts as $index => $post) {
			$params['threads'][] = $this->packageThread($post);
		}
		//导出评论
		$columns = array('coid', 'cid', 'parent', 'author', 'authorId', 'mail', 'url', 'agent', 'ip', 'text', 'status', 'created');
		$sql = $this->db->select(implode(',', $columns))->from('table.comments');
		$comments = $this->db->fetchAll($sql);
		if(!$posts) return false;
		foreach ($comments as $index => $comment) {
			$params['posts'][] = $this->packageComment($comment);
		}
		var_dump($params);
		die();
		
	}
}