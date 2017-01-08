<?php
$options = Helper::options();
$pic_url = $options->pluginUrl . "/AjaxComments/";
$config  = $options->plugin('AjaxComments');
echo "\n";
?>
<style type="text/css">
#ajax_comment_error, #ajax_comment_loading, #ajax_comment_success {
	padding: 3px 5px 3px 10px;
    font-size: 14px;
	margin: 8px 0;
	width: 215px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
	-khtml-border-radius: 4px;
	border-radius: 4px;
}
#ajax_comment_error{border:1px solid #fbc2c4;background:#fbe3e4;color:#8A1F11}
#ajax_comment_loading{background:#fff6bf;border:1px solid #ffd324;color:#514721}
#ajax_comment_success{background:#e6efc2;border:1px solid #c6d880;color:#264409}
</style>
<script>
(function($){
    var loading_dom = '<div id="ajax_comment_loading"><?php echo $config->loadingLan; ?></div><div id="ajax_comment_error"></div>',
    success_dom = '<div id="ajax_comment_success"><?php echo $config->subSuccess; ?></div>',
    name_err  = '<?php echo $config->errUsername; ?>',
    email_err = '<?php echo $config->errEmail; ?>',
    rule_err  = '<?php echo $config->errRuleEmail; ?>',
    text_err  = '<?php echo $config->errText; ?>';

    $(document).ready(function() {
        $body = (window.opera) ? (document.compatMode == "CSS1Compat" ? $('html') : $('body')) : $('html,body');

        var
        wait_time      = '<?php echo $options->commentsPostIntervalEnable * $options->commentsPostInterval; ?>',
        comments_order = '<?php echo $options->commentsOrder; ?>',
        _comments      = '<?php echo $config->_comments; if (strpos($config->_comments, " ")) echo ":first"; ?>',
        _comment_list  = '<?php echo strtr( $config->_comment_list, array( '.' => 'class="', '#' => 'id="')). '"'; ?>',
        _comment_reply = '<?php echo $config->_comment_reply; ?>',
        _comment_form  = '<?php echo $config->_comment_form; ?>',
        _respond       = '<?php echo $config->_respond; ?>',
        _textarea      = '<?php echo $config->_textarea; ?>',
        _submit        = '<?php echo $config->_submit; ?>',

        comments       = $(_comments),
        comment_form   = $(_comment_form),
        respond        = $(_respond),
        textarea       = $(_comment_form +' '+ _textarea),
        $submit        = $(_comment_form +' '+ _submit),
        parent = '';
        textarea.parent().before(loading_dom);
        $('#ajax_comment_loading').hide();
        err = $('#ajax_comment_error').hide();
        $submit.attr('disabled', false);

        click_bind();

        comment_form.submit(function() {
            err.empty().hide();
            $submit.attr('disabled', true).fadeTo('slow', 0.5);

            /* check */
            if(comment_form.find('#author')[0]) {
                if(comment_form.find('#author').val() == '') {
                    err.html(name_err);
                    err_effect(); return false;
                }
                if(comment_form.find('#mail').val() == '') {
                    err.html(email_err);
                    err_effect(); return false;
                }
                var filter  = /^[^@\s<&>]+@([-a-z0-9]+\.)+[a-z]{2,4}$/i;
                if(!filter.test(comment_form.find('#mail').val())) {
                    err.html(rule_err);
                    err_effect(); return false;
                }
            }
            if(comment_form.find(_textarea).val() == '') {
                err.html(text_err);
                err_effect(); return false;
            }

            $('#ajax_comment_loading').slideDown();

            $.ajax({
                url:  $(this).attr('action'),
                type: $(this).attr('method'),
                data: $(this).serialize(),
                dataType: 'text',
                success: function(data) {
                    $('#ajax_comment_loading').slideUp();

                    try {
                        var pos = data.indexOf("<title>Error");
                        if (pos !== 0 && pos < 100) { // return error
                            var msg = data.match(/<div class="container">([\s\S]+?)<\/div>/);
                            err.html($.trim(msg[1]));
                            err_effect();
                            return false;
                        } else {
                            pos = data.indexOf('<li id="' + parent);
                            data = data.substring(pos, data.indexOf('form', pos));
                            $trgs = data.match(/comment-(\d+)/g);
                            len = $trgs.length; new_id = 0;
                            for (var i = 0; i < len; i++) { // find new id
                                trg = $trgs[i].match(/\d+/); if (Number(trg) > Number(new_id)) new_id = trg;
                            }

                            pos = data.indexOf('<li id="comment-' + new_id);
                            data = data.substring(pos, data.indexOf('<\/li>', pos)) + success_dom + '<\/li>'; //get new comment

                            data = parent != '' ? '<div class="comment-children"><ol class="comment-list">' + data + '</ol></div>' : data;
                            parent != '' ? ( $('#' + parent + ' li').length && comments_order == 'DESC' ? $('#' + parent + ' li:first').before(data) : respond.before(data),
                                    parent = '') : (respond.before('<ol '+ _comment_list + '>' + data + '<\/ol>'));

                            $('#comment-' + new_id).hide().fadeIn(1000);
                            comments.length ? ( n = parseInt(comments.text().match(/\d+/)), comments.text(comments.text().replace( n, n + 1 ))) : 0;

                            $body.animate({ scrollTop: $('#comment-' + new_id).offset().top - 200 }, 900);
                            $('textarea').each(function() {this.value = ''});
                            setTimeout(function() {$('#ajax_comment_success').slideUp();}, 2000);
                        }
                    } catch (e) {
                        alert('Error!\n\n' + e );
                    }

                    TypechoComment.cancelReply();
                    $(_comment_reply + ' a, #cancel-comment-reply-link').unbind('click'); click_bind();//new comment bind
                    $('#author').length ? countdown() : $submit.attr('disabled', false).fadeTo('slow', 1);
                }
            });

            return false;

        });// end comment_form.submit()

        function click_bind() { // bind
            $(_comment_reply + ' a').click(function() { // replay
                $body.animate({ scrollTop: respond.offset().top - 180 }, 400);
                h = $(this)[0].href;
                parent = 'comment-' + h.substring(h.indexOf('replyTo=') + 8, h.indexOf('#'));
                textarea.focus();
            });
            $('#cancel-comment-reply-link').click(function() { // cancle
                parent = '';
            });
        }

        function err_effect() { // error
            err.slideDown();
            setTimeout(function() {$submit.attr('disabled', false).fadeTo('', 1); err.slideUp();}, 2000);
        }

        var wait = wait_time, submit_val = comment_form.find(_submit).val();
        function countdown() {
            wait > 0 ? ($submit.val(wait), wait--, setTimeout(countdown, 1000))
                   : ($submit.val(submit_val).attr('disabled', false).fadeTo('slow', 1), wait = wait_time);
        }
    });
})(jQuery)
</script>
