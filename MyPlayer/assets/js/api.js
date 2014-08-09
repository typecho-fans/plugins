'use strict';
/*
 * @plugin api 转换api
 * @author perichr
 * @version 1.0.0.2
 * @link http://perichr.org
 */

(function(root, doc, perichr, undefined) {

	var P = root[perichr],
		MP = {
			id: 'api.js',
			Init: function() {
				f = MP.fn
				o = MP.option
				o('apis', {})
			}
		},
		o, f, Add = function(key, item) {
			o.apis[key] = item
		},
		AddRange = function(list) {
			MP.fn.each(list, Add)
		}
	P.Load(MP)


	AddRange({
		'xiami': {
			'check': function(href) {
				return href && 0 == href.indexOf('http://www.xiami.com/song/')
			},
			'create': function(href) {
				//http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20json%20where%20url=%22__________%22&format=json&callback=cbfunc
				//http://www.xiami.com/app/iphone/song/id/1772360365
				this.base = 'flash'
				this.attributes.width = 257
				this.attributes.height = 33
				this.attributes.src = href.replace(/.*\/(\d+)\/?/, 'http://www.xiami.com/widget/1426712_$1/singlePlayer.swf')
				this.callback()
			}
		},
		'youku': {
			'check': function(href) {
				return href && 0 == href.indexOf('http://v.youku.com/v_show/id_')
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributes.width = 480
				this.attributes.height = 400
				this.attributes.src = href.replace(/^.*id_(.*)\.html$/g, 'http://player.youku.com/player.php/sid/$1/v.swf')
				this.callback()
			}
		},
		'tudou': {
			'check': function(href) {
				return href && 0 == href.indexOf('http://www.tudou.com/programs/view/')
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributes.width = 480
				this.attributes.height = 400
				this.attributes.src = href.replace('programs\/view', 'v') + 'v.swf'
				this.callback()
			}
		},
		'iqiyi': {
			'check': function(href) {
				return href && 0 == href.indexOf('http://www.iqiyi.com/v_')
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributes.width = 480
				this.attributes.height = 400
				var id = href.replace(/^.*v_(.*)\.html$/g, '$1'),bind = this
				f.jsonp({
					url: '../../api.php',
					data: {service: 'iqiyi', id: id},
					success: function(data) {
						if (data.url) {
							bind.attributes.src = data.url
							bind.callback()
						}
					}
				})
			}
		},
		'yinyuetai': {
			'check': function(href) {
				return href && 0 == href.indexOf('http://v.yinyuetai.com/video/')
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributes.width = 480
				this.attributes.height = 334
				this.attributes.src = href.replace(/^.*\/(\d*)$/g, 'http://player.yinyuetai.com/video/player/$1/v_0.swf')
				this.callback()
			}
		},
		'letv': {
			'check': function(href) {
				return href && 0 == href.indexOf('http://www.letv.com/ptv/vplay/')
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributes.width = 541
				this.attributes.height = 450
				this.attributes.src = href.replace(/^.*\/(\d*)\.html$/g, 'http://i7.imgs.letv.com/player/swfPlayer.swf?autoPlay=0&id=$1')
				this.callback()
			}
		},
		'56': {
			'check': function(href) {
				return href && /http:\/\/www.56.com\/[u\d]+\/v_/.test(href)
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributes.width = 480
				this.attributes.height = 408
				this.attributes.src = href.replace(/^.*\/v_(.*)\.html$/g, 'http://player.56.com/v_$id.swf')
				this.callback()
			}
		},
		'bilibili': {
			'check': function(href) {
				return /http:\/\/(www\.bilibili\.tv|bilibili\.kankanews\.com)?\/video\/av([0-9]+)\/(?:index_([0-9]+)\.html)?/.test(href)
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributes.width = 544
				this.attributes.height = 452
				if (href.indexOf('.html') == -1) href += "/index_1.html"
				var aid = href.replace(/^.*\/av(\d+)\/.*/g, '$1'),
					page = href.replace(/^.*\/index_(\d+)\.html$/g, '$1'),
					bind = this
				this.attributes.src = 'http://static.hdslb.com//miniloader.swf?aid=' + aid + '&page=' + page
				this.callback()
			}
		},
		'sina': {
			'check': function(href) {
				return href && (/#\d+$/.test(href) || /^http:\/\/video\.sina\.com\.cn\/.+\d+\.html$/.test(href))
			},
			'create': function(href) {
				var bind = this, bindWithVid = function( vid ){
					bind.base = 'flash'
					bind.attributes.width = 480
					bind.attributes.height = 370
					bind.attributes.src = 'http://you.video.sina.com.cn/api/sinawebApi/outplayrefer.php/vid=' + vid + '/s.swf'
					bind.callback()
				}
				if(/#\d+$/.test(href)){
					bindWithVid( href.replace(/.+#(\d+)$/g, '$1') )
					return
				}
				f.jsonp({
					url: '../../api.php',
					data: {service: 'sina', id: encodeURIComponent(href)},
					success: function(data) {
						if (data.url) {
							bindWithVid( data.url )
						}
					}
				})
			}
		},
		'audio': {
			'optional': 'type,lyrics',
			'check': function(href, type) {
				return (type && /^ogg|mp3$/.test(type)) || (href && /\.(ogg|mp3)$/.test(href))
			},
			'create': function(href) {
				this.base = 'audio'
				this.attributes.src = href
				this.callback()
			}
		},
		'video': {
			'optional': 'type',
			'check': function(href, type) {
				return (type && /^ogg|mp4$/.test(type)) || (href && /\.(ogv|mp4)$/.test(href))
			},
			'create': function(href) {
				this.base = 'video'
				this.attributes.src = href
				this.callback()
			}
		},
		'flash': {
			'optional': 'width,height',
			'check': function(href) {
				return href && /\.swf$/.test(href)
			},
			'create': function(href) {
				this.base = 'flash'
				this.attributs.width = this.width || 480
				this.attributs.height = this.height || 400
				this.attributs.src = href
				this.callback()
			}
		},
	})



})(window, document, '_perichr_')