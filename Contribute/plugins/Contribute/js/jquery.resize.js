/*
    Masked Input plugin for jQuery
    Copyright (c) 2007-2013 Josh Bush (digitalbush.com)
    Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
    Version: 1.3.1
*/
!function(a){a.fn.resizeable=function(b){var c=a.extend({minHeight:100,afterResize:null},b);return this.each(function(){function h(b){return textarea=a(b.data.el),textarea.blur(),e=k(b).y,d=textarea.height()-e,textarea.css("opacity",.25),a(document).mousemove(i).mouseup(j),!1}function i(a){var b=k(a).y,c=d+b;return e>=b&&(c-=5),e=b,c=Math.max(f,c),textarea.height(c+"px"),f>c&&j(a),!1}function j(){var f=textarea.outerHeight();a(document).unbind("mousemove",i).unbind("mouseup",j),textarea.css("opacity",1),textarea.focus(),textarea=null,d=null,e=0,c.afterResize&&c.afterResize.call(g,f)}function k(a){return{x:a.clientX+document.documentElement.scrollLeft,y:a.clientY+document.documentElement.scrollTop}}var d,b=a('<span class="resize"><i></i></span>').insertAfter(this),e=0,f=c.minHeight,g=this;b.bind("mousedown",{el:this},h)})}}(jQuery);