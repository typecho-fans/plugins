<?php
namespace NOS\Model;

class ListPartsInfo
{

    public function __construct($bucket, $key, $uploadId, $storageClass, $partNumberMarker, $nextPartNumberMarker, $maxParts, $isTruncated, array $listPart)
    {
        $this->bucket = $bucket;
        $this->key = $key;
        $this->uploadId = $uploadId;
        $this->storageClass = $storageClass;
        $this->partNumberMarker = $partNumberMarker;
        $this->nextPartNumberMarker = $nextPartNumberMarker;
        $this->maxParts = $maxParts;
        $this->isTruncated = $isTruncated;
        $this->listPart = $listPart;
    }

    /**
     * @return the $bucket
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return the $key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return the $uploadId
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * @return the $storageClass
     */
    public function getStorageClass()
    {
        return $this->storageClass;
    }

    /**
     * @return the $partNumberMarker
     */
    public function getPartNumberMarker()
    {
        return $this->partNumberMarker;
    }

    /**
     * @return the $nextPartNumberMarker
     */
    public function getNextPartNumberMarker()
    {
        return $this->nextPartNumberMarker;
    }

    /**
     * @return the $maxParts
     */
    public function getMaxParts()
    {
        return $this->maxParts;
    }

    /**
     * @return the $isTruncated
     */
    public function getIsTruncated()
    {
        return $this->isTruncated;
    }

    /**
     * @return the $listPart
     */
    public function getListPart()
    {
        return $this->listPart;
    }


    private $bucket = '';
    private $key = '';
    private $uploadId = '';
    private $storageClass = '';
    private $partNumberMarker = 0;
    private $nextPartNumberMarker = 0;
    private $maxParts = 0;
    private $isTruncated = NULL;
    private $listPart =array();
}

