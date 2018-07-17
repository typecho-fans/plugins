<?php

namespace qcloudcos;

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'error_code.php');

date_default_timezone_set('PRC');

class Cosapi {

    //计算sign签名的时间参数
    const EXPIRED_SECONDS = 180;
    //1M
    const SLICE_SIZE_1M = 1048576;
    //20M 大于20M的文件需要进行分片传输
    const MAX_UNSLICE_FILE_SIZE = 20971520;
	//失败尝试次数
    const MAX_RETRY_TIMES = 3;

    //HTTP请求超时时间
    private static $timeout = 60;
    private static $region = 'gz'; // default region is guangzou

	/**
     * 设置HTTP请求超时时间
     * @param  int  $timeout  超时时长
     */
    public static function setTimeout($timeout = 60) {
        if (!is_int($timeout) || $timeout < 0) {
            return false;
        }

        self::$timeout = $timeout;
        return true;
    }

    public static function setRegion($region) {
        self::$region = $region;
    }

    /**
     * 上传文件,自动判断文件大小,如果小于20M则使用普通文件上传,大于20M则使用分片上传
     * @param  string  $bucket   bucket名称
     * @param  string  $srcPath      本地文件路径
     * @param  string  $dstPath      上传的文件路径
     * @param  string  $bizAttr      文件属性
     * @param  string  $slicesize    分片大小(512k,1m,2m,3m)，默认:1m
     * @param  string  $insertOnly   同名文件是否覆盖
     * @return [type]                [description]
     */
    public static function upload(
            $bucket, $srcPath, $dstPath, $bizAttr=null, $sliceSize=null, $insertOnly=null) {
        if (!file_exists($srcPath)) {
            return array(
                        'code' => COSAPI_PARAMS_ERROR,
                        'message' => 'file ' . $srcPath .' not exists',
                        'data' => array()
                    );
        }

        $dstPath = self::normalizerPath($dstPath, false);

        //文件大于20M则使用分片传输
        if (filesize($srcPath) < self::MAX_UNSLICE_FILE_SIZE ) {
            return self::uploadFile($bucket, $srcPath, $dstPath, $bizAttr, $insertOnly);
        } else {
            $sliceSize = self::getSliceSize($sliceSize);
            return self::uploadBySlicing($bucket, $srcPath, $dstPath, $bizAttr, $sliceSize, $insertOnly);
        }
    }

    /*
     * 创建目录
     * @param  string  $bucket bucket名称
     * @param  string  $folder       目录路径
	 * @param  string  $bizAttr    目录属性
     */
    public static function createFolder($bucket, $folder, $bizAttr = null) {
        if (!self::isValidPath($folder)) {
            return array(
                        'code' => COSAPI_PARAMS_ERROR,
                        'message' => 'folder ' . $path . ' is not a valid folder name',
                        'data' => array()
                    );
        }

        $folder = self::normalizerPath($folder, True);
        $folder = self::cosUrlEncode($folder);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $folder);
        $signature = Auth::createReusableSignature($expired, $bucket);

        $data = array(
            'op' => 'create',
            'biz_attr' => (isset($bizAttr) ? $bizAttr : ''),
        );

        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 目录列表
     * @param  string  $bucket bucket名称
     * @param  string  $path     目录路径，sdk会补齐末尾的 '/'
     * @param  int     $num      拉取的总数
     * @param  string  $pattern  eListBoth,ListDirOnly,eListFileOnly  默认both
     * @param  int     $order    默认正序(=0), 填1为反序,
     * @param  string  $offset   透传字段,用于翻页,前端不需理解,需要往前/往后翻页则透传回来
     */
    public static function listFolder(
                    $bucket, $folder, $num = 20,
                    $pattern = 'eListBoth', $order = 0,
                    $context = null) {
        $folder = self::normalizerPath($folder, True);

        return self::listBase($bucket, $folder, $num, $pattern, $order, $context);
    }

    /*
     * 目录列表(前缀搜索)
     * @param  string  $bucket bucket名称
     * @param  string  $prefix   列出含此前缀的所有文件
     * @param  int     $num      拉取的总数
     * @param  string  $pattern  eListBoth(默认),ListDirOnly,eListFileOnly
     * @param  int     $order    默认正序(=0), 填1为反序,
     * @param  string  $offset   透传字段,用于翻页,前端不需理解,需要往前/往后翻页则透传回来
     */
    public static function prefixSearch(
                    $bucket, $prefix, $num = 20,
                    $pattern = 'eListBoth', $order = 0,
                    $context = null) {
        $path = self::normalizerPath($prefix);

        return self::listBase($bucket, $prefix, $num, $pattern, $order, $context);
    }

