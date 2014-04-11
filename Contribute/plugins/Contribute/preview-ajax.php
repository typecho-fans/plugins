<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script>
$(function() {
  var trigger = $('.md-trigger');
  var content = $('.md-content-main');
  var title = $('.md-content h1');

  trigger.click(function(e) {
    e.preventDefault();
    var input = $(this).parent().prev().children();
    var postTitle = $(this).parent().children().first();
    $.ajax({
        url: "<?php $options->index('/action/contribute?preview'); ?>",
        type: "POST",
        data: "cid=" + input.val(),
        async: false,
        success: function(data) {
            title.text(postTitle.text());
            content.html(data);
        }
    });
  });
});
</script>
