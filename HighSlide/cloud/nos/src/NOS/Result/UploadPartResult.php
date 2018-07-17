<?php
namespace NOS\Result;
use NOS\Core\NosException;

class UploadPartResult extends Result
{
    protected function parseDataFromResponse()
    {
        $header = $this->rawResponse->header;
        if (isset($header["etag"])) {
            return trim($header["etag"],"\"");
        }
        throw new NosException("cannot get ETag");
    }
}