    /*
     * 目录更新
     * @param  string  $bucket bucket名称
     * @param  string  $folder      文件夹路径,SDK会补齐末尾的 '/'
     * @param  string  $bizAttr   目录属性
     */
    public static function updateFolder($bucket, $folder, $bizAttr = null) {
        $folder = self::normalizerPath($folder, True);

        return self::updateBase($bucket, $folder, $bizAttr);
    }

   /*
     * 查询目录信息
     * @param  string  $bucket bucket名称
     * @param  string  $folder       目录路径
     */
    public static function statFolder($bucket, $folder) {
        $folder = self::normalizerPath($folder, True);

        return self::statBase($bucket, $folder);
    }

    /*
     * 删除目录
     * @param  string  $bucket bucket名称
     * @param  string  $folder       目录路径
	 *  注意不能删除bucket下根目录/
     */
    public static function delFolder($bucket, $folder) {
        if (empty($bucket) || empty($folder)) {
            return array(
                    'code' => COSAPI_PARAMS_ERROR,
                    'message' => 'bucket or path is empty');
        }

        $folder = self::normalizerPath($folder, True);

        return self::delBase($bucket, $folder);
    }

    /*
     * 更新文件
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件路径
     * @param  string  $authority:  eInvalid(继承Bucket的读写权限)/eWRPrivate(私有读写)/eWPrivateRPublic(公有读私有写)
	 * @param  array   $customer_headers_array 携带的用户自定义头域,包括
     * 'Cache-Control' => '*'
     * 'Content-Type' => '*'
     * 'Content-Disposition' => '*'
     * 'Content-Language' => '*'
     * 'x-cos-meta-自定义内容' => '*'
     */
    public static function update($bucket, $path,
                  $bizAttr = null, $authority=null,$customer_headers_array=null) {
        $path = self::normalizerPath($path);

        return self::updateBase($bucket, $path, $bizAttr, $authority, $customer_headers_array);
    }

    /*
     * 查询文件信息
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件路径
     */
    public static function stat($bucket, $path) {
        $path = self::normalizerPath($path);

        return self::statBase($bucket, $path);
    }

    /*
     * 删除文件
     * @param  string  $bucket
     * @param  string  $path      文件路径
     */
    public static function delFile($bucket, $path) {
        if (empty($bucket) || empty($path)) {
            return array(
                    'code' => COSAPI_PARAMS_ERROR,
                    'message' => 'path is empty');
        }

        $path = self::normalizerPath($path);

        return self::delBase($bucket, $path);
    }

    /**
     * 内部方法, 上传文件
     * @param  string  $bucket  bucket名称
     * @param  string  $srcPath     本地文件路径
     * @param  string  $dstPath     上传的文件路径
     * @param  string  $bizAttr     文件属性
     * @param  int     $insertOnly  是否覆盖同名文件:0 覆盖,1:不覆盖
     * @return [type]               [description]
     */
    private static function uploadFile($bucket, $srcPath, $dstPath, $bizAttr = null, $insertOnly = null) {
        $srcPath = realpath($srcPath);
	    $dstPath = self::cosUrlEncode($dstPath);

	    if (filesize($srcPath) >= self::MAX_UNSLICE_FILE_SIZE ) {
		    return array(
                'code' => COSAPI_PARAMS_ERROR,
                'message' => 'file '.$srcPath.' larger then 20M, please use uploadBySlicing interface',
                'data' => array()
            );
	    }

        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $dstPath);
        $signature = Auth::createReusableSignature($expired, $bucket);
        $fileSha = hash_file('sha1', $srcPath);

        $data = array(
            'op' => 'upload',
            'sha' => $fileSha,
            'biz_attr' => (isset($bizAttr) ? $bizAttr : ''),
        );

        $data['filecontent'] = file_get_contents($srcPath);

