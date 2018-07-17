<?php
namespace NOS\Result;

use NOS\Core\NosException;

class ObjDeduplicateResult extends Result
{
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if(!isset($content) || empty($content))
        {
            throw new NosException("invalid response,empty rsoponse body");
        }
        try {
            $xml = simplexml_load_string($content);
        }catch(\Exception $e)
        {
            throw new NosException("invalid response,rsoponse body is invalid xml");
        }
        if(isset($xml->ObjectContentAlreadyExist))
        {
            return strval($xml->ObjectContentAlreadyExist);
        }
        throw new NosException("invalid response,no ObjectContentAlreadyExist found,xml format exception");
    }
}

