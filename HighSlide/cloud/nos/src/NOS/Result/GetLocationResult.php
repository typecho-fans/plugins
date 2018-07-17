<?php
namespace NOS\Result;

use NOS\Core\NosException;

class GetLocationResult extends Result
{

    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if (!isset($content) || empty($content)) {
            throw new NosException("invalid response,empty response body");
        }
        try {
            $xml = simplexml_load_string($content);
        } catch (\Exception $e) {
            throw new NosException("invalid response,response body is invalid xml");
        }
        $location = strval($xml['0']);
        if ($xml->count() != 0) {
            throw new NosException("invalid response,child found,xml format exception");
        }
        return $location;
    }
}

