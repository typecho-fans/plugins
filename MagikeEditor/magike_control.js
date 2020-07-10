/********************************************
	Magike Project
	copyright (c) Magike Group
	This software must be only used in Magike Systeam.
	http://www.magike.net
	powered by qining
********************************************/

function getScrollTop(){
	var yScrolltop;
	var xScrollleft;
	if (self.pageYOffset || self.pageXOffset) {
		yScrolltop = self.pageYOffset;
		xScrollleft = self.pageXOffset;
	} else if (typeof(document.documentElement) != "undefined" && typeof(document.documentElement.scrollTop) != "undefined" || typeof(document.documentElement.scrollLeft) != "undefined" ){	 // Explorer 6 Strict
		yScrolltop = document.documentElement.scrollTop;
		xScrollleft = document.documentElement.scrollLeft;
	} else if (typeof(document.body) != "undefined") {// all other Explorers
		yScrolltop = document.body.scrollTop;
		xScrollleft = document.body.scrollLeft;
	}

	arrayPageScroll = new Array(xScrollleft,yScrolltop);
	return arrayPageScroll;
}

function getPageSize(){
	var de = document.documentElement;
	var w = window.innerWidth || self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var h = window.innerHeight || self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	arrayPageSize = new Array(w,h);
	return arrayPageSize;
}

function findPosX(obj){var curleft = 0;if (obj && obj.offsetParent) {while (obj.offsetParent) {	curleft += obj.offsetLeft;obj = obj.offsetParent;}} else if (obj && obj.x) curleft += obj.x;return curleft;}
function findPosY(obj){var curtop = 0;if (obj && obj.offsetParent) {	while (obj.offsetParent) {	curtop += obj.offsetTop;obj = obj.offsetParent;}} else if (obj && obj.y) curtop += obj.y;return curtop;}

var ajaxFinish = true;
function ajaxLoadingStart()
{
	ajaxFinish = false;
	$("#ajax_loading").show();
}

function ajaxLoadingFinish()
{
	ajaxFinish = true;
	$("#ajax_loading").fadeOut("slow");
}

function registerTableCheckbox(table,className)
{
	$("."+className,$("#"+table)).click
	(
		function()
		{
			$(this.parentNode.parentNode).toggleClass("select");
		}
	);

	$("tr",$("#"+table)).mouseover
	(
		function()
		{
			if($("."+className,$(this)).attr("checked") != true && !$(this).hasClass("heading"))
			{
				$(this).addClass("hover");
			}
		}
	);
	
	$("tr",$("#"+table)).mouseout
	(
		function()
		{
			if($("."+className,$(this)).attr("checked") != true && !$(this).hasClass("heading"))
			{
				$(this).removeClass("hover");
			}
		}
	);
}

function selectTableAll(table,className)
{
	$("."+className,$("#"+table)).each(
		function()
		{
			$(this).attr("checked",true);
			$(this.parentNode.parentNode).addClass("select");
		}
	);
}

function selectTableNone(table,className)
{
	$("."+className,$("#"+table)).each(
		function()
		{
			$(this).attr("checked",false);
			$(this.parentNode.parentNode).removeClass("select");
		}
	);
}

function selectTableOther(table,className)
{
	$("."+className,$("#"+table)).each(
		function()
		{
			if($(this).attr("checked") == true)
			{
				$(this).attr("checked",false);
                $(this.parentNode.parentNode).removeClass("select");
                $(this.parentNode.parentNode).removeClass("hover");
			}
			else
			{
				$(this).attr("checked",true);
                $(this.parentNode.parentNode).addClass("select");
			}
		}
	);
}

function registerInputFocus(element)
{
	$("input",element).focus
	(
		function()
		{
			e = $(this);
			if(e.attr("type") == "text" || e.attr("type") == "password")
			{
				e.addClass("focus");
			}
		}
	);
	
	$("textarea.text",element).focus
	(
		function()
		{
			$(this).addClass("focus");
		}
	);
	
	$("input",element).blur
	(
		function()
		{
			e = $(this);
			if(e.attr("type") == "text" || e.attr("type") == "password")
			{
				e.removeClass("focus");
			}
		}
	);
	
	$("textarea",element).blur
	(
		function()
		{
			$(this).removeClass("focus");
		}
	);
	
	$("span.button",element).mouseover
	(
		function()
		{
			$(this).addClass("focus");
		}
	);
	
	$("span.button",element).mouseout
	(
		function()
		{
			$(this).removeClass("focus");
			$(this).removeClass("click");
		}
	);
	
	$("span.button",element).mousedown
	(
		function()
		{
			$(this).removeClass("click");
		}
	);
	
	$("span.button",element).mouseup
	(
		function()
		{
			$(this).removeClass("click");
		}
	);
}

