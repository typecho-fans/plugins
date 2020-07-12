/* http://www.kryogenix.org/code/browser/searchhi/ */
/* Modified 20021006 to fix query string parsing and add case insensitivity */
/* Modified 20070316 to stop highlighting inside nosearchhi nodes */
/* Modified 20081217 to do in-page searching and wrap up in an object */
/* Modified 20081218 to scroll to first hit like 
   http://www.woolyss.free.fr/js/searchhi_Woolyss.js and say when not found */

searchhi = {
  highlightWord: function(node,word) {
    // Iterate into this nodes childNodes
    if (node.hasChildNodes) {
	    for (var hi_cn=0;hi_cn<node.childNodes.length;hi_cn++) {
		    searchhi.highlightWord(node.childNodes[hi_cn],word);
	    }
    }

    // And do this node itself
    if (node.nodeType == 3) { // text node
	    tempNodeVal = node.nodeValue.toLowerCase();
	    tempWordVal = word.toLowerCase();
	    if (tempNodeVal.indexOf(tempWordVal) != -1) {
		    var pn = node.parentNode;
		    // check if we're inside a "nosearchhi" zone
		    var checkn = pn;
		    while (checkn.nodeType != 9 && 
		    checkn.nodeName.toLowerCase() != 'body') { 
		    // 9 = top of doc
			    if (checkn.className.match(/\bnosearchhi\b/)) { return; }
			    checkn = checkn.parentNode;
		    }
		    if (pn.className != "searchword") {
			    // word has not already been highlighted!
			    var nv = node.nodeValue;
			    var ni = tempNodeVal.indexOf(tempWordVal);
			    // Create a load of replacement nodes
			    var before = document.createTextNode(nv.substr(0,ni));
			    var docWordVal = nv.substr(ni,word.length);
			    var after = document.createTextNode(nv.substr(ni+word.length));
			    var hiwordtext = document.createTextNode(docWordVal);
			    var hiword = document.createElement("span");
			    hiword.className = "searchword";
			    hiword.appendChild(hiwordtext);
			    pn.insertBefore(before,node);
			    pn.insertBefore(hiword,node);
			    pn.insertBefore(after,node);
			    pn.removeChild(node);
			    searchhi.found += 1;
			    if (searchhi.found == 1) pn.scrollIntoView();
		    }
	    }
    }
  },

  googleSearchHighlight: function( str ) {
//    var ref = document.referrer;
//    if (ref.indexOf('?') == -1) return;
//    var qs = ref.substr(ref.indexOf('?')+1);
//    var qsa = qs.split('&');
//    for (var i=0;i<qsa.length;i++) {
//	    var qsip = qsa[i].split('=');
//      if (qsip.length == 1) continue;
//      if (qsip[0] == 'q' || qsip[0] == 'p') { // q= for Google, p= for Yahoo
//		    var wordstring = unescape(qsip[1].replace(/\+/g,' '));
//		    searchhi.process(wordstring);
//      }
//      if (qsip[0] == 'wd') { // wd= for baidu
//		if(qsip[1]=='') continue;
//		var xx=new GB2312UTF8(); 
//        var wordstring=xx.Gb2312ToUtf8( qsip[1].replace(/\+/g,' ')); 
//		searchhi.process(wordstring);
////		execScript('wd = DeCodeAnsi("'+qsip[1]+'")','vbscript');
////		words = decodeURI(wd.replace(/\+/g,' ')).split(/\s+/);
////		for (w=0;whighlightWord(document.getElementsByTagName("body")[0],words[w]);
//	  }
//    }
    if(str=='')return;
    var wordstring = unescape( str.replace(/\+/g,' '));
	searchhi.process(wordstring);
  },
  
  process: function(wordstring) {
	
    searchhi.found = 0;
    var words = wordstring.split(/\s+/);
    for (w=0;w<words.length;w++) {
	    searchhi.highlightWord(document.getElementsByTagName("body")[0],words[w]);
    }
    if (searchhi.found === 0) {
      searchhi.nohits();
    }
  },
  
  nohits: function() {
  },
  
  init: function() {
    if (!document.createElement || !document.getElementsByTagName) return;
    // hook up forms of type searchhi
    var frms = document.getElementsByTagName("form");
    for (var i=0; i<frms.length; i++) {
      if (frms[i].className.match(/\bsearchhi\b/)) {
        frms[i].onsubmit = function() {
          var inps = this.getElementsByTagName("input");
          for (var j=0; j<inps.length; j++) {
            if (inps[j].type == "text") {
              searchhi.process(inps[j].value);
              return false;
            }
          }
        };
      }
    }
    // highlight search engine referrer results
    searchhi.googleSearchHighlight(httpd_referer);
  }
};

(function(i) {var u =navigator.userAgent;var e=/*@cc_on!@*/false; var st =
setTimeout;if(/webkit/i.test(u)){st(function(){var dr=document.readyState;
if(dr=="loaded"||dr=="complete"){i()}else{st(arguments.callee,10);}},10);}
else if((/mozilla/i.test(u)&&!/(compati)/.test(u)) || (/opera/i.test(u))){
document.addEventListener("DOMContentLoaded",i,false); } else if(e){     (
function(){var t=document.createElement('doc:rdy');try{t.doScroll('left');
i();t=null;}catch(e){st(arguments.callee,0);}})();}else{window.onload=i;}})(searchhi.init);
