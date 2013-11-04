<?php
/**************************************************************************
 *
 * Copyright (c) 2011 Baidu.com, Inc. All Rights Reserved
 *
 *************************************************************************/

require_once 'BaeException.class.php';

/**
 * Base class for the Bae Open API.
 * 
 * @package   BaeOpenAPI
 * @author    yexw(yexinwei@baidu.com)
 * @version   $Revision: 1.10 $
 **/
class BaeBase
{
    public    $errcode;
    public    $errmsg;
    protected $_handle = null;

    /**
     * Class constructor
     * 
     */
    public function __construct()
    {
        //todo
    }

    /**
     * @brief Generates a user-level error/warning/notice message
     * 
     * @param string $error_msg     The designated error message for this error. 
     * @param string $error_type The designated error type for this error.It  
     * only works with the E_USER family of constants.
     */
    public function error($error_msg, $error_type = E_USER_ERROR)
    {
        echo '<pre>';
        debug_print_backtrace();
        echo '</pre>';
        trigger_error($error_msg, $error_type);
    }

    /**
     * @brief return the handle
     * 
     */
    public function getHandle()
    {
        return $this->_handle;
    }

    /**
     * @brief return the error message
     * 
     */
    public function errmsg()
    {
        return $this->errmsg;
    }

    /**
     * @brief return the error code
     * 
     */
    public function errno()
    {
        return $this->errcode;
    }

}