var confirmElement;
function magikeConfirm(el)
{
	confirmElement = el;
	
	div = $(document.createElement("div"));
	p = $(document.createElement("p"));
	p.text($(el).attr("msg"));
	div.append(p);
	
	magikeUI.createPopup({title: mgTruncate($(el).attr("msg"),20,'...'),center: true,block:true,
	shadow:true,width: 300,height: 0,text:div,
	ok:'OK',cancel:'Cancel',handle:magikeConfirmHandle});
}

function magikeConfirmHandle()
{
	magikeLocation($(confirmElement).attr('rel'));
}

function mgTruncate(str,length,pre)
{
	return str.length > length ? str.substr(0,length) + pre : str;
}

var submitConfirmElement;
function magikeSubmitConfirm(msg,el,action)
{
	submitConfirmElement = el;
	
	div = $(document.createElement("div"));
	p = $(document.createElement("p"));
	p.text(msg);
	div.append(p);
	
	if(action)
	{
		$('input[@name=do]').val(action);
	}
	
	magikeUI.createPopup({title: mgTruncate(msg,16,'...'),center: true,block:true,
	shadow:true,width: 300,height: 0,text:div,
	ok:'OK',cancel:'Cancel',handle:magikeSubmitConfirmHandle});
}

function magikeSubmitConfirmHandle()
{
	if($('input:checked',$('#' + submitConfirmElement)).val())
	{
		document.getElementById(submitConfirmElement).submit();
	}
	else
	{
		$('.magikeShadow').remove();
		$('.magikePopup').remove();
	}
}

function magikeLocation(url)
{
	setTimeout("window.location.href = '" + url + "'; ",0);
}

function magikeCreateSelect(item)
{
	select = $(document.createElement("select"));
	for(var i in item)
	{
		option = $(document.createElement("option"));
		option.attr("value",item[i]);
		option.html(i);
		select.append(option);
	}
	
	return select;
}

MagikeUI = function()
{
	
}

