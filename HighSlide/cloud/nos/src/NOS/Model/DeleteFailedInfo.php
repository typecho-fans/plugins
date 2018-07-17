<?php
namespace NOS\Model;

class DeleteFailedInfo
{

    public function __construct($key,$code,$message){
        $this->key = $key;
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @return the $key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return the $code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return the $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }


    private $key = '';
    private $code = '';
    private $message = '';
}

