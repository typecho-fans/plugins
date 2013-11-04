<?php
/**
 * 百度云消息服务 PHP SDK
 * 
 * 本文件提供百度云消息服务的PHP版本SDK
 * 
 * @author 百度移动.云事业部
 * @copyright Copyright (c) 2012-2020 百度在线网络技术(北京)有限公司
 * @version 1.2.0
 * @package
 */
if (!defined('API_ROOT_PATH')) {
    define ( 'API_ROOT_PATH', dirname( __FILE__));
}
require_once(API_ROOT_PATH . '/lib/RequestCore.class.php');
require_once(API_ROOT_PATH . '/lib/BcmsException.class.php');
require_once(API_ROOT_PATH . '/lib/BaeBase.class.php');

/**
 * 
 * Bcms
 * 
 * Bcms类提供百度云消息服务的PHP版本SDK，用户首先实例化这个类，设置自己的accessKey、secretKey，即可使用百度云消息服务
 * 
 * @author 百度消息服务@百度云架构部
 * @version 1.2.0
 */
class Bcms extends BaeBase
{
    /**
     * 可选参数的KEY
     * 
     * 用户关注：是
     * 在调用Bcms类的SDK方法时，根据用户的个性化需要，可能需要传入可选参数，而可选参数需要放在关联数组$optional中传入，
     * 这里定义了$optional数组可用的KEY
     * 如，在调用dropQueue方法时，指定过期时间为5分钟，则这样使用SDK：
     *   $bcms = new Bcms ( $accessKey, $secretKey, $host );
     *   $optional [ Bcms::EXPIRES ] = 5 * 60;
     *   $bcms->dropQueue($queueName, $optional );
     */

    /**
     * 队列类型
     * 
     * createQueue创建队列时，用户可以指定要创建的队列类型，如果不指定，BCMS默认创建单模式队列, 0: 多模式，1: 单模式
     * @var string QUEUE_ALIAS_NAME
     */
    const QUEUE_TYPE = 'queue_type';
    /**
     * 队列别名
     * 
     * createQueue创建队列时，用户可以为队列指定一个别名
     * @var string QUEUE_ALIAS_NAME
     */
    const QUEUE_ALIAS_NAME = 'queue_alias_name';
    /**
     * 邮件发件人
     * 
     * 在调用mail方法发送邮件时，用户可能需要指定发件人邮箱
     * @var string FROM
     */
    const FROM = 'from';
    /**
     * 生效起始时间
     * 
     * 调用Grant赋予其它用户权限时，用户可能希望指定权限生效的时间
     * @var string EFFECT_START
     */
    const EFFECT_START = 'effect_start';
    /**
     * 生效结束时间
     * 
     * 调用Grant赋予其它用户权限时，用户可能希望指定权限生效的时间
     * @var string EFFECT_END
     */
    const EFFECT_END = 'effect_end';
    /**
     * 消息ID号
     * 
     * fetchMessage等，用户可能需要指定消息ID号
     * @var int MSG_ID
     */
    const MSG_ID = 'msg_id';
    /**
     * 要获取的消息个数
     * 
     * fetch消息时，用户可能需要指定要获取多少个消息
     * @var int FETCH_NUM
     */
    const FETCH_NUM = 'fetch_num';
    /**
     * 邮件标题
     * 
     * mail发送邮件时，用户可能需要指定邮件的标题
     * @var unknown_type
     */
    const MAIL_SUBJECT = 'mail_subject';
    /**
     * 请求发起时的时间戳
     * 
     * 用户一般不需要设置，SDK会自动设置为当前时间
     * @var int TIMESTAMP
     */
    const TIMESTAMP = 'timestamp';
    /**
     * 请求过期时间
     * 
     * 如果用户不设置，则默认10分钟后该请求过期
     * @var int EXPIRES
     */
    const EXPIRES = 'expires';
    /**
     * API版本号
     * 
     * 用户一般不需要设置
     * @var string VERSION
     */
    const VERSION = 'v';

    /**
     * Bcms常量
     * 
     * 用户关注：否
     */
    const QUEUE_NAME = 'queue_name';
    const MSG_TIMEOUT = 'msg_timeout';
    const DESTINATION = 'destination';
    const METHOD = 'method';
    const HOST = 'host';
    const PRODUCT = 'bcms';
    const SIGN = 'sign';
    const ACCESS_TOKEN = 'access_token';
    const SECRET_KEY = 'client_secret';
    const ACCESS_KEY = 'client_id';
    const ADDRESS = 'address';
    const MESSAGE = 'message';
    const LABEL = 'label';
    const USER = 'user';
    const USERTYPE = 'usertype';
    const ACTIONS = 'actions';
    const TOKEN = 'token';    
    const DEFAULT_HOST = 'bcms.api.duapp.com';
    
