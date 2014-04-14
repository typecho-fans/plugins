/**
 * modalEffects.js v1.0.0
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright 2013, Codrops
 * http://www.codrops.com
 */
var ModalEffects=function(){function a(){var a=document.querySelector(".md-overlay");[].slice.call(document.querySelectorAll(".md-trigger")).forEach(function(b){function f(a){classie.remove(d,"md-show"),a&&classie.remove(document.documentElement,"md-perspective")}function g(){f(classie.has(b,"md-setperspective"))}var d=document.querySelector("#"+b.getAttribute("data-modal")),e=d.querySelector(".md-close");b.addEventListener("click",function(){classie.add(d,"md-show"),a.removeEventListener("click",g),a.addEventListener("click",g),classie.has(b,"md-setperspective")&&setTimeout(function(){classie.add(document.documentElement,"md-perspective")},25)}),e.addEventListener("click",function(a){a.stopPropagation(),g()})})}a()}();