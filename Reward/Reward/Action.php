<?php
class Reward_Action implements Widget_Interface_Do
{
    
    public function execute()
    {
    
    }

    public function action()
    {
        header("Content-type: text/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        require_once 'alipay/f2fpay/model/builder/AlipayTradePrecreateContentBuilder.php';
        require_once 'alipay/f2fpay/service/AlipayTradeService.php';


        // (必填) 商户网站订单系统中唯一订单号，64个字符以内，只能包含字母、数字、下划线，
    	// 需保证商户系统端不能重复，建议通过数据库sequence生成，
    	//$outTradeNo = "qrpay".date('Ymdhis').mt_rand(100,1000);
        //$outTradeNo = $_POST['out_trade_no'];
        $outTradeNo = date('Ymdhis').mt_rand(100,1000);
    
    	// (必填) 订单标题，粗略描述用户的支付目的。如“xxx品牌xxx门店当面付扫码消费”
    	$subject = $_POST['subject'];
         //$subject = "test";
    	// (必填) 订单总金额，单位为元，不能超过1亿元
    	// 如果同时传入了【打折金额】,【不可打折金额】,【订单总金额】三者,则必须满足如下条件:【订单总金额】=【打折金额】+【不可打折金额】
        $totalAmount = $_POST['total_amount'];
        //$totalAmount = "0.01";
    	// 支付超时，线下扫码交易定义为5分钟
    	$timeExpress = "5m";
    
    	//第三方应用授权令牌,商户授权系统商开发模式下使用
    	$appAuthToken = "";//根据真实值填写
        
    	// 创建请求builder，设置请求参数
    	$qrPayRequestBuilder = new AlipayTradePrecreateContentBuilder();
    	$qrPayRequestBuilder->setOutTradeNo($outTradeNo);
    	$qrPayRequestBuilder->setTotalAmount($totalAmount);
    	$qrPayRequestBuilder->setTimeExpress($timeExpress);
    	$qrPayRequestBuilder->setSubject($subject);
    
    	$qrPayRequestBuilder->setAppAuthToken($appAuthToken);
    
    
    	// 调用qrPay方法获取当面付应答
    	$qrPay = new AlipayTradeService($config);
    	
    	
    	$qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);
        
    	//	根据状态值进行业务处理
    	switch ($qrPayResult->getTradeStatus()){
    		case "SUCCESS":
    			//echo "支付宝创建订单二维码成功:"."<br>---------------------------------------<br>";
    			$response = $qrPayResult->getResponse();
    			//$qrcode = $qrPay->create_erweima($response->qr_code);
    			//echo $qrcode;
    			echo json_encode($response);
    			
    			break;
    		case "FAILED":
    			//echo "支付宝创建订单二维码失败!!!"."<br>--------------------------<br>";
    			if(!empty($qrPayResult->getResponse())){
    				echo json_encode($qrPayResult->getResponse());
    			}
    			break;
    		case "UNKNOWN":
    			//echo "系统异常，状态未知!!!"."<br>--------------------------<br>";
    			if(!empty($qrPayResult->getResponse())){
    				echo json_encode($qrPayResult->getResponse());
    			}
    			break;
    		default:
    			break;
    	}   
    }
    
    public function action_alipay_query()
    {
        
        header("Content-type: text/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        require_once 'alipay/f2fpay/service/AlipayTradeService.php';
        
        if (!empty($_POST['out_trade_no'])&& trim($_POST['out_trade_no'])!=""){
            ////获取商户订单号
            $out_trade_no = trim($_POST['out_trade_no']);
        
            //第三方应用授权令牌,商户授权系统商开发模式下使用
            $appAuthToken = "";//根据真实值填写
        
            //构造查询业务请求参数对象
            $queryContentBuilder = new AlipayTradeQueryContentBuilder();
            $queryContentBuilder->setOutTradeNo($out_trade_no);
        
            $queryContentBuilder->setAppAuthToken($appAuthToken);
        
        
            //初始化类对象，调用queryTradeResult方法获取查询应答
            $queryResponse = new AlipayTradeService($config);
            $queryResult = $queryResponse->queryTradeResult($queryContentBuilder);
        
            //根据查询返回结果状态进行业务处理
            switch ($queryResult->getTradeStatus()){
                case "SUCCESS":
                    echo json_encode($queryResult->getResponse());
                    break;
                case "FAILED":
                    if(!empty($queryResult->getResponse())){
                        echo json_encode($queryResult->getResponse());
                    }
                    break;
                case "UNKNOWN":
                    if(!empty($queryResult->getResponse())){
                        echo json_encode($queryResult->getResponse());
                    }
                    break;
                default:
                    break;
            }
            return ;
        }
    }
}