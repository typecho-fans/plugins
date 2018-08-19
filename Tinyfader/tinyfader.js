// tinyfader - by Michael Leigeber - URI: http://www.scriptiny.com/
var TINY={};

function T$(i){return document.getElementById(i)}
function T$$(e,p){return p.getElementsByTagName(e)}

TINY.fader=function(){
	function fade(n,p){this.n=n; this.init(p)}
	fade.prototype.init=function(p){
		var s=T$(p.id), u=this.u=T$$('li',s), l=u.length, i=this.l=this.c=this.z=0;
		if(p.navid&&p.activeclass){this.g=T$$('li',T$(p.navid)); this.s=p.activeclass}
		s.style.overflow='hidden'; this.a=p.auto||0; this.p=p.resume||0;
		for(i;i<l;i++){
			if(u[i].parentNode==s){
				u[i].style.position='absolute'; this.l++; u[i].o=p.visible?100:0;
				u[i].style.opacity=u[i].o/100; u[i].style.filter='alpha(opacity='+u[i].o+')'
			}
		}
		this.pos(p.position||0,this.a?1:0,p.visible)
	},
	fade.prototype.auto=function(){
		this.u.ai=setInterval(new Function(this.n+'.move(1,1)'),this.a*1000)
	},
	fade.prototype.move=function(d,a){
		var n=this.c+d, i=d==1?n==this.l?0:n:n<0?this.l-1:n; this.pos(i,a)
	},
	fade.prototype.pos=function(i,a,v){
		var p=this.u[i]; this.z++; p.style.zIndex=this.z;
		clearInterval(p.si); clearInterval(this.u.ai); this.u.ai=0; this.c=i;
		if(p.o>=100&&!v){p.o=0; p.style.opacity=0; p.style.filter='alpha(opacity=0)'}
		if(this.g){for(var x=0;x<this.l;x++){this.g[x].className=x==i?this.s:''}}
		p.si=setInterval(new Function(this.n+'.fade('+i+','+a+')'),20)
	},
	fade.prototype.fade=function(i,a){
		var p=this.u[i];
		if(p.o>=100){
			clearInterval(p.si); if((a||(this.a&&this.p))&&!this.u.ai){this.auto()}
		}else{
			p.o+=5; p.style.opacity=p.o/100; p.style.filter='alpha(opacity='+p.o+')'
		}
	};
	return{fade:fade}
}();