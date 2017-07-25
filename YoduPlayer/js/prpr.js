var ody=document.getElementById('ydmc');
if (yaudio.paused) {var autopause=0;
ody.className = 'icon-bofang';
document.getElementById("ydfm").className = "";
} else {var autopause=1;
ody.className = 'icon-music';
document.getElementById("ydfm").className = "Rotation";
}

document.getElementById('ydtitle').innerHTML = yaudio.ti+'&nbsp;-&nbsp;'+yaudio.art;
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

timeout = setInterval(updateProgress, 500);
