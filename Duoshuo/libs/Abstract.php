<?php
/**
 * DuoshuoSDK 基类定义
 *
 * @version		$Id: Abstract.php 0 10:17 2012-7-23
 * @author 		shen2
 * @copyright	Copyright (c) 2012 - , Duoshuo, Inc.
 * @link		http://dev.duoshuo.com
 */
class Duoshuo_Abstract {
	const DOMAIN = 'duoshuo.com';
	const STATIC_DOMAIN = 'static.duoshuo.com';
	
	protected static $_instance = null;
	
	/**
	 *
	 * @var string
	 */
	public function __construct(){
		$this->getOption('short_name');
		$this->getOption('secret');
		$this->db = Typecho_Db::get();	
	}
	
	public function rfc3339_to_mysql($string){
		if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
			return DateTime::createFromFormat(DateTime::RFC3339, $string)->format('Y-m-d H:i:s');
		}
		else{
			$timestamp = strtotime($string);
			return gmdate('Y-m-d H:i:s', $timestamp  + $this->timezone('gmt_offset') * 3600);
		}
	}
	
	public function rfc3339_to_mysql_gmt($string){
		if (method_exists('DateTime', 'createFromFormat')){	//	php 5.3.0
			return DateTime::createFromFormat(DateTime::RFC3339, $string)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
		}
		else{
			$timestamp = strtotime($string);
			return gmdate('Y-m-d H:i:s', $timestamp);
		}
	}
	
	
	/**
	 *
	 * @return Duoshuo_Client
	 */
	public function getClient($remoteAuth = null){	//如果不输入参数，就是游客
		return new Duoshuo_Client($this->getOption('short_name'), $this->getOption('secret'), $remoteAuth);
	}
	
		//导出用户
	function exportUsers($users){
		if (count($users) === 0) return 0;
		$params = array('users'=>array());
		foreach($users as $user){
			$params['users'][] = $this->packageUser($user);
		}
		$remoteResponse = $this->getClient()->request('POST', 'users/import', $params);
		if(is_array($remoteResponse)) $this->updateOption('user_id',$remoteResponse['response'][1]);
		return count($remoteResponse['response']);
	}
	//导出文章
	function exportPosts($threads){
		if (count($threads) === 0)	return 0;
		$params = array('threads'	=>	array());
		foreach($threads as $index => $thread){
			$params['threads'][] = $this->packageThread($thread);
		}

		$remoteResponse = $this->getClient()->request('POST','threads/import', $params);
		return count($remoteResponse['response']);
	}

	//导出评论
	function exportComments($comments){
		if (count($comments) === 0)	return 0;
		$params = array('posts'	=>	array());
		foreach($comments as $comment){
			$params['posts'][] = $this->packageComment($comment);
		}

		$remoteResponse = $this->getClient()->request('POST', 'posts/import', $params);

		//写入评论对应的多说id
		if(is_array($remoteResponse) && $remoteResponse['code'] == 0){
			$condition = 'UPDATE '.$this->db->getPrefix().'comments SET post_id = CASE coid'."\n\r";
			foreach ($remoteResponse['response'] as $coid => $postid) {
				$condition .= 'WHEN ' .$coid. ' THEN \''.$postid.'\''."\n\r";
				$coids[] = $coid;
			}
			$condition .= 'END'."\n\r".'WHERE coid IN ('.implode(',', $coids).')';
			$this->db->query($condition);
		}
		return count($comments);
	}

	public function syncLog(){
		$this->updateOption('sync_lock',  time());
		
		$last_log_id = $this->getOption('last_log_id');
		if (!$last_log_id)
			$last_log_id = 0;
		$limit = 50;
		$params = array(
				'limit' => $limit,
				'order' => 'asc',
		);
		$client = $this->getClient();
		$posts = array();
		$affectedThreads = array();

		//do{
			
			$params['since_id'] = $last_log_id;
			$response = $client->request('GET', 'log/list', $params);
			if (is_string($response))
				throw new Duoshuo_Exception($response, Duoshuo_Exception::INTERNAL_SERVER_ERROR);
			
			if (!isset($response['response']))
				throw new Duoshuo_Exception($response['message'], $response['code']);
			
			

			foreach($response['response'] as $log){
				switch($log['action']){
					case 'create':
						$affected = $this->createPost($log['meta']);
						break;
					case 'approve':
						$affected = $this->approvePost($log['meta']);
						break;
					case 'spam':
						$affected = $this->spamPost($log['meta']);
						break;
					case 'delete':
						$affected = $this->deletePost($log['meta']);
						break;
					case 'delete-forever':
						$affected = $this->deleteForeverPost($log['meta']);
						break;
					case 'update'://现在并没有update操作的逻辑
					default:
						$affected = array();
				}
				//合并
				if (is_array($affected))
					$affectedThreads = array_merge($affectedThreads, $affected);
		
				if (strlen($log['log_id']) > strlen($last_log_id) || strcmp($log['log_id'], $last_log_id) > 0)
					$last_log_id = $log['log_id'];
			}
			
			$this->updateOption('last_log_id', $last_log_id);
		
		//} while (count($response['response']) == $limit);//如果返回和最大请求条数一致，则再取一次
			
		$this->updateOption('sync_lock',  0);


		//更新静态文件
		if ($this->getPlugOption('sync_to_local') && $this->getPlugOption('seo_enabled'))
			$this->refreshThreads(array_unique($affectedThreads));
		
		return count($response['response']);
	}
}
