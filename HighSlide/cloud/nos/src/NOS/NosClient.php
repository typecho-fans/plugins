<?php
namespace NOS;

use NOS\Core\MimeTypes;
use NOS\Core\NosException;
use NOS\Http\RequestCore;
use NOS\Http\RequestCore_Exception;
use NOS\Http\ResponseCore;
use NOS\Result\BodyResult;
use NOS\Result\InitiateMultipartUploadResult;
use NOS\Result\ListBucketsResult;
use NOS\Result\ListMultipartUploadResult;
use NOS\Model\ListMultipartUploadInfo;
use NOS\Result\ListObjectsResult;
use NOS\Result\ListPartsResult;
use NOS\Result\PutSetDeleteResult;
use NOS\Result\ExistResult;
use NOS\Model\ObjectListInfo;
use NOS\Result\UploadPartResult;
use NOS\Core\NosUtil;
use NOS\Model\ListPartsInfo;
use NOS\Result\aclResult;
use NOS\Result\GetLocationResult;
use NOS\Result\GetObjectMetaResult;
use NOS\Result\MultiDeleteResult;

/**
 * Class NOSClient
 * Wrote By liuyiming 2016-06
 * Object Storage Service(NOS) 的客户端类，封装了用户通过NOS API对NOS服务的各种操作，
 * 用户通过NOSClient实例可以进行Object，MultipartUpload等操作，具体
 * 的接口规则可以参考官方NOS API文档
 */
class NosClient
{

