<!doctype html>
<html amp lang="zh">
<head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <title><?php echo($AMPpage['title']); ?></title>
    <link rel="canonical" href="<?php echo($AMPpage['permalink']); ?>"/>
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <script type="application/ld+json">
      {
        "@context": "http://schema.org",
        "@type": "BlogPosting",
        "headline": "<?php echo($AMPpage['title']); ?>",
        "mainEntityOfPage": "<?php echo($AMPpage['permalink']); ?>",
        "author": {
          "@type": "Person",
          "name": "<?php echo($AMPpage['author']); ?>"
        },
        "datePublished": "<?php echo($AMPpage['date']); ?>",
        "dateModified": "<?php echo($AMPpage['modified']); ?>",
        "image": {
          "@type": "ImageObject",
          "url": "<?php echo($AMPpage['imgData']['url']); ?>",
          "width": "<?php echo($AMPpage['imgData']['width']); ?>",
          "height": "<?php echo($AMPpage['imgData']['height']); ?>"
        },
        "publisher": {
          "@type": "Organization",
          "name": "<?php echo($AMPpage['publisher']); ?>",
          "logo": {
            "@type": "ImageObject",
            "url": "<?php echo($AMPpage['LOGO']); ?>",
            "width": 60,
            "height": 60
          }
        },
        "description": "<?php echo($AMPpage['desc']); ?>"
      }
                </script>
    <style amp-custom>*{margin:0;padding:0}html,body{height:100%}body{background:#fff;color:#666;font-size:14px;font-family:"-apple-system","Open Sans","HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",Helvetica,Arial,sans-serif}::selection,::-moz-selection,::-webkit-selection{background-color:#2479CC;color:#eee}h1{font-size:1.5em}h3{font-size:1.3em}h4{font-size:1.1em}a{color:#2479CC;text-decoration:none}article{padding:85px 15px 0}article .entry-content{color:#444;font-size:16px;font-family:Arial,'Hiragino Sans GB',冬青黑,'Microsoft YaHei',微软雅黑,SimSun,宋体,Helvetica,Tahoma,'Arial sans-serif';-webkit-font-smoothing:antialiased;line-height:1.8;word-wrap:break-word}article h1.title{color:#333;font-size:2em;font-weight:300;line-height:35px;margin-bottom:25px}article .entry-content p{margin-top:15px;text-indent: 2em;}article h1.title a{color:#333;transition:color .3s}article h1.title a:hover{color:#2479CC}article blockquote{background-color:#f8f8f8;border-left:5px solid #2479CC;margin-top:10px;overflow:hidden;padding:15px 20px}article code{background-color:#eee;border-radius:5px;font-family:Consolas,Monaco,'Andale Mono',monospace;font-size:80%;margin:0 2px;padding:4px 5px;vertical-align:middle}article pre{background-color:#f8f8f8;border-left:5px solid #ccc;color:#5d6a6a;font-size:14px;line-height:1.6;overflow:hidden;padding:0.6em;position:relative;white-space:pre-wrap;word-break:break-word;word-wrap:break-word}article table{border:0;border-collapse:collapse;border-spacing:0}article pre code{background-color:transparent;border-radius:0 0 0 0;border:0;display:block;font-size:100%;margin:0;padding:0;position:relative}article table th,article table td{border:0}article table th{border-bottom:2px solid #848484;padding:6px 20px;text-align:left}article table td{border-bottom:1px solid #d0d0d0;padding:6px 20px}article .copyright-info,article .amp-info{font-size:14px}article .notice{background-color:#f5d09a;border:1px solid #e2e2e2;border-left:5px solid #fff000;color:#333;font-size:15px;padding:5px 10px;margin:20px 0px}article .post-info,article .entry-content .date{font-size:14px}article .entry-content blockquote,article .entry-content ul,article .entry-content ol,article .entry-content dl,article .entry-content table,article .entry-content h1,article .entry-content h2,article .entry-content h3,article .entry-content h4,article .entry-content h5,article .entry-content h6,article .entry-content pre{margin-top:15px}article pre b.name{color:#eee;font-family:"Consolas","Liberation Mono",Courier,monospace;font-size:60px;line-height:1;pointer-events:none;position:absolute;right:10px;top:10px}article .entry-content .date{color:#999}article .entry-content ul ul,article .entry-content ul ol,article .entry-content ul dl,article .entry-content ol ul,article .entry-content ol ol,article .entry-content ol dl,article .entry-content dl ul,article .entry-content dl ol,article .entry-content dl dl,article .entry-content blockquote > p:first-of-type{margin-top:0}article .entry-content ul,article .entry-content ol,article .entry-content dl{margin-left:25px}.header{background-color:#fff;box-shadow:0 0 40px 0 rgba(0,0,0,0.1);box-sizing:border-box;font-size:14px;height:60px;padding:0 15px;position:absolute;width:100%}.footer{font-size:.9em;padding:15px 0 25px;text-align:center;width:auto}.header h1{font-size:30px;font-weight:400;line-height:30px;margin:15px 0px}.menu-list li a,.menu-list li span{border-bottom:solid 1px #ededed;color:#000;display:block;font-size:18px;height:60px;line-height:60px;text-align:center;width:86px}.header h1 a{color:#333}.tex .hljs-formula{background:#eee8d5}</style>
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
    <noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
</head>
<body>
<header class="header">
    <div class="header-title"><h1><a href="<?php echo(Typecho_Common::url("ampindex/", $this->baseurl)); ?>"><?php echo($AMPpage['publisher']);?></a></h1></div>
</header>

<article class="post"><h1 class="title"><?php echo($AMPpage['title']); ?></h1>
    <div class="entry-content">
        <?php echo($AMPpage['AMPtext']); ?>
    </div>
    <p class="notice">当前页面是本站的「<a href="//www.ampproject.org/zh_cn/">Google AMP</a>」版。查看和发表评论请点击：<a
                href="<?php echo($AMPpage['permalink']); ?>">完整版 »</a></p>
    <?php if(!$AMPpage['isMarkdown']){echo('<p class="notice">因本文不是用Markdown格式的编辑器书写的，转换的页面可能不符合AMP标准。</p>');} ?>
</article>
<footer><div class="footer"><p>© 2018 <a data-type="amp" href="https://github.com/holmesian/Typecho-AMP">AMP for Typecho</a> v<?php echo(AMP_Plugin::$version) ?>
            , Designed by  <a href="https://holmesian.org/" target="_blank">Holmesian</a>.</p></div></footer>
</body>
</html>