<?php
namespace NOS\Result;

use NOS\Model\ObjectInfo;
use NOS\Model\ObjectListInfo;
use NOS\Core\NosException;

/**
 * Class ListObjectsResult
 *
 * @package NOS\Result
 */
class ListObjectsResult extends Result
{

    private function parseObjectList($xml)
    {
        $objectList = array();
        if (isset($xml->Contents)) {
            foreach ($xml->Contents as $content) {
                $key = isset($content->Key) ? strval($content->Key) : '';
                $lastModified = isset($content->LastModified) ? strval($content->LastModified) : '';
                $eTag = isset($content->ETag) ? strval($content->ETag) : '';
                $size = isset($content->Size) ? intval($content->Size) : 0;
                $storageClass = isset($content->StorageClass) ? strval($content->StorageClass) : "";
                $objectList[] = new ObjectInfo($key, $lastModified, $eTag, $size, $storageClass);
            }
        }
        return $objectList;
    }

    /**
     * 解析ListObjects接口返回的xml数据
     *
     * return ObjectListInfo
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if(!isset($content) || empty($content))
        {
            throw new NosException("invalid response,empty response body");
        }
        try {
            $xml = new \SimpleXMLElement($this->rawResponse->body);
        } catch (\Exception $e) {
            throw new NosException("invalid response,response body invalid xml");
        }
        $bucketName = isset($xml->Name) ? strval($xml->Name) : '';
        $prfix = isset($xml->Prefix) ? strval($xml->Prefix) : '';
        $maxKeys = isset($xml->MaxKeys) ? intval($xml->MaxKeys) : '';
        $marker = isset($xml->Marker) ? strval($xml->Marker) : '';
        $nextMarker = isset($xml->NextMarker) ? strval($xml->NextMarker) : '';
        $isTruncatedStr = isset($xml->IsTruncated) ? strval($xml->IsTruncated) : '';
        $isTruncated = $isTruncatedStr === "true" ? true : false;
        $commonPrefixes = isset($xml->CommonPrefixes) ? strval($xml->CommonPrefixes->Prefix) : '';
        $objectList = $this->parseObjectList($xml);
        return new ObjectListInfo($bucketName, $prfix, $commonPrefixes, $marker, $nextMarker, $maxKeys, $isTruncated, $objectList);
    }
}