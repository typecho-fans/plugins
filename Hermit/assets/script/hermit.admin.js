jQuery(document).ready(function(b) {
	var c = b(".body").first();
	b("#wmd-button-bar").before('<div id="gohermit-container"><button id="gohermit" title="\u6dfb\u52a0\u867e\u7c73\u97f3\u4e50"><img src="' + hermit_img_url + '" width="16" height="16" />\u6dfb\u52a0\u867e\u7c73\u97f3\u4e50</button></div>');
	var list = ["songlist", "album", "collect"],
		e = function(a) {
			b("#text").val(b("#text").val() + a)
		},
		d = function() {
			b("#gohermit").removeClass("selected");
			b("#hermit-box").remove()
		};
	c.on("click", "#gohermit", function(a) {
		a.preventDefault();
		0 < b("#hermit-box").length ? (b(this).removeClass("selected"), b("#hermit-box").remove()) : (b(this).addClass("selected"), b("#gohermit-container").after('<div id="hermit-box" class="postbox"><div id="hermit-content"><div id="hermit-tab"><ul id="hermit-tabul"><li class="hermit-tabli current">\u5355\u66f2</li><li class="hermit-tabli">\u4e13\u8f91</li><li class="hermit-tabli">精选集</li></ul></div><div id="hermit-body"><ul id="hermit-bodyul"><li class="hermit-bodyli current"><textarea id="hermit-song" placeholder="\u8f93\u5165\u867e\u7c73\u6b4c\u66f2\u5730\u5740\uff0c\u591a\u4e2a\u5730\u5740\u8bf7\u56de\u8f66\u6362\u884c"></textarea></li><li class="hermit-bodyli"><input type="text" id="hermit-album" placeholder="\u8f93\u5165\u867e\u7c73\u4e13\u8f91\u5730\u5740" /></li><li class="hermit-bodyli"><input type="text" id="hermit-collect" placeholder="输入虾米精选集地址" /></li></ul></div></div><div id="hermit-action" class="clear"><a id="hermit-delete" class="submitdelete deletion" href="javascript:;">\u53d6\u6d88</a><label for="hermit-auto"><input type="checkbox" id="hermit-auto">自动播放</label><label for="hermit-loop"><input type="checkbox" id="hermit-loop">循环播放</label><label for="hermit-unexpand"><input type="checkbox" id="hermit-unexpand">折叠播放列表</label><label for="hermit-fullheight"><input type="checkbox" id="hermit-fullheight">显示全部音乐</label><button id="hermit-publish" class="primary">\u6dfb\u52a0\u5230\u6587\u7ae0\u4e2d</button></div></div>'));
		return !1
	});
	c.on("click", "#hermit-delete", function(a) {
		a.preventDefault();
		d()
	});
	c.on("click", ".hermit-tabli", function(a) {
		a.preventDefault();
		b(this).hasClass("current") || (a = b(".hermit-tabli").index(b(this)), b(".hermit-tabli, .hermit-bodyli").removeClass("current"), b(".hermit-tabli:eq(" + a + "), .hermit-bodyli:eq(" + a + ")").addClass("current"));
		return !1
	});
	c.on("click", "#hermit-publish", function(a) {
		a.preventDefault();
		var index = b(".hermit-tabli").index(b(".hermit-tabli.current")),
			ue = Number( b('#hermit-unexpand').prop("checked")),
			fh = Number( b('#hermit-fullheight').prop("checked")),
			auto = Number( b('#hermit-auto').prop("checked")),
			loop = Number( b('#hermit-loop').prop("checked"));
		switch (list[index]) {
		case "songlist":
			var a = b("#hermit-song").val(),
				c = [],
				f = /http:\/\/www.xiami.com\/song\/(\d+).*?/,
				a = a.split(/\r?\n/g);

			a = b.grep(a, function(a) {
				return f.test(a) ? !0 : !1
			});
			0 < a.length ? (b.each(a, function(a, b) {
				c.push(b.match(f)[1])
			}), a = '[hermit auto='+auto+' loop='+loop+' unexpand='+ue+' fullheight='+fh+']songlist#:' + c.join(",") + '[/hermit]', e(a), d()) : alert("请输入正确的虾米歌曲地址");
			break;
		case "album":
			var a = b("#hermit-album").val(),
				g = /http:\/\/www.xiami.com\/album\/(\d+).*?/;				
			g.test(a) ? (a = a.match(g)[1], e('[hermit auto='+auto+' loop='+loop+' unexpand='+ue+' fullheight='+fh+']album#:' + a + '[/hermit]'), d()) : alert("请输入正确的虾米专辑地址")
			break;
		case "collect":
			var a = b("#hermit-collect").val(),
				g = /http:\/\/www.xiami.com\/collect\/(\d+).*?/;
			g.test(a) ? (a = a.match(g)[1], e('[hermit auto='+auto+' loop='+loop+' unexpand='+ue+' fullheight='+fh+']collect#:' + a + '[/hermit]'), d()) : alert("请输入正确的虾米精选集地址")
			break;		 	
		}
	})
});