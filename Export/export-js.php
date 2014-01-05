<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script>
$(document).ready(function () {
    $('#export-plugin .typecho-option-tabs li').click(function() {
        var tabBox = $('#export-plugin div');

        $(this).siblings('li')
        .removeClass('active').end()
        .addClass('active');

        tabBox.siblings('div')
        .addClass('hidden').end()
        .eq($(this).index()).removeClass('hidden');

        return false;
    });
});
</script>
