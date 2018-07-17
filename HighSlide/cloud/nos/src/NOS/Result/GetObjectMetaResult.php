<?php
namespace NOS\Result;

class GetObjectMetaResult extends Result
{

    protected function parseDataFromResponse()
    {
        return $this->rawResponse->header;
    }
}

