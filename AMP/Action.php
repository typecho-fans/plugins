<?php

class AMP_Action extends Typecho_Widget implements Widget_Interface_Do
{
	public function __construct($request, $response, $params = NULL)
	{
		parent::__construct($request, $response, $params);
		$this->LOGO=Helper::options()->plugin('AMP')->LOGO;
		$this->defaultPIC=Helper::options()->plugin('AMP')->defaultPIC;
		$this->action();
	}
	
	public function action()
	{
		$this->db = Typecho_Db::get();
	}
	
	
	public function AMPpage()
	{
		$this->article = $this->getArticleBySlug();
		
		if ($this->article['isMarkdown']) {
			?>
			<!doctype html>
			<html amp lang="zh">
			<head>
				<meta charset="utf-8">
				<title><?php print($this->article['title']); ?></title>
				<link rel="canonical" href="<?php print($this->article['permalink']); ?>"/>
				<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
				<script type="application/ld+json">
      {
        "@context": "http://schema.org",
        "@type": "BlogPosting",
        "headline": "<?php print($this->article['title']); ?>",
        "mainEntityOfPage": "<?php print($this->article['permalink']); ?>",
        "author": {
          "@type": "Person",
          "name": "<?php print($this->article['author']); ?>"
        },
        "datePublished": "<?php print($this->article['date']->format('F j, Y')); ?>",
        "dateModified": "<?php print($this->article['date']->format('F j, Y')); ?>",
        "image": {
          "@type": "ImageObject",
          "url": "<?php print($this->Get_post_img()); ?>",
          "width": 700,
          "height": 400
        },
         "publisher": {
          "@type": "Organization",
          "name": "Holmesian Blog",
          "logo": {
            "@type": "ImageObject",
            "url": "<?php print($this->LOGO); ?>",
            "width": 60,
            "height": 60
          }
        },
        "description": "<?php print(mb_substr(str_replace("\r\n", "", $this->article['text']), 0, 150) . "..."); ?>"
      }
				</script>
				<script async src="https://cdn.ampproject.org/v0.js"></script>
				<style amp-custom>*{margin:0;padding:0}html,body{height:100%}body{background:#fff;color:#666;font-size:14px;font-family:"-apple-system","Open Sans","HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",Helvetica,Arial,sans-serif}::selection,::-moz-selection,::-webkit-selection{background-color:#2479CC;color:#eee}h1{font-size:1.5em}h3{font-size:1.3em}h4{font-size:1.1em}a{color:#2479CC;text-decoration:none}article{padding:85px 15px 0}article .entry-content{color:#444;font-size:16px;font-family:Arial,'Hiragino Sans GB',冬青黑,'Microsoft YaHei',微软雅黑,SimSun,宋体,Helvetica,Tahoma,'Arial sans-serif';-webkit-font-smoothing:antialiased;line-height:1.8;word-wrap:break-word}article h1.title{color:#333;font-size:2em;font-weight:300;line-height:35px;margin-bottom:25px}article .entry-content p{margin-top:15px}article h1.title a{color:#333;transition:color .3s}article h1.title a:hover{color:#2479CC}article blockquote{background-color:#f8f8f8;border-left:5px solid #2479CC;margin-top:10px;overflow:hidden;padding:15px 20px}article code{background-color:#eee;border-radius:5px;font-family:Consolas,Monaco,'Andale Mono',monospace;font-size:80%;margin:0 2px;padding:4px 5px;vertical-align:middle}article pre{background-color:#f8f8f8;border-left:5px solid #ccc;color:#5d6a6a;font-size:14px;line-height:1.6;overflow:hidden;padding:0.6em;position:relative;white-space:pre-wrap;word-break:break-word;word-wrap:break-word}article table{border:0;border-collapse:collapse;border-spacing:0}article pre code{background-color:transparent;border-radius:0 0 0 0;border:0;display:block;font-size:100%;margin:0;padding:0;position:relative}article table th,article table td{border:0}article table th{border-bottom:2px solid #848484;padding:6px 20px;text-align:left}article table td{border-bottom:1px solid #d0d0d0;padding:6px 20px}article .copyright-info,article .amp-info{font-size:14px}article .expire-tips{background-color:#f5d09a;border:1px solid #e2e2e2;border-left:5px solid #fff000;color:#333;font-size:15px;padding:5px 10px;margin:20px 0px}article .post-info,article .entry-content .date{font-size:14px}article .entry-content blockquote,article .entry-content ul,article .entry-content ol,article .entry-content dl,article .entry-content table,article .entry-content h1,article .entry-content h2,article .entry-content h3,article .entry-content h4,article .entry-content h5,article .entry-content h6,article .entry-content pre{margin-top:15px}article pre b.name{color:#eee;font-family:"Consolas","Liberation Mono",Courier,monospace;font-size:60px;line-height:1;pointer-events:none;position:absolute;right:10px;top:10px}article .entry-content .date{color:#999}article .entry-content ul ul,article .entry-content ul ol,article .entry-content ul dl,article .entry-content ol ul,article .entry-content ol ol,article .entry-content ol dl,article .entry-content dl ul,article .entry-content dl ol,article .entry-content dl dl,article .entry-content blockquote > p:first-of-type{margin-top:0}article .entry-content ul,article .entry-content ol,article .entry-content dl{margin-left:25px}.header{background-color:#fff;box-shadow:0 0 40px 0 rgba(0,0,0,0.1);box-sizing:border-box;font-size:14px;height:60px;padding:0 15px;position:absolute;width:100%}.footer{font-size:.9em;padding:15px 0 25px;text-align:center;width:auto}.header h1{font-size:30px;font-weight:400;line-height:30px;margin:15px 0px}.menu-list li a,.menu-list li span{border-bottom:solid 1px #ededed;color:#000;display:block;font-size:18px;height:60px;line-height:60px;text-align:center;width:86px}.header h1 a{color:#333}.tex .hljs-formula{background:#eee8d5}</style>
				<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
			<noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
			</head>
			<body>
			<header class="header">
				<div class="header-title"><h1><a href="/">Holmesian Blog</a></h1></div>
			</header>
			
			<article class="post"><h1 class="title"><?php print($this->article['title']); ?></h1>
				<div class="entry-content">
					<?php print($this->AMPInit($this->article['text'])); ?>
				</div>
				<p class="expire-tips">当前页面是本站的「<a href="//www.ampproject.org/zh_cn/">Google AMP</a>」版。查看和发表评论请点击：<a
						href="<?php print($this->article['permalink']); ?>#comments">完整版 »</a></p>
			
			</article>
			
			</body>
			</html>
			<?php
		} else {
			die('Delete');
		}
		
		
	}
	
	public function getArticleBySlug()
	{
		$select = $this->db->select()->from('table.contents')
			->where('slug = ?', $this->request->slug);
		
		$article_src = $this->db->fetchRow($select);
		
		if (count($article_src) > 0) {
			$article = Typecho_Widget::widget("Widget_Abstract_Contents")->push($article_src);
			$select = $this->db->select('table.users.screenName')
				->from('table.users')
				->where('uid = ?', $article['authorId']);
			$author = $this->db->fetchRow($select);
			$article['author'] = $author['screenName'];
			$article['text'] = Typecho_Widget::widget("Widget_Abstract_Contents")->markdown($article['text']);
		} else {
			$article = array('isMarkdown' => false);
		}
		return $article;
		
	}
	
	
	private function AMPInit($text)
	{
			$text = str_replace('<img', '<amp-img width="700" height="1300" layout="responsive" ', $text);
			$text = str_replace('img>', 'amp-img>', $text);
			$text = str_replace('<!- toc end ->', '', $text);
			$text = str_replace('javascript:content_index_toggleToc()', '#', $text);
			return $text;
	}
	
	private function Get_post_img()
	{
		$text = $this->article['text'];
		
		$pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';
		$patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|png))/i';
		$patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|png))/i';
		if (preg_match($patternMDfoot, $text, $img)) {
			$img_url = $img[1];
		} else if (preg_match($patternMD, $text, $img)) {
			$img_url = $img[1];
		} else if (preg_match($pattern, $text, $img)) {
			preg_match("/(?:\()(.*)(?:\))/i", $img[0], $result);
			$img_url = $img[1];
		} else {
			$img_url = $this->defaultPIC;
		}
		return $img_url;
		
	}
	
	public static function headlink()
	{
		$widget = Typecho_Widget::widget('Widget_Archive');
		$ampurl = '';
		if ($widget->is('post')) {
			$slug = $widget->request->slug;
			$fullURL=Typecho_Common::url("amp/{$slug}", Helper::options()->index);
//			$fullURL=Helper::options()->index("amp/{$slug}");
			$ampurl = "<link rel=\"amphtml\" href=\"{$fullURL}\">\n";
		}
		
		echo $ampurl;
	}
	
	
	
}

?>