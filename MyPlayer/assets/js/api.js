'use strict';
P({
    id: 'api.js',
    key: 'MyPlayer',
    Init: function(me){
        var o = me.option, f = me.fn, Add = function(key, item) {
            o.apis[key] = item
        }, AddRange = function(list) {
            me.fn.each(list, Add)
        }
        o('apis', {})
        o('auto_sign', 'data-auto-play')
        AddRange({
            '虾米音乐': {
                'check': function(href) {
                    return href && 0 == href.indexOf('http://www.xiami.com/song/')
                },
                'create': function(href) {
                    this.base = 'flash'
                    this.attributes.width = 257
                    this.attributes.height = 33
                    this.attributes.src = href.replace(/.*\/(\d+)\/?/, 'http://www.xiami.com/widget/1426712_$1/singlePlayer.swf')
                    this.callback()
                }
            },
            '优酷视频': {
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
            '土豆视频': {
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
            '爱奇艺视频': {
                'check': function(href) {
                    return href && 0 == href.indexOf('http://www.iqiyi.com/v_')
                },
                'create': function(href) {
                    this.base = 'flash'
                    this.attributes.width = 480
                    this.attributes.height = 400
                    var id = href.replace(/^.*v_(.*)\.html$/g, '$1'),bind = this
                    f.jsonp({
                        url: me.GetPath('../../api.php'),
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
            '音悦台MV': {
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
            '乐视TV': {
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
            '56视频': {
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
            '哔哩哔哩': {
                'check': function(href) {
                    return /http:\/\/(www\.bilibili\.com|www\.bilibili\.tv|bilibili\.kankanews\.com)?\/video\/av([0-9]+)\/(?:index_([0-9]+)\.html)?/.test(href)
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
            '新浪视频': {
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
                        url: me.GetPath('../../api.php'),
                        data: {service: 'sina', id: encodeURIComeonent(href)},
                        success: function(data) {
                            if (data.url) {
                                bindWithVid( data.url )
                            }
                        }
                    })
                }
            },
            'mp3': {
                'optional': 'type, lyrics',
                'check': function(href, type) {
                    return type ? /^mp3$/.test(type) : (href && /\.(ogg|mp3)$/.test(href))
                },
                'create': function(href) {
                    this.base = 'iframe'
                    var op = {media: [], theme: f.trim(o('theme'))}
                    op.media.push({
                        url: href,
                        title: this.element.innerHTML,
                        lyrics: this.lyrics
                    })
                    if(this.element.getAttribute(o.auto_sign)){
                        op.autoplay = true
                    }
                    this.attributes.src = me.GetPath('../../player/#') + JSON.stringify(op)
                    this.attributes.width = 223
                    this.attributes.height = 24
                    this.callback()
                }
            },
            'html5 audio': {
                'optional': 'type, lyrics',
                'check': function(href, type) {
                    return type ? /^ogg|mp3$/.test(type) : (href && /\.(ogg|mp3)$/.test(href))
                },
                'create': function(href) {
                    this.base = 'audio'
                    this.attributes.src = href
                    this.callback()
                }
            },
            'html5 video': {
                'optional': 'type, width, height',
                'check': function(href, type) {
                    return type ? /^ogg|mp4$/.test(type) : (href && /\.(ogv|mp4)$/.test(href))
                },
                'create': function(href) {
                    this.base = 'video'
                    this.attributs.width = this.width || 480
                    this.attributs.height = this.height || 400
                    this.attributes.src = href
                    this.callback()
                }
            },
            'flash': {
                'optional': 'type, width, height',
                'check': function(href, type) {
                    return type ? /^swf$/.test(type) : (href && /\.swf$/.test(href))
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
        
    }
})