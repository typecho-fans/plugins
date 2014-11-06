<?php
header('Content-Type: text/html; charset=UTF-8');


function uploadfile($inputname)
{
	$immediate = isset($_GET['immediate']) ? $_GET['immediate']:0;
	$attachdir = 'upload';//上传文件保存路径，结尾不要带/
	$dirtype = 1; //1:按天存入目录 2:按月存入目录 3:按扩展名存目录  建议使用按天存
	$maxattachsize = 2097152;//最大上传大小，默认是2M
	$upext = 'txt,rar,zip,jpg,jpeg,gif,png,swf,wmv,avi,wma,mp3,mid';//上传扩展名
	$msgtype = 2;//返回上传参数的格式：1，只返回url，2，返回参数数组
	
	$err = "";
	$msg = "";
	if(!isset($_FILES[$inputname]))return array('err'=>'文件域的name错误或者没选择文件','msg'=>$msg);
	$upfile = $_FILES[$inputname];
	if(!empty($upfile['error']))
	{
		$err = getErrorByCode($upfile['error']);
	}
	elseif(empty($upfile['tmp_name']) || $upfile['tmp_name'] == 'none')$err = '无文件上传';
	else
	{
		$fileinfo = pathinfo($upfile['name']);
		$extension = strtolower($fileinfo['extension']);
		if(preg_match('/'.str_replace(',', '|', $upext).'/i',$extension))
		{
			$filesize = $upfile['size'];
			if($filesize > $maxattachsize)$err='文件大小超过'.$maxattachsize.'字节';
			else
			{
				$year = date('Y');
				$day = date('md');
				$n = time().rand(1000,9999).'.jpg';
				
				$realpath = realpath('.');
				$attach_dir = substr($realpath,0,strpos($realpath,'usr')+3)."/uploads/{$year}/{$day}";
				recursiveMkdir($attach_dir);
				$fname= time().rand(1000,9999).'.'.$extension;
				$target = $attach_dir.'/'.$fname;
				if ( is_resource($upfile['tmp_name']) ) {
					$data = fread($upfile['tmp_name'], $filesize);
					file_put_contents($target, $data);
					fclose($upfile['tmp_name']);
				} else {
					move_uploaded_file($upfile['tmp_name'], $target);
					@unlink($upfile['tmp_name']);
				}
				$target ="/usr/uploads/{$year}/{$day}/{$fname}";
				if($immediate=='1')$target='!'.$target;
				if($msgtype==1)$msg=$target;
				else $msg=array('url'=>$target,'localname'=>$upfile['name'],'id'=>'1');//id参数固定不变，仅供演示，实际项目中可以是数据库ID
			}
		}
		else $err='上传文件扩展名必需为：'.$upext;

		if (is_resource($upfile['tmp_name'])) {fclose($upfile['tmp_name']);}
		else { @unlink($upfile['tmp_name']); }
	}
	return array('err'=>$err,'msg'=>$msg);
}

function getErrorByCode($code)
{
	switch($code)
	{
		case '1':
			$err = '文件大小超过了php.ini定义的upload_max_filesize值';
			break;
		case '2':
			$err = '文件大小超过了HTML定义的MAX_FILE_SIZE值';
			break;
		case '3':
			$err = '文件上传不完全';
			break;
		case '4':
			$err = '无文件上传';
			break;
		case '6':
			$err = '缺少临时文件夹';
			break;
		case '7':
			$err = '写文件失败';
			break;
		case '8':
			$err = '上传被其它扩展中断';
			break;
		case '999':
		default:
			$err = '无有效错误代码';
	}
	return $err;
}

function recursiveMkdir($path) {
	if (!file_exists($path)) {
		recursiveMkdir(dirname($path));
		@mkdir($path, 0777);
	}
}

$rootDir = strstr( dirname(__FILE__), 'usr', TRUE );
require_once $rootDir . 'var/Typecho/Common.php';
require_once $rootDir . 'var/Typecho/Request.php';

$state = uploadfile('upload');
if( $state['err'] ){
	echo $state['err'];
}else{
	echo sprintf("<script type='text/javascript'>console.log(window.parent.CKEDITOR);window.parent.CKEDITOR.tools.callFunction(1, '%s', '');</script>", 
		Typecho_Request::getInstance()->getUrlPrefix() . $state['msg']['url']);
}
