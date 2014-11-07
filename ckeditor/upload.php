<?php
header('Content-Type: text/html; charset=UTF-8');

Class Upload{

    public static $attachdir = 'upload';    //上传文件保存路径，结尾不要带/
    public static $maxattachsize = 2097152; //最大上传大小，默认是2M
    public static $upext = 'txt,rar,zip,jpg,jpeg,gif,png,swf,wmv,avi,wma,mp3,mid';  //上传扩展名
    
    /**
     * 处理上传
     *
     * @access public
     * @return array
     */
    public static function uploadHandle($inputname)
    {
        $immediate = isset($_GET['immediate']) ? $_GET['immediate']:0;
        
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
            if(preg_match('/'.str_replace(',', '|', self::$upext).'/i',$extension))
            {
                $filesize = $upfile['size'];
                if($filesize > self::$maxattachsize)$err='文件大小超过'.self::$maxattachsize.'字节';
                else
                {
                    $attach_dir = self::getAttachDir();
                    recursiveMkdir($attach_dir);
                    $fname= time().rand(1000,9999).'.'.$extension;
                    $target = $attach_dir . '/' . $fname;
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
                    
                    $msg = array('url'=>$target,'localname'=>$upfile['name'],'id'=>'1');//id参数固定不变，仅供演示，实际项目中可以是数据库ID
                }
            }
            else $err='上传文件扩展名必需为：'.self::$upext;

            if (is_resource($upfile['tmp_name'])) {fclose($upfile['tmp_name']);}
            else { @unlink($upfile['tmp_name']); }
        }
        return array('err'=>$err,'msg'=>$msg);
    }

    public static function getAttachDir()
    {
        $year = date('Y');
        $day = date('md');
        $n = time() . rand(1000, 9999) . '.jpg';
        
        $realpath = realpath('.');
        return substr( $realpath , 0 , strpos($realpath, 'usr') + 3 ) . "/uploads/{$year}/{$day}";
    }

    /**
     * 根据错误号获取对应的消息
     *
     * @access public
     * @return string
     */
    public static function getErrorByCode($code)
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

    /**
     * 递归创建目录
     *
     * @access public
     * @return void
     */
    public static function recursiveMkdir($path)
    {
        if (!file_exists($path)) {
            recursiveMkdir(dirname($path));
            @mkdir($path, 0777);
        }
    }
}

$rootDir = strstr( dirname(__FILE__), 'usr', TRUE );
require_once $rootDir . 'var/Typecho/Common.php';
require_once $rootDir . 'var/Typecho/Request.php';

$state = Upload::uploadHandle('upload');
if( $state['err'] ){
    echo $state['err'];
}else{
    echo sprintf("<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction(1, '%s', '');</script>", 
        Typecho_Request::getInstance()->getUrlPrefix() . $state['msg']['url']);
}
