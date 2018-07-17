<?php
namespace NOS\Result;

use NOS\Core\NosException;

class StatusResult extends Result
{

    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if(empty($content))
        {
            throw new NosException("invalid response,,empty response body");
        }
        try {
            $xml = simplexml_load_string($content);
        }catch(\Exception $e)
        {
            throw new NosException("invalid response,,response body is invalid xml");
        }
        if(isset($xml->Status))
        {
            return strval($xml->Status);
        }
        else
        {
            throw new NosException("invalid response,no Status found,xml format exception");
        }
    }
}