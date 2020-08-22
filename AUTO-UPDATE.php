<?php
	/**
	 * Typecho-Fans/Plugins专用自动化更新插件信息与zip包脚本
	 * (非Typecho插件，仅供GitHub Actions功能调用，勿删改！)
	 * 作者：羽中
	 * 反馈：https://github.com/typecho-fans/plugins/issues
	 */
	date_default_timezone_set('Asia/Shanghai');

	//创建临时文件夹
	$tmpDir = realpath('../').'/TMP';
	$tmpNew = $tmpDir.'/NEW';
	if (!is_dir($tmpDir)) {
		mkdir($tmpDir);
	}
	if (!is_dir($tmpNew)) {
		mkdir($tmpNew);
	}

	//分析最新文档变化
	if (!empty($argv['2']) && strpos($argv['2'],'.diff')) {
		$record = @file_get_contents($argv['2'],0,
			stream_context_create(array('http'=>array('header'=>array('Accept: application/vnd.github.v3.diff')))));
		$diffs = explode(PHP_EOL,$record);

		//确定行范围
		$begin = 0;
		$end = count($diffs)-1;
		foreach ($diffs as $line=>$diff) {
			if ($diff=='+++ b/TESTORE.md') {
				$begin = $line;
			}
			if ($begin && $line>$begin && strpos($diff,'diff --git')===0) {
				$end = $line;
				break;
			}
		}
		//提取变化行
		$links = array();
		$urls = array();
		foreach ($diffs as $line=>$diff) {
			if ($begin && $line>$begin && $line<$end && strpos($diff,'+[')===0) {
				preg_match_all('/(?<=\()[^\)]+/',$diff,$links);
				$urls[] = $links['0']['0'];
			}
		}
	}

	//预设循环内变量
	$desciptions = array();
	$links = array();
	$metas = array();
	$url = '';
	$github = false;
	$condition = false;
	$all = 0;
	$authorCode = '';
	$separator = '';
	$authors = array();
	$authorName = array();
	$authorNames = array();
	$author = '';
	$name = array();
	$doc = false;
	$main = false;
	$sub = false;
	$paths = array();
	$api = '';
	$detect = true;
	$datas = array();
	$path = '';
	$pluginFile = '';
	$logs = '--------------'.PHP_EOL.date('Y-m-d',time()).PHP_EOL;
	$infos = array();
	$match = array();
	$version = '';
	$update = 0;
	$zip = '';
	$repoZip = '';
	$download = '';
	$tmpName = '';
	$tmpZip = '';
	$tmpSub = '';
	$phpZip = (object)array();
	$pluginFolder = '';
	$plugin = '';
	$renamed = '';
	$cdn = '';
	$rootPath = '';
	$filePath = '';
	$status = 'failed';
	$done = 0;
	$tables = array();

	//开始分割文档循环
	$source = file_get_contents('TESTORE.md');
	$lines = explode(PHP_EOL,$source);
	$count = count($lines);
	foreach ($lines as $line=>$column) {
		if ($line<38) {
			if ($line=='29') {
				preg_match('/(?<=\()[^\)]+/',$column,$counts);
				$column = str_replace($counts['0'],$count-39,$column);
			}
			$desciptions[] = $column;
		} elseif ($column) {
			preg_match_all('/(?<=\()[^\)]+/',$column,$links);
			preg_match_all('/(?<=)[^\|]+/',$column,$metas);
			$url = $links['0']['0'];
			$github = strpos($url,'github.com');

			//判断地址参数
			if (empty($argv['2'])) { //默认处理GitHub源
				$condition = $github;
			} elseif (strpos($argv['2'],'.diff')) { //提交处理变化地址 (不限GitHub)
				$condition = $urls && in_array($url,$urls);
			} else { //手动处理参数指定
				$condition = strpos($argv['2'],'github.com') && $argv['2']==$url;
			}
			if ($condition) {
				++$all;
				preg_match('/(?<=\[)[^\]]+/',$metas['0']['0'],$name);

				if ($github) {
					//取插件主文件地址
					$doc = (strpos($url,'/blob/master/') || strpos($url,'/blob/main/')) && strpos($url,'.php');
					//单文件情况
					if ($doc) {
						$detect = false;
						$pluginFile = str_replace('blob','raw',$url); //直接确定地址
						$paths = explode((strpos($url,'/raw/main/') ? '/raw/main/' : '/raw/master/'),$pluginFile);
						$url = $paths['0']; //提取仓库路径
					} else {
						$main = strpos($url,'/tree/main/');
						$sub = strpos($url,'/tree/master/') || $main;
						//子目录情况
						if ($sub) {
							$paths = explode(($main ? '/tree/main/' : '/tree/master/'),$url);
							$url = $paths['0']; //提取仓库路径
						}

						//查询仓库文件结构
						if (!$main) {
							$api = @file_get_contents(str_replace('github.com','api.github.com/repos',$url).'/git/trees/master?recursive=1&access_token='.$argv['1'],0,
								stream_context_create(array('http'=>array('header'=>array('User-Agent: PHP')))));
						}
						//兼容main分支名
						if (!$api || $main) {
							$api = @file_get_contents(str_replace('github.com','api.github.com/repos',$url).'/git/trees/main?recursive=1&access_token='.$argv['1'],0,
							stream_context_create(array('http'=>array('header'=>array('User-Agent: PHP')))));
							if ($api && !$main) {
								$main = true;
							}
						}

						$detect = true;
						$pluginFile = $url.($main ? '/raw/main/' : '/raw/master/').($sub ? $paths['1'].'/' : ''); //默认确定前缀
						if ($api) {
							$datas = json_decode($api,true);
							//查找主文件路径
							foreach ($datas['tree'] as $tree) {
								$path = '';
								if (false!==strpos($tree['path'],($sub ? $name['0'].'/Plugin.php' : 'Plugin.php'))) {
									$path = $tree['path'];
									break;
								}
							}
							//找到拼接出地址
							if ($path) {
								$detect = false;
								$pluginFile = $url.($main ? '/raw/main/' : '/raw/master/').$path;
							}
						}
					}

					//从主文件提取信息
					$infos = call_user_func('parseInfo',($detect ? $pluginFile.'Plugin.php' : $pluginFile));
					//单文件情况
					if (!$infos['version'] && $detect) {
						$infos = call_user_func('parseInfo',$pluginFile.$name['0'].'.php');
					}

					if ($infos['version']) {
						//提取版本号
						if (preg_match('/\d+(.\d+)*/',trim(strip_tags($infos['version'])),$match)) {
							$infos['version'] = $match['0'];
						}
						$version = stripos($metas['0']['2'],'v')===0 ? trim(substr($metas['0']['2'],1)) : trim($metas['0']['2']);
					} else {
						$logs .= 'Error: "'.$pluginFile.'" not valid!'.PHP_EOL;
					}
				}

				//对比版本号判断更新
				if ($infos['version'] && $infos['version']>$version || !empty($argv['2'])) { //或有参数即可
					++$update;
					$zip = end($links['0']);

					//准备作者名
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
					//多作者情况
					if ($separator) {
						$authors = explode($separator,$authorCode);
						$authorNames = array();
						foreach ($authors as $key=>$author) {
							preg_match('/(?<=\[)[^\]]+/',$author,$authorName);
							$authorNames[] = empty($authorName['0']) ? $author : $authorName['0'];
						}
						$author = implode($separator,$authorNames);
					//单作者情况
					} else {
						$author = '';
						preg_match('/(?<=\[)[^\]]+/',$authorCode,$authorName);
						$author = empty($authorName['0']) ? $authorCode : $authorName['0'];
					}
					//命名zip包 (加速目录用)
					$cdn = 'ZIP_CDN/'.$name['0'].'_'.($separator ? implode('_',$authorNames) : $author).'.zip';

					//标签发布的需重新打包
					if ($github && strpos($zip,'typecho-fans/plugins/releases/download')) {
						$repoZip = $url.'/archive/'.($main ? 'main' : 'master').'.zip';
						$download = @file_get_contents($repoZip);
						if ($download) {
							$tmpName = '/'.$all.'_'.$name['0'];
							$tmpZip = $tmpDir.$tmpName.'_master.zip';
							//下载实时zip
							file_put_contents($tmpZip,$download);

							//解压实时zip
							$phpZip = new ZipArchive();
							$phpZip->open($tmpZip);
							$tmpSub = $tmpDir.$tmpName;
							mkdir($tmpSub);
							$phpZip->extractTo($tmpSub);
							$pluginFolder = $tmpSub.'/'.basename($url).($main ? '-main/' : '-master/');

							//替换作者名
							$renamed = '';
							if (!empty($infos['author']) && trim(strip_tags($infos['author']))!==$author) {
								$plugin = $pluginFolder.($doc ? $paths['1'] : $path);
								file_put_contents($plugin,str_replace($infos['author'],$author,file_get_contents($plugin)));
								$renamed = '/ Rename Author ';
							}

							//命名zip包 (标签发布用)
							$newZip = $tmpNew.'/'.$name['0'].'_'; //因不支持中文仅用下划线
							for ($j=$line+1;$j<$count;++$j) {
								if ($lines[$j]) {
									preg_match_all('/(?<=)[^\|]+/',$lines[$j],$reMetas);
									preg_match('/(?<=\[)[^\]]+/',$reMetas['0']['0'],$reName);
									//重名增加下划线
									if (!strcasecmp($reName['0'],$name['0'])) {
										$newZip .= '_';
									}
								}
							}
							$newZip = $newZip.'.zip';

							//打包至临时目录
							$phpZip->open($newZip,ZipArchive::CREATE | ZipArchive::OVERWRITE);
							if ($doc) {
								$phpZip->addFile($pluginFolder.$paths['1'],$paths['1']);
							} else {
								$rootPath = $pluginFolder.($sub ? $paths['1'].'/' : '');
								foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath)) as $file) {
									if (!$file->isDir()) {
										$filePath = $file->getRealPath();
										$phpZip->addFile($filePath,$name['0'].'/'.substr($filePath,strlen($rootPath)));
									}
								}
							}

							//复制至加速目录
							if ($phpZip->close() && @copy($newZip,$cdn)) {
								//更新文档下载地址
								$column = str_replace($zip,dirname($zip).'/'.basename($newZip),$column);
								$status = 'succeeded';
								++$done;
							}
							//完成处理记录日志
							$logs .= $name['0'].' - '.date('Y-m-d H:i',time()).' - RE-ZIP '.$renamed.$status.PHP_EOL;
						} else {
							$logs .= 'Error: "'.$repoZip.'" not found!'.PHP_EOL;
						}

					//其他仅下载至加速目录
					} else {
						$download = @file_get_contents($zip);
						if ($download) {
							if (@file_put_contents($cdn,$download)) {
								$status = 'succeeded';
								++$done;
							}
							//完成处理记录日志
							$logs .= $name['0'].' - '.date('Y-m-d H:i',time()).' - '.$status.PHP_EOL;
						} else {
							$logs .= 'Error: "'.$zip.'" not found!'.PHP_EOL;
						}
					}

					//更新文档版本号记录
					if ($github && $infos['version'] && $status=='succeeded') {
						$column = str_replace($version,$infos['version'],$column);
					}
				}
			}
			$tables[] = $column;
		}
	}
	//按插件名排序
	sort($tables);

	//重组文档并生成日志
	file_put_contents('TESTORE.md',implode(PHP_EOL,$desciptions).PHP_EOL.implode(PHP_EOL,$tables).PHP_EOL);
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
		$tokens = token_get_all(@file_get_contents($pluginFile));
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