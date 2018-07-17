<?php
namespace NOS\Model;

class PartInfo
{

    public function __construct($partNumber,$lastModified,$eTag,$size)
    {
        $this->partNumber = $partNumber;
        $this->lastModified = $lastModified;
        $this->eTag = $eTag;
        $this->size = $size;
    }

    /**
     * @return the $partNumber
     */
    public function getPartNumber()
    {
        return $this->partNumber;
    }

    /**
     * @return the $lastModified
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @return the $eTag
     */
    public function getETag()
    {
        return $this->eTag;
    }

    /**
     * @return the $size
     */
    public function getSize()
    {
        return $this->size;
    }

    private $partNumber = 0;
    private $lastModified ='';
    private $eTag = '';
    private $size = 0;
}