    /**
     * 构造函数
     *
     * @param string $accessKeyId
     *            从NOS获得的AccessKeyId
     * @param string $accessKeySecret
     *            从NOS获得的AccessKeySecret
     * @param string $endpoint
     *            默认为nos.netease.com
     * @throws NOSException
     */
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $isCname = FALSE)
    {
        if (empty($accessKeyId)) {
            throw new NosException("access key id is empty");
        }
        if (empty($accessKeySecret)) {
            throw new NosException("access key secret is empty");
        }
        if (empty($endpoint)) {
            throw new NosException("endpoint is empty");
        }
        $this->hostname = $this->checkEndpoint($endpoint, $isCname);
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        self::checkEnv();
    }

    /**
     * 创建bucket，默认创建的bucket的ACL是NOSClient::NOS_ACL_TYPE_PRIVATE
     * 主要是用于单元测试
     *
     * @param string $bucket
     * @param string $acl
     * @param array $options
     * @return null
     */
    public function createBucket($bucket, $acl = self::NOS_ACL_TYPE_PRIVATE, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_PUT;
        $options[self::NOS_OBJECT] = '/';
        $options[self::NOS_HEADERS] = array(
            self::NOS_ACL => $acl
        );
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 删除bucket
     * 如果Bucket不为空（Bucket中有Object，或者有分块上传的碎片），则Bucket无法删除，
     * 必须删除Bucket中的所有Object以及碎片后，Bucket才能成功删除。
     *
     * @param string $bucket
     * @param array $options
     * @return null
     */
    public function deleteBucket($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_DELETE;
        $options[self::NOS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 验证bucket是否存在且有没有权限访问，
     * 返回true，bucket存在且有权限访问
     * 返回false，bucket不存在或无权访问
     *
     * @param unknown $bucket
     * @param unknown $options
     */
    public function doesBucketExist($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_HEAD;
        $options[self::NOS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    /**
     * 列举用户所有的Bucket[GetService], Endpoint类型为cname不能进行此操作
     *
     * @param unknown $options
     * @throws NosException
     */
    public function listBuckets($options = NULL)
    {
        // CName 情况下不允许使用list bucket操作
        if ($this->hostType === self::NOS_HOST_TYPE_CNAME) {
            throw new NosException("operation is not permitted with CName host");
        }
        $this->precheckOptions($options);
        $options[self::NOS_BUCKET] = '';
        $options[self::NOS_METHOD] = self::NOS_HTTP_GET;
        $options[self::NOS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new ListBucketsResult($response);
        return $result->getData();
    }

    /**
     * 获取bucket下的object列表
     *
     * @param string $bucket
     * @param array $options
     *            其中options中的参数如下
     *            'max-keys' => max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000。
     *            'prefix' => 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
     *            'delimiter' => 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
     *            'marker' => 用户设定结果从marker之后按字母排序的第一个开始返回。
     *            其中 prefix，marker用来实现分页显示效果，参数的长度必须小于256字节。
     * @throws NOSException
     * @return ObjectListInfo
     */
    public function listObjects($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_GET;
        $options[self::NOS_OBJECT] = '/';

        foreach (array(
            'delimiter',
            'marker',
            'max-keys',
            'prefix'
        ) as $param) {
            if (isset($options[$param])) {
                $options[self::NOS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        // set default value 100 to max-keys
        if (! isset($options[self::NOS_QUERY_STRING]['max-keys'])) {
            $options[self::NOS_QUERY_STRING]['max-keys'] = 100;
        }
        $response = $this->auth($options);
        $result = new ListObjectsResult($response);
        return $result->getData();
    }

    /**
     * 获取桶的acl
     *
     * @param unknown $bucket
     * @param unknown $options
     * @return mixed|string
     */
    public function getBucketAcl($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_GET;
        $options[self::NOS_OBJECT] = '/';
        $options[self::NOS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new aclResult($response);
        return $result->getData();
    }

    /**
     * 设置桶的acl，private 或者 public_read
     *
     * @param unknown $bucket
     * @param unknown $acl
     * @param unknown $options
     * @return NULL
     */
    public function putBucketAcl($bucket, $acl, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_PUT;
        $options[self::NOS_OBJECT] = '/';
        $options[self::NOS_HEADERS] = array(
            self::NOS_ACL => $acl
        );
        $options[self::NOS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 获取桶所处的分区
     *
     * @param unknown $bucket
     * @param unknown $options
     */
    public function getBucketLocation($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = '/';
        $options[self::NOS_METHOD] = self::NOS_HTTP_GET;
        $options[self::NOS_SUB_RESOURCE] = 'location';
        $response = $this->auth($options);
        $result = new GetLocationResult($response);
        return $result->getData();
    }

    /**
     * 上传内存中的内容
     *
     * @param string $bucket bucket名称
     * @param string $object objcet名称
     * @param string $content 上传的内容
     * @param array $options 可以指定上传的元数据
     * @return null
     */
    public function putObject($bucket, $object, $content, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        //NosUtil::validateContent($content);
        $options[self::NOS_CONTENT] = $content;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_PUT;
        $options[self::NOS_OBJECT] = $object;
        $is_check_md5 = $this->isCheckMD5($options);
        if ($is_check_md5) {
            if(!isset($options[self::NOS_CONTENT_MD5]))
            {
                $content_md5 = md5($content);
                $options[self::NOS_CONTENT_MD5] = $content_md5;
            }
        }
        if (! isset($options[self::NOS_LENGTH])) {
            $options[self::NOS_CONTENT_LENGTH] = strlen($options[self::NOS_CONTENT]);
        } else {
            $options[self::NOS_CONTENT_LENGTH] = $options[self::NOS_LENGTH];
        }
        if (! isset($options[self::NOS_CONTENT_TYPE])) {
            $options[self::NOS_CONTENT_TYPE] = $this->getMimeType($object);
        }

        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 上传本地文件
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param string $file 本地文件路径
     * @param array $options
     * @return null
     * @throws NOSException
     */
    public function uploadFile($bucket, $object, $file, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        NosUtil::throwNOSExceptionWithMessageIfEmpty($file, "file path is invalid");
        $file = NosUtil::encodePath($file);
        if (! file_exists($file)) {
            throw new NosException($file . " file does not exist");
        }
        $options[self::NOS_FILE_UPLOAD] = $file;
        $file_size = filesize($options[self::NOS_FILE_UPLOAD]);
        $is_check_md5 = $this->isCheckMD5($options);
        if ($is_check_md5) {
            if(!isset($options[self::NOS_CONTENT_MD5]))
            {
                $content_md5 = md5_file($options[self::NOS_FILE_UPLOAD]);
                $options[self::NOS_CONTENT_MD5] = $content_md5;
            }
        }
        if (! isset($options[self::NOS_CONTENT_TYPE])) {
            $options[self::NOS_CONTENT_TYPE] = $this->getMimeType($object, $file);
        }

        $options[self::NOS_METHOD] = self::NOS_HTTP_PUT;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = $object;
        $options[self::NOS_CONTENT_LENGTH] = $file_size;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    public function doesObjectExist($bucket, $object,$options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_HEAD;
        $options[self::NOS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    public function GetObjectMeta($bucket, $object,$options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_HEAD;
        $options[self::NOS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new GetObjectMetaResult($response);
        return $result->getData();
    }

    /**
     * 拷贝一个在NOS上已经存在的object成另外一个object
     *
     * @param string $fromBucket
     *            源bucket名称
     * @param string $fromObject
     *            源object名称
     * @param string $toBucket
     *            目标bucket名称
     * @param string $toObject
     *            目标object名称
     * @param array $options
     * @return null
     * @throws NOSException
     */
    public function copyObject($fromBucket, $fromObject, $toBucket, $toObject, $options = NULL)
    {
        $this->precheckCommon($fromBucket, $fromObject, $options);
        $this->precheckCommon($toBucket, $toObject, $options);
        $options[self::NOS_BUCKET] = $toBucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_PUT;
        $options[self::NOS_OBJECT] = $toObject;
        $fromObject = NosUtil::objectEncode($fromObject);
        if (isset($options[self::NOS_HEADERS])) {
            $options[self::NOS_HEADERS][self::NOS_OBJECT_COPY_SOURCE] = '/' . $fromBucket . '/' . $fromObject;
        } else {
            $options[self::NOS_HEADERS] = array(
                self::NOS_OBJECT_COPY_SOURCE => '/' . $fromBucket . '/' . $fromObject
            );
        }
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 将一个桶中的对象移动到同一个桶中的对象，注意，暂不支持跨桶move
     *
     * @param unknown $fromBucket
     * @param unknown $fromObject
     * @param unknown $toBucket
     * @param unknown $toObject
     * @param unknown $options
     */
    public function moveObject($fromBucket, $fromObject, $toBucket, $toObject, $options = NULL)
    {
        $this->precheckCommon($fromBucket, $fromObject, $options);
        $this->precheckCommon($toBucket, $toObject, $options);
        $options[self::NOS_BUCKET] = $toBucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_PUT;
        $options[self::NOS_OBJECT] = $toObject;
        $fromObject = NosUtil::objectEncode($fromObject);
        if (isset($options[self::NOS_HEADERS])) {
            $options[self::NOS_HEADERS][self::NOS_OBJECT_MOVE_SOURCE] = '/' . $fromBucket . '/' . $fromObject;
        } else {
            $options[self::NOS_HEADERS] = array(
                self::NOS_OBJECT_MOVE_SOURCE => '/' . $fromBucket . '/' . $fromObject
            );
        }
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 删除某个Object
     *
     * @param string $bucket
     *            bucket名称
     * @param string $object
     *            object名称
     * @param array $options
     * @return null
     */
    public function deleteObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_DELETE;
        $options[self::NOS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 删除同一个Bucket中的多个Object
     *
     * @param string $bucket
     *            bucket名称
     * @param array $objects
     *            object列表
     * @param array $options
     * @return ResponseCore
     * @throws null
     */
    public function deleteObjects($bucket, $objects, $options = null)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        if (! is_array($objects) || ! $objects) {
            throw new NosException('objects must be array');
        }
        $options[self::NOS_METHOD] = self::NOS_HTTP_POST;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = '/';
        $options[self::NOS_SUB_RESOURCE] = 'delete';
        $options[self::NOS_HEADERS][self::NOS_CONTENT_TYPE] = 'application/xml';
        $quiet = 'false';
        if (isset($options['quiet'])) {
            if (is_bool($options['quiet'])) { //Boolean
                $quiet = $options['quiet'] ? 'true' : 'false';
            } elseif (is_string($options['quiet'])) { // string
                $quiet = ($options['quiet'] === 'true') ? 'true' : 'false';
            }
        }
        $xmlBody = NosUtil::createDeleteObjectsXmlBody($objects,$quiet);
        $options[self::NOS_CONTENT] = $xmlBody;
        $options[self::NOS_HEADERS][self::NOS_CONTENT_MD5] = md5($options[self::NOS_CONTENT]);
        $response = $this->auth($options);
        $result = new MultiDeleteResult($response);
        return $result->getData();
    }

    /**
     * 获得Object内容
     *
     * @param string $bucket
     *            bucket名称
     * @param string $object
     *            object名称
     * @param array $options
     *            该参数中可以设置ALINOS::NOS_FILE_DOWNLOAD，ALINOS::NOS_RANGE可选，可以根据实际情况设置；如果不设置，默认会下载全部内容
     * @return string
     */
    public function getObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_METHOD] = self::NOS_HTTP_GET;
        $options[self::NOS_OBJECT] = $object;
        if (isset($options[self::NOS_LAST_MODIFIED])) {
            $options[self::NOS_HEADERS][self::NOS_IF_MODIFIED_SINCE] = $options[self::NOS_LAST_MODIFIED];
            unset($options[self::NOS_LAST_MODIFIED]);
        }
        if (isset($options[self::NOS_ETAG])) {
            $options[self::NOS_HEADERS][self::NOS_IF_NONE_MATCH] = $options[self::NOS_ETAG];
            unset($options[self::NOS_ETAG]);
        }
        if (isset($options[self::NOS_RANGE])) {
            $range = $options[self::NOS_RANGE];
            $options[self::NOS_HEADERS][self::NOS_RANGE] = "bytes=$range";
            unset($options[self::NOS_RANGE]);
        }
        $response = $this->auth($options);
        $result = new BodyResult($response);
        return $result->getData();
    }

    /**
     * 初始化multi-part upload，支持设置元数据
     *
     * @param string $bucket
     *            Bucket名称
     * @param string $object
     *            Object名称
     * @param array $options
     *            Key-Value数组
     * @throws NOSException
     * @return string 返回uploadid
     */
    public function initiateMultipartUpload($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_METHOD] = self::NOS_HTTP_POST;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = $object;
        $options[self::NOS_SUB_RESOURCE] = 'uploads';
        $options[self::NOS_CONTENT] = '';

        if (! isset($options[self::NOS_CONTENT_TYPE])) {
            $options[self::NOS_CONTENT_TYPE] = $this->getMimeType($object);
        }
        if (! isset($options[self::NOS_HEADERS])) {
            $options[self::NOS_HEADERS] = array();
        }

        $response = $this->auth($options);
        $result = new InitiateMultipartUploadResult($response);
        return $result->getData();
    }

    /**
     * 获取分片大小，根据用户提供的part_size，重新获取一个分片的大小，不超过100M不小于5M
     *
     * @param int $partSize
     * @return int
     */
    private function computePartSize($partSize)
    {
        $partSize = (integer) $partSize;
        if ($partSize <= self::NOS_MIN_PART_SIZE) {
            $partSize = self::NOS_MIN_PART_SIZE;
        } elseif ($partSize > self::NOS_MAX_PART_SIZE) {
            $partSize = self::NOS_MAX_PART_SIZE;
        }
        return $partSize;
    }

    /**
     * 计算文件可以分成多少个part，以及每个part的长度以及起始位置
     * 方法必须在 <upload_part()>中调用
     *
     * @param integer $file_size
     *            文件大小
     * @param integer $partSize
     *            part大小,可能在函数内部被调整
     * @return array An array 包含 key-value 键值对. Key 为 `seekTo` 和 `length`.
     */
    public function generateMultiuploadParts($file_size, $partSize = 5242880)
    {
        $i = 0;
        $size_count = $file_size;
        $values = array();
        $partSize = $this->computePartSize($partSize);
        while ($size_count > 0) {
            $size_count -= $partSize;
            $values[] = array(
                self::NOS_SEEK_TO => ($partSize * $i),
                self::NOS_LENGTH => (($size_count > 0) ? $partSize : ($size_count + $partSize))
            );
            $i ++;
        }
        return $values;
    }

    /**
     * 分片上传的块上传接口
     *
     * @param string $bucket
     *            Bucket名称
     * @param string $object
     *            Object名称
     * @param string $uploadId
     * @param array $options
     *            Key-Value数组
     * @return string eTag
     * @throws NOSException
     */
    public function uploadPart($bucket, $object, $uploadId, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        $this->precheckParam($options, self::NOS_FILE_UPLOAD, __FUNCTION__);
        $this->precheckParam($options, self::NOS_PART_NUM, __FUNCTION__);

        $options[self::NOS_METHOD] = self::NOS_HTTP_PUT;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = $object;
        $options[self::NOS_UPLOAD_ID] = $uploadId;
        $is_check_md5 = $this->isCheckMD5($options);
        if($is_check_md5)
        {
            if(!isset($options[self::NOS_CONTENT_MD5]))
            {
                $frompos = $options[self::NOS_FILE_UPLOAD];
                $topos = $options[self::NOS_SEEK_TO] + $options[self::NOS_LENGTH] - 1;
                $content_md5 = NosUtil::getMd5SumForFile($options[self::NOS_FILE_UPLOAD], $frompos, $topos);
                $options[self::NOS_CONTENT_MD5] = $content_md5;
            }
        }

        if (isset($options[self::NOS_LENGTH])) {
            $options[self::NOS_CONTENT_LENGTH] = $options[self::NOS_LENGTH];
        }
        $response = $this->auth($options);
        $result = new UploadPartResult($response);
        return $result->getData();
    }

    /**
     * 在将所有数据Part都上传完成后，调用此接口完成本次分块上传
     *
     * @param string $bucket
     *            Bucket名称
     * @param string $object
     *            Object名称
     * @param string $uploadId
     *            uploadId
     * @param array $listParts
     *            array( array("PartNumber"=> int, "ETag"=>string))
     * @param array $options
     *            Key-Value数组,可以带MD5用于去重上传
     * @throws NOSException
     * @return null
     */
    public function completeMultipartUpload($bucket, $object, $uploadId, $listParts, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_METHOD] = self::NOS_HTTP_POST;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = $object;
        $options[self::NOS_UPLOAD_ID] = $uploadId;
        $options[self::NOS_CONTENT_TYPE] = 'application/xml';
        if (isset($options[self::NOS_OBJECT_MD5])) {
            $options[self::NOS_HEADERS][self::NOS_OBJECT_MD5] = $options[self::NOS_OBJECT_MD5];
            unset($options[self::NOS_OBJECT_MD5]);
        }
        if (! is_array($listParts)) {
            throw new NosException("listParts must be array type");
        }
        $options[self::NOS_CONTENT] = NosUtil::createCompleteMultipartUploadXmlBody($listParts);
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 获取已成功上传的part
     *
     * @param string $bucket
     *            Bucket名称
     * @param string $object
     *            Object名称
     * @param string $uploadId
     *            uploadId
     * @param array $options
     *            Key-Value数组
     * @return ListPartsInfo
     * @throws NOSException
     */
    public function listParts($bucket, $object, $uploadId, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_METHOD] = self::NOS_HTTP_GET;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = $object;
        $options[self::NOS_UPLOAD_ID] = $uploadId;
        $options[self::NOS_QUERY_STRING] = array();
        foreach (array(
            'max-parts',
            'part-number-marker'
        ) as $param) {
            if (isset($options[$param])) {
                $options[self::NOS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        $response = $this->auth($options);
        $result = new ListPartsResult($response);
        return $result->getData();
    }

    /**
     * 中止分片上传操作
     *
     * @param string $bucket
     *            Bucket名称
     * @param string $object
     *            Object名称
     * @param string $uploadId
     *            uploadId
     * @param array $options
     *            Key-Value数组
     * @return null
     * @throws NOSException
     */
    public function abortMultipartUpload($bucket, $object, $uploadId, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::NOS_METHOD] = self::NOS_HTTP_DELETE;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = $object;
        $options[self::NOS_UPLOAD_ID] = $uploadId;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 罗列出所有执行中的Multipart Upload事件，即已经被初始化的Multipart Upload但是未被
     * Complete或者Abort的Multipart Upload事件
     *
     * @param string $bucket
     *            bucket
     * @param array $options
     *            关联数组
     * @throws NOSException
     * @return ListMultipartUploadInfo
     */
    public function listMultipartUploads($bucket, $options = null)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::NOS_METHOD] = self::NOS_HTTP_GET;
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = '/';
        $options[self::NOS_SUB_RESOURCE] = 'uploads';

        foreach (array('key-marker','max-uploads') as $param)
        {
            if (isset($options[$param])) {
                $options[self::NOS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }

        $response = $this->auth($options);
        $result = new ListMultipartUploadResult($response);
        return $result->getData();
    }

    /**
     * multipart上传统一封装，从初始化到完成multipart，以及出错后中止动作
     *
     * @param string $bucket
     *            bucket名称
     * @param string $object
     *            object名称
     * @param string $file
     *            需要上传的本地文件的路径
     * @param array $options
     *            Key-Value数组
     * @return null
     * @throws NOSException
     */
    public function multiuploadFile($bucket, $object, $file, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        if (isset($options[self::NOS_LENGTH])) {
            $options[self::NOS_CONTENT_LENGTH] = $options[self::NOS_LENGTH];
            unset($options[self::NOS_LENGTH]);
        }
        if (empty($file)) {
            throw new NosException("parameter invalid, file is empty");
        }
        $uploadFile = NosUtil::encodePath($file);
        if (! isset($options[self::NOS_CONTENT_TYPE])) {
            $options[self::NOS_CONTENT_TYPE] = $this->getMimeType($object, $uploadFile);
        }

        $upload_position = isset($options[self::NOS_SEEK_TO]) ? (integer) $options[self::NOS_SEEK_TO] : 0;

        if (isset($options[self::NOS_CONTENT_LENGTH])) {
            $upload_file_size = (integer) $options[self::NOS_CONTENT_LENGTH];
        } else {
            $upload_file_size = filesize($uploadFile);
            if ($upload_file_size !== false) {
                $upload_file_size -= $upload_position;
            }
        }

        if ($upload_position === false || ! isset($upload_file_size) || $upload_file_size === false || $upload_file_size < 0) {
            throw new NosException('The size of `fileUpload` cannot be determined in ' . __FUNCTION__ . '().');
        }
        // 处理partSize
        if (isset($options[self::NOS_PART_SIZE])) {
            $options[self::NOS_PART_SIZE] = $this->computePartSize($options[self::NOS_PART_SIZE]);
        } else {
            $options[self::NOS_PART_SIZE] = self::NOS_MID_PART_SIZE;
        }

        $is_check_md5 = $this->isCheckMD5($options);
        // 如果上传的文件小于partSize,则直接使用普通方式上传
        if ($upload_file_size < $options[self::NOS_PART_SIZE] && ! isset($options[self::NOS_UPLOAD_ID])) {
            return $this->uploadFile($bucket, $object, $uploadFile, $options);
        }

        // 初始化multipart
        if (isset($options[self::NOS_UPLOAD_ID])) {
            $uploadId = $options[self::NOS_UPLOAD_ID];
        } else {
            // 初始化
            $uploadId = $this->initiateMultipartUpload($bucket, $object, $options);
        }

        // 获取的分片
        $pieces = $this->generateMultiuploadParts($upload_file_size, (integer) $options[self::NOS_PART_SIZE]);
        $response_upload_part = array();
        foreach ($pieces as $i => $piece) {
            $from_pos = $upload_position + (integer) $piece[self::NOS_SEEK_TO];
            $to_pos = (integer) $piece[self::NOS_LENGTH] + $from_pos - 1;
            $up_options = array(
                self::NOS_FILE_UPLOAD => $uploadFile,
                self::NOS_PART_NUM => ($i + 1),
                self::NOS_SEEK_TO => $from_pos,
                self::NOS_LENGTH => $to_pos - $from_pos + 1,
                self::NOS_CHECK_MD5 => $is_check_md5
            );
            if ($is_check_md5) {
                $content_md5 = NosUtil::getMd5SumForFile($uploadFile, $from_pos, $to_pos);
                $up_options[self::NOS_CONTENT_MD5] = $content_md5;
            }
            $response_upload_part[] = $this->uploadPart($bucket, $object, $uploadId, $up_options);
        }

        $uploadParts = array();
        foreach ($response_upload_part as $i => $etag) {
            $uploadParts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => $etag
            );
        }
        // for object DEDUPLICATE
        $completeOptions = array(
            self::NOS_OBJECT_MD5 => md5_file($file)
        );
        return $this->completeMultipartUpload($bucket, $object, $uploadId, $uploadParts, $completeOptions);
    }

    /**
     * 支持生成get和put签名, 用户可以生成一个具有一定有效期的
     * 签名过的url
     *
     * @param string $bucket
     * @param string $object
     * @param int $timeout
     * @param string $method
     * @param array $options Key-Value数组
     * @return string
     * @throws NOSException
     */
    public function signUrl($bucket, $object, $timeout = 60, $method = self::NOS_HTTP_GET, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        // method
        if (self::NOS_HTTP_GET !== $method) {
            throw new NosException("method is invalid");
        }
        $options[self::NOS_BUCKET] = $bucket;
        $options[self::NOS_OBJECT] = $object;
        $options[self::NOS_METHOD] = $method;
        if (! isset($options[self::NOS_CONTENT_TYPE])) {
            $options[self::NOS_CONTENT_TYPE] = '';
        }
        $timeout = time() + $timeout;
        $options[self::NOS_PREAUTH] = $timeout;
        $options[self::NOS_DATE] = $timeout;
        return $this->auth($options);
    }

    /**
     * 检测options参数
     *
     * @param array $options
     * @throws NOSException
     */
    private function precheckOptions(&$options)
    {
        NosUtil::validateOptions($options);
        if (! $options) {
            $options = array();
        }
    }

    /**
     * 校验bucket参数
     *
     * @param string $bucket
     * @param string $errMsg
     * @throws NOSException
     */
    private function precheckBucket($bucket, $errMsg = 'bucket is not allowed empty')
    {
        NosUtil::throwNOSExceptionWithMessageIfEmpty($bucket, $errMsg);
    }

    /**
     * 校验object参数
     *
     * @param string $object
     * @throws NOSException
     */
    private function precheckObject($object)
    {
        NosUtil::throwNOSExceptionWithMessageIfEmpty($object, "object name is empty");
    }

    /**
     * 校验bucket,options参数
     *
     * @param string $bucket
     * @param string $object
     * @param array $options
     * @param bool $isCheckObject
     */
    private function precheckCommon($bucket, $object, $options, $isCheckObject = true)
    {
        if ($isCheckObject) {
            $this->precheckObject($object);
        }
        $this->precheckOptions($options);
        $this->precheckBucket($bucket);
    }

    /**
     * 参数校验
     *
     * @param array $options
     * @param string $param
     * @param string $funcName
     * @throws NOSException
     */
    private function precheckParam($options, $param, $funcName)
    {
        if (! isset($options[$param])) {
            throw new NosException('The `' . $param . '` options is required in ' . $funcName . '().');
        }
    }

    /**
     * 检测md5
     *
     * @param array $options
     * @return bool|null
     */
    private function isCheckMD5($options)
    {
        return $this->getValue($options, self::NOS_CHECK_MD5, false, true, true);
    }

    /**
     * 获取value
     *
     * @param array $options
     * @param string $key
     * @param string $default
     * @param bool $isCheckEmpty
     * @param bool $isCheckBool
     * @return bool|null
     */
    private function getValue($options, $key, $default = NULL, $isCheckEmpty = false, $isCheckBool = false)
    {
        $value = $default;
        if (isset($options[$key])) {
            if ($isCheckEmpty) {
                if (! empty($options[$key])) {
                    $value = $options[$key];
                }
            } else {
                $value = $options[$key];
            }
            unset($options[$key]);
        }
        if ($isCheckBool) {
            if ($value !== true && $value !== false) {
                $value = false;
            }
        }
        return $value;
    }

    /**
     * 获取mimetype类型
     *
     * @param string $object
     * @return string
     */
    private function getMimeType($object, $file = null)
    {
        if (! is_null($file)) {
            $type = MimeTypes::getMimetype($file);
            if (! is_null($type)) {
                return $type;
            }
        }

        $type = MimeTypes::getMimetype($object);
        if (! is_null($type)) {
            return $type;
        }

        return self::DEFAULT_CONTENT_TYPE;
    }

    /**
     * 验证并且执行请求，按照NOS Api协议，执行操作
     *
     * @param array $options
     * @return ResponseCore
     * @throws NOSException
     * @throws RequestCore_Exception
     */
    private function auth($options)
    {
        // 验证option，必须是array
        NosUtil::validateOptions($options);
        // 验证bucket，list_bucket时不需要验证
        $this->authPrecheckBucket($options);
        // 验证object
        $this->authPrecheckObject($options);
        // Object名称的编码必须是utf8
        $this->authPrecheckObjectEncoding($options);
        // 验证acl
        $this->authPrecheckAcl($options);
        // 获得当次请求使用的协议头，是https还是http
        $scheme = $this->useSSL ? 'https://' : 'http://';
        // 获得当次请求使用的hostname，bucket拼在前面构成三级域名
        $hostname = $this->generateHostname($options);
        $string_to_sign = '';
        $headers = $this->generateHeaders($options, $hostname);
        $signable_query_string_params = $this->generateSignableQueryStringParam($options);
        $signable_query_string = NosUtil::toQueryString($signable_query_string_params);
        $resource_uri = $this->generateResourceUri($options);
        // 生成请求URL
        $conjunction = '?';
        $non_signable_resource = '';
        if (isset($options[self::NOS_SUB_RESOURCE])) {
            $conjunction = '&';
        }
        if ($signable_query_string !== '') {
            $signable_query_string = $conjunction . $signable_query_string;
            $conjunction = '&';
        }
        $query_string = $this->generateQueryString($options);
        if ($query_string !== '') {
            $non_signable_resource .= $conjunction . $query_string;
            $conjunction = '&';
        }
        $this->requestUrl = $scheme . $hostname . $resource_uri . $signable_query_string . $non_signable_resource;
        // 创建请求
        $request = new RequestCore($this->requestUrl);
        $request->set_useragent($this->generateUserAgent());
        // Streaming uploads
        if (isset($options[self::NOS_FILE_UPLOAD])) {
            if (is_resource($options[self::NOS_FILE_UPLOAD])) {
                $length = null;
                if (isset($options[self::NOS_CONTENT_LENGTH])) {
                    $length = $options[self::NOS_CONTENT_LENGTH];
                } elseif (isset($options[self::NOS_SEEK_TO])) {
                    $stats = fstat($options[self::NOS_FILE_UPLOAD]);
                    if ($stats && $stats[self::NOS_SIZE] >= 0) {
                        $length = $stats[self::NOS_SIZE] - (integer) $options[self::NOS_SEEK_TO];
                    }
                }
                $request->set_read_stream($options[self::NOS_FILE_UPLOAD], $length);
            } else {
                $request->set_read_file($options[self::NOS_FILE_UPLOAD]);
                $length = $request->read_stream_size;
                if (isset($options[self::NOS_CONTENT_LENGTH])) {
                    $length = $options[self::NOS_CONTENT_LENGTH];
                } elseif (isset($options[self::NOS_SEEK_TO]) && isset($length)) {
                    $length -= (integer) $options[self::NOS_SEEK_TO];
                }
                $request->set_read_stream_size($length);
            }
        }
        if (isset($options[self::NOS_SEEK_TO])) {
            $request->set_seek_position((integer) $options[self::NOS_SEEK_TO]);
        }
        if (isset($options[self::NOS_FILE_DOWNLOAD])) {
            if (is_resource($options[self::NOS_FILE_DOWNLOAD])) {
                $request->set_write_stream($options[self::NOS_FILE_DOWNLOAD]);
            } else {
                $request->set_write_file($options[self::NOS_FILE_DOWNLOAD]);
            }
        }

        if (isset($options[self::NOS_METHOD])) {
            $request->set_method($options[self::NOS_METHOD]);
            $string_to_sign .= $options[self::NOS_METHOD] . "\n";
        }
        if (isset($options[self::NOS_CONTENT])) {
            $request->set_body($options[self::NOS_CONTENT]);
            $headers[self::NOS_CONTENT_LENGTH] = strlen($options[self::NOS_CONTENT]);
            //$headers[self::NOS_CONTENT_MD5] = md5($options[self::NOS_CONTENT]);
        }
        uksort($headers, 'strnatcasecmp');
        foreach ($headers as $header_key => $header_value) {
            $header_value = str_replace(array("\r","\n"), '', $header_value);
            if ($header_value !== '') {
                $request->add_header($header_key, $header_value);
            }
            if (strtolower($header_key) === 'content-md5' || strtolower($header_key) === 'content-type' ||
                strtolower($header_key) === 'date') {
                $string_to_sign .= $header_value . "\n";
            } elseif (substr(strtolower($header_key), 0, 6) === self::NOS_DEFAULT_PREFIX) {
                $string_to_sign .= strtolower($header_key) . ':' . $header_value . "\n";
            }
        }
        // 生成 signable_resource
        $signable_resource = $this->generateSignableResource($options);
        //no need to decode
        //$string_to_sign .= rawurldecode($signable_resource) . urldecode($signable_query_string);
        $string_to_sign .= $signable_resource . $signable_query_string;
        $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $this->accessKeySecret, true));
        $request->add_header('Authorization', 'NOS ' . $this->accessKeyId . ':' . $signature);
        if (isset($options[self::NOS_PREAUTH]) && (integer) $options[self::NOS_PREAUTH] > 0) {
            $signed_url = $this->requestUrl . $conjunction . self::NOS_URL_ACCESS_KEY_ID . '=' .
                urlencode($this->accessKeyId) . '&' . self::NOS_URL_EXPIRES . '=' . $options[self::NOS_PREAUTH] .
                '&' . self::NOS_URL_SIGNATURE . '=' . urlencode($signature);
            return $signed_url;
        } elseif (isset($options[self::NOS_PREAUTH])) {
            return $this->requestUrl;
        }

        if ($this->timeout !== 0) {
            $request->timeout = $this->timeout;
        }
        if ($this->connectTimeout !== 0) {
            $request->connect_timeout = $this->connectTimeout;
        }

        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            throw (new NosException('RequestCoreException: ' . $e->getMessage()));
        }
        $response_header = $request->get_response_header();
        $response_header['nos-request-url'] = $this->requestUrl;
        $response_header['nos-redirects'] = $this->redirects;
        $response_header['nos-stringtosign'] = $string_to_sign;
        $response_header['nos-requestheaders'] = $request->request_headers;

        $data = new ResponseCore($response_header, $request->get_response_body(), $request->get_response_code());
        // retry if nos Internal Error
        if ((integer) $request->get_response_code() === 500) {
            if ($this->redirects <= $this->maxRetries) {
                // 设置休眠
                $delay = (integer) (pow(4, $this->redirects) * 100000);
                usleep($delay);
                $this->redirects ++;
                $data = $this->auth($options);
            }
        }

        $this->redirects = 0;
        return $data;
    }

    /**
     * 设置最大尝试次数
     *
     * @param int $maxRetries
     * @return void
     */
    public function setMaxTries($maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * 获取最大尝试次数
     *
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->maxRetries;
    }

    /**
     *
     * @return boolean
     */
    public function isUseSSL()
    {
        return $this->useSSL;
    }

    /**
     *
     * @param boolean $useSSL
     */
    public function setUseSSL($useSSL)
    {
        $this->useSSL = $useSSL;
    }

    /**
     * 检查bucket名称格式是否正确，如果非法抛出异常
     *
     * @param
     *            $options
     * @throws NOSException
     */
    private function authPrecheckBucket($options)
    {
        // [object =='/',bucket == '', method == get] list bucket请求,不需要验证bucket
        if (! (('/' == $options[self::NOS_OBJECT]) && ('' == $options[self::NOS_BUCKET]) &&
            ('GET' == $options[self::NOS_METHOD])) && ! NosUtil::validateBucket($options[self::NOS_BUCKET])) {
            throw new NosException('"' . $options[self::NOS_BUCKET] . '"' . 'bucket name is invalid');
        }
    }

    /**
     * 检查object名称格式是否正确，如果非法抛出异常
     *
     * @param
     *            $options
     * @throws NOSException
     */
    private function authPrecheckObject($options)
    {
        if (isset($options[self::NOS_OBJECT]) && $options[self::NOS_OBJECT] === '/') {
            return;
        }

        if (isset($options[self::NOS_OBJECT]) && ! NosUtil::validateObject($options[self::NOS_OBJECT])) {
            throw new NosException('"' . $options[self::NOS_OBJECT] . '"' . ' object name is invalid');
        }
    }

    /**
     * 检查object的编码，如果是gbk或者gb2312则尝试将其转化为utf8编码
     *
     * @param mixed $options参数
     *
     */
    private function authPrecheckObjectEncoding(&$options)
    {
        $tmp_object = $options[self::NOS_OBJECT];
        try {
            if (NosUtil::isGb2312($options[self::NOS_OBJECT])) {
                $options[self::NOS_OBJECT] = iconv('GB2312', "UTF-8//IGNORE", $options[self::NOS_OBJECT]);
            } elseif (NosUtil::checkChar($options[self::NOS_OBJECT], true)) {
                $options[self::NOS_OBJECT] = iconv('GBK', "UTF-8//IGNORE", $options[self::NOS_OBJECT]);
            }
        } catch (\Exception $e) {
            try {
                $tmp_object = iconv(mb_detect_encoding($tmp_object), "UTF-8", $tmp_object);
            } catch (\Exception $e) {}
        }
        $options[self::NOS_OBJECT] = $tmp_object;
    }

    /**
     * 检查acl规则，只允许私有或者共有读
     *
     * @param unknown $options
     * @throws NosException
     */
    private function authPrecheckAcl(&$options)
    {
        if (isset($options[self::NOS_HEADERS][self::NOS_ACL]) && ! empty($options[self::NOS_HEADERS][self::NOS_ACL])) {
            if (! in_array(strtolower($options[self::NOS_HEADERS][self::NOS_ACL]), self::$NOS_ACL_TYPES)) {
                throw new NosException($options[self::NOS_HEADERS][self::NOS_ACL] . ':' . 'acl is invalid(private,public-read)');
            }
        }
    }

    /**
     * 获得当次请求使用的域名，根据请求host类型的不同而不同
     * bucket在前的三级域名，或者二级域名，如果是cname或者ip的话，则是二级域名
     *
     * @param
     *            $options
     * @return string 剥掉协议头的域名
     */
    private function generateHostname($options)
    {
        if ($this->hostType === self::NOS_HOST_TYPE_IP) {
            $hostname = $this->hostname;
        } elseif ($this->hostType === self::NOS_HOST_TYPE_CNAME) {
            $hostname = $this->hostname;
        } else {
            // 使用三级域名的方式
            $hostname = ($options[self::NOS_BUCKET] == '') ? $this->hostname : ($options[self::NOS_BUCKET] . '.') . $this->hostname;
        }
        return $hostname;
    }

    /**
     * 获得当次请求的资源定位字段
     *
     * @param
     *            $options
     * @return string 资源定位字段
     */
    private function generateResourceUri($options)
    {
        $resource_uri = "";

        // resource_uri + bucket
        if (isset($options[self::NOS_BUCKET]) && '' !== $options[self::NOS_BUCKET]) {
            if ($this->hostType === self::NOS_HOST_TYPE_IP) {
                $resource_uri = '/' . $options[self::NOS_BUCKET];
            }
        }

        // resource_uri + object
        if (isset($options[self::NOS_OBJECT]) && '/' !== $options[self::NOS_OBJECT]) {
            $resource_uri .= '/' . NosUtil::objectEncode($options[self::NOS_OBJECT]);
        }

        // resource_uri + sub_resource
        $conjunction = '?';
        if (isset($options[self::NOS_SUB_RESOURCE])) {
            $resource_uri .= $conjunction . $options[self::NOS_SUB_RESOURCE];
        }
        return $resource_uri;
    }

    /**
     * 生成signable_query_string_param, array类型
     *
     * @param array $options
     * @return array
     */
    private function generateSignableQueryStringParam($options)
    {
        $signableQueryStringParams = array();
        $signableList = array(
            self::NOS_PART_NUM,
            self::NOS_UPLOAD_ID,
        );

        foreach ($signableList as $item) {
            if (isset($options[$item])) {
                $signableQueryStringParams[$item] = $options[$item];
            }
        }
        return $signableQueryStringParams;
    }

    /**
     * 生成用于签名resource段
     *
     * @param mixed $options
     * @return string
     */
    private function generateSignableResource($options)
    {
        $signableResource = "";
        $signableResource .= '/';
        if (isset($options[self::NOS_BUCKET]) && '' !== $options[self::NOS_BUCKET]) {
            $signableResource .= $options[self::NOS_BUCKET];
            // 如果操作没有Object操作的话，这里最后是否有斜线有个trick，ip的域名下，不需要加'/'， 否则需要加'/'
            if ($options[self::NOS_OBJECT] == '/') {
                $signableResource .= "/";
            }
        }
        // signable_resource + object
        if (isset($options[self::NOS_OBJECT]) && '/' !== $options[self::NOS_OBJECT]) {
            $signableResource .= '/' . NosUtil::objectEncode($options[self::NOS_OBJECT]);
        }

        $signableSubResourceList = array(
            'acl',
            'location',
            'versioning',
            'uploads',
            'delete',
            'deduplication'
        );
        if (isset($options[self::NOS_SUB_RESOURCE])) {
           if(in_array($options[self::NOS_SUB_RESOURCE], $signableSubResourceList))
           {
               $signableResource .= '?' . $options[self::NOS_SUB_RESOURCE];
           }
        }
        return $signableResource;
    }

    /**
     * 生成query_string
     *
     * @param mixed $options
     * @return string
     */
    private function generateQueryString($options)
    {
        // 请求参数
        $queryStringParams = array();
        if (isset($options[self::NOS_QUERY_STRING])) {
            $queryStringParams = array_merge($queryStringParams, $options[self::NOS_QUERY_STRING]);
        }
        return NosUtil::toQueryString($queryStringParams);
    }

    /**
     * 初始化headers
     *
     * @param mixed $options
     * @param string $hostname
     *            hostname
     * @return array
     */
    private function generateHeaders($options, $hostname)
    {
        $headers = array(
            self::NOS_CONTENT_MD5 => '',
            self::NOS_CONTENT_TYPE => isset($options[self::NOS_CONTENT_TYPE]) ? $options[self::NOS_CONTENT_TYPE] : self::DEFAULT_CONTENT_TYPE,
            self::NOS_DATE => isset($options[self::NOS_DATE]) ? $options[self::NOS_DATE] : gmdate('D, d M Y H:i:s \G\M\T'),
            self::NOS_HOST => $hostname
        );
        if (isset($options[self::NOS_CONTENT_MD5])) {
            $headers[self::NOS_CONTENT_MD5] = $options[self::NOS_CONTENT_MD5];
        }

        // 合并HTTP headers
        if (isset($options[self::NOS_HEADERS])) {
            $headers = array_merge($headers, $options[self::NOS_HEADERS]);
        }
        return $headers;
    }

    /**
     * 生成请求用的UserAgent
     *
     * @return string
     */
    private function generateUserAgent()
    {
        return self::NOS_NAME . "/" . self::NOS_VERSION . " (" . php_uname('s') . "/" . php_uname('r') . "/" . php_uname('m') . ";" . PHP_VERSION . ")";
    }

    /**
     * 检查endpoint的种类
     * 如有有协议头，剥去协议头
     *
     * @param string $endpoint
     * @param boolean $isCName
     * @return string 剥掉协议头的域名
     */
    private function checkEndpoint($endpoint, $isCName)
    {
        $ret_endpoint = null;
        if (strpos($endpoint, 'http://') === 0) {
            $ret_endpoint = substr($endpoint, strlen('http://'));
        } elseif (strpos($endpoint, 'https://') === 0) {
            $ret_endpoint = substr($endpoint, strlen('https://'));
            $this->useSSL = true;
        } else {
            $ret_endpoint = $endpoint;
        }
        if ($isCName) {
            $this->hostType = self::NOS_HOST_TYPE_CNAME;
        } elseif (NosUtil::isIPFormat($ret_endpoint)) {
            $this->hostType = self::NOS_HOST_TYPE_IP;
        } else {
            $this->hostType = self::NOS_HOST_TYPE_NORMAL;
        }
        return $ret_endpoint;
    }

    /**
     * 用来检查sdk所以来的扩展是否打开
     *
     * @throws NOSException
     */
    public static function checkEnv()
    {
        if (function_exists('get_loaded_extensions')) {
            // 检测curl扩展
            $enabled_extension = array(
                "curl"
            );
            $extensions = get_loaded_extensions();
            if ($extensions) {
                foreach ($enabled_extension as $item) {
                    if (! in_array($item, $extensions)) {
                        throw new NosException("Extension {" . $item . "} is not installed or not enabled, please check your php env.");
                    }
                }
            } else {
                throw new NosException("function get_loaded_extensions not found.");
            }
        } else {
            throw new NosException('Function get_loaded_extensions has been disabled, please check php config.');
        }
    }

    /**
     * 设置http库的请求超时时间，单位秒
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * 设置http库的连接超时时间，单位秒
     *
     * @param int $connectTimeout
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    // NOS 内部常量
    const NOS_BUCKET = 'bucket';
    const NOS_OBJECT = 'object';
    const NOS_HEADERS = NosUtil::NOS_HEADERS;
    const NOS_METHOD = 'method';
    const NOS_QUERY = 'query';
    const NOS_UPLOAD_ID = 'uploadId';
    const NOS_PART_NUM = 'partNumber';
    const NOS_MAX_PART_SIZE = NosUtil::NOS_MAX_PART_SIZE;
    const NOS_MID_PART_SIZE = NosUtil::NOS_MID_PART_SIZE;
    const NOS_MIN_PART_SIZE = NosUtil::NOS_MIN_PART_SIZE;
    const NOS_CONTENT_MD5 = 'Content-MD5';
    const NOS_CONTENT_TYPE = 'Content-Type';
    const NOS_CONTENT_LENGTH = 'Content-Length';
    const NOS_CONTENT_ENCODING = 'Content_Encoding';
    const NOS_CONTENT_LANGUAGE = 'Content_Language';
    const NOS_IF_MODIFIED_SINCE = 'If-Modified-Since';
    const NOS_IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    const NOS_IF_MATCH = 'If-Match';
    const NOS_IF_NONE_MATCH = 'If-None-Match';
    const NOS_CACHE_CONTROL = 'Cache-Control';
    const NOS_EXPIRES = 'Expires';
    const NOS_PREAUTH = 'preauth';
    const NOS_CONTENT_DISPOSTION = 'Content-Disposition';
    const NOS_RANGE = 'range';
    const NOS_ETAG = 'etag';
    const NOS_LAST_MODIFIED = 'lastmodified';
    const OS_CONTENT_RANGE = 'Content-Range';
    const NOS_CONTENT = NosUtil::NOS_CONTENT;
    const NOS_BODY = 'body';
    const NOS_LENGTH = NosUtil::NOS_LENGTH;
    const NOS_HOST = 'Host';
    const NOS_DATE = 'Date';
    const NOS_AUTHORIZATION = 'Authorization';
    const NOS_FILE_DOWNLOAD = 'fileDownload';
    const NOS_FILE_UPLOAD = 'fileUpload';
    const NOS_PART_SIZE = 'partSize';
    const NOS_SEEK_TO = 'seekTo';
    const NOS_SIZE = 'size';
    const NOS_QUERY_STRING = 'query_string';
    const NOS_SUB_RESOURCE = 'sub_resource';
    const NOS_DEFAULT_PREFIX = 'x-nos-';
    const NOS_CHECK_MD5 = 'checkMD5';
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    const NOS_VERSING_ENABLE = 'enabled';
    const NOS_VERSING_SUSPEND = 'Suspended';
    static $NOS_VERSING_TYPES = array(
        self::NOS_VERSING_ENABLE,
        self::NOS_VERSING_SUSPEND
    );

   const NOS_DUPLICATION_ENABLE = 'Enabled';
   const NOS_DUPLICATION_SUSPEND = 'Suspended';
   static $NOS_DUPLICATION_TYPES = array(
       self::NOS_DUPLICATION_ENABLE,
       self::NOS_DUPLICATION_SUSPEND
   );

    // 私有URL变量
    const NOS_URL_ACCESS_KEY_ID = 'NOSAccessKeyId';
    const NOS_URL_EXPIRES = 'Expires';
    const NOS_URL_SIGNATURE = 'Signature';

    // HTTP方法
    const NOS_HTTP_GET = 'GET';
    const NOS_HTTP_PUT = 'PUT';
    const NOS_HTTP_HEAD = 'HEAD';
    const NOS_HTTP_POST = 'POST';
    const NOS_HTTP_DELETE = 'DELETE';
    const NOS_HTTP_OPTIONS = 'OPTIONS';

    // 其他常量
    const NOS_ACL = 'x-nos-acl';
    const NOS_MULTI_PART = 'uploads';
    const NOS_MULTI_DELETE = 'delete';
    const NOS_OBJECT_COPY_SOURCE = 'x-nos-copy-source';
    const NOS_OBJECT_MOVE_SOURCE = 'x-nos-move-source';
    const NOS_OBJECT_MD5 = 'x-nos-object-md5';
    const NOS_ACL_TYPE_PRIVATE = 'private';
    const NOS_ACL_TYPE_PUBLIC_READ = 'public-read';
    const NOS_ENCODING_TYPE = "encoding-type";
    const NOS_ENCODING_TYPE_URL = "url";

    // 域名类型
    const NOS_HOST_TYPE_NORMAL = "normal";
    // http://bucket.nos.netease.com
    const NOS_HOST_TYPE_IP = "ip";
    // http://1.1.1.1/bucket/object
    const NOS_HOST_TYPE_SPECIAL = 'special';
    // http://bucket.guizhou.gov/object
    const NOS_HOST_TYPE_CNAME = "cname";
    // http://mydomain.com/object

    // NOS ACL数组
    static $NOS_ACL_TYPES = array(
        self::NOS_ACL_TYPE_PRIVATE,
        self::NOS_ACL_TYPE_PUBLIC_READ
    );
    // NOSClient版本信息
    const NOS_NAME = "nos-php-sdk";
    const NOS_VERSION = "1.0.0";
    const NOS_BUILD = "20160730";
    const NOS_OPTIONS_ORIGIN = 'Origin';

    // 是否使用ssl
    private $useSSL = false;
    private $maxRetries = 3;
    private $redirects = 0;

    // 用户提供的域名类型，有四种 NOS_HOST_TYPE_NORMAL, NOS_HOST_TYPE_IP, NOS_HOST_TYPE_SPECIAL, NOS_HOST_TYPE_CNAME
    private $hostType = self::NOS_HOST_TYPE_NORMAL;
    private $requestUrl;
    private $accessKeyId;
    private $accessKeySecret;
    private $hostname;
    private $timeout = 0;
    private $connectTimeout = 0;
}