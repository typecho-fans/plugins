<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script>
$(document).ready(function () {
    $('#dbmanager-plugin .typecho-option-tabs li').click(function() {
        var tabBox = $('#dbmanager-plugin > div');

        $(this).siblings('li')
        .removeClass('active').end()
        .addClass('active');

        tabBox.siblings('div')
        .addClass('hidden').end()
        .eq($(this).index()).removeClass('hidden');
        console.log($(this).index());

        return false;
    });
});
</script>
