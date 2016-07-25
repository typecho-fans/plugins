// Typing.js
// https://github.com/coffeedeveloper/typing.js
function Typing(opts){this.version="1.1";this.source=opts.source;this.output=opts.output;this.delay=opts.delay||120;this.chain={parent:null,dom:this.output,val:[]}}Typing.fn=Typing.prototype={toArray:function(eles){var result=[];for(var i=0;i<eles.length;i++){result.push(eles[i])}return result},init:function(){this.chain.val=this.convert(this.source,this.chain.val)},convert:function(dom,arr){var that=this,children=this.toArray(dom.childNodes);children.forEach(function(node){if(node.nodeType===3){arr=arr.concat(node.nodeValue.split(""))}else{if(node.nodeType===1){var val=[];val=that.convert(node,val);arr.push({"dom":node,"val":val})}}});return arr},print:function(dom,val,callback){setTimeout(function(){dom.appendChild(document.createTextNode(val));callback()},this.delay)},play:function(ele){if(!ele){return}if(!ele.val.length&&ele.parent){this.play(ele.parent)}if(!ele.val.length){return}var curr=ele.val.shift();var that=this;if(typeof curr==="string"){this.print(ele.dom,curr,function(){if(ele.val.length){that.play(ele)}else{if(ele.parent){that.play(ele.parent)}}})}else{var dom=document.createElement(curr.dom.nodeName);var attrs=that.toArray(curr.dom.attributes);attrs.forEach(function(attr){dom.setAttribute(attr.name,attr.value)});ele.dom.appendChild(dom);curr.parent=ele;curr.dom=dom;this.play(curr.val.length?curr:curr.parent)}},start:function(){this.init();this.play(this.chain)}};

var Lutachu = function(w, d) {
  if(!w.localStorage || !w.XMLHttpRequest) {
    return alert('请更新浏览器！');
  }

  a = function(d) {
    var a = document.querySelectorAll(d);

    if (a.length == 1)
      a = a[0];

    a.css = function(t) {
      if (!t || typeof t != 'string') return;

      if (t.substr(-1) == ';') {
        t = t.substr(0, t.length - 1);
      }

      if (a.length > 1) {
        for (var i = a.length - 1; i >= 0; i--) {
          a[i].style.cssText += (";" + t);
        };
      } else {
        a.style.cssText += (";" + t);
      }
    }

    return a;
  };

  /* Ajax From iToor.js */
  a.x = function(d) {
    return function(h, l, j, k, g) {
      if (typeof l == "function") {
        k = j;
        j = l;
        l = 0;
      }

      if (d[h]) {
        return j(d[h]);
      }
      
      (g = new XMLHttpRequest()).open(l ? "POST" :"GET", h, 1);
      !l || g.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      
      if (j || k) {
        g.onreadystatechange = function() {
          if (g.readyState == 4) {
            g.status > 199 && g.status < 301 || g.status == 304 ? j(d[h + (l || "")] = (g.getResponseHeader("Content-Type") || "").match(/json/) ? JSON.parse(g.responseText || "null") :g.responseText) :!k || k(g.status);
          }
        };
      }

      g.send(l || "");
      return g;
    };
  }({});

  a.stor = function(k, v) {
    return v ? localStorage[k] = v : localStorage[k];
  }

return a;
}(window, document);

function Ukagaka(data) {
  this.init(data);
}