MagikeUI.prototype = {
	//实例化一个窗口
	createPopup: function(args)
	{
		popupTitleText = args.title ? args.title : 'Magike Window';
		//判断浏览器
		var isIE = 0;
		
		//创建窗口外框
		var popupWindow = $(document.createElement("div"));
		popupWindow.addClass("magikePopup");
		popupWindow.released = true;
		popupWindow.x = false;
		popupWindow.y = false;
		
		//是否有遮罩层
		popupWindow.shadow = args.shadow ? args.shadow : false;
		
		if(popupWindow.shadow)
		{
			this.createShadow();
		}
		
		//创建窗口阴影
		var popupShadow = $(document.createElement("div"));
		popupShadow.addClass("magikePopupShadow");
		
		//创建窗口内部
		var popupContent = $(document.createElement("div"));
		popupContent.addClass("magikePopupContent");
		popupWindow.append(popupContent);
		popupWindow.append(popupShadow);
		
		//创建窗口文本部分
		var popupText = $(document.createElement("div"));
		popupText.addClass("magikePopupText");
		popupText.append(args.text ? args.text : null);
		
		//创建窗口按钮部分
		var popupButton = $(document.createElement("div"));
		
		var okButton = $(document.createElement("span"));
		okButton.addClass("button");
		okButton.css("margin-left","10px");
		okButton.text(args.ok ? args.ok : "OK");
		
		var cancelButton = $(document.createElement("span"));
		cancelButton.addClass("button");
		cancelButton.css("float","right");
		cancelButton.css("margin-right","10px");
		cancelButton.text(args.cancel ? args.cancel : "Cancel");
		
		popupButton.addClass("magikePopupButton");
		popupButton.append(okButton);
		popupButton.append(cancelButton);
		
		if(args.width)
		{
			popupWindow.width(args.width+2);
			popupShadow.width(args.width);
			popupContent.width(args.width);
		}
		if(args.height)
		{
			popupWindow.height(args.height+2);
			popupShadow.height(args.height);
			popupContent.height(args.height);
			popupText.height(args.height - 61);
			popupButton.height(36);
		}
		
		//创建窗口标题
		var popupTitle = $(document.createElement("div"));
		popupTitle.addClass("magikePopupTitle");
		popupTitle.html(popupTitleText);
		
		popupWindow.block = args.block ? args.block : false;
		if(!popupWindow.block)
		{
			popupTitle.css("cursor","move");
		}
		
		//创建窗口关闭按钮
		var closeBar = $(document.createElement("span"));
		closeBar.addClass("magikePopupClose");
		popupTitle.append(closeBar);
		popupContent.append(popupTitle);
		popupContent.append(popupText);
		popupContent.append(popupButton);
		
		$(document.body).append(popupWindow);
		
		if(!args.height)
		{
			resizeHeight = popupText.height() + 70;
			popupWindow.height(resizeHeight+2);
			popupShadow.height(resizeHeight);
			popupContent.height(resizeHeight);
			popupText.height(resizeHeight - 61);
			popupButton.height(36);
		}
		
		if(args.center)
		{
			size = getPageSize();
			pos = getScrollTop();
			
			vleft = parseInt((size[0] - popupWindow.width())/2 + pos[0]);
			vtop = parseInt((size[1] - popupWindow.height())/2 + pos[1]);
			popupWindow.css({left:vleft + 'px',top:vtop + 'px'});
		}
		
		//增加事件监听
		registerInputFocus(popupWindow);
		
		if(!popupWindow.block)
		{
		popupTitle.mousedown(
			function()
			{
				popupWindow.released = false;
				popupShadow.hide();
			}
		);
		
		popupWindow.mousedown(
			function()
			{
				$('.magikePopup').css('z-index',995);
				$('.magikePopupShadow').css('z-index',996);
				$('.magikePopupContent').css('z-index',997);
				popupWindow.css('z-index',998);
				popupShadow.css('z-index',999);
				popupContent.css('z-index',1000);
			}
		);
		
		$(document).mouseup(
			function()
			{
				popupWindow.released = true;
				popupWindow.x = false;
				popupWindow.y = false;
				popupShadow.show();
			}
		);
		
		popupTitle.mousemove(
			function(e)
			{
				if(!popupWindow.released)
				{
					if(isIE ? e.button : !e.button)
					{
						if(!popupWindow.x && !popupWindow.y)
						{
							popupWindow.x = e.clientX;
							popupWindow.y = e.clientY;
						}
						
						popupWindow.css('left',parseInt(popupWindow.css('left').replace('px',''))+(e.clientX - popupWindow.x)+'px');
						popupWindow.css('top',parseInt(popupWindow.css('top').replace('px',''))+(e.clientY - popupWindow.y)+'px');
						popupWindow.x = e.clientX;
						popupWindow.y = e.clientY;
					}
					else
					{
						popupWindow.released = true;
					}
				}
			}
		);
		
		$(document).mousemove(
			function(e)
			{
				
				if(!popupWindow.released)
				{
					if(isIE ? e.button : !e.button)
					{
						if(!popupWindow.x && !popupWindow.y)
						{
							popupWindow.x = e.clientX;
							popupWindow.y = e.clientY;
						}
						
						popupWindow.css('left',parseInt(popupWindow.css('left').replace('px',''))+(e.clientX - popupWindow.x)+'px');
						popupWindow.css('top',parseInt(popupWindow.css('top').replace('px',''))+(e.clientY - popupWindow.y)+'px');
						popupWindow.x = e.clientX;
						popupWindow.y = e.clientY;
					}
					else
					{
						popupWindow.released = true;
					}
				}
			}
		);
		}
		
		$('.magikePopupClose',popupWindow).click(
			function()
			{
				popupWindow.remove();
				if(popupWindow.shadow)
				{
					$('.magikeShadow').remove();
				}
			}
		);
		
		cancelButton.click(
			function()
			{
				popupWindow.remove();
				if(popupWindow.shadow)
				{
					$('.magikeShadow').remove();
				}
			}
		);
		
		okButton.click(args.handle ? args.handle : function()
		{
			popupWindow.remove();
			if(popupWindow.shadow)
			{
				$('.magikeShadow').remove();
			}
		});
	},
	
	//创建一个阴影
	createShadow: function()
	{
		var shadow = $(document.createElement("div"));
		shadow.addClass("magikeShadow");
		shadow.css('width',document.documentElement.scrollWidth > document.documentElement.clientWidth ? 
		document.documentElement.scrollWidth : document.documentElement.clientWidth);
		shadow.css('height',document.documentElement.scrollHeight > document.documentElement.clientHeight ? 
		document.documentElement.scrollHeight : document.documentElement.clientHeight);
		$(document.body).append(shadow);
		
		$(window).resize(
			function()
			{
				shadow.css('width',document.documentElement.scrollWidth > document.documentElement.clientWidth ? 
				document.documentElement.scrollWidth : document.documentElement.clientWidth);
				shadow.css('height',document.documentElement.scrollHeight > document.documentElement.clientHeight ? 
				document.documentElement.scrollHeight : document.documentElement.clientHeight);
			}
		);
	}
};

