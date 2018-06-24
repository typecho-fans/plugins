'use strict';
P({
	id: 'editor.js',
	key: 'MyPlayer',
	Init: function(me) {
        me.Load('api.js', function(){
        	var f = me.fn,
            	o = me.option,
            	api = me.GetPlugin('api.js'),
            	apis = api.option.apis
            $(function() {
            	var $wbr,
                	textarea = f.id('text'),
                    $textarea = $(textarea),
                	md = $('input[name=markdown]').size() == 1 ,
                    $button = $('<li class="wmd-button" id="wmd-myplayer-button" title="MyPlayer" ><span style="background: none; line-height: 20px;">MP</span></li>').on('click', function(event) {
                    	show_dialog( )
                    }),
                    $title = $('<input type="text" placeholder="标题" />'),
                    $url = $('<input type="text" placeholder="网址" />'),
                    $optional = {
                    	type:$('<input type="text"  placeholder="格式 | 强制指定格式" />'),
                    	lyrics:$('<input type="text"  placeholder="歌词 | 仅对音乐文件有效" />'),
                    	width:$('<input type="text" style="width:50%" placeholder="宽度 | 指定影像宽度" />'),
                    	height:$('<input type="text" style="width:50%"  placeholder="高度 | 指定影像高度" />')
                    },
                    $ok = $('<button type="button" class="btn btn-s primary">确定</button>'),
                    $cancel = $('<button type="button" class="btn btn-s">取消</button>'),
                    $tip = $('<span></span>'),
                    $form = $('<form>').append($title, $url, $optional.type, $optional.lyrics, $optional.width, $optional.height, $ok, $cancel, $tip),
                    $dialog = $('<div class="wmd-prompt-dialog" rol="dialog" style="margin-top: -200px;">').append('<div><p><b>插入链接</b></p><p>请在下方的输入框内输入要插入的媒体页面链接。</p></div>').append($form),
                    $pop = $('<div class="wmd-prompt-background" style="position: absolute; top: 0px; z-index: 1000; opacity: 0.5; height: 905px; left: 0px; width: 100%;">').add($dialog)
            	var text = []
                $.each(apis,function(index){
                	text.push(index)
                })
                $('div',$dialog).append($('<p>').text('目前支持的有' + text.join('、') + '等。'))
            	if (md){
                	insert_button()
                }else{
                    $('.notice button.yes').click(function(){
                    	md = true
                    	insert_button()
                    })
                    $('.notice button.no').click(function(){
                    	md = false
                    	insert_button()
                    })
                }
            	function insert_button(){
                	if (md) {
                        $wbr = $('#wmd-button-row').append($('<li class="wmd-spacer wmd-spacer1" id="wmd-spacer1"></li>'))
                    } else {
                        $wbr = $('<ul class="wmd-button-row">').insertAfter('.url-slug')
                    }
                    $wbr.append($('<li class="wmd-button" id="wmd-myplayer-button" title="MyPlayer" ></li>').append($button))
                }
                
            	function show_dialog() {
                    $('input', $pop).val('').attr('readonly',false).show()
                    $tip.text('请输入有效的链接！')
                	check($url.val())
                    $url.val('http://')
                    $cancel.click(function(){
                        $pop.remove()
                    }),
                    $ok.click(function(){
                    	write()
                        $cancel.click()
                    })
                    $url.keyup(onkeyup)
                    $optional.type.keyup(onkeyup)
                    $title.val($textarea.getSelection().text)
                    $pop.appendTo(document.body)
                }
            	var mode = {}, time
            	function onkeyup(){
                	clearTimeout(time)
                	time = setTimeout(function(){
                    	mode.key = check($url.val(), $optional.type.val())
                        $tip.text(mode.key ? '将启用 ' + mode.key + ' 播放器！' : '这是不认识的链接！')
                    	f.each($optional, function(key, $input){
                        	if( !mode.key || (apis[mode.key].optional && apis[mode.key].optional.indexOf(key)>-1) ) {
                                $input.attr('readonly',false).show()
                                mode[key] = true
                            } else{
                                $input.attr('readonly',true).hide()
                                mode[key] = false
                            }
                        })
                    }, 100);
                }
            	function check(href, type) {
                	var result = false
                	f.each(apis, function(key, api) {
                    	if (api.check.call(null, href, type)) {
                        	result = key
                        	return false
                        }
                    })
                	return result
                }
            	function write() {
                	var sel = $textarea.getSelection(), end,
                	html = '<a href="' + $url.val() + '"'
                	if(mode && mode['type'] && $optional.type.val()) html += ' data-type="' + $optional.type.val() + '"'
                	if(mode && mode['lyrics'] && $optional.lyrics.val()) html += ' data-lyrics="' + $optional.lyrics.val() + '"'
                	if(mode && mode['width'] && $optional.width.val()) html += ' data-width="' + $optional.width.val() + '"'
                	if(mode && mode['height'] && $optional.height.val()) html += ' data-height="' + $optional.height.val() + '"'
                	html += '>'  + $title.val() + '</a>'
                	end = (sel ? sel.start : 0) +  html.length
                    $textarea.replaceSelection(html)
                    $textarea.setSelection(end, end)
                }
            })
        })
    }
})

