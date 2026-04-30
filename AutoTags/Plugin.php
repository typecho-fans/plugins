<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 标签自动生成插件
 * 
 * @package AutoTags
 * @author DT27
 * @version 3.0.1
 * @link https://dt27.cn/php/autotags-for-typecho/
 */
class AutoTags_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('AutoTags_Plugin', 'write');

    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

        $isActive = new Typecho_Widget_Helper_Form_Element_Radio('isActive',
            array(
                '1' => '是',
                '0' => '否',
            ),'1', _t('是否启用标签自动提取功能'), _t('自动提取功能在文章已存在标签时不生效.')
        );
        $form->addInput($isActive);
    
        $api_key = new Typecho_Widget_Helper_Form_Element_Text(
            'api_key', NULL, 'sk-or-v1-*********',
            _t('你的OpenRouter API Key'),
            _t('免费申请地址：<a href="https://openrouter.ai/settings/keys" target="_blank">https://openrouter.ai/settings/keys</a>')
        );
        $form->addInput($api_key);

        $api_model = new Typecho_Widget_Helper_Form_Element_Text(
            'api_model', NULL, 'minimax/minimax-m2.5:free',
            _t('调用ai模型'),
            _t('免费模型列表：<a href="https://openrouter.ai/models?fmt=cards&max_price=0&order=newest&output_modalities=text" target="_blank">https://openrouter.ai/models</a>，若网站日志报错可能是访问人数过多，可以多换几个试试。')
        );
        $form->addInput($api_model);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 发布文章时自动提取标签
     *
     * @access public
     * @return void
     */
    public static function write($contents, $edit)
    {
		$title = $contents['title'];
        $html = $contents['text'];
        $isMarkdown = (0 === strpos($html, '<!--markdown-->'));
        if($isMarkdown){
            $html = Markdown::convert($html);
        }
		//过滤 html 标签等无用内容
        $text = str_replace("\n", '', trim(strip_tags(html_entity_decode($html))));
        // 1. 截取正文前 800 字，防止 Token 过长导致模型拒答
        $text = mb_substr($text, 0, 800);
        $autoTags = Typecho_Widget::widget('Widget_Options')->plugin('AutoTags');
        //插件启用,且未手动设置标签
        if($autoTags->isActive == 1 && !$contents['tags']) {
            //Typecho_Widget::widget('Widget_Metas_Tag_Admin')->to($tags);
            //foreach($tags->stack as $tag){
            //    $tagNames[] = $tag['name'];
            //}
            $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
            // 请求提示词
            $prompt = "请为这篇文章提取1到5个关键词作为标签。仅返回标签本身，多个标签用英文逗号分隔，不要包含任何解释。标题：{$title}；内容：{$text}";
            $data = [
                'model' => $autoTags->api_model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'stream' => false, // 非流式响应
                //'response_format' => ['type' => 'json_object'] // 要求 JSON 格式（若模型支持）
            ];

            // 初始化 cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $autoTags->api_key,
                'Content-Type: application/json',
                'HTTP-Referer: https://dt27.cn/php/autotags-for-typecho/',
                'X-Title: 标签自动提取插件 For Typecho'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            // 执行请求
            $response = curl_exec($ch);
            // 检查错误
            if (curl_errno($ch)) {
                error_log('cURL Error: ' . curl_error($ch));
                curl_close($ch);
                exit;
            }
            // 获取 HTTP 状态码
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            // 处理响应
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    // 将逗号分隔的字符串转换为数组
                    $tagsArray = array_map('trim', explode(',', $result['choices'][0]['message']['content'])); // 分割并去除每个标签的空白
                    //echo "Extracted Tags (Array):\n";
                    $contents['tags']=implode(',', array_unique($tagsArray));
                } else {
                    error_log("Error: No tags returned in response.\n");
                    return $contents;
                }
            } else {
                error_log("HTTP Error: " . $httpCode . "\n");
                error_log("Response: " . $response . "\n");
            }
        }
        return $contents;
    }
}
