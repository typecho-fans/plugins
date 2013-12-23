<?php
/**
 * DuoshuoSDK 本地服务类定义
 *
 * @version		$Id: LocalServer.php 0 10:17 2012-7-23
 * @author 		shen2
 * @copyright	Copyright (c) 2012 - , Duoshuo, Inc.
 * @link		http://dev.duoshuo.com
 */
class Duoshuo_LocalServer{
	
	protected $response = array();
	
	protected $plugin;
	
	public function __construct($plugin){
		$this->plugin = $plugin;
	}
	
	/**
	 * 从服务器pull评论到本地
	 * 
	 * @param array $input
	 */
	public function sync_log($input = array()){
		$syncLock = $this->plugin->getOption('sync_lock');//检查是否正在同步评论 同步完成后该值会置0
		if($syncLock && $syncLock > time()- 300){//正在或5分钟内发生过写回但没置0
			$this->response = array(
				'code'	=>	Duoshuo_Exception::SUCCESS,
				'response'=> '同步中，请稍候',
			);
			return;
		}

		try{
			$this->response['affected'] = $this->plugin->syncLog();
			$this->response['last_log_id'] = $this->plugin->getOption('last_log_id');
		}
		catch(Exception $ex){
			//$this->plugin->updateOption('sync_lock', $ex->getLine());
		}
		$this->response['code'] = Duoshuo_Exception::SUCCESS;
	}
	
	public function sendResponse(){
		echo json_encode($this->response);
	}
	
	public function dispatch($input){
		if (!isset($input['signature']))
			throw new Duoshuo_Exception('Invalid signature.', Duoshuo_Exception::INVALID_SIGNATURE);
		
		$signature = $input['signature'];
		unset($input['signature']);
		
		ksort($input);
		$baseString = http_build_query($input, null, '&');
		
		$expectSignature = base64_encode(hash_hmac('sha1', $baseString, $this->plugin->getOption('secret'), true));

		if ($signature !== $expectSignature)
			throw new Duoshuo_Exception('Invalid signature, expect: ' . $expectSignature . '. (' . $baseString . ')', Duoshuo_Exception::INVALID_SIGNATURE);
		
		$method = $input['action'];
		
		if (!method_exists($this, $method))
			throw new Duoshuo_Exception('Unknown action.', Duoshuo_Exception::OPERATION_NOT_SUPPORTED);
		
		$this->$method($input);
		$this->sendResponse();
	}
	
	static function sendException($e){
		$response = array(
			'code'	=>	$e->getCode(),
			'errorMessage'=>$e->getMessage(),
		);
		echo json_encode($response);
	}
}