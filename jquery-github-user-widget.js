/**
 * @author bh-lay
 * 
 * @github https://github.com/bh-lay/github-widget-user
 * @modified 2014-11-5 16:0
 */
(function($){
	//图片
	var imgbase64 = 'data:image/png;base64,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';
	
	//css
	var css_tpl = '<style type="text/css">.github-widget-loading{line-height:100px;text-align:center;font-size:24px;color:#aaa}.github-user-widget_body{max-width:300px;margin:auto;overflow:hidden;background:#fff;box-shadow:1px 2px 16px rgba(0,0,0,.2)}.gitUW_avatar{display:block;overflow:hidden}.gitUW_avatar img{display:block;width:100%;min-height:100px;background:#333;-webkit-transition:0.3s;-moz-transition:0.3s;transition:0.3s}.gitUW_avatar:hover img{-webkit-transform:scale(1.3);-moz-transform:scale(1.3);transform:scale(1.3)}.gitUW_name{display:block;padding:10px 10px 20px;border-bottom:1px solid #eee;-webkit-transition:0.4s;-moz-transition:0.4s;transition:0.4s}.gitUW_name strong{display:block;width:100%;overflow:hidden;font-size:26px;color:#333;line-height:30px;text-overflow:ellipsis}.gitUW_name span{display:block;width:100%;font-size:20px;font-style:normal;font-weight:300;line-height:24px;color:#666;text-overflow:ellipsis;overflow:hidden}.gitUW_name:hover{background:#eee}.gitUW_info{padding:10px 0 20px}.gitUW_info p{height:25px;padding-left:10px;text-overflow:ellipsis;overflow:hidden}.gitUW_info p span,.gitUW_info p a{display:inline-block;vertical-align:text-top;height:25px;line-height:25px;font-size:14px}.gitUW_info p a{color:#4183c4;text-decoration:none}.gitUW_info p a:hover{text-decoration:underline}.gitUW_info p span{color:#333}.gitUW_count{height:75px;background:#333}.gitUW_count a{display:block;float:left;width:33.33%;padding:15px 0;text-align:center;-webkit-transition:0.4s;-moz-transition:0.4s;transition:0.4s}.gitUW_count a strong{display:block;line-height:30px;font-size:28px;font-weight:bold;color:#4183c4}.gitUW_count a span{display:block;line-height:15px;font-size:12px;color:#999}.gitUW_count a:hover{background:#444}.gitUW_ico_company,.gitUW_ico_location,.gitUW_ico_email,.gitUW_ico_blog,.gitUW_ico_created_at{display:inline-block;vertical-align:text-top;width:25px;height:25px;background-image:url(' + imgbase64 + ')}.gitUW_ico_company{background-position:0 0}.gitUW_ico_location{background-position:0 -25px}.gitUW_ico_email{background-position:-25px 0}.gitUW_ico_blog{background-position:-25px -25px}.gitUW_ico_created_at{background-position:-50px 0}</style>';
	//模版
	var user_tpl = '<div class="github-user-widget_body"><a class="gitUW_avatar" href="<%=user.html_url%>" title="<%=user.name%>的github" target="_blank"><img src="<%=user.avatar_url %>" alt="<%=user.name%>" /></a><a class="gitUW_name" href="<%=user.html_url%>" title="<%=user.name%>的github" target="_blank"><strong><%=user.name%></strong><span><%=user.login%></span></a><div class="gitUW_info"><% if(user.company && user.company.length){%><p><i class="gitUW_ico_company"></i><span><%=user.company%></span></p><%}%><% if(user.location && user.location.length){%><p><i class="gitUW_ico_location"></i><span><%=user.location%></span></p><%}%><% if(user.email && user.email.length){%><p><i class="gitUW_ico_email"></i><a href="mailto:<%=user.email%>"><%=user.email%></a></p><%}%><% if(user.blog && user.blog.length){%><p><i class="gitUW_ico_blog"></i><a href="<%=user.blog%>" target="_blank"><%=user.blog%></a></p><%}%><% if(user.created_at.length){%><p><i class="gitUW_ico_created_at"></i><span><%=user.created_at%> 加入</span></p><%}%></div><div class="gitUW_count"><a href="<%=user.html_url%>/followers" target="_blank"><strong><%=user.followers%></strong><span>followers</span></a><a href="<%=user.html_url%>" target="_blank"><strong><%=user.public_repos%></strong><span>Repos</span></a><a href="<%=user.html_url%>/following" target="_blank"><strong><%=user.following%></strong><span>Following</span></a></div></div>';
	
	//格式化时间
	function parseDate(input){
		var date = new Date(input);
		return date.getFullYear() + ' - ' + (date.getMonth() + 1) + ' - ' + date.getDate();
	}
	//渲染部分
	function render(str, data){
		if(!str || !data){
			return '';
		}
		return (new Function("obj",
			"var p=[];" +
			"with(obj){p.push('" +
			str.replace(/[\r\t\n]/g, " ")
			   .split("<%").join("\t")
			   .replace(/((^|%>)[^\t]*)'/g, "$1\r")
			   .replace(/\t=(.*?)%>/g, "',$1,'")
			   .split("\t").join("');")
			   .split("%>").join("p.push('")
			   .split("\r").join("\\'")
		+ "');}return p.join('');"))(data);
	}

	//获取用户信息
	function getUserInfo(user_name,callback){
		$.ajax({
			url: 'https://api.github.com/users/' + user_name,
			async: false,
			dataType: 'jsonp',
			success: function(results){
				if(results && results.meta && results.meta.status == 200){
					var user = results.data;
					user.created_at = parseDate(user.created_at);
					callback && callback(null,user);
				}else{
					callback && callback(404);
				}
			}
		});
	}
	
	//创建widget
	function createWidget($dom,user_name){
		if(!user_name || user_name.length< 1){
			return
		}
		$dom.html('<div class="github-user-widget_body github-widget-loading">正在加载</div>');
		getUserInfo(user_name,function(err,user){
			var html;
			if(err){
				html = '<div class="github-user-widget_body github-widget-loading">加载失败</div>';
			}else{
				html = render(user_tpl,{
					'user' : user
				});
			}
			$dom.html(html);
		});
	}
	$.fn.github_user_widget = function(userName){
		$(this).each(function(){
			var $container = $(this);
			var user_name = userName || $container.data('user');
			createWidget($container,user_name);
		});
	};
	$(function(){
		//页面加入css
		$('head').append(css_tpl);
		//置空无用变量
		css_tpl = imgbase64 = null;
		//查找并生成默认的widget
		setTimeout(function(){
			$('.github-widget-user').github_user_widget();
		});
	});
})(jQuery);