        if (isset($insertOnly) && strlen($insertOnly) > 0) {
            $data['insertOnly'] = (($insertOnly == 0 || $insertOnly == '0' ) ? 0 : 1);
        }

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        return self::sendRequest($req);
    }

    /**
     * 内部方法,上传文件
     * @param  string  $bucket  bucket名称
     * @param  string  $srcPath     本地文件路径
     * @param  string  $dstPath     上传的文件路径
     * @param  string  $bizAttr     文件属性
     * @param  string  $sliceSize   分片大小
     * @param  int     $insertOnly  是否覆盖同名文件:0 覆盖,1:不覆盖
     * @return [type]                [description]
     */
    private static function uploadBySlicing(
            $bucket, $srcFpath,  $dstFpath, $bizAttr=null, $sliceSize=null, $insertOnly=null) {
        $srcFpath = realpath($srcFpath);
        $fileSize = filesize($srcFpath);
        $dstFpath = self::cosUrlEncode($dstFpath);
        $url = self::generateResUrl($bucket, $dstFpath);
        $sliceCount = ceil($fileSize / $sliceSize);
        // expiration seconds for one slice mutiply by slice count
        // will be the expired seconds for whole file
        $expiration = time() + (self::EXPIRED_SECONDS * $sliceCount);
        if ($expiration >= (time() + 10 * 24 * 60 * 60)) {
            $expiration = time() + 10 * 24 * 60 * 60;
        }
        $signature = Auth::createReusableSignature($expiration, $bucket);

        $sliceUploading = new SliceUploading(self::$timeout * 1000, self::MAX_RETRY_TIMES);
        for ($tryCount = 0; $tryCount < self::MAX_RETRY_TIMES; ++$tryCount) {
            if ($sliceUploading->initUploading(
                        $signature,
                        $srcFpath,
                        $url,
                        $fileSize, $sliceSize, $bizAttr, $insertOnly)) {
                break;
            }

            $errorCode = $sliceUploading->getLastErrorCode();
            if ($errorCode === -4019) {
                // Delete broken file and retry again on _ERROR_FILE_NOT_FINISH_UPLOAD error.
                Cosapi::delFile($bucket, $dstFpath);
                continue;
            }

            if ($tryCount === self::MAX_RETRY_TIMES - 1) {
                return array(
                            'code' => $sliceUploading->getLastErrorCode(),
                            'message' => $sliceUploading->getLastErrorMessage(),
                            'requestId' => $sliceUploading->getRequestId(),
                        );
            }
        }

        if (!$sliceUploading->performUploading()) {
            return array(
                        'code' => $sliceUploading->getLastErrorCode(),
                        'message' => $sliceUploading->getLastErrorMessage(),
                        'requestId' => $sliceUploading->getRequestId(),
                    );
        }

        if (!$sliceUploading->finishUploading()) {
            return array(
                        'code' => $sliceUploading->getLastErrorCode(),
                        'message' => $sliceUploading->getLastErrorMessage(),
                        'requestId' => $sliceUploading->getRequestId(),
                    );
        }

        return array(
                    'code' => 0,
                    'message' => 'success',
                    'requestId' => $sliceUploading->getRequestId(),
                    'data' => array(
                        'accessUrl' => $sliceUploading->getAccessUrl(),
                        'resourcePath' => $sliceUploading->getResourcePath(),
                        'sourceUrl' => $sliceUploading->getSourceUrl(),
                    ),
                );
    }

    /*
     * 内部公共函数
     * @param  string  $bucket bucket名称
     * @param  string  $path       文件夹路径
     * @param  int     $num        拉取的总数
     * @param  string  $pattern    eListBoth(默认),ListDirOnly,eListFileOnly
     * @param  int     $order      默认正序(=0), 填1为反序,
     * @param  string  $context    在翻页查询时候用到
     */
    private static function listBase(
            $bucket, $path, $num = 20, $pattern = 'eListBoth', $order = 0, $context = null) {
        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createReusableSignature($expired, $bucket);

        $data = array(
            'op' => 'list',
        );

        if (self::isPatternValid($pattern) == false) {
            return array(
                    'code' => COSAPI_PARAMS_ERROR,
                    'message' => 'parameter pattern invalid',
                    );
        }
        $data['pattern'] = $pattern;

        if ($order != 0 && $order != 1) {
            return array(
                        'code' => COSAPI_PARAMS_ERROR,
                        'message' => 'parameter order invalid',
                    );
        }
		$data['order'] = $order;

		if ($num < 0 || $num > 199) {
            return array(
                        'code' => COSAPI_PARAMS_ERROR,
                        'message' => 'parameter num invalid, num need less then 200',
                    );
		}
        $data['num'] = $num;

        if (isset($context)) {
            $data['context'] = $context;
        }

        $url = $url . '?' . http_build_query($data);

        $req = array(
                    'url' => $url,
                    'method' => 'get',
                    'timeout' => self::$timeout,
                    'header' => array(
                        'Authorization: ' . $signature,
                    ),
                );

        return self::sendRequest($req);
    }

    /*
     * 内部公共方法(更新文件和更新文件夹)
     * @param  string  $bucket  bucket名称
     * @param  string  $path        路径
     * @param  string  $bizAttr     文件/目录属性
     * @param  string  $authority:  eInvalid/eWRPrivate(私有)/eWPrivateRPublic(公有读写)
	 * @param  array   $customer_headers_array 携带的用户自定义头域,包括
     * 'Cache-Control' => '*'
     * 'Content-Type' => '*'
     * 'Content-Disposition' => '*'
     * 'Content-Language' => '*'
     * 'x-cos-meta-自定义内容' => '*'
     */
    private static function updateBase(
            $bucket, $path, $bizAttr = null, $authority = null, $custom_headers_array = null) {
        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createNonreusableSignature($bucket, $path);

        $data = array('op' => 'update');

	    if (isset($bizAttr)) {
	        $data['biz_attr'] = $bizAttr;
	    }

	    if (isset($authority) && strlen($authority) > 0) {
			if(self::isAuthorityValid($authority) == false) {
                return array(
                        'code' => COSAPI_PARAMS_ERROR,
                        'message' => 'parameter authority invalid');
			}

	        $data['authority'] = $authority;
	    }

	    if (isset($custom_headers_array)) {
	        $data['custom_headers'] = array();
	        self::add_customer_header($data['custom_headers'], $custom_headers_array);
	    }

        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ),
        );

		return self::sendRequest($req);
    }

    /*
     * 内部方法
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件/目录路径
     */
    private static function statBase($bucket, $path) {
        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createReusableSignature($expired, $bucket);

        $data = array('op' => 'stat');

        $url = $url . '?' . http_build_query($data);

        $req = array(
            'url' => $url,
            'method' => 'get',
            'timeout' => self::$timeout,
            'header' => array(
                'Authorization: ' . $signature,
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 内部私有方法
     * @param  string  $bucket  bucket名称
     * @param  string  $path        文件/目录路径路径
     */
    private static function delBase($bucket, $path) {
        if ($path == "/") {
            return array(
                    'code' => COSAPI_PARAMS_ERROR,
                    'message' => 'can not delete bucket using api! go to ' .
                                 'http://console.qcloud.com/cos to operate bucket');
        }

        $path = self::cosUrlEncode($path);
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $path);
        $signature = Auth::createNonreusableSignature($bucket, $path);

        $data = array('op' => 'delete');

        $data = json_encode($data);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $signature,
                'Content-Type: application/json',
            ),
        );

        return self::sendRequest($req);
    }

    /*
     * 内部公共方法, 路径编码
     * @param  string  $path 待编码路径
     */
	private static function cosUrlEncode($path) {
        return str_replace('%2F', '/',  rawurlencode($path));
    }

    /*
     * 内部公共方法, 构造URL
     * @param  string  $bucket
     * @param  string  $dstPath
     */
    private static function generateResUrl($bucket, $dstPath) {
        $conf_object = new Conf();
        $appId = $conf_object::$APPID;
        $endPoint = Conf::API_COSAPI_END_POINT;
        $endPoint = str_replace('region', self::$region, $endPoint);

        return $endPoint . $appId . '/' . $bucket . $dstPath;
    }

	/*
     * 内部公共方法, 发送消息
     * @param  string  $req
     */
    private static function sendRequest($req) {
        $rsp = HttpClient::sendRequest($req);
        if ($rsp === false) {
            return array(
                'code' => COSAPI_NETWORK_ERROR,
                'message' => 'network error',
            );
        }

        $info = HttpClient::info();
        $ret = json_decode($rsp, true);

        if ($ret === NULL) {
            return array(
                'code' => COSAPI_NETWORK_ERROR,
                'message' => $rsp,
                'data' => array()
            );
        }

        return $ret;
    }

    /**
     * Get slice size.
     */
	private static function getSliceSize($sliceSize) {
        // Fix slice size to 1MB.
        return self::SLICE_SIZE_1M;
	}

    /*
     * 内部方法, 规整文件路径
     * @param  string  $path      文件路径
     * @param  string  $isfolder  是否为文件夹
     */
	private static function normalizerPath($path, $isfolder = False) {
		if (preg_match('/^\//', $path) == 0) {
            $path = '/' . $path;
        }

        if ($isfolder == True) {
            if (preg_match('/\/$/', $path) == 0) {
                $path = $path . '/';
            }
        }

        // Remove unnecessary slashes.
        $path = preg_replace('#/+#', '/', $path);

		return $path;
	}

    /**
     * 判断authority值是否正确
     * @param  string  $authority
     * @return [type]  bool
     */
    private static function isAuthorityValid($authority) {
        if ($authority == 'eInvalid' || $authority == 'eWRPrivate' || $authority == 'eWPrivateRPublic') {
            return true;
	    }
	    return false;
    }

    /**
     * 判断pattern值是否正确
     * @param  string  $authority
     * @return [type]  bool
     */
    private static function isPatternValid($pattern) {
        if ($pattern == 'eListBoth' || $pattern == 'eListDirOnly' || $pattern == 'eListFileOnly') {
            return true;
	    }
	    return false;
    }

    /**
     * 判断是否符合自定义属性
     * @param  string  $key
     * @return [type]  bool
     */
    private static function isCustomer_header($key) {
        if ($key == 'Cache-Control' || $key == 'Content-Type' ||
                $key == 'Content-Disposition' || $key == 'Content-Language' ||
                $key == 'Content-Encoding' ||
                substr($key,0,strlen('x-cos-meta-')) == 'x-cos-meta-') {
            return true;
	    }
	    return false;
    }

	/**
     * 增加自定义属性到data中
     * @param  array  $data
	 * @param  array  $customer_headers_array
     * @return [type]  void
     */
    private static function add_customer_header(&$data, &$customer_headers_array) {
        if (count($customer_headers_array) < 1) {
            return;
        }
	    foreach($customer_headers_array as $key=>$value) {
            if(self::isCustomer_header($key)) {
	            $data[$key] = $value;
            }
	    }
    }

    // Check |$path| is a valid file path.
    // Return true on success, otherwise return false.
    private static function isValidPath($path) {
        if (strpos($path, '?') !== false) {
            return false;
        }
        if (strpos($path, '*') !== false) {
            return false;
        }
        if (strpos($path, ':') !== false) {
            return false;
        }
        if (strpos($path, '|') !== false) {
            return false;
        }
        if (strpos($path, '\\') !== false) {
            return false;
        }
        if (strpos($path, '<') !== false) {
            return false;
        }
        if (strpos($path, '>') !== false) {
            return false;
        }
        if (strpos($path, '"') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Copy a file.
     * @param $bucket bucket name.
     * @param $srcFpath source file path.
     * @param $dstFpath destination file path.
     * @param $overwrite if the destination location is occupied, overwrite it or not?
     * @return array|mixed.
     */
    public static function copyFile($bucket, $srcFpath, $dstFpath, $overwrite = false) {
        $url = self::generateResUrl($bucket, $srcFpath);
        $sign = Auth::createNonreusableSignature($bucket, $srcFpath);
        $data = array(
            'op' => 'copy',
            'dest_fileid' => $dstFpath,
            'to_over_write' => $overwrite ? 1 : 0,
        );
        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $sign,
            ),
        );

        return self::sendRequest($req);
    }

    /**
     * Move a file.
     * @param $bucket bucket name.
     * @param $srcFpath source file path.
     * @param $dstFpath destination file path.
     * @param $overwrite if the destination location is occupied, overwrite it or not?
     * @return array|mixed.
     */
    public static function moveFile($bucket, $srcFpath, $dstFpath, $overwrite = false) {
        $url = self::generateResUrl($bucket, $srcFpath);
        $sign = Auth::createNonreusableSignature($bucket, $srcFpath);
        $data = array(
            'op' => 'move',
            'dest_fileid' => $dstFpath,
            'to_over_write' => $overwrite ? 1 : 0,
        );
        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => self::$timeout,
            'data' => $data,
            'header' => array(
                'Authorization: ' . $sign,
            ),
        );

        return self::sendRequest($req);
    }
}
