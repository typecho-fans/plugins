var ody=document.getElementById('ydmc');
if (yaudio.paused) {var autopause=0;
ody.className = 'iconfont icon-bofang';
} else {var autopause=1;
ody.className = 'iconfont icon-music';
}
var otheraudio = document.getElementsByTagName('audio');
if(autopause==1||znst==1){
if(otheraudio.paused || otheraudio.length==0){var znst=0;
           yaudio.play();
           ody.className = 'iconfont icon-music';
           console.info("背景音乐恢复播放");  
}
else{var znst=1;
  yaudio.pause();
            ody.className = 'iconfont icon-bofang';
       console.info("背景音乐智能暂停");  
}
}