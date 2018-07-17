<?php
namespace NOS\Result;

use NOS\Model\BucketInfo;
use NOS\Model\BucketListInfo;
use NOS\Core\NosException;

/**
 * Class ListBucketsResult
 *
 * @package NOS\Result
 */
class ListBucketsResult extends Result
{

    /**
     *
     * @return BucketListInfo
     */
    protected function parseDataFromResponse()
    {
        $bucketList = array();
        $content = $this->rawResponse->body;
        if (! isset($content) || empty($content)) {
            throw new NosException("invalid response,empty response body");
        }
        try {
            $xml = new \SimpleXMLElement($content);
        } catch (\Exception $e) {
            throw new NosException("invalid response,response body is invalid xml");
        }
        if (isset($xml->Buckets) && isset($xml->Buckets->Bucket)) {
            foreach ($xml->Buckets->Bucket as $bucket) {
                $bucketInfo = new BucketInfo(strval($bucket->Name), strval($bucket->CreationDate));
                $bucketList[] = $bucketInfo;
            }
        }
        return new BucketListInfo($bucketList);
    }
}