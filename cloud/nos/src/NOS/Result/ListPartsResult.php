<?php
namespace NOS\Result;

use NOS\Model\PartInfo;
use NOS\Model\ListPartsInfo;
use NOS\Core\NosException;

class ListPartsResult extends Result
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
            throw new NosException("invalid response,response body invalid xml");
        }
        $bucket = isset($xml->Bucket) ? strval($xml->Bucket) : '';
        $key = isset($xml->Key) ? strval($xml->Key) : '';
        $uploadId = isset($xml->UploadId) ? strval($xml->UploadId) : '';
        $storageClass = isset($xml->StorageClass) ? strval($xml->StorageClass) : '';
        $partNumberMarker = isset($xml->PartNumberMarker) ? intval($xml->PartNumberMarker) : '';
        $nextPartNumberMarker = isset($xml->NextPartNumberMarker) ? intval($xml->NextPartNumberMarker) : '';
        $maxParts = isset($xml->MaxParts) ? intval($xml->MaxParts) : '';
        $isTruncated = isset($xml->IsTruncated) ? strval($xml->IsTruncated) : '';
        $partList = array();
        if (isset($xml->Part)) {
            foreach ($xml->Part as $part) {
                $partNumber = isset($part->PartNumber) ? intval($part->PartNumber) : '';
                $lastModified = isset($part->LastModified) ? strval($part->LastModified) : '';
                $eTag = isset($part->ETag) ? strval($part->ETag) : '';
                $size = isset($part->Size) ? intval($part->Size) : '';
                $partList[] = new PartInfo($partNumber, $lastModified, $eTag, $size);
            }
        }
        return new ListPartsInfo($bucket, $key, $uploadId, $storageClass, $partNumberMarker, $nextPartNumberMarker, $maxParts, $isTruncated, $partList);
    }
}

