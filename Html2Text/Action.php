<?php
/**
 * WeChatHelper Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */

class WeChatHelper_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $_db;
    private $_options;

    private $_textTpl;
    private $_imageTpl;
    private $_itemTpl;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        $this->_db = Typecho_Db::get();
        $this->_options = Helper::options()->plugin('WeChatHelper');
        $this->_textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                            </xml>"; 
        $this->_imageTpl = "<xml>
                             <ToUserName><![CDATA[%s]]></ToUserName>
                             <FromUserName><![CDATA[%s]]></FromUserName>
                             <CreateTime>%s</CreateTime>
                             <MsgType><![CDATA[news]]></MsgType>
                             <ArticleCount>%s</ArticleCount>
                             <Articles>%s</Articles>
                             <FuncFlag>1</FuncFlag>
                             </xml>";
        $this->_itemTpl = "<item>
                             <Title><![CDATA[%s]]></Title> 
                             <Description><![CDATA[%s]]></Description>
                             <PicUrl><![CDATA[%s]]></PicUrl>
                             <Url><![CDATA[%s]]></Url>
                             </item>";
    }

    /**
     * 链接重定向
     * 
     */
    public function link()
    {
        if($this->request->isGet()){
            //$this->getAction();
        }
        if($this->request->isPost()){
            $this->postAction();
        }
    }

    /**
     * 校验
     * 
     */
    public function getAction(){
        $_token = Helper::options()->plugin('WeChatHelper')->token;
        $echoStr = $this->request->get('echostr');

        if($this->checkSignature($_token)){
            echo $echoStr;
            exit;
        }
    }

    /**
     * 数据
     * 
     */
    public function postAction(){
        $postStr = file_get_contents("php://input");//$this->request->get("HTTP_RAW_POST_DATA");
        if (!empty($postStr)){
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $msgType = $postObj->MsgType;
                $keyword = trim($postObj->Content);
                $cmd = strtolower(substr($keyword, 0, 1));
                $resultStr = "";
                if($msgType == "text"){
                    if($cmd=="h"){
                        $contentStr = "\"n\" 最新日志\n\"r\" 随机日志\n\"l\" 手气不错\n\"s 关键词\" 搜索日志\n\"f\" 访客评论排行榜";
                        $resultStr = $this->baseText($postObj, $contentStr);
                    }elseif ($cmd=="f") {//访客排行
                        $resultStr = $this->commentRank($postObj);
                    }elseif ($cmd=="r") {//随机日志
                        $resultStr = $this->randomPost($postObj);
                    }elseif ($cmd=="n") {//最新日志
                        $resultStr = $this->newPost($postObj);
                    }elseif ($cmd=="l") {//手气不错
                        $resultStr = $this->luckyPost($postObj);
                    }elseif ($cmd=="s") {//搜索日志
                        $searchParam = trim(substr($keyword, 1));
                        $resultStr = $this->searchPost($postObj, $searchParam);
                    }elseif ($cmd=="b" && !$this->isBind()) {//用户绑定
                        $contentStr = $this->bindUser($postObj);
                        $resultStr = $this->baseText($postObj, $contentStr);
                    }elseif ($cmd=="u" && $this->isBindUser($postObj)){//用户解绑命令
                        $contentStr = $this->unbindUser($postObj);
                        $resultStr = $this->baseText($postObj, $contentStr);
                    }
                }else if($msgType == "event"){
                    if($postObj->Event == "subscribe"){
                        $contentStr = $this->_options->welcome;
                        $resultStr = $this->baseText($postObj, $contentStr);
                    }
                }
                if($resultStr == ""){
                    $resultStr = $this->baseText($postObj);
                }
                echo $resultStr;
        }else {
            echo "";
            exit;
        }
        /*
        $dir = __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR;
        $myfile = $dir.'wechatDebug.txt';
        echo $myfile;
        $file_pointer = @fopen($myfile,"a");
        @fwrite($file_pointer, $postObj->FromUserName." - ");
        @fwrite($file_pointer, $postObj->ToUserName." | ");
        @fclose($file_pointer);
        */
    }

    public function action(){
        $this->widget('Widget_User')->pass('administrator');
        $this->response->goBack();
    }

    private function checkSignature($_token)
    {
        $signature = $this->request->get('signature');
        $timestamp = $this->request->get('timestamp');
        $nonce = $this->request->get('nonce');
                
        $token = $_token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        
        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    /** 基础文本信息 **/
    private function baseText($postObj, $contentStr = null){
        if($contentStr == null){
            $contentStr = '不明白你在说什么，但是你可以发送\'h\'来查看帮助！';
        }
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $time = time();
        $resultStr = sprintf($this->_textTpl, $fromUsername, $toUsername, $time, $contentStr);
        return $resultStr;
    }

    /** 最新日志 **/
    private function newPost($postObj){
        $sql = $this->_db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit($this->_options->imageNum);
        $result = $this->_db->fetchAll($sql);

        $resultStr = $this->sqlData($postObj, $result);
        return $resultStr;
    }

    /** 随机日志 **/
    private function randomPost($postObj){
        $sql = $this->_db->select()->from('table.contents')
            ->where('table.contents.status = ?','publish')
            ->limit($this->_options->imageNum)
            ->order('RAND()');
        $result = $this->_db->fetchAll($sql);

        $resultStr = $this->sqlData($postObj, $result);
        return $resultStr;
    }

    /** 手气不错 **/
    private function luckyPost($postObj){
        $sql = $this->_db->select()->from('table.contents')
            ->where('table.contents.status = ?','publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.password IS NULL')
            ->limit(1)
            ->order('RAND()');
        $result = $this->_db->fetchAll($sql);

        $resultStr = $this->sqlData($postObj, $result);
        return $resultStr;
    }

    /** 搜索日志 **/
    private function searchPost($postObj, $searchParam){
        $searchParam = '%' . str_replace(' ', '%', $searchParam) . '%';

        $sql = $this->_db->select()->from('table.contents')
            ->where('table.contents.password IS NULL')
            ->where('table.contents.title LIKE ? OR table.contents.text LIKE ?', $searchParam, $searchParam)
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit($this->_options->imageNum);
        $result = $this->_db->fetchAll($sql);

        $resultStr = $this->sqlData($postObj, $result);
        return $resultStr;
    }

    private function sqlData($postObj, $data){
        $_subMaxNum = Helper::options()->plugin('WeChatHelper')->subMaxNum;
        $resultStr = "";
        $num = 0;
        $tmpPicUrl = "";
        if($data != null){
            foreach($data as $val){
                $val = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($val);
                $content = Typecho_Common::subStr($this->deleteHtml($val['text']), 0, $_subMaxNum, '...');
                
                $preg = "/<img\ssrc=(\'|\")(.*?)\.(jpg|png)(\'|\")/is";
                preg_match($preg, $val['text'], $images);
                if($images==null){
                    $tmpPicUrl = $this->_options->imageDefault;
                }else{
                    $tmpPicUrl = $images[2].'.'.$images[3];
                }
                $resultStr .= sprintf($this->_itemTpl, $val['title'], $content, $tmpPicUrl, $val['permalink']);
                $num++;
            }
        }else{
                $resultStr = "没有找到任何信息！";
        }
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $time = time();
        if($data != null){
            $resultStr = sprintf($this->_imageTpl, $fromUsername, $toUsername, $time, $num, $resultStr);
        }else{
            $resultStr = sprintf($this->_textTpl, $fromUsername, $toUsername, $time, $resultStr);
        }
        return $resultStr;
    }

    /** 水墙 **/
    private function commentRank($postObj){
        $_rankNum = Helper::options()->plugin('WeChatHelper')->rankNum;
        $sql = $this->_db->select('COUNT(author) AS cnt','author', 'url', 'mail')
            ->from('table.comments')
            ->where('status = ?', 'approved')
            ->where('type = ?', 'comment')
            ->where('authorId = ?', '0')
            ->where('mail != ?', 'icesword28@qq.com')
            ->group('author')
            ->order('cnt', Typecho_Db::SORT_DESC)
            ->limit($_rankNum);
        $result = $this->_db->fetchAll($sql);
        $contentStr = "";
        $num = 1;
        foreach ($result as $val)
        {
            $contentStr .= $num++.'> '.$val['author'].'＠'.$val['cnt'].chr(10);
        }
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $time = time();
        $resultStr = sprintf($this->_textTpl, $fromUsername, $toUsername, $time, $contentStr);
        return $resultStr;
    }

    /** 绑定微信用户 **/
    private function bindUser($postObj){
        $_bindCaptcha = $this->_options->bindCaptcha;
        $_bindUserNo = $this->_options->bindUserNo;
        $resultStr = "";
        if($_bindUserNo == null || $_bindUserNo == ""){
            $content = trim(substr(trim($postObj->Content), 1));
            if($content == $_bindCaptcha){
                $array["bindUserNo"] = (string) $postObj->FromUserName;
                Widget_Plugins_Edit::configPlugin("WeChatHelper", $array);
                $resultStr = "恭喜，绑定成功！";
            }else{
                $resultStr = "对不起，绑定失败。";
            }
        }else{
            $resultStr = '管理员用户已经绑定，解绑请用管理员帐户发送\'unb\'。';
        }
        return $resultStr;
    }

    /** 解绑微信用户 **/
    private function unbindUser($postObj){
        $_bindUserNo = $this->_options->bindUserNo;
        $sendUser = (string) $postObj->FromUserName;
        $resultStr = "";
        if($_bindUserNo != "" && $sendUser == $_bindUserNo){
            $array["bindUserNo"] = "";
            $array["bindCaptcha"] = WeChatHelper_Plugin::randString(8);
            Widget_Plugins_Edit::configPlugin("WeChatHelper", $array);
            $resultStr = "恭喜，解除绑定成功！";
        }
        return $resultStr;
    }

    /***
     * 判断用户是否为绑定用户
     **/
    private function isBindUser($postObj){
        $_bindUserNo = $this->_options->bindUserNo;
        $sendUser = (string) $postObj->FromUserName;
        if($_bindUserNo != "" && $sendUser == $_bindUserNo){
            return true;
        }else{
            return false;
        }
    }

    /***
     * 判断是否绑定了用户
     **/
    private function isBind(){
        $_bindUserNo = $this->_options->bindUserNo;
        if($_bindUserNo != null || $_bindUserNo != ""){
            return true;
        }else{
            return false;
        }
    }

    /** 清除HTML、换行、空格 **/
    private function deleteHtml($str) { 
        $str = trim($str); //清除字符串两边的空格
        $str = strip_tags($str,""); //利用php自带的函数清除html格式
        $str = preg_replace("/\t/", "", $str); //使用正则表达式匹配需要替换的内容，如：空格，换行，并将替换为空。
        $str = preg_replace("/\r\n/", "", $str); 
        $str = preg_replace("/\r/", "", $str); 
        $str = preg_replace("/\n/", "", $str); 
        $str = preg_replace("/ /", "", $str);
        $str = preg_replace("/&nbsp; /", "", $str);  //匹配html中的空格
        return trim($str); //返回字符串
    }
}
?>