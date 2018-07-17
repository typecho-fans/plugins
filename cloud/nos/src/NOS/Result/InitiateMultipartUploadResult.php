<?php
namespace NOS\Result;

use NOS\Core\NosException;

class InitiateMultipartUploadResult extends Result
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
        if (isset($xml->UploadId)) {
            return strval($xml->UploadId);
        }
        throw new NosException("invalid response,no UploadId found,xml format exception");
    }
}

