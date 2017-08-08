
(function () {
    if(!window.REWARD_TPL){
        return ;
    }
    $(REWARD_TPL).appendTo("body");
    var REWARD_PLUGIN_STR = {
        UNHAPPY: "唉！～不开心。。。😔",
        DEFAULT: "等待大佬打赏中~",
        ORDER_URL: "/reward/alipay/order",
        QUERY_URL: "/reward/alipay/query",
        WEIBO_DIALOG_SELECTOR:".reward_w.endzy-reward-layer",
        WEIBO_DIALOG_CLOSE_SELECTOR:".reward-close",
        WAIT_BUYER_PAY_DIALOG_SELECTOR:"#reward-plugin",
        QRCODE_CONTAINER_SELECTOR:"#reward-qrcode-container",
        REWARD_MSG_SELECTOR:"#reward-msg",
        REWARD_PREFIX:"赞赏《",
        BLOG_TITLE_SELECTOR:"title",
        REWARD_SUFFIX:"》",
        CANCLE_BTN_SELECTOR:"#cancel-pay",
        REWARD_BTN_SELECTOR:"#webo_reward_btn",
        PAY_BTN_SELECTOR:".reward-pay",
        TOTALAMOUNT_SELECTOR:"#endzy-rewardNum",
        RANDOM_AMOUNT_SELECTOR:"label.reward-random"
    };
    var REWARD_ORDER = {
        INTEVAL_ID: '',
        BUYER: '',
        OUT_TRADE_NO: ''
    };
    function prepay(payurl) {
        $(REWARD_PLUGIN_STR.QRCODE_CONTAINER_SELECTOR).children().remove();
        $(REWARD_PLUGIN_STR.QRCODE_CONTAINER_SELECTOR).qrcode({ width: 200, height: 200, correctLevel: 0, text: payurl });
    }
    function makeRandomTotalAmount(){
        var arr = [2.33,6.66,9.99,8.88,1,23];
        for(var i = 0; i < 10; i++){
            arr.push(parseFloat(Math.random()*10).toFixed(2));
        }
        return arr;
    }
    function setRewardText(text) {
        $(REWARD_PLUGIN_STR.REWARD_MSG_SELECTOR).text(text);
    }
    function getNewOrderRequest(totalAmount) {
        return {
            subject: REWARD_PLUGIN_STR.REWARD_PREFIX + $(REWARD_PLUGIN_STR.BLOG_TITLE_SELECTOR).text() + REWARD_PLUGIN_STR.REWARD_SUFFIX,
            total_amount: totalAmount
        };
    }
    function clearOldOrderQuery() {
        if (REWARD_ORDER.INTEVAL_ID) {
            clearInterval(REWARD_ORDER.INTEVAL_ID);
        }
    }
    function onWaitBuyerPay(qrpay_query_response){
        setRewardText("紧紧抱住【" + qrpay_query_response.buyer_logon_id + "】大佬的大腿！");
    }
    function onTradeSuccess(qrpay_query_response){
        setRewardText("感谢大佬打赏【" + qrpay_query_response.buyer_pay_amount + "】元！🙏");
        $(REWARD_PLUGIN_STR.CANCLE_BTN_SELECTOR).hide();
        clearOldOrderQuery();
        setTimeout(function () { 
            $(REWARD_PLUGIN_STR.WAIT_BUYER_PAY_DIALOG_SELECTOR).hide();
            $(REWARD_PLUGIN_STR.REWARD_MSG_SELECTOR).text(REWARD_PLUGIN_STR.DEFAULT);
            $(REWARD_PLUGIN_STR.WAIT_BUYER_PAY_DIALOG_SELECTOR).show();
        }, 3000);
    }
    function startNewOrderQuery() {
        var isFirstWait = true;
        REWARD_ORDER.INTEVAL_ID = setInterval(function () {
            $.post(REWARD_PLUGIN_STR.QUERY_URL, { out_trade_no: REWARD_ORDER.OUT_TRADE_NO }, function (data) {
                if (data.code == 10000) {
                    if (data.trade_status == "WAIT_BUYER_PAY" && isFirstWait) {
                        onWaitBuyerPay(data);
                        isFirstWait = false;
                    }
                    if (data.trade_status == "TRADE_SUCCESS") {
                        onTradeSuccess(data);
                    }
                }
            });
        }, 1000);
    }
    function requestNewOrder(totalAmount) {
        $.post(REWARD_PLUGIN_STR.ORDER_URL, getNewOrderRequest(totalAmount), function (data) {
            if (data.code == 10000) {
                REWARD_ORDER.OUT_TRADE_NO = data.out_trade_no;
                prepay(data.qr_code);
                clearOldOrderQuery();
                startNewOrderQuery();
            }
        });
    }
    //设置取消按钮 class 与 评论提交按钮 class 相同
    $(REWARD_PLUGIN_STR.CANCLE_BTN_SELECTOR).attr("class",$("[type='submit']").attr("class"));
    //关闭按钮
    $(REWARD_PLUGIN_STR.WEIBO_DIALOG_CLOSE_SELECTOR).click(function () {
        //微博赞赏框
        $(REWARD_PLUGIN_STR.WEIBO_DIALOG_SELECTOR).hide();
    });
    //赏按钮
    $(REWARD_PLUGIN_STR.REWARD_BTN_SELECTOR).click(function () {
        $(REWARD_PLUGIN_STR.WEIBO_DIALOG_SELECTOR).show();
    });
    //立即支付按钮
    $(REWARD_PLUGIN_STR.PAY_BTN_SELECTOR).click(function () {
        var totalAmount = parseFloat($(REWARD_PLUGIN_STR.TOTALAMOUNT_SELECTOR).val());
        if (totalAmount) {
            $(REWARD_PLUGIN_STR.WEIBO_DIALOG_SELECTOR).hide();
            $(REWARD_PLUGIN_STR.WAIT_BUYER_PAY_DIALOG_SELECTOR).show();
            requestNewOrder(totalAmount);
        }
    });
    //骰子按钮
    $(REWARD_PLUGIN_STR.RANDOM_AMOUNT_SELECTOR).click(function () {
        $(REWARD_PLUGIN_STR.TOTALAMOUNT_SELECTOR).scroll({arr:makeRandomTotalAmount()});
    });
    //算了按钮
    $(REWARD_PLUGIN_STR.CANCLE_BTN_SELECTOR).click(function () {
        clearOldOrderQuery();
        setRewardText(REWARD_PLUGIN_STR.UNHAPPY);
        $(REWARD_PLUGIN_STR.WAIT_BUYER_PAY_DIALOG_SELECTOR).hide(3000,function(){
            setRewardText(REWARD_PLUGIN_STR.DEFAULT);
        });
    });
})();