    /**
     * Bcms私有变量
     * 
     * 用户关注：否
     */
    private $_clientId = NULL;
    private $_clientSecret = NULL;
    private $_host = NULL;
    private $_requestId = 0;
    private $_curlOpts = array(CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 5);
    /**
     * Bcms 错误常量
     * 
     * 用户关注：否
     */
    const BCMS_SDK_SYS = 1;
    const BCMS_SDK_INIT_FAIL = 2;
    const BCMS_SDK_PARAM = 3;
    const BCMS_SDK_HTTP_STATUS_ERROR_AND_RESULT_ERROR = 4;
    const BCMS_SDK_HTTP_STATUS_OK_BUT_RESULT_ERROR = 5;

    /**
     * 错误常量与错误字符串的映射
     * 
     * 用户关注：否
     */
    private $_arrayErrorMap = array(
        '0' => 'php sdk error',
        self::BCMS_SDK_SYS => 'php sdk error',
        self::BCMS_SDK_INIT_FAIL => 'php sdk init error',
        self::BCMS_SDK_PARAM => 'lack param',
        self::BCMS_SDK_HTTP_STATUS_ERROR_AND_RESULT_ERROR => 'http status is error, and the body returned is not a json string',
        self::BCMS_SDK_HTTP_STATUS_OK_BUT_RESULT_ERROR => 'http status is ok, but the body returned is not a json string',
    );

