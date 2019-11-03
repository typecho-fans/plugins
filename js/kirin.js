/* ***

* 神代綺凜式魔改js
* By: Sanakey
* Last Update: 2019.11.02

神代綺凜式魔改js文件。

本代码为神代綺凜(https://moe.best)原创，Sanakey(https://keymoe.com)魔改，并遵守 GPL 2.0 开源协议。

*** */

$(document).ready(function () {
    // 神代綺凜原js部分
    var OriginTitile = document.title;
    (function () {
        var b = !0;
        window.setInterval(function () {
            0 < $("body.compensate-for-scrollbar").length && b && (b = !1, $(
                    "body.compensate-for-scrollbar #bg").attr("style", "transition-duration:0s"),
                setTimeout('$("#bg").attr("style","");cfsFlag=true', 2E3));
            // var a = $("#sidebar").children();
            var a = $("#sidebar").find("section");
            if (0 < a.length) {
                var c = $(a[a.length - 1]);
                a = $(window).scrollTop();
                c = c.offset().top + c.height();
                a -= c;
                c = $("aside.col.w-md.no-border-xs");
                0 < a ? c.css("opacity", "0") : c.css("opacity", "1")
            }
            300 > $(document).scrollTop() ? $("#kotori").addClass("hidekotori") : $("#kotori").removeClass(
                "hidekotori")
        }, 300);
        console.log("\n %c handsome modified %c by \u795e\u4ee3\u7eee\u51db lolico.moe \n",
            "color:#444;background:#eee;padding:5px 0;", "color:#fff;background:#876;padding:5px 0;");
        console.log("%c ",
            "background:url(https://ws1.sinaimg.cn/large/71785a53ly1fxylsf6ke7j216z0o6q8j.jpg) no-repeat center;background-size:cover;padding-left:100%;padding-bottom:55%;overflow:hidden;border-radius:10px;margin:5px 0"
        );
        window.setInterval(function () {
            if (document.getElementById("aboutPage")) {
                var a = document.getElementById("aboutPage"),
                    b = a.contentWindow.document.getElementById("mainc");
                try {
                    a.style.height = b.scrollHeight + "px"
                } catch (e) {}
            }
        }, 300);
        var d;
        document.addEventListener("visibilitychange", function () {
            document.hidden ? (clearTimeout(d), d = setTimeout(function () {
                document.title =
                    "|\uff65\u03c9\uff65\uff40\u0029\u4f60\u770b\u4e0d\u89c1\u6211\u2026\u2026"
            }, 500)) : (document.title = "_(:3\u300d\u300d\u8fd8\u662f\u88ab\u53d1\u73b0\u4e86", d =
                setTimeout(function () {
                    document.title = OriginTitile
                }, 2E3))
        })
    })();

    // colorfulTags();

    // <div id="bg"></div> 添加背景div
    $('#header').before('<div id="bg"></div>');

    // 优化主页面无法点击图片进入文章
    // if (!$('.post-meta .ahover').length > 0) {
    //     setHref(getHref());
    // }
    
    // 添加右侧栏折叠
    var toggleStr = '<div class="nav navbar-nav hidden-xs">\n' +
            '          <a id="aside-btn" href="#" class="btn no-shadow navbar-btn" ui-toggle-class="app-aside-folded" target=".app">\n' +
            '            <i class="fontello fontello-dedent text icon-fw"></i>\n' +
            '            <i class="fontello fontello-indent icon-fw text-active"></i>\n' +
            '          </a>\n' +
            '        </div>'
    $('#header .navbar-collapse').prepend(toggleStr);


    // 页脚添加版权信息 '&nbsp;|&nbsp;Theme modified by <a href="https://moe.best" target="_blank">Jindai Kirin</a>&nbsp;|&nbsp;'
    var copyrightInfo = '&nbsp;|&nbsp; \n' +
        '<div class="github-badge">\n' +
        '    <a href="https://moe.best/" target="_blank" title="handsome主题由JindaiKirin魔改">\n' +
        '        <span class="badge-subject">Modified</span><span class="badge-value bg-red">JindaiKirin</span>\n' +
        '    </a>\n' +
        '</div>' +
        '&nbsp;|&nbsp; \n'
    $('#footer span.pull-right').append(copyrightInfo);
})

// pjax加载完成之后调用此函数
function needpjax() {
    $(document).ready(function () {
        colorfulTags(); //彩色标签云
        // 优化主页面无法点击图片进入文章
        setHref(getHref());
    })
}

$(window).load(function () {
    1 < location.hash.length && $('.tocify-item[data-unique="' + decodeURI(location.hash.substr(1)) + '"]').click()
});

function updateLiveStatus(b) {
    1 == b.data.liveStatus && $("#bilibili-live").removeClass("hide")
};

function getHref() {
    var hrefArr = [];
    $('.post-meta .index-post-title>a').each(function () {
        hrefArr.push($(this).attr('href'));
    });
    // console.log(hrefArr);
    return hrefArr;
}

function setHref(arr) {
    $('.post-meta').each(function (index) {
        $(this).append('<a href="' + arr[index] + '" class="ahover"></a>')
    });
}

// 彩色标签云
function colorfulTags() {
    var tags = document.querySelectorAll("#tag_cloud-2 a");
    var colorArr = ["#428BCA", "#AEDCAE", "#ECA9A7", "#DA99FF", "#FFB380", "#D9B999","#3bca6e","#f23232","#834e75","#23b7e5","#f60"];
    tags.forEach(tag => {
        tagsColor = colorArr[Math.floor(Math.random() * colorArr.length)];
        tag.style.backgroundColor = tagsColor;
    });
}

