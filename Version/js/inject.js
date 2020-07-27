$(function(){

	$('.version-plugin-btn-revert').click(function(e){
		var vid = $(this).parent().attr('version-id')
		var time = $(this).parent().attr('time')

		var message = "确定要回退到 "+time+" 的时候吗?"

		if(confirm(message)) {
			$.ajax({
				url: location.origin + "/version-plugin/revert",
				data: {vid: vid},
				cache: false,
				type: 'GET',
				success: function (data) {
					window.location.reload()
				},
				error: function (xhr, status, error) {
					alert("回退失败")
				}
			});
		}
	})

	$('.version-plugin-btn-delete').click(function(e){
		var vid = $(this).parent().attr('version-id')
		var _this = this

		var message = "确定要删除这个版本吗?"

		if(confirm(message)) {
			$.ajax({
				url: location.origin + "/version-plugin/delete",
				data: {vid: vid},
				cache: false,
				type: 'GET',
				success: function(data) {
					$(_this).parent().parent().parent().remove();
				},
				error: function(xhr, status, error) {
					alert("删除失败")
				}
			});
		}
	})

	$('.version-plugin-btn-preview').click(function(e){
		var vid = $(this).parent().attr('version-id')

		$('.version-plugin-view').removeClass('hidden')
		$('.version-plugin-text').text('内容正在加载...')

		$.ajax({
			url: location.origin + "/version-plugin/preview",
			data: {vid: vid},
			cache: false,
			type: 'GET',
			success: function(data) {
				$('.version-plugin-text').text(data)
			},
			error: function(xhr, status, error) {
				alert("内容加载失败")
			}
		});
	})

	// 点击屏幕四周可以关闭
	$('.version-plugin-view').click(function(e){
		$(this).toggleClass('hidden')
	})
	// 取消默认操作
	$('.version-plugin-view-container').click(function(e){
		e.stopPropagation()
	})

	// 保存版本的描述
	var saveDes = function(e, _this)
	{
		var vid = _this.parent().parent().parent().find('.version-plugin-actions').attr('version-id')
		var last = _this.attr('last')
		var des = _this.val()

		if(last!=des)
		{
			_this.attr('last', des)
			_this.val('正在设置版本描述..')

			$.ajax({
				url: location.origin + "/version-plugin/comment",
				data: {vid: vid, comment: des},
				cache: false,
				type: 'GET',
				success: function(data) {
					_this.val(des)
				},
				error: function(xhr, status, error) {
					_this.val('描述设置失败')
					alert('描述设置失败')
				}
			})
		}
	}

	// 失去焦点时保存
	$('.version-plugin-desc-textarea').bind('blur', function (e){
		saveDes(e, $(this))
	});
	// 回车时保存
	$('.version-plugin-desc-textarea').bind('keydown blur', function (e){
		var key = e.which;

		if (key == 13) {
			e.stopPropagation()
			saveDes(e, $(this))
			return false
		}
	});


})



function version_plugin_inj(content, vers)
{
	// 保证最后执行
	setTimeout(function(){
		var seul = $('#edit-secondary ul').eq(0)

		// 调整宽度
		seul.find('li').eq(0).removeClass("w-50")
		seul.find('li').eq(1).removeClass("w-50")
		seul.find('li').eq(0).addClass("w-30")
		seul.find('li').eq(1).addClass("w-30")
		
		seul.append('<li class="w-40"><a href="#tab-verions" id="tab-verions-btn">历史版本'+(vers>0?("("+vers+")"):"")+'</a></li>')

		version_plugin_overwrite() // 为了搞这个，我都要崩溃了
	}, 200)
	
	var se = $('#edit-secondary')
	se.append(content)
}
