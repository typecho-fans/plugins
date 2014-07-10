<?php
/**
 * 在显示404之前使用规则重写
 * 
 * @package RewriteRule
 * @author laobubu
 * @version 1.0.0
 * @link http://laobubu.net
 */
class RewriteRule_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->error404Handle = array('RewriteRule_Plugin', 'error404Handle');
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
    public static function config(Typecho_Widget_Helper_Form $form){
        $introduce = new Typecho_Widget_Helper_Layout("div");
        $introduce->html(<<<EOI
<p>在 Typecho 正式显示 404 之前，此插件会遍历列表并作出跳转。</p>
<p>规则列表一行一个，格式为： Pattern   Rewrite结果  [flags]</p>
<p>比如【 <code class="success">^\/(\d+)\/?$  /archives/$1   T</code> 】可以把 /12 以302重定向跳转到 /archivers/12 </p>
<p><a href="https://github.com/laobubu/Typecho_rewriterule">详细说明看这里</a></p>
EOI
);
        $rules = new Typecho_Widget_Helper_Form_Element_Textarea('rules', NULL, '不符合书写规范的行可以当注释哦', _t('规则列表'));
        $form->addInput($rules);
        $form->addItem($introduce);
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
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
	public static function error404Handle($that,$select) {
		$rules = Typecho_Widget::widget('Widget_Options')->plugin('RewriteRule')->rules;
		$rules = str_replace("\r","",$rules);
		$rules = explode("\n",$rules);
		$uri = $_SERVER['REQUEST_URI'];
		
		$func_decorate_key = function($value) {return '$'.$value;};
		$matcher_list = array('cid','slug','category');
		
		foreach($rules as $i) {
			$i = preg_replace("/\s+/","\t",trim($i));
			$l = explode("\t",$i,3);
			if (count($l)>=2) {
				$flags = $l[2]?$l[2]:"";
				$pattern = "/".$l[0]."/";
				if (stristr($flags,'i'))	$pattern.="i";
				
				if (!preg_match($pattern,$uri,$match_out))
					continue;
				
				if (stristr($flags,'c')) {
					$count_of_matcher = 0;
					foreach ($matcher_list as $mli) {
						if (isset($match_out[$mli])) {
							$select = $select->where('typecho_contents.`'.$mli.'` = ?', $match_out[$mli]);
							$count_of_matcher++;
						}
					}
					if ($count_of_matcher==0) continue;
					$that->slug = NULL;
					$that->query($select);
					$match_out['cid'] = $that->cid;
					$match_out['slug'] = $that->slug;
					$match_out['category'] = $that->category;
					if (!$that->slug) continue;
				}
				
				$r1 = array_map($func_decorate_key,array_keys($match_out));
				$r2 = array_values($match_out);

				$newuri = str_replace($r1,$r2,$l[1]);
				
				header(stristr($flags,'t') ? "HTTP/1.1 302 Found" : "HTTP/1.1 301 Moved Permanently");
				header("Location: ".$newuri);
				echo '<meta http-equiv="refresh" content="0; url='.$newuri.'" />';
				echo "<a href=\"{$newuri}\">Click to continue</a>";
				exit;
			}
		}
	}
}