var MagikeUI = MagikeUI; 
var magikeUI = new MagikeUI();

function fixCssHack()
{
	$("td").each
	(
		function()
		{
			if($(this).html() == "")
			{
				$(this).html("&nbsp;");
			}
		}
	);
	
	$(".message").fadeIn(1000);
	$(".message").dblclick(function(){$(this).hide();});
	$(".proc").click(function(){$(this).hide();});
	
	$(window).unload(
		function()
		{
			$('span.button').unbind();
			$('textarea').unbind();
			$('input').unbind();
			$('tr').unbind();
		}
	);
}

var validateElements;
var showLoading;
function magikeValidator(url,mod)
{
	validateElements = null;
	$(".validate-word").html("");
	showLoading = true;
	
	if(typeof(tinyMCE) != "undefined")
	{
		tinyMCE.triggerSave();
	}
	
	s = $('.validate-me').serialize();
	$.ajax({
		type: 'POST',
		url: url + '?mod=' + mod,
		data: s,
		cache: false,
		dataType: "json",
		success: function(js){
			if(js != 0)
			{
				for(var i in js)
				{
					$("#" + i + "-word").html(js[i]);
				}
				$(".proc").fadeOut();
			}
			else
			{
				validateSuccess.apply(this);
			}
			showLoading = false;
		}
	});
}

$(document).ajaxStart(
	function()
	{
		$(".proc").hide();
		if(showLoading)
		{
			$(".proc").show();
		}
	}
);

/**输入框架自动完成beta
 * Author:张炼
 * Date:2007-8-17
 **/

