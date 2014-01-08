<?php
/**
 * Duoshuo_Typecho HTTP封装 
 *
 * @version		1.0.0 
 * @author 		rakiy [xux851@gmail.com]
 * @copyright	Copyright (c) 2012 , Duoshuo, Inc. 
 * @link		http://dev.duoshuo.com|http://ysido.com
 */
class Duoshuo_Client{

	var $end_point = 'http://api.duoshuo.com/';
	var $format = 'json';
	var $userAgent;
	var $shortName;
	var $secret;
	var $jwt;
	var $accessToken;
	var $http;
	
	public function __construct($shortName = null, $secret = null, $jwt = null){
		$this->shortName = $shortName;
		$this->secret = $secret;
		$this->jwt = $jwt;
		$this->generator = Typecho_Widget::widget('Widget_Options')->generator;
		$this->http = Typecho_Http_Client::get();
		$this->userAgent = $this->generator . '|Duoshuo/'. Duoshuo_Typecho::VERSION;
		$this->plugin = Duoshuo_Typecho::getInstance();
		if($this->plugin->getPlugOption('end_point') !== NULL)
			$this->end_point = Duoshuo_Typecho::$PointMap[intval($this->plugin->getPlugOption('end_point'))];
	}
	
	/**
	 * 
	 * @param $method
	 * @param $path
	 * @param $params
	 * @throws Duoshuo_Exception
	 * @return array
	 */
	public function request($method, $path, $params = array()){


        $params['short_name'] = $this->shortName;
        $params['secret'] = $this->secret;
        
        if ($this->jwt)
			$params['jwt'] = $this->jwt;
        
       		
		$url = $this->end_point . $path. '.' . $this->format;
		
		return $this->httpRequest($url, $method, $params);
	}
	
	public function httpRequest($url, $method, $params){
		$method = isset($method) ? $method : 'GET';		//默认方法
		switch($method){
			case 'GET':
				$response = $this->http->setTimeout(60)
	            			->setQuery($params)
	        				->setHeader('User-Agent', $this->userAgent);
				break;
			case 'POST':
				$response = $this->http->setTimeout(60)
	            			->setData($params)
	        				->setHeader('User-Agent', $this->userAgent);
				break;
			default:
		}
		try{
			$response = $response->send($url);	
		}catch(Duoshuo_Exception $e){
			throw new Duoshuo_Exception('连接服务器失败, 详细信息：' . json_encode($e), Duoshuo_Exception::REQUEST_TIMED_OUT);
		}
		
		$json = json_decode($this->http->getResponseBody(), true);
		return $json === null ? $this->http->getResponseBody() : $json;
	}
}
