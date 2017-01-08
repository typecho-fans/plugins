(function(global, undefined) {

'use strict';

var Player;
var players = [];
var playerSelector = '.remix';
var utils;
var http;
var remix = global.remix;

/**
 * 工具 utils
 */
utils = {
  /**
   * DOM
   *
   * utils.dom.getAll('b')
   * utils.dom.getAll(document.querySelector('a'), 'b')
   * utils.dom.get('b')
   * utils.dom.get(document.querySelector('a'), 'b')
   */
  dom: {
    getAll: function() {
      var node;
      var selector;
      var results;

      if (arguments.length === 1) {
        node = global.document.documentElement;
        selector = arguments[0];
      } else {
        node = arguments[0];
        selector = arguments[1];
      }

      if (node && node.querySelectorAll) {
        results = node.querySelectorAll(selector);
      }

      return results;
    },
    get: function() {
      var results = this.getAll.apply(this, arguments);

      if (results && results.length) {
        results = results[results.length - 1];
      }

      return results;
    },
    getData: function(node, attribute) {
      var data;
      var ua = global.navigator.userAgent;

      if ((/msie/i).test(ua)) {
        data = node.getAttribute('data-' + attribute);
      } else {
        data = node.dataset[attribute];
      }

      return data;
    },
    setData: function(node, attribute, value) {
      var ua = global.navigator.userAgent;

      if ((/msie/i).test(ua)) {
        node.setAttribute('data-' + attribute, value);
      } else {
        node.dataset[attribute] = value;
      }
    }
  },
  /**
   * CSS
   *
   * utils.css.add(node, 'example');
   * utils.css.remove(node, 'example');
   * utils.css.toggle(node, 'example');
   * utils.css.swap(node, 'example1', 'example2');
   */
  css: {
    has: function(node, cStr) {
      var regx = new RegExp('(^|\\s)' + cStr + '(\\s|$)');

      return (node.className !== undefined ? regx.test(node.className) : false);
    },
    add: function(node, cStr) {
      if (!node || !cStr || this.has(node, cStr)) {
        return false;
      }

      node.className = (node.className ? node.className + ' ' : '') + cStr;
    },
    remove: function(node, cStr) {
      var regx;

      if (!node || !cStr || !this.has(node, cStr)) {
        return false;
      }

      regx = new RegExp('( ' + cStr + ')|(' + cStr + ')', 'g');
      node.className = node.className.replace(regx, '');
    },
    toggle: function(node, cStr) {
      var found;
      var method;

      found = this.has(node, cStr);
      method = (found ? this.remove : this.add);

      method.apply(this, [node, cStr]);

      // return !found;
    },
    swap: function(node, cStr1, cStr2) {
      var temp = {
        className: node.className
      };

      this.remove(temp, cStr1);
      this.add(temp, cStr2);

      node.className = temp.className;
    }
  },
  /**
   * Events
   *
   * utils.events.add(node, 'click', 'handlerFunction');
   * utils.events.remove(node, 'click', 'handlerFunction');
   */
  events: {
    add: function(node, evtName, evtHandler) {
      var eventObject = {
        detach: function() {
          return remove(node, evtName, evtHandler);
        }
      };

      if (global.addEventListener) {
        node.addEventListener(evtName, evtHandler, false);
      } else {
        node.attachEvent('on' + evtName, evtHandler);
      }

      return eventObject;
    },
    remove: (global.removeEventListener !== undefined ? function(node, evtName, evtHandler) {
      return node.removeEventListener(evtName, evtHandler, false);
    } : function(node, evtName, evtHandler) {
      return node.detachEvent('on' + evtName, evtHandler);
    }),
  }
};

/**
 * 请求 http
 *
 * 不做跨域处理
 */
http = {
  req: function(settings) {
    var xhr;
    var config = {
      method: settings.type || 'GET',
      url: ((settings.url || location.href) + '').replace('/#.*$/', '').replace('/^\/\//', location.protocol + '//'),
      data: settings.data || null,
      dataType: settings.dataType || 'json',
      async: settings.async || true,
      callback: settings.callback || new Function
    };

    // 获取 XMLHttpRequest 对象
    xhr = this.xhr.call(this);

    // 设置请求地址
    if (config.data) {
      config.url = this.setUrl(config.url, config.data);
    }

    xhr.open(config.method, config.url, config.async);
    xhr.setRequestHeader("Content-Type", "charset=UTF-8");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.setRequestHeader("Remix-Hash", remix.hash);

    // 就绪及回调
    xhr.onreadystatechange = function() {
      if (4 == xhr.readyState) {
        if (200 == xhr.status) {
          var data = ('json' != config.dataType) ? xhr.responseText : JSON.parse(xhr.responseText);

          config.callback.call(this, data);
        } else {
          console.error('Bad Request: ' + xhr.statusText);
        }
      }
    };

    try {
      xhr.send();
    } catch (e) {}
  },
  xhr: function() {
    try {
      return new XMLHttpRequest();
    } catch (e) {}
  },
  setUrl: function(url, data) {
    var query;
    var result = [];

    for (var i in data) {
      result.push(i + '=' + data[i]);
    }

    query = result.join('&');

    return url + ((/\?/).test(url) ? '&' : '?') + query;
  }
};

/**
 * 播放器 Player
 */
Player = function(node) {

  var dom;
  var css;
  var data;
  var selector;
  var actions;
  var controller;
  var soundObject;

  /* 选择器 */
  selector = {
    playlist: '.remix-playlist',
    controls: {
      main: '.remix-controls',
      progress: {
        track: '.remix-progress',
        loaded: '.remix-progress-loaded',
        played: '.remix-progress-played'
      },
      button: {
        play: '.remix-button-play',
        volume: '.remix-button-volume',
        menu: '.remix-button-menu'
      },
      duration: '.remix-duration',
      detail: '.remix-detail'
    }
  };

  /* CSS */
  css = {
    disabled: 'disabled',
    selected: 'selected',
    active: 'active',
    noVolume: 'no-volume',
    mute: 'mute',
    menu: 'open'
  };

  /* 节点 */
  dom = {
    player: null,
    playlist: null,
    controls: {
      main: null,
      progress: {
        track: null,
        loaded: null,
        played: null
      },
      button: {
        play: null,
        volume: null,
        menu: null
      },
      duration: null,
      detail: null
    }
  };

  /* 数据 */
  data = {
    auto: false,
    loop: true,
    selectedIndex: 0,
    sources: null
  };

  /* 控制器 */
  function Controller() {
    /* 获取所有歌曲资源 */
    function getSources() {
      return data.sources;
    }

    /* 获取某一条歌曲资源 */
    function getSource(index) {
      var sources;
      var source;

      if (data.selectedIndex === null) {
        return index;
      }

      sources = getSources();
      index = (index !== undefined ? index : data.selectedIndex);
      source = sources[index];

      return source;
    }

    /* 获取下一首歌曲资源 */
    function getNext() {
      var source;
      var total = data.sources.length;

      // 让选择位置递增一位
      if (data.selectedIndex !== null) {
        data.selectedIndex++;
      }

      if (total > 1) {  // 列表多首歌
        if (data.selectedIndex >= total) {  // 超过列表
          if (1 == data.loop) {
            // 返回第一首歌的数据，并设置选择位置为 0
            source = getSource(0);
            data.selectedIndex = 0;
          } else {
            // 返回空数据，并设置选择位置为刚播完的歌曲
            source = getSource(data.selectedIndex);
            data.selectedIndex--;
          }
        } else {  // 未超过列表
          // 返回下一首歌的数据，选择位置为下一首歌
          source = getSource(data.selectedIndex);
        }
      } else {  // 列表一首歌
        if (1 == data.loop) {
          // 返回第一首歌数据，并设置选择位置为 0
          source = getSource(0);
          data.selectedIndex = 0;
        } else {
          // 返回空数据，并设置位置为 0
          source = getSource(data.selectedIndex);
          data.selectedIndex = 0;
        }
      }

      return source;
    }

    /* 获取上一首歌曲资源 */
    function getPrevious() {
      data.selectedIndex--;

      if (data.selectedIndex < 0) {
        if (1 == data.loop) {
          data.selectedIndex = data.sources.length - 1;
        } else {
          data.selectedIndex++;
        }
      }

      return getSource();
    }

    /* 清除选择样式 */
    function resetSelected() {
      var items;
      var selectedClass = '.' + css.selected;

      if (data.sources !== null) {
        items = utils.dom.getAll(dom.playlist, selectedClass);
      }

      if (items) {
        for (var i = 0; i < items.length; i++) {
          utils.css.remove(items[i], css.selected);
        }
      }
    }

    /* 选择单曲 */
    function select(index) {
      var items;

      // 重置选择样式
      resetSelected();

      if (index !== undefined && index !== null) {
        items = utils.dom.getAll(dom.playlist, 'li');

        for (var i = 0; i < items.length; i++) {
          if (index == i) {
            utils.css.add(items[i], css.selected);
            break;
          }
        }
      }

      data.selectedIndex = index;
    }

    /* 获取地址 */
    function getURL() {
      var source;
      var url;

      source = getSource();

      if (source) {
        url = source.src;
      }

      return url;
    }

    return {
      getNext: getNext,
      getPrevious: getPrevious,
      getSource: getSource,
      select: select,
      getURL: getURL
    };
  }  // End Controller

  /* 播放时间处理 ms 为毫秒 */
  function getTime(ms) {
    var seconds = Math.floor(ms / 1000);
    var m = Math.floor(seconds / 60);
    var s = Math.floor(seconds % 60);

    return ((m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s);
  }

  /* 设置播放标题 */
  function setTitle(index) {
    var sources;

    sources = data.sources;
    dom.controls.detail.innerHTML = sources[index].title + ' - ' + sources[index].author;
  }

  /* 设置播放列表初始状态 */
  function setPlaylist() {
    if (data.sources.length > 1) {
      utils.css.add(dom.playlist, css.menu);
    }
  }

  /* 创建声音媒体 */
  function makeSound(url) {
    var sound = soundManager.createSound({
      url: url,
      // type: 'audio/mp3',

      whileplaying: function() {
        var progressMaxLeft = 100;
        var width;

        // 播放进度
        width = Math.min(100, Math.max(0, (100 * this.position / this.durationEstimate))) + '%';

        if (this.duration) {
          // 播放进度
          dom.controls.progress.played.style.width = width;
          // 播放时间
          dom.controls.duration.innerHTML = '-' + getTime(this.duration - this.position);
        }
      },

      // 缓冲状态
      onbufferchange: function(isBuffering) {
        if (isBuffering) {
          utils.css.add(dom.player, 'buffering');
        } else {
          utils.css.remove(dom.player, 'buffering');
        }
      },

      // 播放中
      onplay: function() {
        utils.css.swap(dom.controls.button.play, 'paused', 'playing');
      },

      // 暂停
      onpause: function() {
        utils.css.swap(dom.controls.button.play, 'playing', 'paused');
      },

      // 重新播放
      onresume: function() {
        utils.css.swap(dom.controls.button.play, 'paused', 'playing');
      },

      // 加载中
      whileloading: function() {
        var width = ((this.bytesLoaded / this.bytesTotal) * 100) + '%';

        // 加载进度条
        dom.controls.progress.loaded.style.width = width;

        if (!this.isHTML5) {
          dom.controls.duration.innerHTML = getTime(this.durationEstimate);
        }
      },

      onload: function(ok) {
        if (ok) {
          dom.controls.duration.innerHTML = getTime(this.duration);
        } else if (this._iO && this._iO.onerror) {
          this._iO.onerror();
        }
      },

      onerror: function() {
        var source;

        source = controller.getSource();

        if (source) {
          dom.controls.detail.innerHTML = '错误: 当前歌曲无法播放';
        }

        // 设置播放按钮为暂停中状态
        utils.css.swap(dom.controls.button.play, 'playing', 'paused');
      },

      onstop: function() {
        utils.css.remove(dom.controls.button.play, 'playing');
      },

      onfinish: function() {
        var source;

        utils.css.remove(dom.controls.button.play, 'playing');
        dom.controls.progress.played.style.width = 0;
        dom.controls.duration.innerHTML = '00:00';

        // 获取下一首数据
        source = controller.getNext();

        // 设置播放器信息
        controller.select(data.selectedIndex);
        setTitle(data.selectedIndex);

        if (source) {
          this.play({
            url: source.src
          });
        }
      }
    });

    return sound;
  }  // End makeSound

  /* 播放选择的歌曲 */
  function playIndex(index) {
    var source = data.sources[index];

    if (soundManager.canPlayURL(source.src)) {
      if (!soundObject) {
        soundObject = makeSound(source.src);
      }

      soundObject.stop();

      controller.select(index);

      setTitle(index);

      soundObject.play({
        url: source.src,
        position: 0
      });
    }
  }

  /* 获取请求数据后初始播放器数据 */
  function refresh(sources) {
    if (undefined == sources || null == sources || sources.length < 1) {
      return;
    }

    // 初始媒体资源
    data.sources = sources;
    // 初始播放模式
    data.auto = utils.dom.getData(dom.player, 'auto');
    data.loop = utils.dom.getData(dom.player, 'loop');

    // 初始播放列表
    for (var i = 0; i < sources.length; i++) {
      var item = global.document.createElement('li');

      item.innerHTML = sources[i].title + ' - ' + sources[i].author;
      utils.dom.setData(item, 'index', i);
      dom.playlist.appendChild(item);
    };

    // 实例化播放控制器
    controller = new Controller();

    // 默认选择第一个
    controller.select(0);

    // 自动播放
    if (1 == data.auto) {
      playIndex(0);
    }

    // 设置播放文字
    setTitle(0);

    // 设置播放列表默认伸缩
    setPlaylist();

    // 绑定动作
    utils.events.add(dom.player, 'click', actions.handleClick);
    utils.events.add(dom.controls.progress.track, 'click', actions.handleShuffle);
  }

  /* 初始化 */
  function init() {
    if (!node) {
      console.warn('Could not find any elements of player!');
    }

    // 初始节点
    dom.player = node;
    dom.playlist = utils.dom.get(dom.player, selector.playlist);
    // controls
    dom.controls.main = utils.dom.get(dom.player, selector.controls.main);
    dom.controls.progress.track = utils.dom.get(dom.controls.main, selector.controls.progress.track);
    dom.controls.progress.loaded = utils.dom.get(dom.controls.main, selector.controls.progress.loaded);
    dom.controls.progress.played = utils.dom.get(dom.controls.main, selector.controls.progress.played);
    dom.controls.button.play = utils.dom.get(dom.controls.main, selector.controls.button.play);
    dom.controls.button.volume = utils.dom.get(dom.controls.main, selector.controls.button.volume);
    dom.controls.button.menu = utils.dom.get(dom.controls.main, selector.controls.button.menu);
    dom.controls.duration = utils.dom.get(dom.controls.main, selector.controls.duration);
    dom.controls.detail = utils.dom.get(dom.controls.main, selector.controls.detail);

    if (global.navigator.userAgent.match(/mobile/i)) {
      // 为手机客户端上 HTML5 audio set volume.
      utils.css.add(dom.player, css.noVolume);
    }

    var id = utils.dom.getData(dom.player, 'songs');
    var type = utils.dom.getData(dom.player, 'type');
    var serve = utils.dom.getData(dom.player, 'serve');

    // 初始播放器再刷新播放器数据
    http.req({
      url: remix.url,
      data: {'serve': serve, 'do': type, 'id': id},
      callback: refresh
    });
  }

  init();

  /* 动作 */
  actions = {
    handleClick: function(e) {
      var evt;
      var target;
      var offset;
      var targetNodeName;
      var index;
      var src;
      var handled;

      evt = (e || global.event);
      target = evt.target || evt.srcElement;

      if (target && target.nodeName) {
        targetNodeName = target.nodeName.toLowerCase();

        // 播放列表触发
        if (targetNodeName === 'li') {
          index = utils.dom.getData(target, 'index');
          src = data.sources[index].src;

          if (data.selectedIndex === index) {
            if (!soundObject) {
              soundObject = makeSound(src);
            }
            soundObject.togglePause();
          } else {
            if (soundManager.canPlayURL(src)) {
              playIndex(index);
            }
          }

          handled = true;
        }

        // 按钮触发
        if (targetNodeName === 'i') {
          var btnPlayRegx = new RegExp('remix-button-play');
          var btnVolumeRegx = new RegExp('remix-button-volume');
          var btnMenuRegx = new RegExp('remix-button-menu');

          // 播放按钮
          if (btnPlayRegx.test(target.className)) {
            index = data.selectedIndex;
            src = data.sources[index].src;

            if (!soundObject) {
              soundObject = makeSound(src);
            }

            controller.select(index);
            soundObject.togglePause();

            handled = true;
          }

          // 音量按钮
          if (btnVolumeRegx.test(target.className)) {
            if (soundObject) {
              soundObject.toggleMute();
              utils.css.toggle(dom.controls.button.volume, css.mute);
            }

            handled = true;
          }

          // 菜单按钮
          if (btnMenuRegx.test(target.className)) {
            utils.css.toggle(dom.playlist, css.menu);

            handled = true;
          }
        }
      }

      if (!handled) {
        return false;
      }
    },
    handleShuffle: function(e) {
      var target;
      var barX;
      var barWidth;
      var x;
      var newPosition;
      var sound;

      target = dom.controls.progress.track;
      barX = getOffX(target);
      barWidth = target.offsetWidth;
      x = (e.clientX - barX);
      newPosition = (x / barWidth);
      sound = soundObject;

      if (sound && sound.duration) {
        sound.setPosition(sound.duration * newPosition);
        sound._iO.whileplaying.apply(sound);
      }

      function getOffX(o) {
        var curleft = 0;

        if (o.offsetParent) {
          while (o.offsetParent) {
            curleft += o.offsetLeft;
            o = o.offsetParent;
          }
        } else if (o.x) {
          curleft += o.x;
        }

        return curleft;
      }

      return false;
    }
  };

};

/* soundManager 初始化 */
soundManager.setup({
  html5PollingInterval: 50,
  flashVersion: 9
});

soundManager.onready(function() {
  var nodes;

  nodes = utils.dom.getAll(playerSelector);

  if (nodes && nodes.length) {
    for (var i = 0; i < nodes.length; i++) {
      players.push(new Player(nodes[i]));
    }
  }
});

})(this);
