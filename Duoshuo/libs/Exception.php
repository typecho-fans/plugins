<?php
/**
 * DuoshuoSDK Exception类定义
 *
 * @version		$Id: Exception.php 0 10:17 2012-4-27 
 * @author 		allen 
 * @copyright	Copyright (c) 2012 - , Duoshuo, Inc.
 * @link		http://dev.duoshuo.com
 */
class Duoshuo_Exception extends Exception{
	const SUCCESS		= 0;
	const ENDPOINT_NOT_VALID = 1;
	const MISSING_OR_INVALID_ARGUMENT = 2;
	const ENDPOINT_RESOURCE_NOT_VALID = 3;
	const NO_AUTHENTICATED = 4;
	const INVALID_API_KEY = 5;
	const INVALID_API_VERSION = 6;
	const CANNOT_ACCESS = 7;
	const OBJECT_NOT_FOUND = 8;
	const API_NO_PRIVILEGE = 9;
	const OPERATION_NOT_SUPPORTED = 10;
	const API_KEY_INVALID = 11;
	const NO_PRIVILEGE = 12;
	const RESOURCE_RATE_LIMIT_EXCEEDED = 13;
	const ACCOUNT_RATE_LIMIT_EXCEEDED = 14;
	const INTERNAL_SERVER_ERROR = 15;
	const REQUEST_TIMED_OUT = 16;
	const NO_ACCESS_TO_THIS_FEATURE = 17;
	const INVALID_SIGNATURE = 18;

	const USER_DENIED_YOUR_REQUEST = 21;
	const EXPIRED_TOKEN = 22;
	const REDIRECT_URI_MISMATCH = 23;
	const DUPLICATE_CONNECTED_ACCOUNT = 24;

	const PLUGIN_DEACTIVATED = 30;
}
