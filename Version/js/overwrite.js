// 覆盖掉原有的代码，只为控制自动保存机制，不得不说官方的代码一言难尽，
// 简直毫无规范性可言，不得不写出这么长的代码覆盖掉原代码
// 那么多代码都是被迫依赖的，核心目的只是控制自动保存时带个参数，让服务端那边能识别
// 出来是自动保存的而不是手动保存的
function version_plugin_overwrite()
{
	// 控制选项和附件的切换(Copy自write-js.php)
	// 官方没有封装成函数，无奈只好照着写了一遍
	// 为的是将新添加的按钮也注册一下
	var fileUploadInit = false;
	$('#edit-secondary .typecho-option-tabs li').unbind('click')
	$('#edit-secondary .typecho-option-tabs li').click(function() {
		$('#edit-secondary .typecho-option-tabs li').removeClass('active');
		$(this).addClass('active');
		$(this).parents('#edit-secondary').find('.tab-content').addClass('hidden');
		
		var selected_tab = $(this).find('a').attr('href'),
			selected_el = $(selected_tab).removeClass('hidden');

		if (!fileUploadInit) {
			selected_el.trigger('init');
			fileUploadInit = true;
		}

		return false;
	});

	//////////////////////////////////////////

	var submitted = false
	
	var form = $('form[name=write_post],form[name=write_page]')
	var formAction = form.attr('action')

	var idInput = $('input[name=cid]')
	var cid = idInput.val()
	var draft = $('input[name=draft]')
	var draftId = draft.length > 0 ? draft.val() : 0
	
	var locked = false
	var btnSave = $('#btn-save')//.removeAttr('name').removeAttr('value')
	var btnSubmit = $('#btn-submit')//.removeAttr('name').removeAttr('value')
	var btnPreview = $('#btn-preview')
	var doAction = $('input[name="do"]', form)
	var changed = false
	var autoSave = $('#auto-save-message')
	var lastSaveTime = null

	form.submit(function () {
        submitted = true;
	})
	

	$(':input', form).unbind('input change')

	$(':input', form).bind('input change', function (e) {
		var tagName = $(this).prop('tagName');

        if ((tagName.match(/(input|textarea)/i) && e.type == 'change') || $(this).hasClass('version-plugin-no-listening-input')) { // 过滤一些元素
			return;
		}

        changed = true;
    });


	form.unbind('field')

    form.bind('field', function () {
        changed = true;
	});

	////

	// 发送保存请求
	function saveData(cb, t) 
	{
		t = t || 'none'

		function callback(o) {
			lastSaveTime = o.time;
			cid = o.cid;
			draftId = o.draftId;
			idInput.val(cid);
			autoSave.text('已保存' + ' (' + o.time + ')').effect('highlight', 1000);
			locked = false;

			btnSave.removeAttr('disabled');
			btnPreview.removeAttr('disabled');

			if (!!cb) {
				cb(o)
			}
		}

		changed = false;
		btnSave.attr('disabled', 'disabled');
		btnPreview.attr('disabled', 'disabled');
		autoSave.text('正在保存');

		if (typeof FormData !== 'undefined') {
			var data = new FormData(form.get(0));
			data.append('do', 'save');

			$.ajax({
				url: formAction + "&t="+t,
				processData: false,
				contentType: false,
				type: 'POST',
				data: data,
				success: callback
			});
		} else {
			var data = form.serialize() + '&do=save';
			$.post(formAction + "&t="+t, data, callback, 'json');
		}
	}

	////

	var autoSaveOnce = !!cid;

	function autoSaveListener () {
        setInterval(function () {
            if (changed && !locked) {
                locked = true;
                saveData(null, 'auto');
            }
        }, 10000);
    }

    if (autoSaveOnce) {
        autoSaveListener();
	}
	
	$('#text').unbind('input propertychange')

	$('#text').bind('input propertychange', function () {
		if (!locked) {
			autoSave.text('尚未保存 ' + (lastSaveTime ? '(上次保存时间' + lastSaveTime + ')' : ''));
		}

		changed = true

		if (!autoSaveOnce) {
			autoSaveOnce = true;
			autoSaveListener();
		}
	});

	////

	// 自动检测离开页
	$(window).unbind('beforeunload')

    $(window).bind('beforeunload', function () {
        if (changed && !submitted) {
            return '内容已经改变尚未保存, 您确认要离开此页面吗?';
        }
	});

	////

	// 预览功能
    var isFullScreen = false;

    function previewData(cid) {
        isFullScreen = $(document.body).hasClass('fullscreen');
        $(document.body).addClass('fullscreen preview');

        var frame = $('<iframe frameborder="0" class="preview-frame preview-loading"></iframe>')
            .attr('src', './preview.php?cid=' + cid)
            .attr('sandbox', 'allow-scripts')
            .appendTo(document.body);

        frame.load(function () {
            frame.removeClass('preview-loading');
        });

        frame.height($(window).height() - 53);
    }

    function cancelPreview() {
        if (submitted) {
            return;
        }

        if (!isFullScreen) {
            $(document.body).removeClass('fullscreen');
        }

        $(document.body).removeClass('preview');
        $('.preview-frame').remove();
	};
	
	$('#btn-cancel-preview').unbind('click')

	$('#btn-cancel-preview').click(cancelPreview);
	

	$('#window').unbind('message')

    $(window).bind('message', function (e) {
        if (e.originalEvent.data == 'cancelPreview') {
            cancelPreview();
        }
    });


	btnPreview.unbind('click')

    btnPreview.click(function () {
        if (changed) {
            locked = true;

            if (confirm('修改后的内容需要保存后才能预览, 是否保存?')) {
                saveData(function (o) {
                    previewData(o.draftId);
                });
            } else {
                locked = false;
            }
        } else if (!!draftId) {
            previewData(draftId);
        } else if (!!cid) {
            previewData(cid);
        }
    });



	btnSave.unbind('click')

    btnSave.click(function () {
        doAction.attr('value', 'save');
    });


	btnSubmit.unbind('click')

    btnSubmit.click(function () {
        doAction.attr('value', 'publish');
	});

}