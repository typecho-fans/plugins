/** @license
 * @author <a href="mailto:i@perichr.org">perichr</a>
 * @version 1.0.0.5
 * @link http://perichr.org
 */
(function(root, doc, perichr, undefined) {
	'use strict';
	if (root[perichr]) {
		root[perichr].Load()
		return
	}
	
	/* 初始化开始 */
	var
		P = root[perichr] = {}, //顶级公开对象"_perichr_"
		F = P.Functions = {}, //函数库（公开）
		U = P.Plugins = {}, //插件库（公开）
		_seed_ = (new Date()).getTime(), // 种子
		_script_cache_ = {}, //缓存的脚本名（私有）
		_plugin_cache_ = {}, //插件继承对象
		_fn_ = _plugin_cache_.FN = {}, //函数库
		_option_ = _plugin_cache_.OPTION = {}, //配置库
		
	/* 常用函数开始 */
		 
		/**
		 * @name _perichr_.Functions.ForEach
		 * @description 循环
		 * @param {Object} object 被循环的对象
		 * @param {Function} callback 回调函数
		 * @param {Boolean} force 强制为数组
		 */
		Each = _fn_.each = F.ForEach = function(object, callback, force) {
			var isArraylike = IsArraylike(object)
			if (force && !isArraylike) object = [object];
			(force || isArraylike) ? arr_each(object) : obj_each(object)

			function arr_each(array) {
				for (var index = 0, length = array.length; index < length; index++) {
					if (callback.call(array[index], index, array[index]) === false) break
				}
			}

			function obj_each(object) {
				for (var name in object) {
					if (callback.call(object[name], name, object[name]) === false) break
				}
			}
		},

		/**
		 * @name _perichr_.Functions.IndexOfArray
		 * @description 循环
		 * @param {multi} item 对象
		 * @param {Array} array 数组
		 * @return {Number} result 结果
		 */
		Index = _fn_.index = F.IndexOfArray = function(item, array) {
			var index, length = array.length
			for (index = 0; length > index; index++) if (item == array[index]) return index
			return -1
		},

		/**
		 * @name _perichr_.Functions.IsArraylike
		 * @description 判断是否为类数组对象
		 * @param {Object} object 被循环的对象
		 * @return {Boolean} result 结果
		 */
		IsArraylike = _fn_.arraylike = F.IsArraylike = function(object) {
			var length = object.length,
				type = typeof object
			if (/string|function/.test(type)) return false
			if (object.nodeType === 1 && length) return true
			return type === 'array' || length === 0 || typeof length === 'number' && length > 0 && (length - 1) in object
		},

		/**
		 * @name _perichr_.Functions.Arraify
		 * @description 转换为数组
		 * @param {Object} object 目标对象
		 * @return {Boolean} result 结果
		 */
		Arraify = _fn_.array = F.Arraify = function(object) {
			var array = []
			try {
				array = Array.prototype.slice.call(object, 0)
			} catch (error) {
				for (var item, array = [], i = 0; item = object[i++];) array.push(item)
			}
			return array
		},

		/**
		 * @name _perichr_.Functions.Extend
		 * @description 合并对象
		 * @param {Object} target 接受合并的对象
		 * @param {Object} object 被合并的对象
		 * @param {Object} override 是否复写属性
		 */
		Extend = _fn_.extend = F.Extend = function(target, object, override) {
			for (var property in object) if (object.hasOwnProperty(property) && (!target.hasOwnProperty(property) || override)) target[property] = object[property]
			return target
		},

		/**
		 * @name _perichr_.Functions.CreateScript
		 * @description 插入一个脚本链接
		 * @param {Stirng} src 脚本地址
		 * @param {Function} callback 回调函数
		 * @param {Boolean} remove 完成后是否删除脚本dom
		 */
		Js = _fn_.js = F.CreateScript = function(src, callback, remove) {
			if (typeof callback == 'boolean') {
				remove = callback
				callback = null
			}
			var s = _script_cache_[src] || {
				callback: []
			},
				run_callback = function() {
					while (s.callback[0]) {
						var callback = s.callback.shift()
						callback(200, 'success')
					}
				}
			if (callback) s.callback.push(callback)
			if (s.ready) {
				run_callback()
				return
			}
			src = Trim(src)
			src = GetFullUrl(src, _option_.jslib)
			if (_script_cache_[src]){
				if(/[\?&]callback=/.test(src)) return
			} else{
				_script_cache_[src] = s
			}
			setTimeout(function() {
				var script = doc.createElement('script')
				script.src = src
				script.onload = script.onreadystatechange = function() {
					if (!script.readyState || /loaded|complete/.test(script.readyState)) {
						script.onload = script.onreadystatechange = null
						s.ready = true
						run_callback()
						if (remove) {
							setTimeout(function(){
								if(script.parentNode)script.parentNode.removeChild(script)
							}, 500)
						}
					}
				}
				doc.body.appendChild(script)
			}, 0)
		},
		Jsonp = _fn_.jsonp = F.Jsonp = function(options) {
			options.data = options.data || {}
			options.data._ = (new Date).valueOf()
			options.data.callback = options.data.callback || Seed('cb')
			options.url += ((/\?/).test(options.url) ? '&' : '?') + Serialize(options.data)
			root[options.data.callback] = function(data) {
				options.success && options.success(data)
			}
			Js(options.url, function() {
				delete root[options.data.callback]
			}, true)
		},

		/**
		 * @name _perichr_.Functions.Loader
		 * @description 插入一组脚本或函数并依次执行
		 * @argument {Stirng} 脚本地址
		 * @argument {Number} 延时执行
		 * @argument {Function} 回调函数
		 * @argument {Array} 上述合集
		 */
		Loader = _fn_.load = F.JsLoader = function() {
			var params = Arraify(arguments),
				load = function() {
					if (params.length > 0) {
						var argument = params.shift()
						if (!IsArraylike(argument)) argument = [argument]
						go(argument)
					}
				},
				go = function(list) {
					var remain = list.length,
						cb = function() {
							remain--
							if (remain == 0) load()
						}
					Each(list, function() {
						var item = this,
							type = typeof item
						switch (type) {
						case ('string'):
							Js(item, cb, true)
							break
						case ('function'):
							item()
							cb()
							break
						case ('number'):
							setTimeout(cb, item)
							break
						default:
							cb()
							break
						}
					})
				}
			load()
		},

		/**
		 * @name _perichr_.Functions.Trim
		 * @description 删除两侧空格
		 * @param {Stirng} string 文本
		 * @return {Stirng} result 结果
		 */
		Trim = _fn_.trim = F.Trim = function(string) {
			return string.replace(/^(\s|\u00A0)+/, "").replace(/(\s|\u00A0)+$/, "")
		},

		/**
		 * @name _perichr_.Functions.Seed
		 * @description 不重复的种子
		 * @return {Number} result 结果
		 */
		Seed = _fn_.seed = F.Seed = function(prefix) {
			return (prefix ? Prefix(prefix) : '') + (_seed_++)
		},

		/**
		 * @name _perichr_.Functions.GetElementById
		 * @description 获取指定id的元素
		 * @param {Stirng} id 元素id
		 * @return {Stirng} result 结果
		 */
		Id = _fn_.id = F.GetElementById = function(id) {
			return doc.getElementById(id)
		},

		/**
		 * @name _perichr_.Functions.QuerySelector
		 * @description 使用xpath获取第一个元素
		 * @param {Stirng} xpath
		 * @return {Stirng} result 结果
		 */
		Qs = _fn_.qs = F.QuerySelector = function(xpath) {
			return doc.querySelector(xpath)
		},

		/**
		 * @name _perichr_.Functions.QuerySelectorAll
		 * @description 使用xpath获取元素集
		 * @param {Stirng} xpath
		 * @return {Stirng} result 结果
		 */
		Qa = _fn_.qa = F.QuerySelectorAll = function(xpath) {
			return doc.querySelectorAll(xpath)
		},

		El = _fn_.element = F.CreateElement = function(tag, attributes, childs) {
			var element = doc.createElement(tag)
			if (attributes) {
				Each(attributes, function(key, value) {
					Attr(element, key, value)
				})
			}
			if (childs && childs.length>0) {
				Each(childs, function() {
					var settings = this
					Append(element, El(settings.tag, settings.attributes, settings.childs))
				})
			}
			return element
		},

		/**
		 * @name _perichr_.Functions.RemoveElement
		 * @description 删除指定dom
		 * @param {Element} element dom对象
		 */
		Rm = _fn_.remove = F.RemoveElement = function(element) {
			element.parentNode.removeChild(element)
		},

		Append = _fn_.append = F.AppendChild = function(target, element) {
			target.appendChild(element)
		},

		Prepend = _fn_.prepend = F.PrependChild = function(target, element) {
			if (target.hasChildNodes()) target.insertBefore(element, target.firstChild)
			else target.appendChild(element)
		},

		Before = _fn_.before = F.BeforeChild = function(target, element) {
			target.parentNode.insertBefore(element, target)
		},

		After = _fn_.after = F.AfterElement = function(target, element) {
			var parent = target.parentNode
			if (parent.lastChild === target) parent.appendChild(element)
			else parent.insertBefore(element, target.nextSibling)
		},
		Txt = _fn_.text = F.ElementText = function(element, text) {
			if (text === undefined){
				if ( typeof element.textContent === "string" ) {
					return element.textContent;
				} else {
					var ret = ''
					for ( element = element.firstChild; element; element = elem.nextSibling ) {
						ret += Txt( element );
					}
					return ret
				}
			} else {
				element.innerHTML = ''
				Append(element, document.createTextNode(text))
			}
		},
		Attr = _fn_.attribute = F.ElementAttribute = function(element, key, value) {
			if (value === undefined){
				return element.getAttribute(key)
			} else {
				element.setAttribute(key, value)
			}
		},
		HasC = _fn_.hasclass = F.ContainsClass = function(){
			return doc.body.classList ? function(element, key){
				return element.classList.contains(key)
			} : function(element, key){
				return Index(key, element.className.split(' ')) > -1
			}
		}(),

		AddC = _fn_.addclass = F.AddClass = function(){
			return doc.body.classList ? function(element, key){
				element.classList.add(key)
			} : function(element, key){
				(Index(key, element.className.split(' ')) == -1) && (element.className += (' ' + key))
			}
		}(),
		RmC = _fn_.rmclass = F.RemoveClass = function(){
			return doc.body.classList ? function(element, key){
				element.classList.remove(key)
			} : function(element, key){
				var className = element.className.split(' '),
					index = Index(key, className)
				if(index > -1){
					classNames.splice(index, 1)
					element.className = classNames.join(" ")
				}
			}
		}(),
		TgC = _fn_.toggleclass = F.ToggleClass = function(){
			return doc.body.classList ? function(element, key){
				element.classList.toggle(key)
			} : function(element, key){
				var className = element.className.split(' '),
					index = Index(key, className)
				if(index > -1){
					classNames.splice(index, 1)
					element.className = classNames.join(' ')
				} else {
					element.className += ' ' + key
				}
			}
		}(),
		
		On = _fn_.on = F.AddEvent = function(element, e, fn, capture) {
			var add = doc.body.addEventListener ?
			function() {
				this.addEventListener(e, fn, capture)
			} : function() {
				this.attachEvent('on' + e, fn)
			}
			capture = !! capture
			Each(element, add, true)
		},

		Off = _fn_.off = F.RemoveEvent = function(element, e, fn, capture) {
			var remove = doc.body.removeEventListener ?
			function() {
				this.removeEventListener(e, fn, capture)
			} : function() {
				this.detachEvent('on' + e, fn)
			}
			capture = !! capture
			Each(element, remove, true)
		},

		/**
		 * @name _perichr_.Functions.Serialize
		 * @description 序列化
		 * @return {String} result 结果
		 */
		Serialize = _fn_.serialize = F.Serialize = function(object) {
			var retval = [];
			for (var key in object) key = [key, object[key]].join('='), retval.push(key)
			return retval.join('&')
		},

		Times = _fn_.times = F.Times = function (n, iterator, context) {
			var accum = Array(Math.max(0, n))
			for (var i = 0; i < n; i++) accum[i] = iterator.call(context, i)
			return accum
		},


		/**
		 * @name _perichr_.Load
		 * @description 载入插件功能
		 * @param {Object} plugin 插件对象
		 */
		Lo = P.Load = function(plugin) {
			if (plugin) {
				var key = plugin.id || (Prefix('noname') + Seed()),
					cache = {},
					getter = function(type) {
						type = type.toLocaleUpperCase()
						return function(key, b) {
							key = key.toLocaleLowerCase()
							return b ? cache[type][key] : _plugin_cache_[type][key]
						}
					},
					gsetter = function(type, callback) {
						type = type.toLocaleUpperCase()
						return function(key, value) {
							key = key.toLocaleLowerCase()
							if (value === undefined) {
								
								return cache[type][key] || _plugin_cache_[type][key]
							} else {
								cache[type][key] = value
								callback && callback(key, value)
							}
						}
					}
				U[key] = plugin
				plugin.GetPlugin = function(key) {
					return key ? U[key] : plugin
				}
				plugin.GetOption = getter('option')
				plugin.option = cache.OPTION = gsetter('option')
				plugin.GetFn = getter('fn')
				plugin.fn = cache.FN =gsetter('fn', function(name, func) {
					plugin.fn[name] = func || _fn_[name]
				})
				Each(_fn_, function(name, func) {
					plugin.fn[name] = func
				})
				plugin.Init && plugin.Init()
			} else {
				var script = Id(Prefix('js'))
				if (!script) return
				var datainit = script.getAttribute('data-init'),
					dataoptions = script.getAttribute('data-options')
				_option_.jslib = GetFullUrl(script.src.replace(/(.+)perichr\.js$/, '$1')) 
				if (dataoptions) {
					Extend(_option_, JSON.parse(dataoptions))
				}
				if (datainit) {
					var i = 0
					datainit = datainit.split(',')
					Loader.apply(null, datainit)
				}
				Rm(script)
			}
		}
	/* 常用函数结束 */

	/* 初始化结束 */

	/* 自启动开始 */
	Lo()
	/* 自启动结束 */

	/* 预载的私有函数开始 */
	function Prefix(key) {
		return perichr + key + '_'
	}
	function GetFullUrl(url, base) {
		if(/^(http:\/\/|\/)/.test(url)){
			return url
		}
		if(!base){
			base = window.location
			base = base.protocol + '//' + base.host + base.pathname	
		}
		url = (base + '/' + url).replace(/([^:])[\/]+/g, '$1/')
		return url
	}
	/* 预载的私有函数结束 */

})(window, document, '_perichr_')
