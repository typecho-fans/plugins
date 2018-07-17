<?php
namespace NOS\Result;

use NOS\Core\NosException;
use NOS\Model\DeleteFailedInfo;

class MultiDeleteResult extends Result
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
        if(!isset($xml->Error) && !isset($xml->Deleted)){
            return null;
        }
        $succ = array();
        foreach ($xml->Deleted as $deleted){
            $succ[] = strval($deleted->Key);
        }
        $fails = array();
        foreach ($xml->Error as $err){
           $failInfo = new DeleteFailedInfo(strval($err->Key),strval($err->Code),strval($err->Message));
           $fails[] = $failInfo;
        }
        $result = array();
        if(count($succ)){
            $result['succeed'] = $succ;
        }
        if(count($fails)){
            $result['failed'] = $fails;
        }
        return $result;
    }
}