Ukagaka.prototype = {
  data: {
    width: 160,
    height: 160,

    client: {
      width: document.documentElement.clientWidth,
      height: document.documentElement.clientHeight
    },

    position: {
      x: 0,
      y: 0
    }
  },

  body: document.createElement('div'),

  init: function(data) {
    var self = this;
    for (var d in data) {
      this.data[d] = data[d];
    }

    this.body.id = 'ukagaka';
    this.body.innerHTML = '<div class="ukagaka-character"></div>'
                        + '<div class="ukagaka-chatlog">'
                        + '   <div class="ukagaka-chatlog-source">'
                        + '   </div>'
                        + '   <div class="ukagaka-chatlog-output">'
                        + '   </div>'
                        + '   <div class="ukagaka-menu">'
                        + '     <a class="ukagaka-menu-notice">显示公告</a>'
                        + '     <a class="ukagaka-menu-talk">聊&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;天</a>'
                        + '     <a class="ukagaka-menu-food">吃 零 食</a>'
                        + '     <a class="ukagaka-menu-lifetime">生存时间</a>'
                        + '     <a class="ukagaka-menu-close">关闭春菜</a>'
                        + '   </div>'
                        + '   <div id="ukagaka-talk">'
                        + '     <input  class="ukagaka-talk" type="text">'
                        + '     <button class="ukagaka-submit">提交</button>'
                        + '     <button class="ukagaka-close">&times;</button>'
                        + '   </div>'
                        + '   <div class="ukagaka-btn">'
                        + '     <a class="ukagaka-btn-menu">| Menu |</a>'
                        + '   </div>'
                        + '</div>';

    var shokan = document.createElement('a');
    shokan.id  = 'ukagaka-shokan';
    shokan.innerHTML = '召唤!';
    shokan.onclick = function() {
      self.show('ukagaka');
    }

    Lutachu('body').appendChild(this.body);
    Lutachu('body').appendChild(shokan);
    this.typing =  new Typing({
      source: Lutachu('.ukagaka-chatlog-source'),
      output: Lutachu('.ukagaka-chatlog-output'),
      delay: 20
    });

    // Check
    if (Lutachu.stor('ukagaka_hide') == 1) {
      this.hide();
    }

    this.data['hate'] = Lutachu.stor('ukagaka_hate') ? Lutachu.stor('ukagaka_hate') : 0;
    if ((Date.parse(new Date()) - this.data['hate']) > 30 * 100000) {
      this.data['hate'] = 0;
      Lutachu.stor('ukagaka_hate', 0);
    }

    // Set Height & Width
    Lutachu('#ukagaka, .ukagaka-character').css('width:' + data['width'] + 'px;' + 'height:' + data['height'] + 'px');
    Lutachu('.ukagaka-chatlog').css('right:' + (+data['width'] + 10) + 'px');

    // Set Position
    this.data['position'] = JSON.parse(Lutachu.stor('ukagaka_position'));
    if (this.data['position']['x'] > 0) {
      this.body.style.left = this.data['position']['x'] + 'px';
    }

    if (this.data['position']['y'] > 0) {
      this.body.style.top = this.data['position']['y'] + 'px';
    }

    // Set Food
    this.data['feed_times'] = Lutachu.stor('ukagaka_feed_times') ? Lutachu.stor('ukagaka_feed_times') : 0;
    this.data['feed_time']  = Lutachu.stor('ukagaka_feed_time') ? Lutachu.stor('ukagaka_feed_time') : 0;

    // Set Face
    if (this.data['hate'] > 0) {
      this.hate();
    } else {
      Lutachu('.ukagaka-character').css('background-image:url(' + data['character'][0] + ')');
    }

    var self = this;
    Lutachu.x(this.data.api, function(data) {
      self.data['born'] = data['born'];
      self.data['notice'] = data['notice'];
      self.data['talk'] = [data['question'], data['answer']];
      self.data['food'] = [data['foods'], data['eatsay']];
      self.data['nick'] = data['nickname'];
      self.data['talk'] = data['talk'];

      if (self.data['hate'] == 0) {
        self.show('notice');
      }
    }, function() {
      self.type('好像出错了，是什么错误呢...请联系管理猿');
    });

    // Set Event
    this.event();
  },

  show: function(t) {
    this.face(2);
    Lutachu('.ukagaka-menu,#ukagaka-talk').css('display:none');
    Lutachu('.ukagaka-btn-menu').css('display:block');

    switch (t) {
      case 'ukagaka':
        if (this.data['hate'] == 0) {
          Lutachu('#ukagaka').css('display:block');
          Lutachu('#ukagaka-shokan').css('display:none');
          this.face(2);
          var content = ['我回来了~', this.data['nick'] + '想我了么？', '我就知道会再次相遇的。', '相遇是见好事呢！'];
          this.type(content[Math.ceil(Math.random() * 3)]);
          Lutachu.stor('ukagaka_hide', '0');
        } else {
          return;
        }
        break;

      case 'notice':
        this.type(this.data['notice']);
        break;

      case 'menu':
        this.type('准备做什么呢？');
        Lutachu('.ukagaka-menu').css('display:block');
        Lutachu('.ukagaka-btn-menu').css('display:none');
        break;

      case 'food':
        var self = this,
            food = [];
        Lutachu('.ukagaka-chatlog-output').innerHTML = '';

        for (var i in this.data['food'][0]) {
          var f = this.data['food'][0][i];
          food[i] = document.createElement('a');
          food[i].className = 'ukagaka-food'
          food[i].setAttribute('data-food-id', i);
          food[i].innerHTML = f;
          food[i].onclick = function() {
            self.food(this);
          };
          Lutachu('.ukagaka-chatlog-output').appendChild(food[i]);
        }

        var prefoods = Lutachu('.ukagaka-food');
        break;

      case 'lifetime':
        var time1 = new Date(),
            time2 = new Date(this.data['born'] * 1000),
            year  = time1.getFullYear() - time2.getFullYear(),
            month = time1.getMonth()    - time2.getMonth(),
            day   = time1.getDay()      - time2.getDay(),
            hours = time1.getHours()    - time2.getHours(),
            mins  = time1.getMinutes()  - time2.getMinutes(),
            secs  = time1.getSeconds()  - time2.getSeconds();

        this.type('我已经与' + this.data['nick'] + ' 一起生存了 ' + (year > 0 ? year + '年 ' : '') + (month > 0 ? month + '月 ' : '') + (day > 0 ? day + '天 ' : '') + (hours > 0 ? hours + '小时 ' : '') + (mins > 0 ? mins + '分钟 ' : '') + (secs > 0 ? secs + '秒 ' : '') + '的快乐时光啦～*^_^*');
        console.log(mins)
        break;

      case 'talk':
        var self = this,
            time;

        Lutachu('#ukagaka-talk').css('display:block');
        Lutachu('.ukagaka-talk').onkeydown = function(e) {
          var ev = e || windows.event;
          var k = ev.keyCode || ev.which;

          if(k == 13) {
            clearTimeout(time);
            time = setTimeout(function() {
              var c = Lutachu('.ukagaka-talk').value;
              self.talk(c);
              Lutachu('.ukagaka-talk').value = '';
            }, 50);
          }
        };

        Lutachu('.ukagaka-submit').onclick = function() {
          clearTimeout(time);
          time = setTimeout(function() {
            var c = Lutachu('.ukagaka-talk').value;
            self.talk(c);
            Lutachu('.ukagaka-talk').value = '';
          }, 50);
        };

        Lutachu('.ukagaka-close').onclick = function() {
          self.show('menu');
        }
        break;
    }
  },

  talk: function(v) {
    var t = this.data['talk'][0].indexOf(v);

    if (t > -1) {
      var c = this.data['talk'][1][t];
      this.type(c);
    } else {
      this.type('嗯.....?');
    }
  },

  hate: function() {
    Lutachu('.ukagaka-btn-menu').css('display:none');
    this.type('最讨厌你了！我不认识你！奏凯！');
    this.face(3);
    this.hide();

    var t = Date.parse(new Date());
    this.data['hate'] = t;
    Lutachu.stor('ukagaka_hate', t);
  },

  face: function(n) {
    Lutachu('.ukagaka-character').css('background-image:url(' + this.data['character'][(n - 1)] + ')');
  },

  food: function(v) {
    var a = v.getAttribute('data-food-id');
    var n = Date.parse(new Date());

    if (this.data['feed_times'] > 0) {
      if ((n - this.data['feed_time']) > 30 * 100000) {
        this.data['feed_times'] = 0;
      } else if ((n - this.data['feed_time']) > 15 * 100000) {
        this.data['feed_times'] = this.data['feed_times'] - Math.round(this.data['feed_times'] / 2);
      } else {
        this.data['feed_times']++;
      }
    } else {
      this.data['feed_times'] = 1;
    }

    this.data['feed_time']  = n;

    if (this.data['feed_times'] >= 5 && this.data['feed_times'] < 8) {
      this.type('好饱啊，快吃不下了╰（￣▽￣）╭。');
    } else if (this.data['feed_times'] >= 8 && this.data['feed_times'] < 10) {
      this.type('吃不下了，不要给我吃了啦！');
    } else if (this.data['feed_times'] >= 10) {
      this.type('最讨厌你了！');
      return this.hate();
    } else {
      this.type(this.data['food'][1][a]);
    }

    Lutachu.stor('ukagaka_feed_times', this.data['feed_times']);
    Lutachu.stor('ukagaka_feed_time', this.data['feed_time']);
  },

  hide: function(f) {
    var self = this;
    if (f > 0) {
      Lutachu.stor('ukagaka_hide', 1);
      Lutachu('#ukagaka').css('display:none');
      return Lutachu('#ukagaka-shokan').css('display:block');
    }

    if (this.data['hate'] == 0) {
      Lutachu('.ukagaka-menu').css('display:none');
      this.face(3);
      var content = ['再见~', '要记得再叫我出来呢！', '嗯QAQ，就要分别了么。', '暂时的分开是有意义的！'];
      this.type(content[Math.ceil(Math.random() * 3)]);

      setTimeout(function(){
        self.hide(1);
      }, 500);
    } else {
      self.hide(1);
    }
  },

  type: function(v) {
    Lutachu('.ukagaka-chatlog-source').innerHTML = v;
    Lutachu('.ukagaka-chatlog-output').innerHTML = '';

    this.typing.start();
  },

  event: function() {
    var self = this,
        body = this.body,
        moveX, moveY, moveTop, moveLeft, moveable;

    body.onmousedown = function() {
      moveable = true;
      moveX = window.event.clientX;
      moveY = window.event.clientY;

      moveTop  = parseInt(body.style.top) || ukagaka.body.offsetTop;
      moveLeft = parseInt(body.style.left) || ukagaka.body.offsetLeft;

      if (isFirefox = navigator.userAgent.indexOf("Firefox") > 0) {
        window.getSelection().removeAllRanges();
      }

      document.onmousemove = function() {
        if (moveable) {
          var x = moveLeft + window.event.clientX - moveX;
          var y = moveTop + window.event.clientY - moveY;

          body.style.left = x + 'px';
          body.style.top = y + 'px';
        }
      };

      document.onmouseup = function() {
        if (moveable) {
          Lutachu.stor('ukagaka_position', JSON.stringify({x: body.style.left.replace('px', ''), y: body.style.top.replace('px', '')}));
          moveable = false;
          moveX = moveY = moveTop = moveLeft = 0;
        }
      }
    };

    Lutachu('.ukagaka-btn-menu').onclick = function() {
      self.show('menu');
    };

    Lutachu('.ukagaka-menu-notice').onclick = function() {
      self.show('notice');
    };

    Lutachu('.ukagaka-menu-talk').onclick = function() {
      self.show('talk');
    };

    Lutachu('.ukagaka-menu-food').onclick = function() {
      self.show('food');
    };

    Lutachu('.ukagaka-menu-lifetime').onclick = function() {
      self.show('lifetime');
    };

    Lutachu('.ukagaka-menu-close').onclick = function() {
      self.hide();
    };

    var outTimer, talkTimer, outTime = 0, talkTime = 0;
    function outCheck() {
      outTime++;
      outTimer = setTimeout(function(){ outCheck(); }, 1000);

      if (parseInt(outTime) == parseInt(600)) {
        self.type(self.data['nick'] + '跑到哪里去了呢....');
        self.face(3);

        clearTimeout(talkTimer);
        if (outTimer) {
          clearTimeout(outTimer);
          outTimer = -1;
        }
      }
    }

    function talkSelf() {
      talkTime++;

      if (parseInt(talkTime%60) == parseInt(9)) {
        if (self.data['talk'].length > 0) {
          var random = Math.ceil(Math.random() * (self.data['talk'].length - 1));
          self.face(random);
          self.type(self.data['talk'][random][0]);
        } else {
          var json  = document.createElement('script');  
          json.type = 'text/javascript';  
          json.src  = 'http://api.hitokoto.us/rand?encode=jsc&fun=ukagaka.hitokoto';  
          document.getElementsByTagName("head")[0].appendChild(json);  
        }
      }

      talkTimer = setTimeout(function(){ talkSelf(); }, 1000);
    }

    talkSelf();

    window.document.onmousemove = function() {
      if (outTimer == -1) {
        self.type(self.data['nick'] + '欢迎回来！');
        talkSelf();
      }

      if (outTimer) {
        clearTimeout(outTimer);
      }

      outTime = 0;
      outCheck();
    }
  },

  hitokoto: function(data) {
    if (data['hitokoto'].search(/痛苦|悲伤|哭|死|回忆|极端|逃避|结束|软落|负|无法/) > -1){
      this.face(3);
    } else if (data['hitokoto'].search(/喜欢|爱|快乐|幸福|笑|希望|前进|美丽|温柔|正/) > -1) {
      this.face(2);
    } else {
      this.face(1);
    }

    this.type(data['hitokoto']);
  }
};