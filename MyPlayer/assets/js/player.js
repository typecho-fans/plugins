'use strict';
//MyPlayer 播放页面
(function(root, doc, $, undefined){
	var defaultOptions = {media:[],front:'white',background:'orange',actived:'red'},
        $jplayer, $player, $list, $state, $title, $progress, $lyric, options = {}, current = -1, length, first = true
    $(function(){
    	getOptions()
    	length = options.media.length
    	init()
    	setTheme(options)
    	if(options.media && length > 0 ){
        	if(length>1){
                $('body').append($list)
            }
        	next()
        }
    })
	function next(){
    	if(current == length -1 ){
        	current = -1
        }
    	load(options.media[++current])
    }
	function setTheme(){
    	if(options.front){
            $player.css('color',options.front)
            $state.css({
                'border-left-color':options.front,
                'border-right-color':options.front
            })
        }
    	if(options.background){
            $player.css('background-color',options.background)
        }
    	if(options.actived){
            $progress.css('background-color',options.actived)
        }
    }
	function loading(text){
        $title.text(text)
        $player.attr('title',text)
    	doc.title = text
    }
	function load(media){
    	if($jplayer){
            $jplayer.jPlayer('destroy')
            $jplayer.unbind().empty().remove()
            $jplayer = null
        }
    	if(media.url){
        	setNewMedia(media)
        }else if(media.xiami){
        	loading('正在尝试载入虾米音乐……')
            $.ajax({
            	url: '../api.php',
            	data:{service: 'xiami',id: media.xiami},
            	type: 'GET',
            	dataType: 'jsonp',
            	async: false,
            	success: function(data) {
                	if(data[0]){
                    	data = data[0]
                    	media.url = data.url
                    	media.title = data.title
                    	media.artist = data.artist
                    	media.lyric = data.lyric_url
                    	setNewMedia(media)
                    }
                },
            	error:function(){
                	options.media.splice(current,1)
                	length--
                	current--
                	next()
                }
            })
        }
    }
	function getType(url){
    	var index = url.indexOf('?')
    	if(index>0){
        	url = url.substring(0,index)
        }
    	index = url.lastIndexOf('.')
    	return url.substring(index+1)
    }
	function parseLyric(text){
    	text = text.split('\r\n')
    	var lyric = []
        $.each(text, function(index, value){
        	value = $.trim(value)
        	var d = value.match(/^\[\d{2}:\d{2}((\.|\:)\d{2})\]/g)
        	if(!d) return
        	var dt = String(d).split(':')
        	var t = value.split(d)
        	var _t = Math.round(parseInt(dt[0].split('[')[1])*60+parseFloat(dt[1].split(']')[0])*100)/100
          	lyric.push([_t, t[1]])
        })
    	return lyric
    }
	function setNewMedia(media){
    	media.type = media.type || getType( media.url ) || 'mp3'
        $jplayer = $('<div>').appendTo($player).attr('id','jplayer')
        .bind($.jPlayer.event.timeupdate, function(e) {
            $progress.width(Math.round(e.jPlayer.status.currentPercentAbsolute / 100 * $player.width()))
        })
        .jPlayer({
        	ready:function(){
                $title.text(media.title + (media.artist? ' by ' + media.artist : ''))
                $player.attr('title',media.title)
            	doc.title = media.title
            	var obj ={}
            	obj[media.type] = media.url
                $jplayer.jPlayer('setMedia', obj)
            	if(!first || options.autoplay){
                	play()
                	first = false
                }
            },
        	ended:function(){
                $progress.width('0')
            	if(length == 1){
                	play()
                }else{
                	next()
                }                
            },
        	consoleAlerts:true,
        	preload: 'none',
        	swfPath: '../assets/swf/'
            ,supplied: media.type
        })
    	if(media.lyric){
            $.ajax({
            	url: '../api.php',
            	data:{service: 'lyric', url: media.lyric},
            	type: 'GET',
            	async: false,
            	success: function(data) {
                	if(data && data.source){
                    	data = parseLyric(data.source)
                        $jplayer.jPlayer.hasLyric = true
                    	var m = 0
                        $jplayer.bind($.jPlayer.event.timeupdate, function(e) {
                        	if(e.jPlayer.status.currentTime < 0.5){
                            	m = 0
                            }
                        	if ( m < data.length && e.jPlayer.status.currentTime > data[m][0]){
                                $lyric.text(data[m][1])
                                $player.attr('title',$lyric.text())
                            	m++
                            }
                        })
                    }
                }
            })
        }
    }
	function getOptions(){
    	options = $.extend(options, defaultOptions)
    	try{
            $.extend(options, JSON.parse(document.location.hash.replace(/^#/,'')))
        }catch(e){
        }
    	options.theme=options.theme.split('|')
    	if(options.theme[0]){
        	options.front = options.theme[0]
        }
    	if(options.theme[1]){
        	options.background = options.theme[1]
        }
    	if(options.theme[2]){
        	options.actived = options.theme[2]
        }
    }
	function init(){
        $player = $('<div>').addClass('player')
        $progress = $('<span>').addClass('progress')
        $state = $('<span>').addClass('state').on('click',toggle)
        $title = $('<span>').addClass('title')
        $lyric = $('<span>').addClass('lyric').hide()
        $list = $('<ol>').addClass('list')
        $player.append($progress, $state, $title, $lyric)
        $('body').append($player)
    }
	function play(){
    	if($jplayer){
            $jplayer.jPlayer('play')
            $state.addClass('playing')
        	if($jplayer.jPlayer.hasLyric){
                $title.hide()
                $lyric.show()
            }
        }
    }
	function pause(){
    	if($jplayer){
            $jplayer.jPlayer('pause')
            $state.removeClass('playing')
        	if($jplayer.jPlayer.hasLyric){
                $title.show()
                $lyric.hide()
                $player.attr('title',$title.text())
            }
        }
    }
	function toggle(){
        $state.hasClass('playing') ? pause() : play()
    }
})(window, document, jQuery)

