<?php
error_reporting(E_ALL);
include 'header.php';
include 'menu.php';

date_default_timezone_set('PRC');

$stat = Typecho_Widget::widget('Widget_Stat');

$db = Typecho_Db::get();
$prefix = $db->getPrefix();

//计算分页
$pageSize = 20;
$currentPage = isset($_REQUEST['page']) ? ($_REQUEST['page'] + 0) : 1;

$all = $db->fetchAll($db->select()->from('table.comment_push')
    ->order('table.comment_push.time', Typecho_Db::SORT_DESC));

$pageCount = ceil(count($all) / $pageSize);

$current = $db->fetchAll($db->select()->from('table.comment_push')
    ->page($currentPage, $pageSize)
    ->order('table.comment_push.time', Typecho_Db::SORT_DESC));


?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <div class="typecho-list-operate clearfix">
                    <form method="POST"
                          action="<?php $options->adminUrl('extending.php?panel=CommentPush/Logs.php'); ?>">
                        <div class="search" role="search">


                            <select name="page">
                                <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                                    <option
                                        value="<?php echo $i; ?>"<?php if ($i == $currentPage): ?> selected="true"<?php endif; ?>>
                                        第<?php echo $i; ?>页
                                    </option>
                                <?php endfor; ?>
                            </select>

                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                            <?php if (isset($request->uid)): ?>
                                <input type="hidden" value="<?php echo htmlspecialchars($request->get('uid')); ?>"
                                       name="uid"/>
                            <?php endif; ?>
                        </div>
                    </form>
                </div><!-- end .typecho-list-operate -->
                <form method="post" name="manage_posts" class="operate-form">
                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="15%"/>
                                <col width="15%"/>
                                <col width="15%"/>
                                <col width="25%"/>
                                <col width="15%"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th><?php _e('服务'); ?></th>
                                <th><?php _e('接收方'); ?></th>
                                <th><?php _e('结果'); ?></th>
                                <th><?php _e('错误'); ?></th>
                                <th><?php _e('时间'); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($current as $line): ?>
                                <tr>
                                    <td><?php echo $line['service']; ?></td>
                                    <td>
                                        <span class="show-hide">显示</span>
                                        <span class="org-value">
                                            <pre><?php echo $line['object']; ?></pre>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="show-hide">显示</span>
                                        <span class="org-value">
                                           <pre><?php echo $line['result']; ?></pre>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="show-hide">显示</span>
                                        <span class="org-value">
                                            <pre><?php echo $line['error']; ?></pre>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('Y-m-d H:i:s', $line['time']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form><!-- end .operate-form -->


            </div><!-- end .typecho-list -->
        </div><!-- end .typecho-page-main -->
    </div>
</div>


<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>
<script>
    $(function () {
        var show = $('.show-hide')
        var pre = $('.org-value')

        show.css('color', 'blue');
        show.css('cursor', 'cursor');
        $('.org-value pre').css('background-color', '#E3FFDA');

        pre.hide();

        show.on('click', function () {
            $(this).hide().parent().find('.org-value').show();
        });

        pre.on('click', function () {
            $(this).hide().parent().find('.show-hide').show();
        });
    });
</script>
<?php
include 'footer.php';
?>
