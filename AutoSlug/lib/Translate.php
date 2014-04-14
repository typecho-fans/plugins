<?php
/**
 * 百度翻译
 *
 * @package DuTranslate
 * @version 1.0
 * @link https://github.com/typecho-fans/plugins/tree/master/AutoSlug/
 * @license GNU General Public License 2.0
 */
class DuTranslate
{
    /** 百度应用 API Key */
    private $_apiKey;

    /**
     * 构造函数
     *
     * @param string $apiKey 百度应用 API Key
     * @return void
     */
    public function __construct($apiKey = NULL)
    {
        /** 获取 API Key */
        $this->_apiKey = $apiKey;
    }

    /**
     * 翻译
     *
     * @access public
     * @param string $word 待翻译的字符串
     * @param string $from 翻译前的语言
     * @param string $to 翻译后的语言
     * @return string
     */
    public function transform($word, $from = 'zh', $to = 'en')
    {
        /** 构建请求地址及参数 */
        $url = 'http://openapi.baidu.com/public/2.0/bmt/translate';
        $post = array(
            'client_id' => $this->_apiKey,
            'q' => $word,
            'from' => $from,
            'to' => $to
        );

        /** 配置 cURL 选项 */
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($post),
            CURLOPT_TIMEOUT => 60
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        if (!$result = curl_exec($ch)) {
            return false;
        }
        curl_close($ch);

        $result = json_decode($result, true);

        /** 返回翻译错误 */
        if (isset($result['error_code'])) {
            return false;
        }

        /** 去除标点符号及转换成小写 */
        $result = $result['trans_result'][0]['dst'];
        $result = preg_replace('/[[:punct:]]/', '', $result);
        $result = strtolower(trim($result));

        return $result;
    }
}
