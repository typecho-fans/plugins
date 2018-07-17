<?php
namespace NOS\Model;

class UploadInfo
{

    public function __construct($key,$uploadId,$storageClass)
    {
        $this->key = $key;
        $this->uploadId = $uploadId;
        $this->storageClass = $storageClass;
    }

    /**
     * @return the $bucket
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


    private $key = '';
    private $uploadId = '';
    private $storageClass ='';
}

