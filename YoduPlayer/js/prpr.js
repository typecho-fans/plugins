document.body.onclick=function(){ //点击播放器外面的事物时关闭抽屉
document.querySelector("#playlist").classList.add("yhidden");
document.getElementById('bgmplayer').classList.remove("bgmon");
};
document.getElementById('bgmplayer').onclick=function(){ //避免事件被覆盖
    event.stopPropagation();
};

function playlist(){//歌曲列表具现化
  var element = document.getElementById('playlist');
  element.innerHTML='';
if(musicApi.length>0&&musicArr.length==0){
  var ydapi=musicApi[0];//console.info(musicApi[0]);
  fetch(ydapi.api+'?server='+ydapi.type+'&type=playlist&id='+ydapi.id+'&auth='+ydapi.auth).then(response => response.json()).then(data=>{
if(musicArr.length==0){
musicArr=data;
if(ydapi.sj=='1'){a=parseInt(Math.random()*musicArr.length);}
sj=musicArr[a];
//console.info(musicArr[0].mp3);
yaudio.src=sj.mp3;
yaudio.ti=sj.title;
yaudio.art=sj.artist;
yaudio.fm=sj.cover;
}
	for (var i = 0; i < musicArr.length; i++){
		var item = musicArr[i];var anum = i+1;
      if(anum<10){anum='0'+anum;}
      var geshou="";
      if(item.artist){
          geshou= '&nbsp;-&nbsp;<span class="artist">'+item.artist+'</span>';
      }
   element.innerHTML=element.innerHTML+'<li class="yd-lib"><span class="anum">'+anum+'.</span><strong style="margin-left:3px;">'+item.title+'</strong>'+geshou+'</li>';
		if (item.mp3 == "") {
			document.querySelectorAll("#playlist li")[i].style.color='#ddd';
		}
	}
var playlistli=document.querySelectorAll("#playlist li");
playlistli.forEach((value, index) => {
    playlistli[index].classList.remove("yd-playing");
    playlistli[index].onclick=function(){ 
 next(index);//使用next函数进行点播
  };
});
playlistli[a].classList.add("yd-playing");


var ody=document.getElementById('ydmc');
var geshou='';
if (yaudio.paused) {var autopause=0;
ody.innerHTML= '<svg viewBox="0 0 20 20" fill="currentColor" class="ydicon"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>';
document.getElementById("ydfm").classList.add("paused");
} else {var autopause=1;
ody.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor" class="ydicon"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
document.getElementById("ydfm").classList.remove("paused");
}

if(yaudio.art.length>0){geshou='&nbsp;-&nbsp;'+yaudio.art;}
document.getElementById('ydtitle').innerHTML = yaudio.ti+geshou;
document.getElementById("ydfm").src=yaudio.fm;
var setProgress = function(value){
		var currentSec = parseInt(value%60) < 10 ? '0' + parseInt(value%60) : parseInt(value%60),
			ratio = value / yaudio.duration * 100;

document.getElementById('jindu').style.width=ratio+'%';
document.getElementById('ytime').innerHTML = (parseInt(value/60)+':'+currentSec);
	}

	var updateProgress = function(){
		setProgress(yaudio.currentTime);
	}

timeout = setInterval(updateProgress, 300);



}); 
  
}else{
  
  
  
  
  
  
  
	for (var i = 0; i < musicArr.length; i++){
		var item = musicArr[i];var anum = i+1;
      if(anum<10){anum='0'+anum;}
      var geshou="";
      if(item.artist){
          geshou= '&nbsp;-&nbsp;<span class="artist">'+item.artist+'</span>';
      }
   element.innerHTML=element.innerHTML+'<li class="yd-lib"><span class="anum">'+anum+'.</span><strong style="margin-left:3px;">'+item.title+'</strong>'+geshou+'</li>';
		if (item.mp3 == "") {
			document.querySelectorAll("#playlist li")[i].style.color='#ddd';
		}
	}
var playlistli=document.querySelectorAll("#playlist li");
playlistli.forEach((value, index) => {
    playlistli[index].classList.remove("yd-playing");
    playlistli[index].onclick=function(){ 
 next(index);//使用next函数进行点播
  };
});
playlistli[a].classList.add("yd-playing");

yoduplayer();
}}

function yoduplayer(){//初始化音乐播放器
var ody=document.getElementById('ydmc');
var geshou='';
if (yaudio.paused) {var autopause=0;
ody.innerHTML= '<svg viewBox="0 0 20 20" fill="currentColor" class="ydicon"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>';
document.getElementById("ydfm").classList.add("paused");
} else {var autopause=1;
ody.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor" class="ydicon"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>';
document.getElementById("ydfm").classList.remove("paused");
}

if(yaudio.art.length>0){geshou='&nbsp;-&nbsp;'+yaudio.art;}
document.getElementById('ydtitle').innerHTML = yaudio.ti+geshou;
document.getElementById("ydfm").src=yaudio.fm;
var setProgress = function(value){
		var currentSec = parseInt(value%60) < 10 ? '0' + parseInt(value%60) : parseInt(value%60),
			ratio = value / yaudio.duration * 100;

document.getElementById('jindu').style.width=ratio+'%';
document.getElementById('ytime').innerHTML = (parseInt(value/60)+':'+currentSec);
	}

	var updateProgress = function(){
		setProgress(yaudio.currentTime);
	}

timeout = setInterval(updateProgress, 300);
}

playlist();