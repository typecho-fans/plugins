<?php
namespace NOS\Result;

use NOS\Core\NosException;

class aclResult extends Result
{
    /**
     * 获取返回头中的x-nos-acl
     * {@inheritDoc}
     * @see \NOS\Result\Result::parseDataFromResponse()
     */
    protected function parseDataFromResponse()
    {
        if(isset($this->rawResponse->header['x-nos-acl']))
        {
            $acl_type = strval($this->rawResponse->header['x-nos-acl']);
            return $acl_type;
        }
        else
        {
            throw new NosException("invalid response,no require head found");
        }
    }
}