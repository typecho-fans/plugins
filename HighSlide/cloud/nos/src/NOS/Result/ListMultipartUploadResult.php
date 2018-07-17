<?php
namespace NOS\Result;

use NOS\Model\UploadInfo;
use NOS\Model\ListMultipartUploadInfo;
use NOS\Core\NosException;

class ListMultipartUploadResult extends Result
{

    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if (! isset($content) || empty($content)) {
            throw new NosException("invalid response,empty response body");
        }
        try {
            $xml = simplexml_load_string($content);
        } catch (\Exception $e) {
            throw new NosException("invalid response,response body is invalid xml");
        }

        $bucket = isset($xml->Bucket) ? strval($xml->Bucket) : "";
        $nextKeyMarker = isset($xml->NextKeyMarker) ? strval($xml->NextKeyMarker) : "";
        $isTruncated = isset($xml->IsTruncated)? strval($xml->IsTruncated):'';
        $listUpload = array();

        if (isset($xml->Upload)) {
            foreach ($xml->Upload as $upload) {
                $key = isset($upload->Key) ? strval($upload->Key) : "";
                $uploadId = isset($upload->UploadId) ? strval($upload->UploadId) : "";
                $storageClass = isset($upload->StorageClass) ? strval($upload->StorageClass) : "";
                $listUpload[] = new UploadInfo($key, $uploadId, $storageClass);
            }
        }
        return new ListMultipartUploadInfo($bucket, $nextKeyMarker,$isTruncated, $listUpload);
    }
}

