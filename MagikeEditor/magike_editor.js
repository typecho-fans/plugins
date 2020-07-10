/********************************************
	Magike Project
	copyright (c) Magike Group
	This software must be only used in Magike Systeam.
	http://www.magike.net

	modify by Hanny: http://www.imhan.com
********************************************/   
var start = 0;
var end = 0;
var hasPrepare = false;
var magikeTextareaScrolltop = 0;
var range = null;
var magikeTextarea;
var editorDraftChange = false;

var magikeToolbar;
var magikeEditor;
var insertImageToEditor;
var insertLinkToEditor;

function addButtonCtx(button_ctx)
{
	$(button_ctx).appendTo(magikeToolbar);
}

function addButton(label ,left_tag, right_tag, accesskey)
{
	addButtonCtx("<button type=\"button\" accesskey=\"" + accesskey + "\" onclick=\"editorAdd('" + left_tag + "','" + right_tag + "');\">" + label + "</button>");
}

//创建工具栏区
function createToolbar()
{
	$("<p class=\"toolbarspace\"></p>").prependTo(magikeTextarea.parentNode);
	$("<div id=\"tttt\" class=\"toolbar\"></div>").prependTo(magikeTextarea.parentNode);
	magikeToolbar = magikeTextarea.parentNode.firstChild;
}

function createDefaultButtons()
{
	addButton('<strong>B</strong>', '<strong>', '</strong>', 'b');
	addButton('<em>I</em>', '<em>', '</em>', 'i');
	addButton('<del>del</del>', '<del>', '</del>', 'd');
	addButton('quote', '<blockquote>', '</blockquote>', 'q');
	addButton('code', '<code>', '</code>', 'c');
	addButtonCtx("<button type=\"button\" accesskey=\"a\" style=\"color:#0000EE;text-decoration: underline;\" onclick=\"editorInsertLink('插入一个链接','资源地址','标题','目标','插入','取消','请输入一个链接URL。');\">link</button>");
	addButtonCtx("<button type=\"button\" accesskey=\"t\" onclick=\"editorInsertImage('插入一张图片','图片地址','标题','对齐','插入','取消','请输入一个图片URL');\">img</button>");
	addButton('more', '<!--more-->', '', 'm');
}

/*
	insmode 说明
	BIT：
	0：是否安装附件管理器
		0: 未安装附件管理器
		1: 已安装附件管理器
	2~1：图片插入的方式
		0: 默认的图片插入方式
		1: 仅插入图片
		2: 插入图片+链接
		3: 保留（默认的插入方式）
*/

function initEditorInterface(elid, insmode)
{
	/*
		//已经安装附件管理器插件
		Typecho.insertFileToEditor = function (title, url, is_img) {
			//get_attach_id(url);
			var lst = $("#file-list").children("li");
			var index = 0;
			while (index < lst.length) {
				if (lst[index].dataset['url'] == url) {
					break;
				}
				index++;
			}
			if (index >= 8) {
				return;
			}

            //Typecho.insertFileToEditor(t.text(), p.data('url'), p.data('image'));
			var textarea = $('#text'), sel = textarea.getSelection(),
				html = '<attach>' + lst[index].dataset['cid'] + '</attach>';
				offset = (sel ? sel.start : 0) + html.length;
			textarea.replaceSelection(html);
			textarea.setSelection(offset, offset);

		};
		*/


	Typecho.insertFileToEditor = function (title, url, is_img) {
		var textarea = $('#text'), sel = textarea.getSelection();
		var html = '';
		if (is_img) {
			//插入图片
			picture_mode = (insmode >> 1) & 0x3;
			if (picture_mode == 1) {
				//仅插入图片
				html = '<img src="' + url + '" alt="' + title + '" />';
			} else if (picture_mode == 2) {
				//插入图片链接
				html = '<a href="' + url + '" title="点击查看原图" target="_blank"><img src="' + url + '" alt="' + title + '" /></a>';
			}
		} else {
			attach_mode = insmode & 0x1;
			if (attach_mode) {
				//已经安装附件管理器插件
				var lst = $("#file-list").children("li");
				var index = 0;
				while (index < lst.length) {
					if (lst[index].children[1].text == title) {
						break;
					}
					index++;
				}
				if (index >= 8) {
					return;
				}
				html = '<attach>' + lst[index].children[0].value + '</attach>';
			} else {
				html =  '<a href="' + url + '">' + title + '</a>';
			}
		}
		var offset = (sel ? sel.start : 0) + html.length;
		textarea.replaceSelection(html);
		textarea.setSelection(offset, offset);
	};

}

function initEditor(elid, insmode)
{
	magikeTextarea = document.getElementById(elid);
	initEditorInterface('#'+elid, insmode);
	createToolbar();
	createDefaultButtons();
}

function editPrepare()
{
	magikeTextareaScrolltop = magikeTextarea.scrollTop;
	if(typeof(magikeTextarea.selectionStart) == "number")
	{
		magikeTextarea.focus();
		start = magikeTextarea.selectionStart;
		end = magikeTextarea.selectionEnd;
	}
	
	else if(document.selection)
	{
		magikeTextarea.focus();
		range = document.selection.createRange();
	}
	
	hasPrepare = true;
}

