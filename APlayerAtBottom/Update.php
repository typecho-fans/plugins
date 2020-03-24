<?php
	echo('<title>APlayerAtBottom 增量更新</title>');
	$aab = json_decode(file_get_contents('https://api.713.moe/version/aab.json'),true);
	$ver = $aab['ver'];
	$update = $aab['update'];
	$b_ver = isset($_GET["ver"]) ? $_GET["ver"] : "";
	echo('正在检测目前版本是否过期...<br>');
	if(empty($b_ver)) {
		echo('暂未检测到您目前版本的版本号，请重试！<br>');
		echo('<a href="/admin/plugins.php">>>> 点击返回后台 <<</a>');
	}elseif(file_exists($ver.'.lock') === true){
		echo('您已经是最新版本了，若需重新更新，请删除「'.$ver.'.lock」文件！<br>');
		echo('<a href="/admin/plugins.php">>>> 点击返回后台 <<</a>');
	}elseif($update === 'false'){
		echo('抱歉，新版本不提供增量更新，请前往github下载最新release！<br>');
		echo('戳：<a href="https://github.com/SatoSouta/APlayerAtBottom/releases/tag/'.$ver.'" target="_blank">https://github.com/SatoSouta/APlayerAtBottom/releases/tag/'.$ver.'</a>');
	}else{
		echo('您目前的版本是 '.$b_ver.' ，最新版本为 '.$ver.' -> ');
		if($b_ver < $ver) {
			echo('版本过期<br>');
			echo('开始更新...<br>');
			if(file_exists($b_ver.'.lock') === true){
				unlink($b_ver.'.lock');
			}
			echo('正在下载更新内容...<br>');
			$download_data = file_get_contents('https://api.713.moe/version/aab_download.json');
			$arr = json_decode($download_data,true);
			for($i=0;$i<count($arr);$i++) {
				$url = $arr[$i]['url'];
				$dir = $arr[$i]['dir'];
				$filename = $arr[$i]['filename'];
				$fileinfo = pathinfo($filename);
				$hz = $fileinfo['extension'];
				if(file_exists($url) === true){
					if($dir === '' && $filename === 'Plugin.php') {
						rename('Plugin.php','Plugin_'.$b_ver.'.php');
						$d_data = file_get_contents($url);
						file_put_contents('Plugin.php',$d_data);
						unlink('Plugin_'.$b_ver.'.php');
						echo('正在下载「'.$filename.'」...<br>');
					}elseif($hz === 'php'){
						$d_data = file_get_contents($url);
						file_put_contents($filename,$d_data);
						echo('正在下载「'.$filename.'」...<br>');
					}else{
						if(is_dir($dir) == false) {
							echo('发现：「'.$dir.'」文件夹不存在，创建中...<br>');
							mkdir($dir,0755,true);
							echo('成功：「'.$dir.'」文件夹创建完毕！<br>');
						}else{
							echo('成功：「'.$dir.'」文件夹存在，无需创建...<br>');
						}
						$d_data = file_get_contents($url);
						file_put_contents($dir.'/'.$filename,$d_data);
						echo('正在下载「'.$filename.'」到「'.$dir.'」...<br>');
					}
				}else{
					echo('文件「'.$filename.'」下载失败！<br>');
					$no = true;
				}
			}
			if($no === true){
				echo('部分内容下载失败，更新失败，请重试！若多次失败可以前往github下载最新版本~<br>');
			}else{
				echo('全部更新内容下载完毕！<br>');
				echo('更新完毕！您目前的版本是 '.$ver.' ，最新版本为 '.$ver.'<br/>');
				fopen($ver.'.lock',"w");
			}
			echo('<a href="/admin/plugins.php">>>> 点击返回后台 <<</a>');
		}elseif($b_ver === $ver){
			echo('您所使用的为最新版本！<br>');
			echo('更新停止<br>');
			echo('<a href="/admin/plugins.php">>>> 点击返回后台 <<</a>');
		}else{
			echo('您的版本比咱最新版本高了！<br>');
			echo('更新停止...<br>');
			echo('<a href="/admin/plugins.php">>>> 点击返回后台 <<</a>');
		}
	}
?>