<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class TeStore_Action extends Typecho_Widget
{
	private $options;
	private $settings;
	private $security;
	private $user;
	private $useCurl;
	private $pluginRoot;

	/**
	 * 构造函数与初始化
	 * 
	 * @access public
	 * @param Typecho_Request $request
	 * @param Typecho_Response $response
	 * @param mixed $params
	 */
	public function __construct($request,$response,$params=NULL)
	{
		parent::__construct($request,$response,$params);

		$this->options = $this->widget('Widget_Options');
		$this->settings = $this->options->plugin('TeStore');
		$this->security = $this->widget('Widget_Security');
		$this->user = $this->widget('Widget_User');
		$this->useCurl = $this->settings->curl;
		$this->pluginRoot = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__;
	}

	/**
	 * 获取已启用插件名称
	 * 
	 * @access private
	 * @return array
	 */
	private function getActivePlugins()
	{
		$activatedPlugins = Typecho_Plugin::export();
		return array_keys($activatedPlugins['activated']);
	}

	/**
	 * 获取已安装插件信息
	 * 
	 * @access private
	 * @param string $name 插件名称
	 * @return array
	 */
	private function getLocalInfos($name)
	{
		$infos = array();
		$pluginDir = $this->pluginRoot.'/'.$name;
		$pluginFile = is_dir($pluginDir) ? $pluginDir.'/Plugin.php' : $pluginDir.'.php';
		if (is_file($pluginFile)) {
			$parse = Typecho_Plugin::parseInfo($pluginFile);
			$infos = array(strip_tags($parse['author']),strip_tags($parse['version'])); //兼容html混写
		}
		return $infos;
	}

	/**
	 * 读取并整理插件信息
	 * 
	 * @access public
	 * @return array
	 */
	public function getPluginData()
	{
		$pluginInfo = array();
		$cacheDir = $this->pluginRoot.'/TeStore/data/';
		$cacheFile = $cacheDir.'list.json';
		$cacheTime = $this->settings->cache_time;

		//读取缓存文件
		if ($cacheTime && is_file($cacheFile) && (time()-filemtime($cacheFile))<=$cacheTime*3600) {
			$data = file_get_contents($cacheFile);
			$pluginInfo = Json::decode($data,true);
		//读取表格地址
		} else {
			$html = '';
			$isRaw = false;
			$pages = array_filter(preg_split('/(\r|\n|\r\n)/',strip_tags($this->settings->source)));
			foreach ($pages as $page) {
				$page = trim($page);
				if ($page) {
					$proxy = $this->settings->proxy;
					$isRaw = strpos($page,'raw.githubusercontent.com') || strpos($page,'raw/master');
					//替换加速地址
					if ($proxy || $isRaw) {
						$page = str_replace(array('github.com','raw.githubusercontent.com'),$proxy,$page);
						$page = $proxy=='cdn.jsdelivr.net/gh' ? str_replace(array('blob/','raw/','master/'),'',$page) : str_replace(array('blob/','raw/'),'',$page);
					}
					$html .= $this->useCurl ? $this->curlGet($page) : @file_get_contents($page,0,
						stream_context_create(array('http'=>array('timeout'=>20)))); //设20秒超时
					//转码MD格式
					if ($proxy || $isRaw) {
						$html = htmlspecialchars_decode(Markdown::convert($html)); //fix 17.10.30 Markdown
					}
				}
			}

			//解析表格内容
			if ($html) {
				$dom = new DOMDocument('1.0','utf-8');
				$html = function_exists('mb_convert_encoding') ? mb_convert_encoding($html,'HTML-ENTITIES','UTF-8') : $html;
				@$dom->loadHTML($html);
				$trs = $dom->getElementsByTagName('tr');

				$tdVal = '';
				$texts = array();
				$tds = array();
				$a = (object)array();
				$href = '';
				$urls = array();
				foreach ($trs as $trKey=>$trVal) {
					if ($trVal->parentNode->tagName=='tbody') {
						//获取td纯文本
						foreach ($trVal->childNodes as $tdKey=>$td) {
							$tdVal = $td->nodeValue;
							if ($tdVal) {
								$texts[$trKey][] = htmlspecialchars(trim($tdVal));
							}
						}
						$tds = $trs->item($trKey)->getElementsByTagName('td');
						//获取td元数据
						foreach ($tds as $tdKey=>$tdVal) {
							if ($tdKey!==1 && $tdKey!==2) {
								$a = $tds->item($tdKey)->getElementsByTagName('a');
								$href = $a->item(0) ? $a->item(0)->getAttribute('href') : '';
								if ($tdKey==3) {
									$href = str_replace(array('<td align="right">','</td>'),'',$dom->saveXML($tds->item($tdKey))); //全取作者栏html
								}
								$urls[] = trim($href);
							}
						}
					}
				}
				$texts = array_values($texts);
				$urls = array_chunk($urls,3);

				//合并关联键名
				$keys = array('pluginName','desc','version','mark','pluginUrl','authorHtml','zipFile');
				$names = array();
				$vals = array();
				$datas = array();
				foreach ($texts as $key=>$val) {
					$names[] = isset($val[0]) ? $val[0] : $val[1]; //fix for PHP 7.0+
					$vals = array_values(array_filter($val));
					unset($vals[3]); //去除作者栏text
					$datas[] = array_combine($keys,array_merge($vals,$urls[$key]));
				}
				//按插件名排序
				array_multisort($names,SORT_ASC,$datas);

				$pluginInfo = $datas;
			}

			//生成缓存文件
			if ($pluginInfo && $cacheTime) {
				if (!is_dir($cacheDir)) {
					$this->makedir($cacheDir);
				}
				file_put_contents($cacheFile,Json::encode($pluginInfo));
			}
		}

		return $pluginInfo;
	}

	/**
	 * 输出插件列表页面
	 * 
	 * @access private
	 * @return void
	 */
	public function market()
	{
		//禁止非管理员访问
		$this->user->pass('administrator');

		include_once 'views/market.php';
	}

	/**
	 * 执行安装插件步骤
	 * 
	 * @access public
	 * @return void
	 */
	public function install()
	{
		$this->security->protect();
		//禁止非管理员访问
		$this->user->pass('administrator');

		$plugin = $this->request->plugin;
		$author = $this->request->author;
		$zip = $this->request->zip;
		$result = array(
			'status'=>false,
			'error'=>_t('没有找到插件文件')
		);

		if ($zip) {
			//检测是否已启用
			$activated = $this->getActivePlugins();
			if (!empty($activated) && in_array($plugin,$activated)) {
				$result['error'] = _t('请先禁用此插件');
			} else {
				$tempDir = $this->pluginRoot.'/TeStore/.tmp';
				$tempFile = $tempDir.'/'.$plugin.'.zip';
				if (is_dir($tempDir)) {
					@$this->delTree($tempDir,true); //清理临时目录
				} else {
					$this->makedir($tempDir); //创建临时目录
				}
				$proxy = $this->settings->proxy;
				//替换为加速地址
				if ($proxy || strpos($zip,'raw.githubusercontent.com')  || strpos($zip,'raw/master')) {
					$cdn = $this->ZIP_CDN($plugin,$author);
					$zip = $cdn ? $cdn : $zip;
					$proxy = $proxy ? $proxy : 'cdn.jsdelivr.net/gh';
					$zip = str_replace(array('github.com','raw.githubusercontent.com'),$proxy,$zip);
					$zip = $proxy=='cdn.jsdelivr.net/gh' ? str_replace(array('blob/','raw/','master/'),'',$zip) : str_replace(array('blob/','raw/'),'',$zip);
				}
				//下载至临时目录
				$zipFile = $this->useCurl ? $this->curlGet($zip) : @file_get_contents($zip,0,
					stream_context_create(array('http'=>array('timeout'=>20)))); //设20秒超时
				if (!$zipFile) {
					$result['error'] = _t('下载压缩包出错');
				} else {
					if (strpos($zipFile,'404')===0 || strpos($zipFile,'Couldn\'t find')===0) {
						$result['error'] = _t('未找到下载文件');
						@unlink($tempFile);
					} else {
						file_put_contents($tempFile,$zipFile);
						$phpZip = new ZipArchive();
						$open = $phpZip->open($tempFile,ZipArchive::CHECKCONS);
						if ($open!==true) {
							$result['error'] = _t('压缩包校验错误');
							@unlink($tempFile);
						} else {
							//解压至临时目录
							if (!$phpZip->extractTo($tempDir)) {
								$error = error_get_last();
								$result['error'] = $error['message'];
							} else {
								$phpZip->close();
								@unlink($tempFile); //删除已解压包

								//遍历各文件层级
								foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir)) as $fileName) {
									if (!is_dir($fileName)) {
										$tmpRoutes[] = $fileName;
									}
								}

								//定位Plugin.php
								$trueDir = '';
								$parentDir = '';
								foreach ($tmpRoutes as $tmpRoute) {
									if (!strcasecmp(basename($tmpRoute),'Plugin.php')) {
										$trueDir = dirname($tmpRoute);
										$parentDir = dirname($trueDir);
									}
								}

								//处理目录型插件
								if ($trueDir) {
									$pluginDir = $this->pluginRoot.'/'.$plugin;
									if (is_dir($pluginDir)) {
										@$this->delTree($pluginDir,true); //清理旧版残留
									}
									foreach ($tmpRoutes as $tmpRoute) {
										//按文件路径创建目录
										$fileDir = $parentDir==$tempDir ? $tempDir : $parentDir;
										$tarRoute = str_replace((strpos($tmpRoute,$trueDir)===0 ? $trueDir : $fileDir),
											$pluginDir,$tmpRoute);
										$tarDir = dirname($tarRoute);
										if (!is_dir($tarDir)) {
											$this->makedir($tarDir);
										}
										//移动文件到各层目录
										if (!rename($tmpRoute,$tarRoute)) {
											$error = error_get_last();
											$result['error'] = $error['message'];
										}
									}
									$result['status'] = true;

								//处理单文件型插件
								} elseif (count($tmpRoutes)<=2) {
									foreach ($tmpRoutes as $tmpRoute) {
										$name = basename($tmpRoute);
										if ($name==$plugin.'.php') {
											//移动文件到根目录
											if (!rename($tmpRoute,$this->pluginRoot.'/'.$name)) {
												$result['error'] = _t('移动文件出错');
											} else {
												$result['status'] = true;
											}
										}
									}
								}

								//清空临时目录
								@$this->delTree($tempDir,true);
							}
						}
					}
				}
			}
		}

		//返回提示信息
		if ($result['status']) {
			$this->widget('Widget_Notice')->highlight('plugin-'.$plugin);
			$this->widget('Widget_Notice')->set(_t('安装插件 %s 成功, 可以在下方启用',$plugin),'success');
			$this->response->redirect($this->options->adminUrl.'plugins.php#plugin-'.end($activated));
		} else {
			$this->widget('Widget_Notice')->set(_t('安装插件 %s 失败: %s',$plugin,$result['error']),'error');
			$this->response->goBack();
		}
	}

	/**
	 * 执行卸载插件步骤
	 * 
	 * @access public
	 * @return void
	 */
	public function uninstall()
	{
		$this->security->protect();
		//禁止非管理员访问
		$this->user->pass('administrator');

		$plugin  = $this->request->plugin;
		$result = array(
			'status'=>false,
			'error'=>_t('移除文件出错')
		);

		if ($this->getLocalInfos($plugin)) {
			$activated = $this->getActivePlugins();
			//已启用则自动禁用
			if (!empty($activated) && in_array($plugin,$activated)) {
				Helper::removePlugin($plugin);
			}

			$pluginDir = $this->pluginRoot.'/'.$plugin;
			//清空目录型插件
			if (is_dir($pluginDir)) {
				if (!@$this->delTree($pluginDir)) {
					$error = error_get_last();
					$result['error'] = $error['message'];
				} else {
					$result['status'] = true;
				}
			//删除单文件插件
			} else {
				@unlink($pluginDir.'.php');
				$result['status'] = true;
			}
		}

		//返回提示信息
		if ($result['status']) {
			$this->widget('Widget_Notice')->set(_t('删除插件 %s 成功',$plugin),'success');
		} else {
			$this->widget('Widget_Notice')->set(_t('删除插件 %s 失败: %s',$plugin,$result['error']),'error');
		}
		$this->response->goBack();
	}

	/**
	 * 检测可加速zip地址
	 * 
	 * @access public
	 * @param string $name 插件名称
	 * @param string $author 作者名称
	 * @return string
	 */
	public function ZIP_CDN($name='',$author='')
	{
		$datas = array();
		$cacheDir = $this->pluginRoot.'/TeStore/data/';
		$cacheFile = $cacheDir.'zip_cdn.json';
		$cacheTime = $this->settings->cache_time;

		//读取缓存文件
		if ($cacheTime && is_file($cacheFile) && (time()-filemtime($cacheFile))<=$cacheTime*3600) {
			$data = file_get_contents($cacheFile);
			$datas = Json::decode($data,true);
		//读取API数据
		} else {
			$api = 'https://api.github.com/repositories/14101953/contents/ZIP_CDN';
			$data = $this->useCurl ? $this->curlGet($api) : @file_get_contents($api,0,
				stream_context_create(array('http'=>array('header'=>array('User-Agent: PHP'),'timeout'=>20)))); //API要求header
			if ($data) {
				$datas = Json::decode($data,true);
				//生成缓存文件
				if ($cacheTime) {
					if (!is_dir($cacheDir)) {
						$this->makedir($cacheDir);
					}
					file_put_contents($cacheFile,$data);
				}
			}
		}

		$zip = '';
		if ($name && $author) {
			foreach ($datas as $data) {
				if ($data['name']==$name.'_'.$author.'.zip') { //带作者名优先
					$zip = $data['download_url'];
				} elseif ($data['name']==$name.'.zip') {
					$zip = $data['download_url'];
				}
			}
		}

		return $zip;
	}

	/**
	 * 递归创建本地目录
	 * 
	 * @access private
	 * @param string $path 目录路径
	 * @return boolean
	 */
	private function makedir($path)
	{
		$path = preg_replace('/\\\+/','/',$path);
		$current = rtrim($path,'/');
		$last = $current;

		while (!is_dir($current) && false!==strpos($path,'/')) {
			$last = $current;
			$current = dirname($current);
		}
		if ($last==$current) {
			return true;
		}
		if (!@mkdir($last)) {
			return false;
		}

		$stat = @stat($last);
		$perms = $stat['mode'] & 0007777;
		@chmod($last,$perms);

		return $this->makedir($path);
	}

	/**
	 * 清空目录内文件
	 * 
	 * @access private
	 * @param string $folder 目录路径
	 * @param boolean $keep 保留目录
	 * @return boolean
	 */
	private function delTree($folder,$keep=false) {
		$files = array_diff(scandir($folder),array('.','..'));
		foreach ($files as $file) {
			$path = $folder.'/'.$file;
			is_dir($path) ? $this->delTree($path) : unlink($path);
		}
		return $keep ? true : rmdir($folder);
	}

	/**
	 * 使用cURL方法下载
	 * 
	 * @access private
	 * @return string
	 */
	private function curlGet($url) {
		$curl = curl_init();

		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl,CURLOPT_HEADER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,1);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,2);
		curl_setopt($curl,CURLOPT_CAINFO,'usr/plugins/TeStore/data/cacert.pem'); //证书识别库
		curl_setopt($curl,CURLOPT_TIMEOUT,30); //设30秒超时
		curl_setopt($curl,CURLOPT_URL,$url);

		$result = curl_exec($curl);
		curl_close($curl);

		return $result;
	}

}
