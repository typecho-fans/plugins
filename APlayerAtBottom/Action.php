<?php
class APlayerAtBottom_Action extends Typecho_Widget implements Widget_Interface_Do
{

    public function action(){
		if (file_exists(__DIR__ .'/settings.json') == false) {
			header("Location: ".Helper::options()->siteUrl);
		}
		$content = json_decode(file_get_contents(__DIR__ .'/settings.json'),true);
		$set = $content['settings'];
		echo 'const ap = new APlayer({container: document.getElementById(\'downplayer\'),lrcType:'.$set['lrc'].',autoplay:'.$set['autoplay'].',fixed:true,theme:\''.$set['theme'].'\',volume:'.$set['volume'].',order:\''.$set['order'].'\',audio:'.$content['data'].'});';
	}
	
}
?>