    /**
     * setAccessKey
     * 
     * 用户关注：是
     * 服务类方法， 设置Bcms对象的accessKey属性，如果用户在创建Bcms对象时已经通过参数设置了accessKey，这里的设置将会覆盖以前的设置
     * 
     * @access public
     * @param string $accessKey
     * @return 成功：true，失败：false
     * @throws BcmsException
     * 
     * @version 1.2.0
     */
    public function setAccessKey($accessKey)
    {
        $this->_resetErrorStatus();
        try {
            if ($this->_checkString($accessKey, 1, 64)) {
                $this->_clientId = $accessKey;
            } else {
                throw new BcmsException("invaid access_key ( ${accessKey} ), which must be a 1-64 length string", self::BCMS_SDK_INIT_FAIL);
            }
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
        return true;
    }

    /**
     * setSecretKey
     * 
     * 用户关注：是
     * 服务类方法， 设置Bcms对象的secretKey属性，如果用户在创建Bcms对象时已经通过参数设置了secretKey，这里的设置将会覆盖以前的设置
     * 
     * @access public
     * @param string $secretKey
     * @return 成功：true，失败：false
     * @throws BcmsException
     * 
     * @version 1.2.0
     */
    public function setSecretKey($secretKey)
    {
        $this->_resetErrorStatus();
        try {
            if ($this->_checkString($secretKey, 1, 64)) {
                $this->_clientSecret = $secretKey;
            } else {
                throw new BcmsException("invaid secret_key ( ${secretKey} ), which must be a 1-64 length string", self::BCMS_SDK_INIT_FAIL);
            }
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
        return true;
    }
    
    /**
     * setAccessAndSecretKey
     * 
     * 用户关注：是
     * 服务类方法， 同时设置Bcms对象的AcessKey、secretKey属性，如果用户在创建Bcms对象时已经通过参数设置了
     * AccessKey、secretKey，这里的设置将会覆盖以前的设置
     * 
     * @access public
     * @param string $accessKey
     * @param string $secretKey
     * @return 成功：true，失败：false
     * @throws BcmsException
     * 
     * @version 1.2.0
     */
    public function setAccessAndSecretKey($accessKey, $secretKey)
    {
        $this->_resetErrorStatus();
        try {
            if (!$this->_checkString($accessKey, 1, 64)) {
                throw new BcmsException("invaid access_key ( ${accessKey} ), which must be a 1-64 length string", self::BCMS_SDK_INIT_FAIL);
            }
            if (!$this->_checkString($secretKey, 1, 64)) {
                throw new BcmsException("invaid secret_key ( ${secretKey} ), which must be a 1-64 length string", self::BCMS_SDK_INIT_FAIL);
            }
            $this->_clientId = $accessKey;
            $this->_clientSecret = $secretKey;
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
        return true;
    }

    /**
     * setHost
     * 
     * 用户关注：是
     * 服务类方法， 设置Bcms对象的host属性，如果用户在创建Bcms对象时已经通过参数设置了host，这里的设置将会覆盖以前的设置
     * 
     * @access public
     * @param string $host
     * @return 成功：true，失败：false
     * @throws BcmsException
     * 
     * @version 1.2.0
     */
    public function setHost($host)
    {
        $this->_resetErrorStatus();
        try {
            if ($this->_checkString($host, 1, 1024)) {
                $this->_host = $host;
            } else {
                throw new BcmsException("invaid host ( ${host} ), which must be a 1 - 1024 length string", self::BCMS_SDK_INIT_FAIL);
            }
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
        return true;
    }
    
    /**
     * setCurlOpts
     * 
     * 用户关注：是
     * 服务类方法， 设置HTTP交互的OPTION，同PHP curl库的所有opt参数
     * 
     * @access public
     * @param array $arr_curlopt
     * @return 成功：true，失败：false
     * @throws BcmsException
     * 
     * @version 1.2.0
     */
    public function setCurlOpts($arr_curlOpts)
    {
        $this->_resetErrorStatus();
        try {
            if (is_array($arr_curlOpts)) {
                foreach ($arr_curlOpts as $k => $v) {   
                    $this->_curlOpts[$k] = $v;
                }    
            } else {
                throw new BcmsException('invalid param - arr_curlOpts is not an array [' . print_r( $arr_curlOpts, true ) . ']', self::BCMS_SDK_INIT_FAIL);
            }
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
        return true;
    }

    /**
     * getRequestId
     * 
     * 用户关注：是
     * 服务类方法，获取上次调用的request_id，如果SDK本身错误，则直接返回0
     * 
     * @access public
     * @return 上次调用服务器返回的request_id
     * 
     * @version 1.2.0
     */
    public function getRequestId ()
    {
        return $this->_requestId;
    }
    
    /**
     * createQueue
     *  
     * 用户关注：是
     * 服务类方法，创建一个队列
     * 
     * @access public
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION、Bcms::QUEUE_TYPE、Bcms::QUEUE_ALIAS_NAME
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function createQueue($optional = NULL) 
    {
        $this->_resetErrorStatus ();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(), $tmpArgs);
            $arrArgs [self::METHOD] = 'create';
            $arrArgs [self::QUEUE_NAME] = 'queue';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * dropQueue
     *  
     * 用户关注：是
     * 服务类方法，删除一个队列
     * 
     * @access public
     * @param string $queueName 要删除的队列名
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function dropQueue($queueName, $optional = NULL) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME), $tmpArgs);
            $arrArgs[self::METHOD] = 'drop';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * subscribeQueue
     *  
     * 用户关注：是
     * 服务类方法，订阅一个队列，BCMS会向目标地址destination发送一个确认请求，确认请求中会带上 $queueName、$destination、
     * $token三个信息；被订阅者需要明确回复一个json包 '{"result":0}'，BCMS才会认为订阅成功，否则，BCMS会将此订阅视为pending
     * 状态，返回失败；如果在订阅时，被订阅的目标地址尚未准备好，那么后续被订阅者可以调用confirmQueue，带上 $queueName、
     * $destination、$token信息来确认这个订阅，
     * 确认订阅成功后，BCMS将该订阅的状态从pending改为normal，以后有消息到达时，会向目标地址推送消息
     * @see confirmQueue
     * 
     * @access public
     * @param string $queueName 要订阅的队列名
     * @param string $destination 订阅的目的地址
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function subscribeQueue($queueName, $destination, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::DESTINATION), $tmpArgs);
            $arrArgs[self::METHOD] = 'subscribe';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::DESTINATION));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * unsubscribeQueue
     *  
     * 用户关注：是
     * 服务类方法，取消某个队列的某个订阅
     * 
     * @access public
     * @param string $queueName 取消订阅的队列名
     * @param string $destination 取消订阅的目标地址
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function unsubscribeQueue($queueName, $destination, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::DESTINATION), $tmpArgs);
            $arrArgs[self::METHOD] = 'unsubscribe';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::DESTINATION));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * unsubscribeAllQueue
     *  
     * 用户关注：是
     * 服务类方法，取消某个队列所有的订阅
     * 
     * @access public
     * @param string $queueName 要取消哪个队列的所有订阅
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function unsubscribeAllQueue($queueName, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME), $tmpArgs);
            $arrArgs[self::METHOD] = 'unsubscribeall';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * grantQueue
     *  
     * 用户关注：是
     * 服务类方法，将一个队列的某些权限授予（分享）给其他（她）用户
     * 
     * @access public
     * @param string $queueName 要将那个队列的权限授予（分享）给其他（她）用户
     * @param string $label 授权时，为这次授权赋予一个标签，以后收回权限时，凭借此标签
     * @param string $user 给哪个用户授权
     * @param int $userType $user的类型，1: 百度passport用户名；2：百度passport邮箱名；3：百度passport手机号
     * @param string $actions 要授予哪些权限，以json的格式给出， 必须utf-8编码，否则服务器会报参数错误，可以一次授予多个权限
     * @see SDK wiki文档
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION、Bcms::EFFECT_START、Bcms::EFFECT_END
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function grantQueue($queueName, $label, $user, $userType, $actions, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::LABEL, self::USER, self::USERTYPE, self::ACTIONS), $tmpArgs);
            $arrArgs[self::METHOD] = 'grant';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::LABEL, self::USER, self::USERTYPE, self::ACTIONS));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * revokeQueue
     *  
     * 用户关注：是
     * 服务类方法，回收权限
     * 
     * @param string $queueName 要回收哪个队列的权限
     * @param string $label 在授权时，指定的label，这里表示要回收这个权限
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function revokeQueue($queueName, $label, $optional = array ()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::LABEL), $tmpArgs);
            $arrArgs[self::METHOD] = 'revoke';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::LABEL));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * suspendQueue
     *  
     * 用户关注：是
     * 服务类方法，将一个队列暂停
     * 
     * @access public
     * @param string $queueName 要暂停的队列名
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function suspendQueue($queueName, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME), $tmpArgs);
            $arrArgs[self::METHOD] = 'suspend';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * resumeQueue
     *  
     * 用户关注：是
     * 服务类方法，将一个队列从暂停状态恢复成正常状态
     * 
     * @access public
     * @param string $queueName 要恢复的队列名
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function resumeQueue($queueName, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME), $tmpArgs);
            $arrArgs[self::METHOD] = 'resume';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * confirmQueue
     *  
     * 用户关注：是
     * 服务类方法，被订阅者调用该方法，确认订阅，告诉BCMS，目标地址同意订阅，可以往我这里推送消息
     * 
     * @access public
     * @param string $queueName 要确认订阅的队列名
     * @param string $token 本次队列确认需要的token
     * @param string $destination 确认订阅的目标地址
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function confirmQueue($queueName, $token, $destination, $optional = array ()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::TOKEN, self::DESTINATION), $tmpArgs);
            $arrArgs[self::METHOD] = 'confirm';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::TOKEN, self::DESTINATION));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * cancelQueue
     *  
     * 用户关注：是
     * 服务类方法，被订阅者调用该方法，取消订阅，告诉BCMS，不要再往我这个地址推送消息了
     * 
     * @access public
     * @param string $queueName 要取消订阅的队列名
     * @param string $token 本次队列取消需要的token
     * @param string $destination 取消订阅的目标地址
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function cancelQueue($queueName, $token, $destination, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::TOKEN, self::DESTINATION), $tmpArgs);
            $arrArgs[self::METHOD] = 'cancel';
            return $this->_commonProcess($arrArgs, array( self::QUEUE_NAME, self::TOKEN, self::DESTINATION));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * publishMessage
     *  
     * 用户关注：是
     * 服务类方法，向一个队列中发送一条消息
     * 
     * @access public
     * @param string $queueName 要发送消息的队列名
     * @param binary $message 要发送的消息，支持任何类型的消息
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function publishMessage($queueName, $message, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::MESSAGE), $tmpArgs);
            $arrArgs[self::METHOD] = 'publish';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::MESSAGE));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * publishMessages
     *  
     * 用户关注：是
     * 服务类方法，向一个队列中批量发送消息
     * 
     * @access public
     * @param string $queueName 要发送消息的队列名
     * @param binary $message 要发送的消息，以json格式给出，必须utf-8编码，否则服务器会报错，参数错误
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function publishMessages($queueName, $message, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::MESSAGE), $tmpArgs);
            $arrArgs[self::METHOD] = 'publishes';
            $arrArgs[ self::MESSAGE] = $this->_arrayToString($arrArgs[self::MESSAGE]);
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::MESSAGE));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * fetchMessage
     *  
     * 用户关注：是
     * 服务类方法，从一个队列中批量获取消息
     * 
     * @access public
     * @param string $queueName 要获取哪个队列的消息
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION，Bcms::MSG_ID、Bcms::FETCH_NUM
     * 需要说明的是：对于多模式队列，必须指定Bcms::MSG_ID，对于单模式队列，不能指定MSG_ID
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function fetchMessage($queueName, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME), $tmpArgs);    
            $arrArgs[self::METHOD] = 'fetch';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * mail
     *  
     * 用户关注：是
     * 服务类方法，向一个队列中发送邮件
     * 
     * @access public
     * @param string $queueName 要发送邮件的队列名
     * @param string $message 要发送的邮件正文
     * @param string $address 要发往哪些地址，以json格式给出，必须utf-8编码，否则服务器会报参数错误
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION、Bcms::FROM、Bcms::MAIL_SUBJECT
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function mail($queueName, $message, $address, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::MESSAGE, self::ADDRESS), $tmpArgs);
            $arrArgs[self::METHOD] = 'mail';
            $arrArgs[self::ADDRESS] = $this->_arrayToString($arrArgs[self::ADDRESS]);
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::MESSAGE, self::ADDRESS));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * sms
     *  
     * 用户关注：是
     * 服务类方法，向一个队列中发送短信
     * 
     * @access public
     * @param string $queueName 要发送邮件的队列名字
     * @param string $message 要发送的短信内容
     * @param string $address 要发往哪些手机号，以json格式给出，必须utf-8编码，否则服务器会报参数错误
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function sms ($queueName, $message, $address, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::MESSAGE, self::ADDRESS), $tmpArgs);
            $arrArgs [self::METHOD] = 'sms';
            $arrArgs [self::ADDRESS] = $this->_arrayToString($arrArgs [self::ADDRESS]);
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::MESSAGE, self::ADDRESS));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * deleteMessageById
     *  
     * 用户关注：是
     * 服务类方法，删除一个队列中的一条消息
     * 
     * @access public
     * @param string $queueName 要删除消息的队列名
     * @param int $msgId 要删除的消息ID号
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function deleteMessageById($queueName, $msgId, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::MSG_ID), $tmpArgs);
            $arrArgs[self::METHOD] = 'deletemessagebyid';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::MSG_ID));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * deleteMessagesByIds
     *  
     * 用户关注：是
     * 服务类方法，批量从一个队列中删除消息，最多支持一次删除10个消息
     * 
     * @access public
     * @param string $queueName 要删除消息的队列名
     * @param string $msgId 要删除消息的ID号，以json格式给出，必须utf-8编码，否则服务器会报参数错误
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function deleteMessagesByIds($queueName, $msgId, $optional = array()) 
    {
        $this->_resetErrorStatus();
        try {
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::MSG_ID), $tmpArgs);
            $arrArgs[self::METHOD] = 'deletemessagesbyids';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::MSG_ID));
        } catch (Exception $ex) {
            $this->_bcmsExceptionHandler($ex);
            return false; 
        }
    }

    /**
     * confirmMessage
     *  
     * 用户关注：否
     * 服务类方法，确认一个消息已经被使用（内部方法，外部用户无需关注）
     * 
     * @access public
     * @param string $queueName 要确认哪个消息的队列
     * @param string $msgId 要确认消息的ID号
     * @param string $msgTimeout 要确认消息的超时时间戳
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function confirmMessage($queueName, $msgId, $msgTimeout, $optional = array())
    {   
        $this->_resetErrorStatus();
        try {   
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME, self::MSG_ID, self::MSG_TIMEOUT), $tmpArgs);
            $arrArgs[self::METHOD] = 'confirmmsg';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME, self::MSG_ID, self::MSG_TIMEOUT));
        } catch (Exception $ex) {   
            $this->_bcmsExceptionHandler($ex);
            return false;
        }
    }

    /**
     * getMaxMsgId
     *  
     * 用户关注：是
     * 服务类方法，获取一个队列中最大的消息ID号
     * 
     * @access public
     * @param string $queueName 要获取哪个队列的最大消息ID号
     * @param array $optional 可选参数数组，可以设置的非必须参数的key包括：Bcms::VERSION
     * @return 成功：包含执行结果的php数组；失败：false
     * 
     * @version 1.2.0
     */
    public function getMaxMsgId($queueName, $optional = array())
    {   
        $this->_resetErrorStatus();
        try {   
            $tmpArgs = func_get_args();
            $arrArgs = $this->_mergeArgs(array(self::QUEUE_NAME), $tmpArgs);
            $arrArgs[self::METHOD] = 'getmaxmsgid';
            return $this->_commonProcess($arrArgs, array(self::QUEUE_NAME));
        } catch (Exception $ex) {   
            $this->_bcmsExceptionHandler($ex);
            return false;
        }
    }    

    /**
     * __construct
     *  
     * 用户关注：是
     * 
     * 对象构造方法，用户可以传入$accessKey、$secretKey、$host进行初始化
     * 如果用户没有传入$accessKey、$secretKey、$host，这三个参数可以其他几个地方予以设置，如下：
     * 1. 在调用SDK时，在$optional参数中设置，如$optional[Cron::SECRET_KEY] = 'my_secret_key'，影响范围：本次SDK调用
     * 2. 调用SDK对象的setXXX系列函数进行设置，如$bcms->setSecretKey('my_secret_key')，影响范围：自设置之后起的每次SDK调用
     * 3. 全局变量，如g_secretKey = 'my_secret_key'，影响范围：当1、2均无法获取到$accessKey、$secretKey、$host时，会从全局变量中获取
     * 说明：SDK获取$accessKey、$secretKey、$host的优先级是：
     * 1. SDK的$optional参数
     * 2. Bcms对象的属性（通过初始化参数或setXXX系列函数指定）
     * 3. 全局变量
     * 
     * @access public
     * @param string $accessKey 用户在管理平台上申请到的accessKey
     * @param string $secretKey 用户在管理平台上申请到的SecretKey
     * @param string $host 百度定时服务API的域名，不包括http://
     * @throws BcmsException 如果出错，则抛出异常，异常号是self::BCMS_SDK_INIT_FAIL
     * 
     * @version 1.2.0
     */
    public function __construct($accessKey = NULL, $secretKey = NULL, $host = NULL, $arr_curlOpts = array()) 
    {    
        if (is_null($accessKey) || $this->_checkString($accessKey, 1, 64)) {
            $this->_clientId = $accessKey;
        } else {
            throw new BcmsException("invalid param - access key [ ${accessKey} ] , which must be a 1 - 64 length string", self::BCMS_SDK_INIT_FAIL);
        }

        if (is_null($secretKey) || $this->_checkString($secretKey, 1, 64)) {
            $this->_clientSecret = $secretKey;
        } else {
            throw new BcmsException("invalid param - secret key [ ${secretKey} ] , which must be a 1 - 64 length string", self::BCMS_SDK_INIT_FAIL);
        }

        if (is_null($host) || $this->_checkString($host, 1, 1024)) {
            if (!is_null($host)) {
                $this->_host = $host;
            }
        } else {
            throw new BcmsException("invalid param - host [ ${host} ] , which must be a 1 - 1024 length string", self::BCMS_SDK_INIT_FAIL);
        }
        
        if (!is_array($arr_curlOpts)) {
            throw new BcmsException('invalid param - arr_curlopt is not an array [' . print_r( $arr_curlOpts, true ) . ']', self::BCMS_SDK_INIT_FAIL);
        }
        
        foreach ($arr_curlOpts as $k => $v) {
            $this->_curlOpts[$k] = $v;
        }
        $this->_resetErrorStatus();
    }

    /**
     * _checkString
     *  
     * 用户关注：否
     * 
     * 检查参数是否是一个大于等于$min且小于等于$max的字符串
     * 
     * @access private
     * @param string $str 要检查的字符串
     * @param int $min 字符串最小长度
     * @param int $max 字符串最大长度
     * @return 成功：true；失败：false
     * 
     * @version 1.2.0
     */
    private function _checkString($str, $min, $max)
    {
        if (is_string($str) && strlen($str) >= $min && strlen($str) <= $max) {
            return true;
        }
        return false;
    }

    /**
     * _get_ak_sk_host
     * 
     * 用户关注：否
     * 获取AK/SK/HOST的统一过程函数
     * 
     * @access private
     * @param array $opt 参数数组
     * @param string $opt_key 参数数组的key
     * @param string $member 对象成员
     * @param string $g_key 全局变量的名字
     * @param string $env_key 环境变量的名字
     * @param int $min 字符串最短值
     * @param int $max 字符串最长值
     * @throws BcmsException 如果出错，则抛出BcmsException异常，异常类型为self::BCMS_SDK_PARAM
     * 
     * @version 1.2.0
     */
    private function _get_ak_sk_host(&$opt, $opt_key, $member, $g_key, $env_key, $min, $max)
    {
        $dis = array('client_id' => 'access_key' , 'client_secret' => 'secret_key', 'host' => 'host');
        global $$g_key;
        
        if (isset($opt[$opt_key])) {
            if (!$this->_checkString($opt[$opt_key], $min, $max)) {
                throw new BcmsException('invalid ' . $dis[$opt_key] . ' in $optinal ( ' . $opt[$opt_key] . ' ) , which must be a ' . $min . ' - ' . $max . ' length string', self::BCMS_SDK_PARAM);
            }
            return;
        }
        
        if ($this->_checkString($member, $min, $max)) {
            $opt[$opt_key] = $member;
            return;
        }
        
        if (isset($$g_key)) {
            if (!$this->_checkString($$g_key, $min, $max)) {
                throw new BcmsException('invalid ' . $g_key . ' in global area ( ' . $$g_key . ' ) , which must be a ' . $min . ' - ' . $max . ' length string', self::BCMS_SDK_PARAM);
            }
            $opt[$opt_key] = $$g_key;    
            return;
        }
        
        if (false !== getenv($env_key)) {
            if (!$this->_checkString(getenv($env_key) , $min, $max)) {
                throw new BcmsException('invalid ' . $env_key . ' in environment variable ( ' . getenv ( $env_key ) . ' ) , which must be a ' . $min . ' - ' . $max . ' length string', self::BCMS_SDK_PARAM);
            }
            $opt[$opt_key] = getenv($env_key);
            return;
        }
    
        if ($opt_key === self::HOST) {
            $opt[$opt_key] = self::DEFAULT_HOST;
            return;
        }
        throw new BcmsException('no param ( ' . $dis[$opt_key] . ' ) was found', self::BCMS_SDK_PARAM);
    }

    /**
     * _adjustOpt
     *   
     * 用户关注：否
     * 
     * 参数调整方法
     * 
     * @access private
     * @param array $opt 参数数组
     * @throws BcmsException 如果出错，则抛出异常，异常号为 self::BCMS_SDK_PARAM
     * 
     * @version 1.2.0
     */
    private function _adjustOpt(&$opt)
    {
        if (!isset($opt) || empty($opt) || !is_array($opt)) {
            throw new BcmsException('no params are set', self::BCMS_SDK_PARAM);
        }
        
        if (!isset($opt[self::TIMESTAMP])) {
            $opt[self::TIMESTAMP] = time();
        }

        $this->_get_ak_sk_host($opt, self::ACCESS_KEY, $this->_clientId, 'g_accessKey', 'HTTP_BAE_ENV_AK', 1, 64);
        $this->_get_ak_sk_host($opt, self::SECRET_KEY, $this->_clientSecret, 'g_secretKey', 'HTTP_BAE_ENV_SK', 1, 64);
        $this->_get_ak_sk_host($opt, self::HOST, $this->_host, 'g_host', 'HTTP_BAE_ENV_ADDR_BCMS', 1, 1024);
    }

    /**
     * _bcmsGetSign
     *   
     * 用户关注：否
     * 
     * 签名方法
     * 
     * @access private
     * @param array $opt 参数数组
     * @param array $arrContent 可以加入签名的参数数组，返回值
     * @param array $arrNeed 必须的参数
     * @throws BcmsException 如果出错，则抛出异常，异常号为self::BCMS_SDK_PARAM
     * 
     * @version 1.2.0
     */
    private function _bcmsGetSign(&$opt, &$arrContent, $arrNeed = array())
    {
        $arrData = array();
        $arrContent = array();
        $arrNeed[] = self::TIMESTAMP;
        $arrNeed[] = self::METHOD;
        $arrNeed[] = self::ACCESS_KEY;
        
        if (isset($opt[self::EXPIRES])) {
            $arrNeed[] = self::EXPIRES;
        }
        if (isset($opt[self::VERSION])) {
            $arrNeed[] = self::VERSION;
        }
        $arrExclude = array(self::QUEUE_NAME, self::HOST, self::SECRET_KEY);
        foreach ($arrNeed as $key) {
            if (!isset($opt[$key]) || (!is_integer($opt[$key]) && empty($opt[$key]))) {
                throw new BcmsException("lack param (${key})", self::BCMS_SDK_PARAM);
            }
            if (in_array($key, $arrExclude)) {
                continue;
            }
            $arrData[$key] = $opt[$key];
            $arrContent[$key] = $opt[$key];
        }
        foreach ($opt as $key => $value) {
            if (!in_array($key, $arrNeed) && !in_array($key, $arrExclude)) {
                $arrData[$key] = $value;
                $arrContent[$key] = $value;
            }
        }
        ksort($arrData);
        $url = 'http://' . $opt[self::HOST] . '/rest/2.0/' . self::PRODUCT . '/';
        if (isset($opt[self::QUEUE_NAME]) && !is_null($opt[self::QUEUE_NAME])) {
            $url .= $opt[self::QUEUE_NAME];
            $arrContent[self::QUEUE_NAME] = $opt[self::QUEUE_NAME];
        } else if (isset($opt[self::UID]) && !is_null($opt[self::UID])) {
            $url .= $opt[self::UID];
        } else {
            $url .= 'queue';
        }
        $basicString = 'POST' . $url;
        foreach ($arrData as $key => $value) {
            $basicString .= $key . '=' . $value;
        }
        $basicString .= $opt[self::SECRET_KEY];
        $sign = md5(urlencode($basicString));
        $arrContent[self::SIGN] = $sign;
        $arrContent[self::HOST] = $opt[self::HOST];
    }

    /**
     * _baseControl
     *   
     * 用户关注：否
     * 
     * 网络交互方法
     * 
     * @access private
     * @param array $opt 参数数组
     * @throws BcmsException 如果出错，则抛出异常，错误号为self::BCMS_SDK_SYS
     * 
     * @version 1.2.0
     */
    private function _baseControl($opt) 
    {
        $content = '';
        $resource = 'queue';
        if (isset($opt[self::QUEUE_NAME]) && !is_null($opt[self::QUEUE_NAME])) {
            $resource = $opt[self::QUEUE_NAME];
            unset($opt[self::QUEUE_NAME]);
        } else if (isset($opt[self::UID]) && !is_null($opt[self::UID])) {
            $resource = $opt[self::UID];
        }
        $host = $opt[self::HOST];
        unset($opt[self::HOST]);
        foreach ($opt as $k => $v) {
            if (is_string($v)) {
                $v = urlencode($v);
            }
            $content .= $k . '=' . $v . '&';
        }
        $content = substr($content, 0, strlen($content) - 1);
        $url = 'http://' . $host . '/rest/2.0/' . self::PRODUCT . '/';
        $url .= $resource;
        $request = new RequestCore($url);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['User-Agent'] = 'Baidu Message Service Phpsdk Client';
        foreach ($headers as $headerKey => $headerValue) {
            $headerValue = str_replace(array("\r", "\n" ), '', $headerValue);
            if ($headerValue !== '') {
                $request->add_header($headerKey, $headerValue);
            }
        }
        $request->set_method('POST');
        $request->set_body($content);
        if (is_array($this->_curlOpts)) {
            $request->set_curlOpts($this->_curlOpts);
        }
        $request->send_request();
        return new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
    }

    /**
     * _bcmsExceptionHandler
     *   
     * 用户关注：否
     * 
     * 异常处理方法
     * 
     * @access private
     * @param Excetpion $ex 异常处理函数，主要是填充Bcms对象的错误状态信息
     * 
     * @version 1.2.0
     */
    private function _bcmsExceptionHandler($ex)
    {
        $tmpCode = $ex->getCode();
        if (0 === $tmpCode) {
            $tmpCode = self::BCMS_SDK_SYS;
        }

        $this->errcode = $tmpCode;
        if ($this->errcode >= 30000) {
            $this->errmsg = $ex->getMessage();
        } else {    
            $this->errmsg = $this->_arrayErrorMap[$this->errcode] . ', detail info [ ' . $ex->getMessage() . ', break point: ' . $ex->getFile() . ': ' . $ex->getLine() . ' ] .';
        }
    }

    /**
     * _commonProcess
     *   
     * 用户关注：否
     * 
     * 所有服务类SDK方法的通用过程
     * 
     * @access private
     * @param array $paramOpt 参数数组
     * @param array $arrNeed 必须的参数KEY
     * @throws BcmsException 如果出错，则抛出异常
     * 
     * @version 1.2.0
     */
    private function _commonProcess($paramOpt = NULL, $arrNeed = array())
    {
        $this->_adjustOpt($paramOpt);
        $arrContent = array();
        $this->_bcmsGetSign($paramOpt, $arrContent, $arrNeed);
        $ret = $this->_baseControl($arrContent );
        if (empty($ret)) {
            throw new BcmsException('base control returned empty object', self::BCMS_SDK_SYS);
        }
        if ($ret->isOK()) {
            $result = json_decode($ret->body, true);
            if (is_null($result)) {
                throw new BcmsException($ret->body, self::BCMS_SDK_HTTP_STATUS_OK_BUT_RESULT_ERROR);
            }
            $this->_requestId = $result['request_id'];
            return $result;
        }
        $result = json_decode($ret->body, true);
        if (is_null($result)) {
            throw new BcmsException('ret body: ' . $ret->body, self::BCMS_SDK_HTTP_STATUS_ERROR_AND_RESULT_ERROR);
        }
        $this->_requestId = $result['request_id'];
        throw new BcmsException($result['error_msg'], $result['error_code']);
    }

    /**
     * _mergeArgs
     *   
     * 用户关注：否
     * 
     * 合并传入的参数到一个数组中，便于后续处理
     * 
     * @access private
     * @param array $arrNeed 必须的参数KEY
     * @param array $tmpArgs 参数数组
     * @throws BcmsException 如果出错，则抛出异常，异常号为self::Bcms_SDK_PARAM 
     * 
     * @version 1.2.0
     */
    private function _mergeArgs($arrNeed, $tmpArgs)
    {
        $arrArgs = array();
        if (0 == count($arrNeed) && 0 == count($tmpArgs)) {
            return $arrArgs;
        }
        if (count($tmpArgs) - 1 != count($arrNeed) && count($tmpArgs) != count($arrNeed)) { 
            $keys = ' ( ';
            foreach ($arrNeed as $key) {
                $keys .= $key .= ', ';
            }
            if ($keys[strlen($keys) - 1] === ' ' && ',' === $keys[strlen($keys) - 2]) {
                $keys = substr($keys, 0, strlen($keys) - 2);
            }
            $keys .= ' ) ';
            throw new Exception('invalid sdk params, params' . $keys . 'are needed', self::BCMS_SDK_PARAM);
        }
        if (count($tmpArgs) - 1 == count($arrNeed)  && !is_array ($tmpArgs[count($tmpArgs) - 1])) {
            throw new Exception('invalid sdk params, optional param must be an array', self::BCMS_SDK_PARAM);
        }

        $idx = 0;
        foreach ($arrNeed as $key) {   
            if (!is_integer($tmpArgs[$idx]) && empty($tmpArgs[$idx])) {   
                throw new Exception("lack param (${key})", self::BCMS_SDK_PARAM);
            }
            $arrArgs[$key] = $tmpArgs[$idx];
            $idx += 1;
        }
        if (isset($tmpArgs[$idx])) {
            foreach ($tmpArgs[$idx] as $key => $value) {   
                if (!array_key_exists($key, $arrArgs) && (is_integer($value) || !empty($value))) {   
                    $arrArgs[$key] = $value;
                }
            }
        }
        if (isset($arrArgs[self::QUEUE_NAME])) {
            $arrArgs[self::QUEUE_NAME] = urlencode($arrArgs[self::QUEUE_NAME]);
        }
        return $arrArgs;
    }

    /**
     * _resetErrorStatus
     *   
     * 用户关注：否
     * 
     * 恢复对象的错误状态，每次调用服务类方法时，由服务类方法自动调用该方法
     * 
     * @access private
     * 
     * @version 1.2.0
     */
    private function _resetErrorStatus()
    {
        $this->errcode = 0;
        $this->errmsg = $this->_arrayErrorMap[$this->errcode];
        $this->_requestId = 0;
    }

    /**
     * _arrayToString
     * 
     * 用户关注： 否
     *
     * 将array类型的对象转换成字符串
     * 
     * @access private
     * @param $arr 要转换array
     * @throws BcmsException 如果出错，则抛出异常，异常号为self::Bcms_SDK_PARAM 
     * 
     * @version 1.2.0
    */
    function _arrayToString($arr)
    {
        if (!is_array($arr)) {
            return $arr;
        }
        if (0 === count($arr)) {
            return '[]';
        }
        $ret = '[';
        foreach ($arr as $v) {
            $ret .= '"' . $v . '", ';
        }
        $ret = substr($ret, 0, strlen($ret) - 2);
        $ret .= ']';

        return $ret;
    }
}
