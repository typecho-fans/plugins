<?php

namespace NOS\Model;

/**
 * Class ObjectListInfo
 *
 * ListObjects接口返回数据
 *
 * @package NOS\Model
 */
class ObjectListInfo
{
    /**
     * ObjectListInfo constructor.
     *
     * @param string $bucketName
     * @param string $prefix
     * @param string $marker
     * @param string $nextMarker
     * @param string $maxKeys
     * @param string $delimiter
     * @param null $isTruncated
     * @param array $objectList
     * @param array $prefixList
     */
    public function __construct($bucketName, $prefix, $commonPrefixes, $marker, $nextMarker, $maxKeys, $isTruncated, array $objectList)
    {
        $this->bucketName = $bucketName;
        $this->prefix = $prefix;
        $this->marker = $marker;
        $this->nextMarker = $nextMarker;
        $this->maxKeys = $maxKeys;
        $this->isTruncated = $isTruncated;
        $this->objectList = $objectList;
        $this->commonPrefixes = $commonPrefixes;
    }

    /**
     * @return the $commonPrefixes
     */
    public function getCommonPrefixes()
    {
        return $this->commonPrefixes;
    }

    /**
     * @param Ambigous <string, unknown> $commonPrefixes
     */
    public function setCommonPrefixes($commonPrefixes)
    {
        $this->commonPrefixes = $commonPrefixes;
    }

    /**
     * @return string
     */
    public function getBucketName()
    {
        return $this->bucketName;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getMarker()
    {
        return $this->marker;
    }

    /**
     * @return int
     */
    public function getMaxKeys()
    {
        return $this->maxKeys;
    }


    /**
     * @return mixed
     */
    public function getIsTruncated()
    {
        return $this->isTruncated;
    }

    /**
     * 返回ListObjects接口返回数据中的ObjectInfo列表
     *
     * @return ObjectInfo[]
     */
    public function getObjectList()
    {
        return $this->objectList;
    }

    /**
     * @return string
     */
    public function getNextMarker()
    {
        return $this->nextMarker;
    }

    private $bucketName = '';
    private $prefix = '';
    private $marker = '';
    private $nextMarker = '';
    private $maxKeys = 0;
    private $isTruncated = null;
    private $objectList = array();
    private $commonPrefixes = '';
}