function editorAdd(flg1,flg2)
{
	if(!hasPrepare)
	{
		editPrepare();
	}


	editorDraftChange = true;

	if(typeof(magikeTextarea.selectionStart) == "number")
	{
		pre = magikeTextarea.value.substr(0, start);
		post = magikeTextarea.value.substr(end);
		center = magikeTextarea.value.substr(start,end-start);
		magikeTextarea.value = pre + flg1 +center+ flg2+ post;
		
		magikeTextarea.setSelectionRange(start+flg1.length,start+flg1.length);
	}

	else if(document.selection)
	{
		if(range.text.length > 0)
		{
			range.text = flg1 + range.text + flg2;
		}
		else
		{
			range.text = flg1 + flg2;
		}
	}

	setTimeout('magikeTextarea.scrollTop = magikeTextareaScrolltop',0);
	magikeTextarea.focus();
	hasPrepare = false;
	return true;
}

var editorInsertLinkError;
function editorInsertLink(popupTitle,urlWord,titleWord,openType,okText,cancelText,errorWord)
{
	editPrepare();

	div = $(document.createElement("div"));
	editorInsertLinkError = errorWord;
	
	p = $(document.createElement("p"));
	span = $(document.createElement("span"));
	span.text(urlWord);
	input = $(document.createElement("input"));
	input.addClass("text");
	input.attr("type","text");
	input.attr("name","url");
	input.attr("value","http://");
	p.append(span);
	p.append(input);
	div.append(p);
	
	p = $(document.createElement("p"));
	span = $(document.createElement("span"));
	span.text(titleWord);
	input = $(document.createElement("input"));
	input.addClass("text");
	input.attr("type","text");
	input.attr("name","title");
	p.append(span);
	p.append(input);
	div.append(p);
	
	p = $(document.createElement("p"));
	span = $(document.createElement("span"));
	span.text(openType);
	select = magikeCreateSelect({none:"",_blank:"_blank"});
	select.attr("name","link");
	p.append(span);
	p.append(select);
	div.append(p);
	
	magikeUI.createPopup({title: popupTitle,center: true,width: 400,height: 175,text:div,ok:okText,cancel:cancelText,handle:editorInsertLinkHandle});
}

function editorInsertLinkHandle()
{
	var url = $("input[name=url]",$((this.parentNode).parentNode)).val();
	var ititle = $("input[name=title]",$((this.parentNode).parentNode)).val();
	var link = $("select[name=link]",$((this.parentNode).parentNode)).val();
	
	if(url && url != "http://")
	{
		editorAdd('<a href="' + url + '"' + (ititle ? ' title="' + ititle + '"' : '') + (link ? ' target="' + link + '"' : '') + '>','</a>');
		$(((this.parentNode).parentNode).parentNode).remove();
	}
	else
	{
		alert(editorInsertLinkError);
	}
}

var editorInsertImageError;
function editorInsertImage(popupTitle,urlWord,titleWord,alignType,okText,cancelText,errorWord)
{
	editPrepare();

	div = $(document.createElement("div"));
	editorInsertImageError = errorWord;
	
	p = $(document.createElement("p"));
	span = $(document.createElement("span"));
	span.text(urlWord);
	input = $(document.createElement("input"));
	input.addClass("text");
	input.attr("type","text");
	input.attr("name","img_url");
	input.attr("value","http://");
	p.append(span);
	p.append(input);
	div.append(p);
	
	p = $(document.createElement("p"));
	span = $(document.createElement("span"));
	span.text(titleWord);
	input = $(document.createElement("input"));
	input.addClass("text");
	input.attr("type","text");
	input.attr("name","img_title");
	p.append(span);
	p.append(input);
	div.append(p);
	
	p = $(document.createElement("p"));
	span = $(document.createElement("span"));
	span.text(alignType);
	select = magikeCreateSelect({无:"",左:"left",中:"center",右:"right"});
	select.attr("name","img_align");
	p.append(span);
	p.append(select);
	div.append(p);
	
	magikeUI.createPopup({title: popupTitle,center: true,width: 400,height: 175,text:div,ok:okText,cancel:cancelText,handle:editorInsertImageHandle});
}

var editorInsertImageIsImage = true;
function editorInsertImageHandle()
{
	var url = $("input[name=img_url]",$((this.parentNode).parentNode)).val();
	var ititle = $("input[name=img_title]",$((this.parentNode).parentNode)).val();
	var align = $("select[name=img_align]",$((this.parentNode).parentNode)).val();
	
	if(url && url != "http://")
	{
		if(editorInsertImageIsImage)
		{
			editorAdd('<img src="' + url + '"' + (ititle ? ' alt="' + ititle + '"' : '') + (align ? ' align="' + align + '"' : '') + '/>','');
		}
		else
		{
			editorAdd('<a href="' + url + '"' + (ititle ? ' title="' + ititle + '"' : '') + '/>','</a>');
		}
		$(((this.parentNode).parentNode).parentNode).remove();
	}
	else
	{
		alert(editorInsertImageError);
	}
}