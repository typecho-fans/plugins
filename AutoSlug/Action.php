<?php
/**
 * 自动生成缩略名操作类
 *
 * @package AutoSlug
 * @author shingchi
 * @version 2.1.0
 */
class AutoSlug_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 插件配置
     *
     * @access private
     * @var Typecho_Config
     */
    private $_config;

    /**
     * 构造方法
     *
     * @access public
     * @var void
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        /* 获取插件配置 */
        $this->_config = parent::widget('Widget_Options')->plugin('AutoSlug');
    }

    /**
     * 转换为英文或拼音
     *
     * @access public
     * @return void
     */
    public function transform()
    {
        $word = $this->request->filter('strip_tags', 'trim', 'xss')->q;

        if (empty($word)) {
            return;
        }

        $result = call_user_func(array($this, $this->_config->mode), $word);
        $result = preg_replace('/[[:punct:]]/', '', $result);
        $result = str_replace(array('  ', ' '), '-', strtolower(trim($result)));
        $message = array('result' => $result);

        $this->response->throwJson($message);
    }

    /**
     * 百度翻译
     *
     * @access public
     * @param string $word 待翻译的字符串
     * @return string
     */
    public function baidu($word)
    {
        $url = 'http://api.fanyi.baidu.com/api/trans/vip/translate';

        $salt = rand(10000, 99999);
        $signStr = $this->_config->bdAppid . $word . $salt . $this->_config->bdKey;
        $sign = md5($signStr);

        $data = array(
            'q' => $word,
            'from' => 'zh',
            'to' => 'en',
            'appid' => $this->_config->bdAppid,
            'salt' => $salt,
            'sign' => $sign
        );

        $result = $this->translate($url, $data);

        if (isset($result['error_code'])) {
            return;
        }

        return $result['trans_result'][0]['dst'];
    }

    /**
     * 有道翻译
     *
     * @access public
     * @param string $word 待翻译的字符串
     * @return string
     */
    public function youdao($word)
    {
        $url = 'http://fanyi.youdao.com/openapi.do';
        $data = array(
            'keyfrom' => $this->_config->ydFrom,
            'key'     => $this->_config->ydKey,
            'type'    => 'data',
            'doctype' => 'json',
            'version' => '1.1',
            'q'       => $word
        );
        $result = $this->translate($url, $data);

        if ($result['errorCode'] > 0) {
            return;
        }

        return $result['translation'][0];
    }

    /**
     * 谷歌翻译
     *
     * @access public
     * @param string $word 待翻译的字符串
     * @return string
     */
    public function google($word)
    {
        $url = 'http://brisk.eu.org/api/translate.php';
        $data = array('from' => 'chinese', 'to' => 'english', 'text' => $word);
        $result = $this->translate($url, $data);

        if (empty($result)) {
            return;
        }

        return $result['res'];
    }


    /**
     * 发送翻译API请求
     *
     * @access public
     * @param string $url 请求地址
     * @param array $data 请求参数
     * @return array
     */
    public function translate($url, $data)
    {
        $client = Typecho_Http_Client::get();
        $client->setData($data)->setTimeout(50)->send($url);

        if (200 === $client->getResponseStatus()) {
            return Json::decode($client->getResponseBody(), true);
        }
    }

    /**
     * 转换成拼音
     *
     * @access public
     * @param string $word 待转换的字符串
     * @return string
     */
    public function pinyin($word)
    {
        require_once 'Pinyin.php';

        $pinyin = new Pinyin();
        return $pinyin->stringToPinyin($word);
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->on($this->request->isAjax())->transform();
    }
}
