/*!
 * classie - class helper functions
 * from bonzo https://github.com/ded/bonzo
 *
 * classie.has( elem, 'my-class' ) -> true/false
 * classie.add( elem, 'my-new-class' )
 * classie.remove( elem, 'my-unwanted-class' )
 * classie.toggle( elem, 'my-class' )
 */

/*jshint browser: true, strict: true, undef: true */
/*global define: false */

!function(a){"use strict";function b(a){return new RegExp("(^|\\s+)"+a+"(\\s+|$)")}function f(a,b){var f=c(a,b)?e:d;f(a,b)}var c,d,e,g;"classList"in document.documentElement?(c=function(a,b){return a.classList.contains(b)},d=function(a,b){a.classList.add(b)},e=function(a,b){a.classList.remove(b)}):(c=function(a,c){return b(c).test(a.className)},d=function(a,b){c(a,b)||(a.className=a.className+" "+b)},e=function(a,c){a.className=a.className.replace(b(c)," ")}),g={hasClass:c,addClass:d,removeClass:e,toggleClass:f,has:c,add:d,remove:e,toggle:f},"function"==typeof define&&define.amd?define(g):a.classie=g}(window);