//自动完成绑定类
function AutoCompleter(textbox, url , boxclass, selectclass, unselectclass, hoverclass){
    //得到输入框
	this.textbox = $(textbox);
	this.textbox.attr("autocomplete", "off");
	this.list = new Array();
	
	//样式的保存
	this.boxclass = boxclass;
	this.selectclass = selectclass;
	this.unselectclass = unselectclass;
	this.hoverclass = hoverclass;
	
	//关键词选取容器
	this.box = document.createElement("div");
	
	//初始化请求地址
	this.url = url;
	
	
	if(!boxclass) 
	this.box.style.cssText = AutoCompleter.defaultBoxStyle;
	else this.box.className = boxclass;
	
	this.box.style.position = "absolute";
	this.box.innerHTML = "Loading...";
	this.hide();
	this.textbox[0].parentNode.insertBefore(this.box, this.textbox[0]);
	
	
	var _completer = this;
	
	//对操作进行事件绑定
	//按键的时候的事件，主要进行
	this.box.onkeydown = function(e){
	    e = e ? e : event;
		return _completer.keydown(e);
	};
	this.textbox.bind("keydown", this.box.onkeydown);
	this.textbox.bind("keyup", function(e){
		_completer.start(e);
	});
	
	if($.browser.opera) {
		this.textbox.bind("keypress", function(e){return e.keyCode!=13 || this.visible == false;});
	}
    if (!this.textbox[0].setSelectionRange && this.textbox[0].createTextRange){  
	    function getcate(){
            this.focus();
            var txb = this;
            var s = txb.scrollTop;
            
            var r = document.selection.createRange();
            _completer.caterange = r.duplicate(); 
            
            var t = txb.createTextRange();
            t.collapse(true);
            t.select();
            
            var j = document.selection.createRange();
            r.setEndPoint("StartToStart",j);
            
            _completer.cateposition = r.text.replace(/\n/g, "").length;
            
            r.collapse(false);
            r.select();
            txb.scrollTop = s;
	    }
	    this.textbox[0].getcate = getcate;
	    //this.textbox.bind("blur", getcate);
    }
	$(document).bind("click", function(e){
        var tf = e.srcElement;
        if(!tf) tf = e.target;
        while(tf && tf != document.documentElement && tf != document.body){
            if(tf == _completer.box || tf == _completer.textbox[0]) return;
            tf = tf.parentNode;
        }
        _completer.hide();
	});
}
AutoCompleter.spliters = ",， 　";
//得到一个元素在页面上的绝对位置，p为想得到位置的元素，返回结果为Object{left:<int>, top:<int>}，r为是否只求到其定位元素
AutoCompleter.getPos = function(tag, r){
    var p = tag;
    var res = {left:p.offsetLeft, top:p.offsetTop};
    do{
        var s = p.currentStyle ? p.currentStyle : getComputedStyle(p, null);
        
        //如果只求到定位元素
        if(r){
            var position = s.position.toLowerCase();
            if(position == "absolute" || position == "relative") break;
        }
        if(p != tag){
            //加上相对位置
            res.left += p.offsetLeft;
            res.top += p.offsetTop;
            
            if(0){
                //加上边框宽度
                var border = parseInt(s.borderTopWidth);
                if(!isNaN(border)) res.top += border;
                border = parseInt(s.borderLeftWidth);
                if(!isNaN(border)) res.left += border;
            }
        }
        p = p.offsetParent;
    }while(p);
    return res;
};
AutoCompleter.defaultBoxStyle = "border:1px solid #369;background:#fff;color:#000;cursor:pointer";
AutoCompleter.defaultUnSelectStyle = "padding:2px 10px";
AutoCompleter.defaultSelectStyle = "padding:2px 10px;background:#B8D6D6;color:#fff";
AutoCompleter.defaultHoverStyle = "padding:2px 10px;background:#B8D6D6";
AutoCompleter.prototype = 
{
    //得到当前光标位置
    getindex: function(){
        if(this.textbox[0].getcate){
            this.textbox[0].getcate();
            return this.cateposition;
        }else{
            if(this.textbox[0].setSelectionRange) return this.textbox[0].selectionEnd;
            else return this.textbox.val().length; 
        }   
    },
    
    setindex: function(i){
        if(this.textbox[0].getcate){
            var r = this.textbox[0].createTextRange();
            r.collapse(true);
            r.moveStart('character', i);
            r.select();
        }
        else{
            this.textbox[0].selectionEnd = i;
            this.textbox[0].selectionStart = i;
        }   
    },
    
    //输入框键被按下之后发生
	keydown: function(e){
	    if(e.ctrlKey){
	        if(e.keyCode == 74){
	            e.keyCode = 0;
	            this.start(e);
	        }
	        else return;
	        return false;
	    }
	    if(!this.visible) return;
	    if(e.keyCode == 38) this.up(e);
	    else if(e.keyCode == 40) this.down(e);
	    else if(e.keyCode == 13) {
	        if(!this.word) return;
	        this.select();
	    }
	    else return;
	    return false;
	},
	
	//往上选词
	up: function(e){
	    var last = this.word ? this.list[this.word] : null;
	    if(!last || !last.previousSibling) this.focus(this.box.childNodes[this.box.childNodes.length - 1]); 
	    else this.focus(last.previousSibling);
	},
	
	focus: function(k){
	    var last = this.word ? this.list[this.word] : null;
	    if(last){
	        if(!this.unselectclass) last.style.cssText = AutoCompleter.defaultUnSelectStyle;
	        else last.className = this.unselectclass;
	    }
	    if(k){
	        if(!this.selectclass) k.style.cssText = AutoCompleter.defaultSelectStyle;
	        else k.className = this.selectclass;
	    }
	    this.word = k.innerHTML;
	},
	
	select: function(){
	    this.hide();
	    this.key = this.word;
	    this.lastkey = this.word;
	    
	    if(!this.word) return;
	    
	    var v = this.textbox.val();
	    
	    var r = this.getwordindex(v);
	    
	    var scroll = this.textbox[0].scrollTop;
	    
	    var bstr = v.substr(0, r.start) + this.word;
	    this.textbox.val(bstr + v.substr(r.end, v.length - r.end));
	    this.setindex(bstr.length);
	    
	    this.textbox[0].scrollTop = scroll;
	},
	
	//往下选词
	down: function(e){
	    var last = this.word ? this.list[this.word] : null;
	    if(!last || !last.nextSibling) this.focus(this.box.childNodes[0]); 
	    else this.focus(last.nextSibling);
	},
	
	controlkeys: [38, 40, 13],
	
	//在按键起来之后发生，开始自动完成
	start: function(e){
	    for(var i = 0; i < this.controlkeys.length; ++i){
	        if(e.keyCode == this.controlkeys[i]) return;
	    }
	    this.key = this.getword();
	    //if(this.lastkey == this.key) return;
	    if(this.key == "") this.hide(e);
	    else this.show(e);
	    this.lastkey = this.key;
	},
	
	getword: function(e){
	    var v = this.textbox.val();
	    if( v == "" ) return v;
	    var r = this.getwordindex(v);
	    if(r.end <= r.start) return "";
	    return v.substring(r.start, r.end);
	},
	
	getwordindex: function(v){
	    var ci = this.getindex();
	    var as = AutoCompleter.spliters;
	    var r = {start: -1, end: v.length};
	    for(var i = 0; i < as.length; ++i) r.start = Math.max(v.lastIndexOf(as.charAt(i), ci - 1), r.start);
	    ++r.start;
	    for(var i = 0; i < as.length; ++i){
	        var e = v.indexOf(as.charAt(i), ci);
	        if(e >= 0 && e < r.end) r.end = e;
	    }
	    return r;
	},
	
	//显示自动完成
	show: function(e){
	    if(this.key != this.lastkey)
	        this.search(this.key, e);
	    else this.justshow();
	},
	
	justshow: function(){
		if(!this.length) return;
	    this.visible = true;
	    var p = AutoCompleter.getPos(this.textbox[0]);
	    this.box.style.top = (p.top + this.textbox[0].offsetHeight) + "px";
	    this.box.style.left = p.left + "px";
	    this.box.style.display = "block";
	},
	
	search: function(k, e){
		var _completer = this;
		cacheList = _completer.serachInCache(k);
		if(cacheList.length == 0)
		{
            $.ajax({
                type: 'GET',
                url: this.url,
                data: "keywords=" + k,
                cache: true,
                dataType: "json",
                success: function(json){
                    _completer.list = json;
                    _completer.reset(json);
                }
            });
		}
		else
		{
			_completer.reset(cacheList);
		}
	},
	
	serachInCache: function(k){
		result = new Array();
		
		for(var i in this.list)
		{
			if(i.toLowerCase().indexOf(k.toLowerCase()) == 0)
			{
				result.push(i);
			}
		}
		
		return result;
	},
	
	reset: function(arr){
		this.length = 0;
	    this.list = {};
	    for(var i = 0; i < arr.length; ++i){
			if(arr[i] == null || arr[i] == "") continue;
	        this.list[arr[i]] = arr[i];
			++ this.length;
	    }
	    this.refresh();
		this.justshow();
	},
	
	refresh: function(){
	    this.word = null;
		
		if(!this.length) this.hide();
	    
	    this.box.innerHTML = "";
	    
	    var _completer = this;
	    for(var k in this.list){
	        if(k == "") continue;
	        var d = document.createElement("div");
	        d.innerHTML = k;
	        this.list[k] = d;
	        this.box.appendChild(d);
	        d.onclick = function(e){
	            _completer.focus(this);
	            _completer.select();
	        };
	        d.onmouseover = function(e){
	            if(this.innerHTML == _completer.word) return;
	            if(_completer.hoverclass) this.className = _completer.hoverclass;
	            else this.style.cssText = AutoCompleter.defaultHoverStyle;
	        };
	        d.onmouseout = function(e){
	            if(this.innerHTML == _completer.word) return;
	            if(!_completer.unselectclass) this.style.cssText = AutoCompleter.defaultUnSelectStyle;
	            else this.className = _completer.unselectclass;
	        };
	        d.onmouseout();
	    }
	},
	
	list: {},
	
	hide: function(e){
	    this.visible = false;
	    this.box.style.display = "none";
	}
}
