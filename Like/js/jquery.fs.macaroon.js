/* 
 * Macaroon v3.0.3 - 2014-02-05 
 * A jQuery plugin for simple access to browser cookies. Part of the Formstone Library. 
 * http://formstone.it/macaroon/ 
 * 
 * Copyright 2014 Ben Plum; MIT Licensed 
 */ 

!function(a){"use strict";function b(b,c,d){var f=new Date;d=a.extend({},e,d),d.expires&&f.setTime(f.getTime()+d.expires);var g=d.expires?"; expires="+f.toGMTString():"",h=d.path?"; path="+d.path:"",i=d.domain?"; domain="+d.domain:"";document.cookie=b+"="+c+g+i+h}function c(a){for(var b=a+"=",c=document.cookie.split(";"),d=0;d<c.length;d++){for(var e=c[d];" "===e.charAt(0);)e=e.substring(1,e.length);if(0===e.indexOf(b))return e.substring(b.length,e.length)}return null}function d(a){b(a,"FALSE",{expires:-6048e5})}var e={domain:null,expires:6048e5,path:null};a.macaroon=function(f,g,h){if("object"==typeof f)return e=a.extend(e,f),null;if(h=a.extend({},e,h),"undefined"!=typeof f){if("undefined"==typeof g)return c(f);null===g?d(f):b(f,g,h)}}}(jQuery,window);
