function qiehuan(){
var el = document.getElementById('bgmplayer');
var className = 'bgmon';
if (el.classList) {
  el.classList.toggle(className);
} else {
  var classes = el.className.split(' ');
  var existingIndex = classes.indexOf(className);

  if (existingIndex >= 0)
    classes.splice(existingIndex, 1);
  else
    classes.push(className);

  el.className = classes.join(' ');
}
}
function playbtu(){
var oyd = document.getElementById('ydmc');
if (yaudio.paused) {
            yaudio.play();
           oyd.className = 'icon-music';
document.getElementById("ydfm").className = "Rotation";
        } else {
            yaudio.pause();
            oyd.className = 'icon-bofang';document.getElementById("ydfm").className = "";
        }
}
function next() {
var oyd=document.getElementById('ydmc');
if (a == musicArr.length - 1) {
            a = 0;
        } else {
            a = a+1;
        }
        sj = musicArr[a];
        yaudio.src = sj.mp3;
        yaudio.ti = sj.title;
        yaudio.art = sj.artist;
		yaudio.fm=sj.cover;
        yaudio.play();var autopause=0;
       oyd.className = 'iicon-music';
document.getElementById("ydfm").className = "Rotation";
document.getElementById('ydtitle').innerHTML = yaudio.ti+'&nbsp;-&nbsp;'+yaudio.art;
document.getElementById("ydfm").src=yaudio.fm;
}
function previous(){
var oyd=document.getElementById('ydmc');
if (a == 0) {
          a =musicArr.length - 1;
        }else{
  a = a-1;
}
        sj = musicArr[a];
        yaudio.src = sj.mp3;
        yaudio.ti = sj.title;
        yaudio.art = sj.artist;
		yaudio.fm=sj.cover;
        yaudio.play();var autopause=0;
       oyd.className = 'icon-music';
document.getElementById("ydfm").className = "Rotation";
document.getElementById('ydtitle').innerHTML = yaudio.ti+'&nbsp;-&nbsp;'+yaudio.art;
document.getElementById("ydfm").src=yaudio.fm;
}

yaudio.addEventListener('ended',
function() {
    next();
},
false);
