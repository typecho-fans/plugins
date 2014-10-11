$(function(){
	var showAnn = $("#announcement_plug");
	var showCon = showAnn.attr("data-content");
	showCon = showCon.split("\n");
	var showConHtml = '';
	if(showCon.length>1){
		for(i=0; i<showCon.length; i++){
			showConHtml += '<li>'+(i+1)+'、'+showCon[i]+'</li>';
		}
	}else{
		showConHtml = '<li>'+showCon+'</li>';
	}
	if(showAnn.attr("data-type")==1){
		//显示弹出框
		function showPopBox(){
			if(getCookie("isShowAnn")!=0){
				$("body").append("<div id='div-mask'></div>");
				$("body").append("<div id='div-pop-box'></div>");
				$("#div-pop-box").css({"left":$(document).scrollLeft(), "top":$(document).scrollTop()});
				changeAnnPosi();
			}else{
				showAnnMin();
			}
			$("#div-pop-box").html('<h3><b>网站公告</b><a class="a-close" href="javascript:;">X</a></h3><div class="div-announcement-main"><ul>'+showConHtml+'</ul></div>');
			closeBtn();
		}
		//改变位置
		function changeAnnPosi(){
			$("#div-mask").css({"height":$(document).height(), "width":$(window).width()+$(document).scrollLeft()});
			var popBox = $("#div-pop-box");
			var leftVal = ($(window).width() - 300) / 2 + $(document).scrollLeft();
			var topVal = ($(window).height() - 160) / 2 + $(document).scrollTop();
			popBox.stop().animate({top: topVal, left: leftVal, width:300, height:160, opacity:1}, "slow", function(){
				var allHeight = $(".div-announcement-main").children("ul").height();
				if(allHeight>140){
					$(".div-announcement-main").css({"overflow-y":"scroll"});
				}
			});
			if (('undefined' == typeof(document.body.style.maxHeight)) && getCookie("isShowAnn")==0){
				$("#aAnnMin").css("top",$(document).scrollTop());
			}
		}
		//关闭按钮
		function closeBtn(){
			var popBox = $("#div-pop-box");
			popBox.find(".a-close").click(function(){
				popBox.delay(100).stop().animate({top: $(document).scrollTop(), left: $(document).scrollLeft(), width:0, height:0, opacity:0}, 300, function(){
					showAnnMin();
				});
			});
		}
		//显示最小化
		function showAnnMin(){
			$("#div-mask").remove();
			$("#div-pop-box").remove();
			$("#div-pop-box").html('');
			setCookie("isShowAnn", "0", 24);
			$("body").append("<a id='aAnnMin' href='javascript:;'></a>");
			var aAnnMin = $("#aAnnMin").animate({left:0, opacity:1}, 500);
			aAnnMin.click(function(){
				delCookie("isShowAnn");
				$(this).remove();
				showPopBox();
			});
		}
		showPopBox();
		// 根据窗口变动调整位置
		$(window).resize(function() {
			changeAnnPosi();
		}).scroll(function() {
			changeAnnPosi();
		});
	}else{
		//底部固定
		$("body").append("<div id='div-ann-bm'><div class='div-ann-main'><ul id='ulAnnBox'>"+showConHtml+"</ul><a class='a-close' href='javascript:;'>X</a></div></div>");
		var ul = $("#ulAnnBox");
		var timer = setInterval(function(){
			ul.animate({marginTop:"-25px"}, 600, function(){
				var liFirst = ul.children("li").eq(0);
				liFirst.remove();
				ul.append("<li>"+liFirst.html()+"</li>");
				ul.css("margin-top", "0");
			})
		}, 5000);
		var annBmBox = $("#div-ann-bm");
		annBmBox.find(".a-close").click(function(){
			clearInterval(timer);
			annBmBox.animate({height:0}, 500, function(){
				annBmBox.remove();
			});
		});
		
		if (($.browser.msie) && ($.browser.version == "6.0")){
			$(window).resize(function() {
				annBmBox.css("top",$(window).height()+$(document).scrollTop()-25);
			}).scroll(function() {
				annBmBox.css("top",$(window).height()+$(document).scrollTop()-25);
			});
		}
	}
});
//添加cookie
function setCookie(objName, objValue, objHours) {
	var str = objName + "=" + escape(objValue);
	if (objHours > 0) {// 为0时不设定过期时间，浏览器关闭时cookie自动消失
		var date = new Date();
		var ms = objHours * 3600 * 1000;
		date.setTime(date.getTime() + ms);
		str += "; path=/; expires=" + date.toGMTString();
	}
	document.cookie = str;
}
//获取指定名称的cookie的值
function getCookie(objName) {
	var arrStr = document.cookie.split("; ");
	for ( var i = 0; i < arrStr.length; i++) {
		var temp = arrStr[i].split("=");
		if (temp[0] == objName)
			return unescape(temp[1]);
	}
}
//删除cookie
function delCookie(name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval=getCookie(name);
    if(cval!=null) document.cookie= name + "="+cval+"; path=/; expires="+exp.toGMTString();
}