// 文本框打字机特效
(function webpackUniversalModuleDefinition(a,b){if(typeof exports==="object"&&typeof module==="object"){module.exports=b()}else{if(typeof define==="function"&&define.amd){define([],b)}else{if(typeof exports==="object"){exports["POWERMODE"]=b()}else{a["POWERMODE"]=b()}}}})(this,function(){return(function(a){var b={};function c(e){if(b[e]){return b[e].exports}var d=b[e]={exports:{},id:e,loaded:false};a[e].call(d.exports,d,d.exports,c);d.loaded=true;return d.exports}c.m=a;c.c=b;c.p="";return c(0)})([function(c,g,b){var d=document.createElement("canvas");d.width=window.innerWidth;d.height=window.innerHeight;d.style.cssText="position:fixed;top:0;left:0;pointer-events:none;z-index:999999";window.addEventListener("resize",function(){d.width=window.innerWidth;d.height=window.innerHeight});document.body.appendChild(d);var a=d.getContext("2d");var n=[];var j=0;var k=120;var f=k;var p=false;o.shake=true;function l(r,q){return Math.random()*(q-r)+r}function m(r){if(o.colorful){var q=l(0,360);return"hsla("+l(q-10,q+10)+", 100%, "+l(50,80)+"%, "+1+")"}else{return window.getComputedStyle(r).color}}function e(){var t=document.activeElement;var v;if(t.tagName==="TEXTAREA"||(t.tagName==="INPUT"&&t.getAttribute("type")==="text")){var u=b(1)(t,t.selectionStart);v=t.getBoundingClientRect();return{x:u.left+v.left,y:u.top+v.top,color:m(t)}}var s=window.getSelection();if(s.rangeCount){var q=s.getRangeAt(0);var r=q.startContainer;if(r.nodeType===document.TEXT_NODE){r=r.parentNode}v=q.getBoundingClientRect();return{x:v.left,y:v.top,color:m(r)}}return{x:0,y:0,color:"transparent"}}function h(q,s,r){return{x:q,y:s,alpha:1,color:r,velocity:{x:-1+Math.random()*2,y:-3.5+Math.random()*2}}}function o(){var t=e();var s=5+Math.round(Math.random()*10);while(s--){n[j]=h(t.x,t.y,t.color);j=(j+1)%500}f=k;if(!p){requestAnimationFrame(i)}if(o.shake){var r=1+2*Math.random();var q=r*(Math.random()>0.5?-1:1);var u=r*(Math.random()>0.5?-1:1);document.body.style.marginLeft=q+"px";document.body.style.marginTop=u+"px";setTimeout(function(){document.body.style.marginLeft="";document.body.style.marginTop=""},75)}}o.colorful=false;function i(){if(f>0){requestAnimationFrame(i);f--;p=true}else{p=false}a.clearRect(0,0,d.width,d.height);for(var q=0;q<n.length;++q){var r=n[q];if(r.alpha<=0.1){continue}r.velocity.y+=0.075;r.x+=r.velocity.x;r.y+=r.velocity.y;r.alpha*=0.96;a.globalAlpha=r.alpha;a.fillStyle=r.color;a.fillRect(Math.round(r.x-1.5),Math.round(r.y-1.5),3,3)}}requestAnimationFrame(i);c.exports=o},function(b,a){(function(){var d=["direction","boxSizing","width","height","overflowX","overflowY","borderTopWidth","borderRightWidth","borderBottomWidth","borderLeftWidth","borderStyle","paddingTop","paddingRight","paddingBottom","paddingLeft","fontStyle","fontVariant","fontWeight","fontStretch","fontSize","fontSizeAdjust","lineHeight","fontFamily","textAlign","textTransform","textIndent","textDecoration","letterSpacing","wordSpacing","tabSize","MozTabSize"];var e=window.mozInnerScreenX!=null;function c(k,l,o){var h=o&&o.debug||false;if(h){var i=document.querySelector("#input-textarea-caret-position-mirror-div");if(i){i.parentNode.removeChild(i)}}var f=document.createElement("div");f.id="input-textarea-caret-position-mirror-div";document.body.appendChild(f);var g=f.style;var j=window.getComputedStyle?getComputedStyle(k):k.currentStyle;g.whiteSpace="pre-wrap";if(k.nodeName!=="INPUT"){g.wordWrap="break-word"}g.position="absolute";if(!h){g.visibility="hidden"}d.forEach(function(p){g[p]=j[p]});if(e){if(k.scrollHeight>parseInt(j.height)){g.overflowY="scroll"}}else{g.overflow="hidden"}f.textContent=k.value.substring(0,l);if(k.nodeName==="INPUT"){f.textContent=f.textContent.replace(/\s/g,"\u00a0")}var n=document.createElement("span");n.textContent=k.value.substring(l)||".";f.appendChild(n);var m={top:n.offsetTop+parseInt(j["borderTopWidth"]),left:n.offsetLeft+parseInt(j["borderLeftWidth"])};if(h){n.style.backgroundColor="#aaa"}else{document.body.removeChild(f)}return m}if(typeof b!="undefined"&&typeof b.exports!="undefined"){b.exports=c}else{window.getCaretCoordinates=c}}())}])});
POWERMODE.colorful=true;POWERMODE.shake=false;document.body.addEventListener("input",POWERMODE);
