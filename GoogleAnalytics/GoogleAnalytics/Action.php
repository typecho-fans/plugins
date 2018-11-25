<?php
/**
 * GoogleAnalytics Plugin
 *
 * @copyright  Copyright (c) 2018 WeiCN (https://cuojue.org)
 * @license	GNU General Public License 3.0
 * 
 */
class GoogleAnalytics_Action extends Typecho_Widget implements Widget_Interface_Do
{

    /** @var bool 请求适配器 */
	private static $_adapter    = false;
	
	public function __construct($request, $response, $params = NULL)
	{
		parent::__construct($request, $response, $params);
	}
	/**
	 * 添加新的链接转换
	 * 
	 */
	public function ga(){

		//拦截参数不完整
		if(empty($this->request->getReferer()) || 
			empty($this->request->getAgent()) || 
			empty($this->request->get('ga')) || 
			empty($this->request->get('dt')) || 
			empty($this->request->get('ul')) ||  
			empty($this->request->get('sd')) ||  
			empty($this->request->get('sr')) ||  
			empty($this->request->get('vp')) || 
			empty($this->request->get('z')))
		{
			header('HTTP/1.1 403 Forbidden');
			exit();
		}

	   if (!isset($_COOKIE["uuid"])) {
			$str = md5(uniqid(mt_rand(), true));
			$uuid = substr($str,0,8) . '-';
			$uuid .= substr($str,8,4) . '-';
			$uuid .= substr($str,12,4) . '-';
			$uuid .= substr($str,16,4) . '-';
			$uuid .= substr($str,20,12);
			setcookie("uuid", $uuid , time()+368400000);
	   }else{
			$uuid=$_COOKIE["uuid"];
	   }

	   header('content-type: image/jpg');
	   header('HTTP/1.1 204 No Content');

	   if (function_exists("fastcgi_finish_request")) {
			fastcgi_finish_request(); //对于fastcgi会提前返回请求结果，提高响应速度。
	   }

		$url='v=1&t=pageview&';
		$url.='tid='.$this->request->get('ga').'&';
		$url.='cid='.$uuid.'&';
		$url.='dl='.rawurlencode(rawurldecode($this->request->getReferer())).'&';
		$url.='uip='.rawurlencode(rawurldecode($this->request->getip())).'&';
		$url.='ua='.rawurlencode(rawurldecode($this->request->getAgent())).'&';
		$url.='dt='.rawurlencode(rawurldecode($this->request->get('dt'))).'&';
		$url.='dr='.rawurlencode(rawurldecode($this->request->get('dr'))).'&';
		$url.='ul='.rawurlencode(rawurldecode($this->request->get('ul'))).'&';
		$url.='sd='.rawurlencode(rawurldecode($this->request->get('sd'))).'&';
		$url.='sr='.rawurlencode(rawurldecode($this->request->get('sr'))).'&';
		$url.='vp='.rawurlencode(rawurldecode($this->request->get('vp'))).'&';
		$url.='z='.$this->request->get('z');
		$url='https://www.google-analytics.com/collect?'.$url;

		self::asyncRequest($url);
	}

    /**
     * 检测 适配器
     * @return string
     */
    public static function isAvailable()
    {
        function_exists('ini_get') && ini_get('allow_url_fopen') && (self::$_adapter = 'Socket');
        false == self::$_adapter && function_exists('curl_version') && (self::$_adapter = 'Curl');
        
        return self::$_adapter;
	}

    /**
     * 发送异步请求
     * @param $url
     */
    public static function asyncRequest($url)
    {
        self::isAvailable();
        self::$_adapter == 'Socket' ? self::socket($url) : self::curl($url);
	}
	
    /**
     * Socket 请求
     * @param $url
     * @return bool
     */
    public static function socket($url)
    {
        $params = parse_url($url);
        $path = $params['path'] . '?' . $params['query'];
        $host = $params['host'];
        $port = 80;
        $scheme = '';

        if ('https' == $params['scheme']) {
            $port = 443;
            $scheme = 'ssl://';
        }

        if (function_exists('fsockopen')) {
            $fp = @fsockopen ($scheme . $host, $port, $errno, $errstr, 30);
        } elseif (function_exists('pfsockopen')) {
            $fp = @pfsockopen ($scheme . $host, $port, $errno, $errstr, 30);
        } else {
            $fp = stream_socket_client($scheme . $host . ":$port", $errno, $errstr, 30);
        }

        if ($fp === false) {
            return false;
        }

        $out = "GET " . $path . " HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        sleep(1);
        fclose($fp);
	}
	
    /**
     * Curl 请求
     * @param $url
     */
    public static function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // 将curl_exec()获取的信息以文件流的形式返回,不直接输出。  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);  // 连接等待时间  
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);         // curl允许执行时间

        curl_exec($ch);
        curl_close($ch);

    }
	public function action(){
		$this->on($this->request->is('ga'))->ga();
	}
}
?>
