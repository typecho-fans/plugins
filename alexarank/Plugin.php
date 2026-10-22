<?php
/**
 * alexa排名显示
 * 
 * @package alexa排名挂件
 * @author Jrotty
 * @version 1.1
 * @link http://qqdie.com/archives/alexa.html
 */
class alexarank_Plugin implements Typecho_Plugin_Interface
{ 
 public static function activate()
	{
Typecho_Plugin::factory('Widget_Archive')->callAlexa = array('alexarank_Plugin', 'alexa');
    }
	/* 禁用插件方法 */
	public static function deactivate(){}
    public static function config(Typecho_Widget_Helper_Form $form){
$Yoururl =Typecho_Widget::widget('Widget_Options')->siteUrl;
$Yoururl=str_replace('http://','',$Yoururl);      
$Yoururl=str_replace('https://','',$Yoururl);
$Yoururl=str_replace('/','',$Yoururl);     
        $yumi = new Typecho_Widget_Helper_Form_Element_Text('yumi', NULL, $Yoururl, _t('你的域名'), _t('<style>#alexac:after {
content: " ?>";
  color: red;
}#alexac:before {
content: "<?php ";
  color: red;
}</style><div style="
    background: #fff;
    padding: 10px;
    margin-top: -0.5em;"><p><b>步骤一：</b>插件首次启动会为您自动填写域名，如有错误请按这个格式手动填写，例如：qqdie.com</p>
<p><b>步骤二：</b>记住这个模板调用方式<code id="alexac" style="    color: red;
    margin: 0 3px;">$this->alexa();</code></pre></p>
<p><b>步骤三：</b>点击下方的保存设置</p>
<p><b>步骤四：</b>将步骤二中的代码插入模板对应位置</p><p><b>美化：</b>插件只会输出纯数字，可自行套html，css来美化它</p><p><b>或许：</b>如果只是想装逼的话，也可以填别人的域名进去！</p>
</div> '));
        $form->addInput($yumi);

    }
    
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}


    public static function alexa()
    {
        echo self::rank(Typecho_Widget::widget('Widget_Options')->plugin('alexarank')->yumi);
    }

    /**
     * data.alexa.com no longer exists (Alexa was retired in 2022), so the rank now comes from
     * the free NetAPI Top 1M (https://netapi.com/top-1-mln-websites/), updated daily.
     * Cached for 12 hours in the system temp directory; 0 = not in the top 1 million.
     */
    private static function rank($domain)
    {
        $cache = sys_get_temp_dir() . '/alexarank_' . md5($domain) . '.txt';
        if (is_file($cache) && filemtime($cache) > time() - 43200) {
            return (int)file_get_contents($cache);
        }
        $ctx = stream_context_create(array('http' => array('timeout' => 5)));
        $json = @file_get_contents('https://netapi.com/api-json/?method=domain-rank&domain=' . rawurlencode($domain), false, $ctx);
        $data = $json ? json_decode($json, true) : null;
        if (!is_array($data) || !array_key_exists('rank', $data)) {
            // keep the last known value when the request fails
            return is_file($cache) ? (int)file_get_contents($cache) : 0;
        }
        $rank = (int)$data['rank'];
        @file_put_contents($cache, $rank);
        return $rank;
    }
}
