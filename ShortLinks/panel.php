<?php
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="container typecho-page-main">
            <div class="col-mb-12 typecho-list">
                <div class="typecho-option-tabs">
                    <ul class="typecho-option-tabs clearfix d-flex mb-2">
                        <li class="current">
                            <form class="d-flex" action="<?php $options->index('/action/shortlinks?add'); ?>"
                                  method="post">
                                <div class="input-group mr-2">
                                    <label for="key"><?php _e("KEY"); ?></label>
                                    <input name="key" id="key" type="text" value=""/>
                                </div>
                                <div class="input-group">
                                    <label for="target"><?php _e("目标"); ?></label>
                                    <input name="target" id="target" type="text" value="http://"/>
                                </div>
                                <input type="submit" class="btn btn-s primary" value="<?php _e("添加"); ?>"/>
                            </form>
                        </li>
                        <li class="ml-auto current d-flex">
                            <div class="input-group">
                                <?php $ro = Typecho_Router::get('go'); ?>
                                <label for="links"><?php _e("自定义链接"); ?></label>
                                <input id="links" name="links" value="<?php echo $ro['url'] ?>" type="text">
                            </div>
                            <input type="button" id="qlinks" class="btn btn-s primary" value="<?php _e("修改"); ?>"/>
                        </li>
                    </ul>
                </div>
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="15%"/>
                            <col width="25%"/>
                            <col width="47%"/>
                            <col width="5%"/>
                            <col width="8%"/>
                        </colgroup>
                        <thead>
                        <tr>
                            <th><?php _e('KEY'); ?></th>
                            <th><?php _e('站内链接'); ?></th>
                            <th><?php _e('目标链接'); ?> </th>
                            <th><?php _e('统计'); ?> </th>
                            <th><?php _e('操作'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $page = isset($request->page) ? $request->page : 1; ?>
                        <?php $links = $db->fetchAll($db->select()->from('table.shortlinks')->page($page, 15)->order('table.shortlinks.id', Typecho_Db::SORT_DESC)); ?>
                        <?php foreach ($links as $link): ?>
                            <tr class="even" id="<?php _e($link['id']); ?>">
                                <td>
                                    <?php _e($link['key']); ?>
                                </td>
                                <td>
                                    <?php $rourl = str_replace('[key]', $link['key'], $ro['url']); ?>
                                    <a href="<?php $options->index($rourl); ?>"
                                       target="_blank"><?php $options->index($rourl); ?></a>
                                </td>
                                <td id="e-<?php _e($link['id']); ?>"><?php _e($link['target']); ?></td>
                                <td><?php _e($link['count']); ?></td>
                                <td>
                                    <a href="#<?php _e($link['id']); ?>" class="operate-edit"><?php _e("修改"); ?></a>
                                    <a lang="<?php _e('你确认要删除该链接吗?'); ?>"
                                       href="<?php $options->index('/action/shortlinks?del=' . $link['id']); ?>"
                                       class="operate-delete"><?php _e('删除'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="typecho-pager">
                    <div class="typecho-pager-content">
                        <ul>
                            <?php $total = $db->fetchObject($db->select(array('COUNT(id)' => 'num'))->from('table.shortlinks'))->num; ?>
                            <?php for ($i = 1; $i <= ceil($total / 15); $i++): ?>
                                <li class='current'><a
                                        href="<?php $options->adminUrl('extending.php?panel=ShortLinks%2Fpanel.php&page=' . $i); ?>"
                                        style='cursor:pointer;'
                                        title='<?php _e("第 %s 页", $i); ?>'> <?php _e($i); ?> </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
<style>
    .d-flex {
        display: flex;
        flex-wrap: wrap;
    }

    .ml-auto {
        margin-left: auto;
    }

    .mb-2 {
        margin-bottom: 10px;
    }

    .mr-2 {
        margin-right: 10px;
    }

    .input-group {
        display: flex;
        border: 1px solid #d9d9d6;
        border-radius: .22em;
    }

    .input-group:last-child {
        margin-right: 0;
    }

    .input-group label {
        display: block;
        height: 28px;
        line-height: 28px;
        padding: 0 5px;
        background-color: #e9ecef;
    }

    .input-group input {
        padding: 2px 5px;
        border: 0px;
        background-color: #f7f9fb;
        color: #707680;
    }

</style>
<script type="text/javascript">
    $(document).ready(function () {
        function notice(noticeText, noticeType, noticeClass = '.typecho-head-nav') {
            var head = $(noticeClass),
                p = $('<div class="message popup ' + noticeType + '">' +
                    '<ul><li>' + noticeText + '</li></ul></div>'),
                offset = 0;

            if (head.length > 0) {
                p.insertAfter(head);
                offset = head.outerHeight();
            } else {
                p.prependTo(document.body);
            }

            function checkScroll() {
                if ($(window).scrollTop() >= offset) {
                    p.css({
                        'position': 'fixed',
                        'top': 0
                    });
                } else {
                    p.css({
                        'position': 'absolute',
                        'top': offset
                    });
                }
            }

            $(window).scroll(function () {
                checkScroll();
            });

            checkScroll();

            p.slideDown(function () {
                var t = $(this),
                    color = '#C6D880';

                if (t.hasClass('error')) {
                    color = '#FBC2C4';
                } else if (t.hasClass('notice')) {
                    color = '#FFD324';
                }

                t.effect('highlight', {
                    color: color
                })
                    .delay(5000).fadeOut(function () {
                    $(this).remove();
                });
            });
        }

        $('.operate-edit').click(function () {
            var tr = $(this).parents('tr'), t = $(this), id = tr.attr('id');
            var value = $('#e-' + id).html();
            $('#e-' + id).html('<input type="text" id="t-' + id + '" size="55" value="' + value + '" />  <button type="submit" id="u-' + id + '" class="btn-s primary"><?php _e('确认'); ?></button>  <button type="button" id="c-' + id + '" class="btn-s cancel"><?php _e('取消'); ?></button>');
            $("[href='#" + id + "']").hide();

            //确认
            $('#u-' + id).click(function () {
                $.ajax({
                    url: '<?php $options->index('/action/shortlinks?edit'); ?>',
                    data: 'id=' + id + '&url=' + window.btoa($('#t-' + id).val()),// base64编码url
                    dataType: "json",
                    success: function (data) {
                        if (data === 'success') {
                            $('#e-' + id).html($('#t-' + id).val());
                            $("[href='#" + id + "']").show();
                            notice('<?php _e("链接修改成功！"); ?>', 'success');
                        } else {
                            notice('<?php _e("请输入有效链接！"); ?>', 'error');
                        }
                    }
                });
            });
            //取消
            $('#c-' + id).click(function () {
                $('#e-' + id).html(value);
                $("[href='#" + id + "']").show();
            });
        });

        $('#qlinks').click(function () {
            $.ajax({
                url: '<?php $options->index('/action/shortlinks?resetLink'); ?>',
                data: 'link=' + $('#links').val(),
                dataType: 'json',
                success: function (data) {
                    if ('success' === data) {
                        location.reload();
                    }
                }
            });
        });
    });
</script>
