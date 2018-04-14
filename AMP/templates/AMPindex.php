<!doctype html>
<html amp lang="zh">
<head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-list" src="https://cdn.ampproject.org/v0/amp-list-0.1.js"></script>
    <script async custom-template="amp-mustache" src="https://cdn.ampproject.org/v0/amp-mustache-0.1.js"></script>
    <script async custom-element="amp-bind" src="https://cdn.ampproject.org/v0/amp-bind-0.1.js"></script>
    <title><?php print($this->publisher." -- AMP Version"); ?></title>
    <link rel="canonical" href="<?php print($this->baseurl); ?>"/>
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <style amp-custom>*{margin:0;padding:0}html,body{height:100%}body{background:#fff;color:#666;font-size:14px;font-family:"-apple-system","Open Sans","HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",Helvetica,Arial,sans-serif}::selection,::-moz-selection,::-webkit-selection{background-color:#2479cc;color:#eee}h1{font-size:1.5em}h3{font-size:1.3em}h4{font-size:1.1em}a{color:#2479cc;text-decoration:none}header{background-color:#fff;box-shadow:0 0 40px 0 rgba(0,0,0,0.1);box-sizing:border-box;font-size:14px;height:60px;padding:0 15px;position:absolute;width:100%}header a{color:#333}header h1{font-size:30px;font-weight:400;line-height:30px;margin:15px 0}footer{font-size:.9em;text-align:center;width:auto;padding: 10px;}.content{padding-top:60px}article{position:relative;padding:30px;border-top:1px solid #fff;border-bottom:1px solid #ddd}.pageinfo{font-size:15px;padding:5px;margin:5px;text-align:center}.info{background-color:#f5d09a;border:1px solid #e2e2e2;border-left:5px solid #fff000;color:#333;font-size:15px;padding:5px 10px;margin:10px 0}.nav{text-align:center;margin-bottom:-25px}.nav button{width:150px;height:25px;margin:auto;margin-bottom:20px;border-width:0;border-radius:3px;background:#1e90ff;cursor:pointer;outline:0;color:white;font-size:16px}button:hover{background:#59f}article a{font-size:2em}article p{position:relative;line-height:2em;font-size:16px;text-indent:2em;padding-top:15px}</style>
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
    <noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
</head>
<body>
<header>
    <div class="header-title"><h1><a href="<?php print($this->baseurl); ?>"><?php print($this->publisher);?></a></h1></div>
</header>
<div></div>
<div class="content">
    <amp-list width="auto"
              height="650"
              layout="fixed-height"
              src="<?php echo Typecho_Common::url("amp/list/1", $this->baseurl);?>"
              [src]="'<?php echo Typecho_Common::url("amp/list/", $this->baseurl);?>' + pageNumber"
              single-item>

        <template type="amp-mustache">
            {{#article}}
            <article>
                <a href="{{url}}">{{title}}</a>
                <div class="article_content"><p>{{content}}</p></div>
            </article>
            {{/article}}
            <p class="pageinfo">Page {{currentPage}} of {{pageCount}} </p>
        </template>
    </amp-list>
</div>
<footer>
    <div class="nav">
        <button class="prev"
                hidden
                [hidden]="pageNumber < 2"
                on="tap:
    AMP.setState({
      pageNumber: pageNumber - 1
    })">Previous</button>
        <button class="next"
                [hidden]="page ? pageNumber >= page.items.pageCount : false"
                on="tap:
    AMP.setState({
      pageNumber: pageNumber ? pageNumber + 1 : 2
    })">Next</button>
    </div>

    <amp-state id="page"
               src="<?php echo Typecho_Common::url("amp/list/1", $this->baseurl);?>"
               [src]="'<?php echo Typecho_Common::url("amp/list/", $this->baseurl);?>' + pageNumber"></amp-state>
    <div><p class="info">当前页面是本站的「<a href="//www.ampproject.org/zh_cn/">Google AMP</a>」版。查看和发表评论请点击：<a
                    href="<?php print($this->baseurl); ?>">完整版 »</a></p></div>

    <div class="footer"><p>© 2018 <a data-type="mip" href="https://github.com/holmesian/Typecho-AMP">MIP for Typecho</a>
            , Designed by  <a href="https://holmesian.org/" target="_blank">Holmesian</a>.</p></div>
</footer>
</body>
</html>