/*
    Masked Input plugin for jQuery
    Copyright (c) 2007-2013 Josh Bush (digitalbush.com)
    Licensed under the MIT license (http://digitalbush.com/projects/masked-input-plugin/#license)
    Version: 1.3.1
*/
(function($) {
$.fn.resizeable = function (options) {
  var s = $.extend({
      minHeight   :   100,
      afterResize :   null
  }, options);

  return this.each(function () {
      var r = $('<span class="resize"><i></i></span>').insertAfter(this),
          staticOffset, iLastMousePos = 0, iMin = s.minHeight, t = this;

      function startDrag(e) {
          textarea = $(e.data.el);
          textarea.blur();
          iLastMousePos = mousePosition(e).y;

          staticOffset = textarea.height() - iLastMousePos;
          textarea.css('opacity', 0.25);

          $(document).mousemove(performDrag).mouseup(endDrag);
          return false;
      }

      function performDrag(e) {
          var iThisMousePos = mousePosition(e).y,
          iMousePos = staticOffset + iThisMousePos;
          if (iLastMousePos >= (iThisMousePos)) {
              iMousePos -= 5;
          }

          iLastMousePos = iThisMousePos;
          iMousePos = Math.max(iMin, iMousePos);
          textarea.height(iMousePos + 'px');

          if (iMousePos < iMin) {
              endDrag(e);
          }
          return false;
      }

      function endDrag(e) {
          var h = textarea.outerHeight();
          $(document).unbind('mousemove', performDrag).unbind('mouseup', endDrag);

          textarea.css('opacity', 1);
          textarea.focus();
          textarea = null;

          staticOffset = null;
          iLastMousePos = 0;

          if (s.afterResize) {
              s.afterResize.call(t, h);
          }
      }

      function mousePosition(e) {
          return { x: e.clientX + document.documentElement.scrollLeft, y: e.clientY + document.documentElement.scrollTop };
      }

      r.bind('mousedown', {el : this}, startDrag);
  });
};
})(jQuery);