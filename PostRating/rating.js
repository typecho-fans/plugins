/**
 * Typecho PostRating v1.0.1 by Willin Kan.
 * URI: http://kan.willin.org/typecho/
 */

// base64 decoder - 將 PHP base64_encode() 所編碼的 base64 轉碼為中文 utf8
var base64DecodeChars = new Array(
-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
-1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63,
52, 53, 54, 55, 56, 57, 58, 59, 60, 61, -1, -1, -1, -1, -1, -1,
-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14,
15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, -1, -1, -1, -1, -1,
-1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1, -1, -1);

function base64decode(str) {
  var c1, c2, c3, c4, out = '', i = 0, len = str.length;
  while(i < len) {

    /* c1 */
    do {
      c1 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
    } while(i < len && c1 == -1);
    if(c1 == -1) break;

    /* c2 */
    do {
      c2 = base64DecodeChars[str.charCodeAt(i++) & 0xff];
    } while(i < len && c2 == -1);
    if(c2 == -1) break;
    out += String.fromCharCode((c1 << 2) | ((c2 & 0x30) >> 4));

    /* c3 */
    do {
      c3 = str.charCodeAt(i++) & 0xff;
      if(c3 == 61) return out;
      c3 = base64DecodeChars[c3];
    } while(i < len && c3 == -1);
    if(c3 == -1) break;
    out += String.fromCharCode(((c2 & 0XF) << 4) | ((c3 & 0x3C) >> 2));

    /* c4 */
    do {
      c4 = str.charCodeAt(i++) & 0xff;
      if(c4 == 61) return out;
      c4 = base64DecodeChars[c4];
    } while(i < len && c4 == -1);
    if(c4 == -1) break;
    out += String.fromCharCode(((c3 & 0x03) << 6) | c4);
  }
  return out;
}

// utf8 to utf16 - 因 javascript 是用 utf16
function utf8to16(str) {
  var c, char2, char3, out = '', i = 0, len = str.length;
  while(i < len) {
    c = str.charCodeAt(i++);
    switch(c >> 4) {
      case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7:
        out += str.charAt(i-1);
        break;
      case 12: case 13:
        char2 = str.charCodeAt(i++);
        out += String.fromCharCode(((c & 0x1F) << 6) | (char2 & 0x3F));
        break;
      case 14:
        char2 = str.charCodeAt(i++);
        char3 = str.charCodeAt(i++);
        out += String.fromCharCode((((c & 0x0F) << 12) | (char2 & 0x3F) << 6) | ((char3 & 0x3F) << 0));
        break;
    }
  }
  return out;
}

var i = 0, got = -1, len = document.getElementsByTagName('script').length;
while ( i <= len && got == -1){
  var js_url = document.getElementsByTagName('script')[i].src,
    got = js_url.indexOf('rating.js'); i++ ;
}
var hc = js_url.substring(js_url.lastIndexOf('?hc=')+4, js_url.lastIndexOf('&ac=')),
    ac = js_url.substring(js_url.lastIndexOf('&ac=')+4, js_url.lastIndexOf('&f1=')),
    ajax_url = js_url.replace('rating.js', 'rating.php');

// 中文變量
f1 = js_url.lastIndexOf('f1=') > -1 ? utf8to16(base64decode(js_url.substring(js_url.lastIndexOf('&f1=')+4, js_url.lastIndexOf('&f2=')))) : '';
f2 = js_url.lastIndexOf('f2=') > -1 ? utf8to16(base64decode(js_url.substring(js_url.lastIndexOf('&f2=')+4))) : '';

jQuery.noConflict();
jQuery(document).ready(function($) {
var ok = $('.r_label').length ? 0 : 1;

// mouseover
$('.r_img img').mouseover(function() {
  if (ok) return;
  $(this).prevAll().andSelf().css({'background':hc});
  $(this).nextAll().css({'background':'#fff'});
  $('.r_img img').css({'cursor':'pointer'});

// moueout
}).mouseout(function() {
  if (ok) return;
  $('.r_img img').css({'background':''});

// click
}).click(function() {
  if (ok) return;
  $('.r_img img').css({'cursor':'auto'});
  rating = $(this).attr('class').match(/\d+/);
  cid = $('.respond').attr('id').match(/\d+/);

  $('.postrating').contents().fadeOut(100, function(){
    if (ok) return;
    ok = 1;

    $('.r_info').length ? (
      num = $('.r_info').html().match(/\d+/), $('.r_info').html($('.r_info').html().replace(num, Number(num)+1)),
      ave = $('.r_info b').html().match(/\d+[\.\d]*/), s = Math.round((ave*num + Number(rating))/(Number(num)+1)*100)/100,
      $('.r_info b').html($('.r_info b').html().replace(ave, s.toString()))
    ) : (
      $('.no_rating').html('')
    );
    $('.score_'+rating).prevAll().andSelf().css({'background':ac});
    $('.score_'+rating).nextAll().css({'background':'#fff'});
    if (f1 || f2) $('.r_label').html(f1 + rating + f2);
  });

  $('.postrating').contents().fadeIn();

  // ajax
  $.post(ajax_url, 'rating='+rating+'&cid='+cid

  // for debug
  //,function(msg) {alert("Msg: " + msg)}

  );

}); // end click

}); // end jQ
