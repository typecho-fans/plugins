<?php
	/**
	 * Typecho-Fans/Plugins专用自动化升级插件脚本
	 * (非插件！供GitHub Actions功能调用，勿删改！)
	 * 作者：羽中
	 * 反馈：https://github.com/typecho-fans/plugins/issues
	 */

	date_default_timezone_set('Asia/Shanghai');

	//预设循环内变量
	$desciptions = array();
	$links = array();
	$metas = array();
	$url = '';
	$all = 0;
	$authorCode = '';
	$separator = '';
	$authors = array();
	$authorName = '';
	$authorNames = array();
	$name = array();
	$doc = false;
	$sub = false;
	$paths = array();
	$api = '';
	$datas = array();
	$path = '';
	$pluginFile = '';
	$logs = '--------------'.PHP_EOL.date('Y-m-d',time()).PHP_EOL;
	$infos = array();
	$version = '';
	$update = 0;
	$zip = '';
	$download = '';
	$tmpName = '';
	$tmpZip = '';
	$tmpSub = '';
	$phpZip = (object)array();
	$master = '';
	$plugin = '';
	$codes = '';
	$renamed = '';
	$cdn = '';
	$rootPath = '';
	$filePath = '';
	$status = 'failed';
	$done = 0;
	$tables = array();

	//创建临时文件夹
	$tmpDir = realpath('../').'/TMP';
	$tmpNew = $tmpDir.'/NEW';
	if (!is_dir($tmpDir)) {
		mkdir($tmpDir);
	}
	if (!is_dir($tmpNew)) {
		mkdir($tmpNew); //(用于Action检测其中重新打包文件发布至标签)
	}

	//分割文档遍历单行
	$source = file_get_contents('TESTORE.md');
	$lines = explode(PHP_EOL,$source);
	$count = count($lines);
	foreach ($lines as $line=>$column) {
		if ($line<38) {
			$desciptions[] = $column;
		} else {
			preg_match_all('/(?<=\()[^\)]+/',$column,$links);
			preg_match_all('/(?<=)[^\|]+/',$column,$metas);

			if ($column) {
				$url = $links['0']['0'];
				//仅处理GitHub仓库
				if (empty($argv['1']) ? strpos($url,'github.com') : (strpos($argv['1'],'github.com') && $argv['1']==$url)) { //兼容手动参数
					++$all;

					//获取插件主文件地址
					preg_match('/(?<=\[)[^\]]+/',$metas['0']['0'],$name);
					$doc = strpos($url,'/blob/master/') && strpos($url,'.php');
					if (!$doc) {
						$sub = strpos($url,'/tree/master/');
						if ($sub) {
							$paths = explode('/tree/master/',$url);
							$url = $paths['0'];
						}
						$api = @file_get_contents(str_replace('github.com','api.github.com/repos',$url).'/git/trees/master?recursive=1',0,
							stream_context_create(array('http'=>array('header'=>array('User-Agent: PHP')))));
						if ($api) {
							$datas = json_decode($api,true);
							foreach ($datas['tree'] as $tree) {
								if (false!==stripos($tree['path'],($sub ? $name['0'].'/Plugin.php' : 'Plugin.php'))) {
									$path = $tree['path'];
									break;
								}
							}
							$pluginFile = $path ? $url.'/raw/master/'.$path : $url.'/raw/master/'.($sub ? $paths['1'].'/' : '').$name['0'].'.php';
						} else {
							$logs .= 'Error: "'.$url.'" not found!'.PHP_EOL;
						}
					} else {
						$pluginFile = str_replace('blob','raw',$url);
						$paths = explode('/raw/master/',$pluginFile);
						$url = $paths['0'];
					}

					//对比文件版本号更新
					if ($pluginFile) {
						$infos = call_user_func('parseInfo',$pluginFile);
						if ($infos['version']) {
							$version = stripos($metas['0']['2'],'v')===0 ? trim(substr($metas['0']['2'],1)) : trim($metas['0']['2']);
							if ($infos['version']>$version || !empty($argv['1'])) { //或手动强制更新
								++$update;
								$zip = end($links['0']);
								$cdn = 'ZIP_CDN/'.$name['0'].'_'.$infos['author'].'.zip';

								//标签下载的要重新打包
								if (strpos($zip,'typecho-fans/plugins/releases/download')) {
									
									$download = @file_get_contents($url.'/archive/master.zip');
									if ($download) {
										$tmpName = '/'.$all.'_'.$name['0'];
										$tmpZip = $tmpDir.$tmpName.'_master.zip';
										file_put_contents($tmpZip,$download);

										//解压缩master包
										$phpZip = new ZipArchive();
										$phpZip->open($tmpZip);
										$tmpSub = $tmpDir.$tmpName;
										mkdir($tmpSub);
										$phpZip->extractTo($tmpSub);
										$master = $tmpSub.'/'.basename($url).'-master/';

										//提取多作者名
										$authorCode = html_entity_decode(trim($metas['0']['3']));
										switch (true) {
											case (strpos($authorCode,',')) :
											$separator = ',';
											break;
											case (strpos($authorCode,', ')) :
											$separator = ', ';
											break;
											case (strpos($authorCode,'&')) :
											$separator = '&';
											break;
											case (strpos($authorCode,' & ')) :
											$separator = ' & ';
											break;
										}
										if ($separator) {
											$authors = explode($separator,$authorCode);
											$authorName = '';
											foreach ($authors as $key=>$author) {
												preg_match('/(?<=\[)[^\]]+/',$author,$authorName);
												$authorNames[] = empty($authorName['0']) ? $author : $authorName['0'];
											}
											$authorName = implode($separator,$authorNames);
										} else {
											$authorName = '';
											preg_match('/(?<=\[)[^\]]+/',$authorCode,$authorName);
											$authorName = $authorName['0'];
										}
										//强制替换作者名
										$renamed = '';
										if ($authorName!==trim(strip_tags($infos['author']))) {
											$plugin = $master.($doc ? $paths['1'] : $path);
											$codes = file_get_contents($plugin);
											file_put_contents($plugin,str_replace($infos['author'],$authorName,$codes));
											$renamed = '/ Rename Author ';
										}

										//压缩包命名处理 (标签发布不支持中文)
										$newZip = $tmpNew.'/'.$name['0'].'_';
										for ($j=$line+1;$j<$count;++$j) {
											if ($lines[$j]) {
												preg_match_all('/(?<=)[^\|]+/',$lines[$j],$reMetas);
												preg_match('/(?<=\[)[^\]]+/',$reMetas['0']['0'],$reName);
												//重名继续增加下划线
												if (!strcasecmp($reName['0'],$name['0'])) {
													$newZip .= '_';
												}
											}
										}
										$newZip = $newZip.'.zip';
										//打包至临时目录
										$phpZip->open($newZip,ZipArchive::CREATE | ZipArchive::OVERWRITE);
										if (!$doc) {
											$rootPath = $master.($sub ? $paths['1'].'/' : '');
											foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath)) as $file) {
												if (!$file->isDir()) {
													$filePath = $file->getRealPath();
													$phpZip->addFile($filePath,$name['0'].'/'.substr($filePath,strlen($rootPath)));
												}
											}
										} else {
											$phpZip->addFile($master.$paths['1'],$paths['1']);
										}

										//复制一份到加速目录
										if ($phpZip->close() && @copy($newZip,$cdn)) {
											$column = str_replace($zip,dirname($zip).'/'.basename($newZip),$column);
											$status = 'succeeded';
											++$done;
										}
										$logs .= $name['0'].' - '.date('Y-m-d H:i',time()).' - RE-ZIP '.$renamed.$status.PHP_EOL;
									} else {
										$logs .= 'Error: "'.$url.'" not found!'.PHP_EOL;
									}

								//其他仅下载至加速目录
								} else {
									$download = @file_get_contents($zip);
									if ($download) {
										if (@file_put_contents($cdn,$download)) {
											$status = 'succeeded';
											++$done;
										}
										$logs .= $name['0'].' - '.date('Y-m-d H:i',time()).' - '.$status.PHP_EOL;
									} else {
										$logs .= 'Error: "'.$zip.'" not found!'.PHP_EOL;
									}
								}

								//更新文档信息版本号
								if ($status=='succeeded') {
									$column = str_replace($version,$infos['version'],$column);
								}
							}
						} else {
							$logs .= 'Error: "'.$url.'" has no valid plugin file!'.PHP_EOL;
						}
					}
				}
			}
			$tables[] = $column;
		}
	}

	//重组文档并生成日志
	file_put_contents('TESTORE.md',implode(PHP_EOL,$desciptions).PHP_EOL.implode(PHP_EOL,$tables));
	file_put_contents($tmpDir.'/updates.log',$logs.
		'SCANED: '.$all.PHP_EOL.
		'NEED UPDATE: '.$update.PHP_EOL.
		'DONE: '.$done.PHP_EOL);

	/**
	 * 获取插件文件的头信息 (Typecho)
	 *
	 * @param string $pluginFile 插件文件路径
	 * @return array
	 */
	function parseInfo($pluginFile)
	{
		$tokens = token_get_all(file_get_contents($pluginFile));
		$isDoc = false;
		$isFunction = false;
		$isClass = false;
		$isInClass = false;
		$isInFunction = false;
		$isDefined = false;
		$current = NULL;

		/** 初始信息 */
		$info = array(
			'description'	   => '',
			'title'			 => '',
			'author'			=> '',
			'homepage'		  => '',
			'version'		   => '',
			'dependence'		=> '',
			'activate'		  => false,
			'deactivate'		=> false,
			'config'			=> false,
			'personalConfig'	=> false
		);

		$map = array(
			'package'   =>  'title',
			'author'	=>  'author',
			'link'	  =>  'homepage',
			'dependence'=>  'dependence',
			'version'   =>  'version'
		);

		foreach ($tokens as $token) {
			/** 获取doc comment */
			if (!$isDoc && is_array($token) && T_DOC_COMMENT == $token[0]) {

				/** 分行读取 */
				$described = false;
				$lines = preg_split("(\r|\n)", $token[1]);
				foreach ($lines as $line) {
					$line = trim($line);
					if (!empty($line) && '*' == $line[0]) {
						$line = trim(substr($line, 1));
						if (!$described && !empty($line) && '@' == $line[0]) {
							$described = true;
						}

						if (!$described && !empty($line)) {
							$info['description'] .= $line . "\n";
						} else if ($described && !empty($line) && '@' == $line[0]) {
							$info['description'] = trim($info['description']);
							$line = trim(substr($line, 1));
							$args = explode(' ', $line);
							$key = array_shift($args);

							if (isset($map[$key])) {
								$info[$map[$key]] = trim(implode(' ', $args));
							}
						}
					}
				}

				$isDoc = true;
			}

			if (is_array($token)) {
				switch ($token[0]) {
					case T_FUNCTION:
						$isFunction = true;
						break;
					case T_IMPLEMENTS:
						$isClass = true;
						break;
					case T_WHITESPACE:
					case T_COMMENT:
					case T_DOC_COMMENT:
						break;
					case T_STRING:
						$string = strtolower($token[1]);
						switch ($string) {
							case 'typecho_plugin_interface':
								$isInClass = $isClass;
								break;
							case 'activate':
							case 'deactivate':
							case 'config':
							case 'personalconfig':
								if ($isFunction) {
									$current = ('personalconfig' == $string ? 'personalConfig' : $string);
								}
								break;
							default:
								if (!empty($current) && $isInFunction && $isInClass) {
									$info[$current] = true;
								}
								break;
						}
						break;
					default:
						if (!empty($current) && $isInFunction && $isInClass) {
							$info[$current] = true;
						}
						break;
				}
			} else {
				$token = strtolower($token);
				switch ($token) {
					case '{':
						if ($isDefined) {
							$isInFunction = true;
						}
						break;
					case '(':
						if ($isFunction && !$isDefined) {
							$isDefined = true;
						}
						break;
					case '}':
					case ';':
						$isDefined = false;
						$isFunction = false;
						$isInFunction = false;
						$current = NULL;
						break;
					default:
						if (!empty($current) && $isInFunction && $isInClass) {
							$info[$current] = true;
						}
						break;
				}
			}
		}

		return $info;
	}