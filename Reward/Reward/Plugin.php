<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
* 支付宝当面付的赞赏
 *
 *
 * @package Reward
 * @author  KyuuSeiryuu
 * @version 1.0
 * @link http://www.chioy.cn
 */
class Reward_Plugin implements Typecho_Plugin_Interface
{
	
	public static function activate() 
    {
	    Typecho_Plugin::factory('Widget_Archive')->header = array('Reward_Plugin', 'header');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('Reward_Plugin', 'footer');
		Typecho_Plugin::factory('Widget_Archive')->singleHandle = array('Reward_Plugin','render');
		Helper::addRoute("reward_alipay_order","/reward/alipay/order","Reward_Action",'action');
		Helper::addRoute("reward_alipay_query","/reward/alipay/query","Reward_Action",'action_alipay_query');
		
		return _t("插件已启用");
	}
	
	public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
	public static function config(Typecho_Widget_Helper_Form $form){}
	
	/**
	* 禁用插件方法,如果禁用失败,直接抛出异常
	     *
	     * @static
	     * @access public
	     * @return void
	     * @throws Typecho_Plugin_Exception
	     */
	    public static function deactivate() 
	    {
		Helper::removeRoute("reward_alipay_order");
		Helper::removeRoute("reward_alipay_query");
		return _t("插件已禁用");
	}
	
	public static function header()
	    {
		$url = Helper::options()->pluginUrl .'/Reward/css/style.css';
		$tag = '
          <!--Reward Start-->
          <link type="text/css" rel="stylesheet" href="'.$url.'" />
          <!--Reward End-->
          ';
		echo $tag;
	}
	
	public static function footer(){
		$rewardjs = Helper::options()->pluginUrl .'/Reward/js/reward.js';
		$qrcodejs = Helper::options()->pluginUrl .'/Reward/js/jquery.qrcode.js';
		$scrolljs = Helper::options()->pluginUrl .'/Reward/js/scroll.js';
		$tag = '
        <!--WeiboReward Start-->
        <script src="'.$rewardjs.'"></script>
        <script src="'.$qrcodejs.'"></script>
		<script src="'.$scrolljs.'"></script>
        <!--WeiboReward End-->
        ';
		echo $tag;
	}
	
	public static function render($archive){
		$content = $archive->text;
		$tip = '报告大佬，请支付宝';
		$head = 'http://tva2.sinaimg.cn/crop.0.0.996.996.180/9e5562bcjw8f0zdkvez14j20ro0rojtx.jpg';
		$template = "<div id=\"reward-plugin\"><p class=\"background\"></p><div class=\"reward-content-pagecenter\"><div id=\"reward-qrcode-container\" class=\"center\"></div><h3 id=\"reward-msg\">等待大佬打赏中~</h3><button id=\"cancel-pay\">算了不给了</button></div></div><div class=\"reward_w endzy-reward-layer\" style=\"display: none;\"><p class=\"reward-bg\"></p><div class=\"reward-box\"><div class=\"reward-content\"><h3 class=\"reward-title\">打了个赏<span class=\"reward-close\"></span></h3>      <div class=\"reward-user-head\"><p class=\"reward-tip\"></p><div>  <a class=\"reward-img-box\"><img class=\"reward-head-img\" src=\"$head\"></a>  <p class=\"reward-user-name\">$tip</p></div>      </div>      <div class=\"reward-in\"><label class=\"icon-mon\" for=\"endzy-rewardNum\">￥</label><input id=\"endzy-rewardNum\" class=\"reward-num\" type=\"text\" value=\"9.90\"><label for=\"endzy-rewardNum\" class=\"reward-random\"></label><div class=\"W_layer W_layer_pop\">  <div class=\"content layer_mini_info\">    <p class=\"main_txt\"><i class=\"W_icon icon_rederrorS\"></i><span class=\"txt S_txt1\"></span><a class=\"W_ficon ficon_close S_ficon\">X</a></p>    <div class=\"W_layer_arrow\"><span class=\"W_arrow_bor W_arrow_bor_b\"><i class=\"S_line3\"></i><em class=\"S_bg2_br\"></em></span></div>  </div></div>      </div>      <div class=\"reward-pay-bt\"><p class=\"reward-pay-box\"><a class=\"reward-pay\" href=\"javascript:;\">立即支付</a><span class=\"reward-pay-war\">打赏无悔，概不退款</span></p></div></div></div></div>";

    $rewardBtn = "<div class=\"weibo_reward\">
            <spna class=\"weibo_reward_tip\"></spna>
            <p class=\"webo_reward_btn_p\">
            <a id=\"webo_reward_btn\" class=\"webo_reward_btn\"></a>
            </p>
            </div>";

		$appendjs = "<script>window.REWARD_TPL='$template';</script>";
		$content .= $rewardBtn.$appendjs;
		$archive->text = $content;
	}
	
	
}
