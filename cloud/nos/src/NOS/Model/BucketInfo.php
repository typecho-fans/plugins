<?php

namespace NOS\Model;


/**
 * Bucket信息，ListBuckets接口返回数据
 *
 * Class BucketInfo
 * @package NOS\Model
 */
class BucketInfo
{
    /**
     * BucketInfo constructor.
     *
     * @param string $name
     * @param string $createDate
     */
    public function __construct($name,$createDate)
    {
        $this->name = $name;
        $this->createDate = $createDate;
    }

    /**
     * 得到bucket的名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 得到bucket的创建时间
     *
     * @return string
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * bucket的名称
     *
     * @var string
     */
    private $name;

    /**
     * bucket的创建事件
     *
     * @var string
     */
    private $createDate;
}