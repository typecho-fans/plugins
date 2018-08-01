<?php
/**
 * @name 人品计算器
 * @package Moral
 * @author 冰剑
 * @link http://www.binjoo.net
 * @version 1.0.0
 *
 * @param true
 */
class AddonsMoral {
    private $result;
    private $postObj;
    private $params;

    function __construct($result, $postObj = NULL, $params = NULL) {
        $this->result = $result;
        $this->postObj = $postObj;
        $this->params = $params;
    }

    public function execute(){
        $name = str_replace("+", "", $this->params['param']);
        $f = mb_substr($name, 0, 1, 'utf-8');
        $s = mb_substr($name, 1, 1, 'utf-8');
        $w = mb_substr($name, 2, 1, 'utf-8');
        $x = mb_substr($name, 3, 1, 'utf-8');
        $n = ($this->getUnicodeFromUTF8($f) + $this->getUnicodeFromUTF8($s) + $this->getUnicodeFromUTF8($w) + $this->getUnicodeFromUTF8($x)) % 100;
        $addd='';
        if(empty($name)) {
            $addd="大哥不要玩我啊，名字都没有你想算什么！";
        } else if ($n <= 0) {
            $addd ="你一定不是人吧？怎么一点人品都没有？！";
        } else if($n > 0 && $n <= 5) {
            $addd ="算了，跟你没什么人品好谈的...";
        } else if($n > 5 && $n <= 10) {
            $addd ="是我不好...不应该跟你谈人品问题的...";
        } else if($n > 10 && $n <= 15) {
            $addd ="杀过人没有?放过火没有?你应该无恶不做吧?";
        } else if($n > 15 && $n <= 20) {
            $addd ="你貌似应该三岁就偷看隔壁大妈洗澡的吧..."; 
        } else if($n > 20 && $n <= 25) {
            $addd ="你的人品之低下实在让人惊讶啊..."; 
        } else if($n > 25 && $n <= 30) {
            $addd ="你的人品太差了。你应该有干坏事的嗜好吧?";
        } else if($n > 30 && $n <= 35) {
            $addd ="你的人品真差!肯定经常做偷鸡摸狗的事...";
        } else if($n > 35 && $n <= 40) {
            $addd ="你拥有如此差的人品请经常祈求佛祖保佑你吧...";
        } else if($n > 40 && $n <= 45) {
            $addd ="老实交待..那些论坛上面经常出现的偷拍照是不是你的杰作?"; 
        } else if($n > 45 && $n <= 50) {
            $addd ="你随地大小便之类的事没少干吧?";
        } else if($n > 50 && $n <= 55) {
            $addd ="你的人品太差了..稍不小心就会去干坏事了吧?"; 
        } else if($n > 55 && $n <= 60) {
            $addd ="你的人品很差了..要时刻克制住做坏事的冲动哦.."; 
        } else if($n > 60 && $n <= 65) {
            $addd ="你的人品比较差了..要好好的约束自己啊.."; 
        } else if($n > 65 && $n <= 70) {
            $addd ="你的人品勉勉强强..要自己好自为之.."; 
        } else if($n > 70 && $n <= 75) {
            $addd ="有你这样的人品算是不错了..";
        } else if($n > 75 && $n <= 80) {
            $addd ="你有较好的人品..继续保持.."; 
        } else if($n > 80 && $n <= 85) {
            $addd ="你的人品不错..应该一表人才吧?";
        } else if($n > 85 && $n <= 90) {
            $addd ="你的人品真好..做好事应该是你的爱好吧.."; 
        } else if($n > 90 && $n <= 95) {
            $addd ="你的人品太好了..你就是当代活雷锋啊...";
        } else if($n > 95 && $n <= 99) {
            $addd ="你是世人的榜样！";
        } else if($n > 100 && $n < 105) {
            $addd ="天啦！你不是人！你是神！！！"; 
        }else if($n > 105 && $n < 999) {
            $addd="你的人品已经过 100 人品计算器已经甘愿认输，3秒后人品计算器将自杀啊";
        } else if($n > 999) {
            $addd ="你的人品竟然负溢出了...我对你无语.."; 
        }
        $this->result->setText($name."的人品分数为：" . $n . "\n". $addd)->setMsgType(MessageTemplate::TEXT)->send();
    }

    function getUnicodeFromUTF8($word) {   
        if (is_array( $word))   
            $arr = $word;   
        else     
            $arr = str_split($word);   
        $bin_str = '';   
        foreach ($arr as $value)   
            $bin_str .= decbin(ord($value));   
        $bin_str = preg_replace('/^.{4}(.{4}).{2}(.{6}).{2}(.{6})$/','$1$2$3', $bin_str);   
        return bindec($bin_str);
    }
}
?>
