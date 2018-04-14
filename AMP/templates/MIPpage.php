<!DOCTYPE html>
<html lang="zh-cn" mip>
<head>
    <meta charset="utf-8">
    <meta name="X-UA-Compatible" content="IE=edge">
    <title><?php print($MIPpage['title']); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no">
    <link rel="stylesheet" type="text/css" href="https://mipcache.bdstatic.com/static/v1/mip.css">
    <link rel="canonical" href="<?php print($MIPpage['permalink']); ?>">
    <style mip-custom>p{text-indent: 2em;} a,abbr,acronym,address,applet,big,blockquote,body,caption,cite,code,dd,del,dfn,div,dl,dt,em,fieldset,form,h1,h2,h3,h4,h5,h6,html,iframe,img,ins,kbd,label,legend,li,object,ol,p,pre,q,s,samp,small,span,strike,strong,sub,sup,table,tbody,td,tfoot,th,thead,tr,tt,ul,var{margin:0;padding:0;border:0;font-family:inherit;font-size:100%;font-weight:inherit;font-style:inherit;vertical-align:baseline;outline:0}body{line-height:1;color:#000;background:#fff}ol,ul{list-style:none}table{vertical-align:middle;border-spacing:0;border-collapse:separate}caption,td,th{font-weight:400;text-align:left;vertical-align:middle}a img{border:none}html{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}*,:after,:before{-webkit-box-sizing:inherit;-moz-box-sizing:inherit;box-sizing:inherit}button::-moz-focus-inner,input[type=button]::-moz-focus-inner,input[type=reset]::-moz-focus-inner,input[type=submit]::-moz-focus-inner{margin:0;padding:0;border:0}button,input,select{margin:0;padding:0;border:0}body{overflow-x:hidden;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:15px;color:#444;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeLegibility}.mip-nav-wrapper{padding:0 10px;background-color:#323436}.navbar-brand{color:#fff!important}@media screen and (min-width:767px){.mip-nav-wrapper a{color:#fff!important}}@media screen and (max-width:767px){.mip-nav-wrapper .navbar-brand{margin-top:2px}}.navbar-brand{display:block;overflow:hidden;max-width:200px;line-height:41px;white-space:nowrap;text-overflow:ellipsis}mip-fixed[type=bottom],mip-fixed[type=top]{overflow:visible}.post{position:relative;padding:15px 10px;border-top:1px solid #fff;border-bottom:1px solid #ddd;word-wrap:break-word;background-color:#fff}h1.title,h2.title{padding:10px 0;font-size:28px}.post .meta{color:#666}.article-more a{color:#2479c2}.article-content .date{display:inline-block;margin:0 10px;font-size:14px;font-style:italic;color:#999}.article-content{line-height:1.6em;color:#444}@media print{.article-content{font-size:12pt}}.article-content .highlight,.article-content blockquote,.article-content dl,.article-content iframe,.article-content ol,.article-content p,.article-content table,.article-content ul{margin:1em 0}.article-content h1{font-size:2em}.article-content h2{font-size:1.5em}.article-content h3{font-size:1.3em}.article-content h1,.article-content h2,.article-content h3,.article-content h4,.article-content h5,.article-content h6{margin:1em 0;font-weight:700;line-height:1em}.article-content a{text-decoration:none;color:#0e83cd}.article-content a:hover{text-decoration:underline;color:#1094e8}@media print{.article-content a{text-decoration:underline;color:#444}.article-content a:after{content:" (" attr(href) ")";font-size:80%}}.article-content strong{font-weight:700}.article-content em{font-style:italic}.article-content dl,.article-content ol,.article-content ul{margin-left:20px}.article-content dl dl,.article-content dl ol,.article-content dl ul,.article-content ol dl,.article-content ol ol,.article-content ol ul,.article-content ul dl,.article-content ul ol,.article-content ul ul{margin-top:0;margin-bottom:0}.article-content ul{list-style:disc}.article-content ol{list-style:decimal}.article-content dl{list-style:square}.article-content li p{margin:0}.article-content li .highlight,.article-content li blockquote,.article-content li iframe,.article-content li table{margin:1em 0}.article-content img,.article-content video{max-width:100%}.article-content table{max-width:100%;border:1px solid #e3e3e3}.article-content table th{font-weight:700}.article-content table td,.article-content table th{padding:5px 15px}.article-content table tr:nth-child(2n){background:#eee}.article-content blockquote{position:relative;padding:0 20px;border:1px solid #e3e3e3;border-left:5px solid #ddd}.article-content .tip,.article-content .tip-error,.article-content .tip-info{position:relative;margin:1em 0;padding:1em 20px;border:1px solid #e3e3e3;border-left:5px solid #5fb878;border-top-right-radius:2px;border-bottom-right-radius:2px}.article-content .tip br:first-child,.article-content .tip-error br:first-child,.article-content .tip-info br:first-child{display:none}.article-content .tip-error:before,.article-content .tip-info:before{content:"!";position:absolute;top:16px;left:-12px;width:20px;height:20px;font-family:Dosis,"Source Sans Pro","Helvetica Neue",Arial,sans-serif;font-size:14px;font-weight:700;line-height:20px;text-align:center;color:#fff;border-radius:100%;background-color:#5fb878}.article-content .tip-info{border-left-color:#1e9fff}.article-content .tip-info:before{background-color:#1e9fff}.article-content .tip-error{border-left-color:#ff5722}.article-content .tip-error:before{background-color:#ff5722}.main{padding:72px 10px 0;max-width:1000px;margin:0 auto}@media screen and (max-width:767px){.main{padding-top:44px}}code,pre{font-family:"Source Code Pro",Monaco,Menlo,Consolas,monospace;font-size:.95em;color:#4d4d4c;background:#eee;overflow-x:auto;-webkit-overflow-scrolling:touch}code{padding:0 5px}pre{padding:10px 15px;line-height:22px}pre code{display:block;padding:0;border:none}.highlight{overflow:auto;margin:0;padding:10px 15px;color:#4d4d4c;background:#eee}.highlight table{margin:0!important;border:0}.highlight table td,.highlight table th{padding:0}.highlight figcaption{margin:-5px 0 5px;font-size:.9em;color:#999}.highlight figcaption:after,.highlight figcaption:before{content:"";display:table}.highlight figcaption:after{clear:both}.highlight figcaption a{float:right}.highlight pre{padding:0;border:none;background:0 0}.highlight .line{height:22px}pre .comment,pre .title{color:#8e908c}pre .attribute,pre .css .class,pre .css .id,pre .css .pseudo,pre .html .doctype,pre .regexp,pre .ruby .constant,pre .tag,pre .variable,pre .xml .doctype,pre .xml .pi,pre .xml .tag .title{color:#c82829}pre .built_in,pre .constant,pre .literal,pre .number,pre .params,pre .preprocessor{color:#f5871f}pre .class,pre .css .rules .attribute,pre .ruby .class .title{color:#718c00}pre .header,pre .inheritance,pre .ruby .symbol,pre .string,pre .value,pre .xml .cdata{color:#718c00}pre .css .hexcolor{color:#3e999f}pre .coffeescript .title,pre .function,pre .javascript .title,pre .perl .sub,pre .python .decorator,pre .python .title,pre .ruby .function .title,pre .ruby .title .keyword{color:#4271ae}pre .javascript .function,pre .keyword{color:#8959a8}.footer{line-height:1.8;text-align:center;padding:15px;border-top:1px solid #fff;font-size:.9em;color:#999}.footer a{color:#2479c2}.pagination{width:100%;line-height:20px;position:relative;border-top:1px solid #fff;border-bottom:1px solid #ddd;padding:20px 0;overflow:hidden}.pagination .prev{float:left}.pagination .next{float:right}.pagination a{color:#2479c2}</style>
    <script type="application/ld+json">
                    {
                        "@context": "https://ziyuan.baidu.com/contexts/cambrian.jsonld",
                        "@id": "<?php print($MIPpage['mipurl']);?>",
                        "appid": "<?php print($MIPpage['APPID']);?>",
                        "title": "<?php print($MIPpage['title']); ?>",
                        "images": [
                            "<?php print($MIPpage['imgData']['url']); ?>"
                            ],
                        "description": "<?php print($MIPpage['desc']);?>",
                        "pubDate": "<?php print($MIPpage['date']->format('Y-m-d\TH:i:s')); ?>",
                        "upDate": "<?php print($MIPpage['modified']); ?>",
                        "lrDate": "<?php print($MIPpage['modified']); ?>",
                        "isOrignal":1
                    }
               </script>
</head>
<body>
<mip-cambrian site-id="<?php print($MIPpage['APPID']);?>"></mip-cambrian>
<mip-fixed type="top">
    <div class="mip-nav-wrapper">
        <mip-nav-slidedown data-id="bs-navbar" data-showbrand="1" data-brandname="<?php print($MIPpage['publisher']);?>" class="mip-element-sidebar container">
            <nav id="bs-navbar" class="navbar-collapse collapse navbar navbar-static-top">
                <ul class="nav navbar-nav navbar-right">
                    <li><a data-type="mip" href="/">首页</a></li>
                    <li><a href="<?php print($MIPpage['permalink']); ?>">本文PC版</a></li>
                    <li class="navbar-wise-close"><span id="navbar-wise-close-btn"></span></li>
                </ul>
            </nav>
        </mip-nav-slidedown>
    </div>
</mip-fixed>
<div class="main">
    <div class="post-detail">
        <article class="post"><h1 class="title"><?php print($MIPpage['title']); ?></h1>
            <div class="meta"><?php print($MIPpage['date']->format('Y-m-d H:i:s')); ?></div>
            <div class="article-content">
                <?php print($MIPpage['MIPtext']); ?>
                <div class="tip">当前页面是本站的「<a href="https://www.mipengine.org/">Baidu MIP</a>」版。发表评论请点击：<a
                        href="<?php print($MIPpage['permalink']); ?>">完整版 »</a></div>
                <?php if(!$MIPpage['isMarkdown']){print('<div class="tip-error">因本文不是用Markdown格式的编辑器书写的，转换的页面可能不符合MIP标准。</div>');} ?>
        </article>

    </div>
</div>
<div class="footer"><p>© 2018 <a data-type="mip" href="https://github.com/holmesian/Typecho-AMP">MIP for Typecho</a>
        , Designed by  <a href="https://holmesian.org/" target="_blank">Holmesian</a>.</p></div>
<mip-fixed type="gototop">
    <mip-gototop></mip-gototop>
</mip-fixed>
<script src="https://mipcache.bdstatic.com/static/v1/mip.js"></script>
<script src="https://mipcache.bdstatic.com/static/v1/mip-nav-slidedown/mip-nav-slidedown.js"></script>
<script src="https://mipcache.bdstatic.com/static/v1/mip-gototop/mip-gototop.js"></script>
<script src="https://mipcache.bdstatic.com/static/v1/mip-fixed/mip-fixed.js"></script>
<script src="https://mipcache.bdstatic.com/extensions/platform/v1/mip-cambrian/mip-cambrian.js"></script>
</body>
</html>