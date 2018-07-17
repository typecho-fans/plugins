<?php
namespace NOS\Model;

class ListMultipartUploadInfo
{

    public function __construct($bucket,$nextKeyMarker,$isTruncated,$uploads)
    {
        $this->bucket = $bucket;
        $this->nextKeyMarker = $nextKeyMarker;
        $this->uploads = $uploads;
        $this->isTruncated = $isTruncated;
    }

    /**
     * @return the $bucket
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return the $isTruncted
     */
    public function getIsTruncated()
    {
        return $this->isTruncated;
    }

    /**
     * @return the $nextKeyMarker
     */
    public function getNextKeyMarker()
    {
        return $this->nextKeyMarker;
    }

    /**
     * @return the $uploads
     */
    public function getUploads()
    {
        return $this->uploads;
    }

    private $bucket = '';
    private $nextKeyMarker = '';
    private $isTruncated = '';
    private $uploads = array();
}

