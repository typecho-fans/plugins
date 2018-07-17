<?php
namespace NOS\Result;

class BodyResult extends Result
{
    protected function parseDataFromResponse()
    {
        return empty($this->rawResponse->body) ? "" : $this->rawResponse->body;
    }
}

