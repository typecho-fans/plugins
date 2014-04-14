<?php
include_once 'common.php';
include 'header.php';
include 'menu.php';
include 'WeChatHelper/Widget/Utils.php';
$currentUrl = Helper::url("WeChatHelper/Page/CustomReply.php");
?>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2>自定义回复</h2>
            </div>
        </div>
        <div class="colgroup typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-8" role="main">
                <div class="typecho-list-operate clearfix">
                    <form action="<?php _e($currentUrl) ?>" method="get">
                    <div class="operate">
                        <label><i class="sr-only">全选</i><input type="checkbox" class="typecho-table-select-all"></label>
                        <div class="btn-group btn-drop">
                            <button class="dropdown-toggle btn-s" type="button"><i class="sr-only">操作</i>选中项 <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                                <li><a lang="你确认要删除这些文章吗?" href="<?php $options->index('/action/WeChat?customreply&do=delete') ?>">删除</a></li>
                            </ul>
                        </div>  
                    </div>
                    <div class="search" role="search">
                        <input type="hidden" class="text-s" value="WeChatHelper/Page/CustomReply.php" name="panel">
                        <input type="text" class="text-s" placeholder="请输入关键字" value="" name="keywords">
                        <select name="type">
                            <option value="">全部类型</option>
                            <?php foreach (Utils::getMsgType() as $key => $value) {?>
                                <option value="<?php _e($key)?>" <?php (isset($request->type) && $request->type == $key) ? _e('selected')  : _e('') ?>><?php _e($value)?></option>
                            <?php } ?>
                        </select>
                        <button type="submit" class="btn-s">筛选</button>
                    </div>
                    </form>
                </div>
                <form method="post" name="manage_categories" class="operate-form">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="5%">
                            <col width="13%">
                            <col width="13%">
                            <col width="60%">
                            <col width="14%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th> </th>
                                <th><?php _e('关键字'); ?></th>
                                <th><?php _e('类型'); ?></th>
                                <th><?php _e('内容'); ?></th>
                                <th><?php _e('状态'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td style="padding:0;height:0;line-height:0;border:none" colspan="5"></td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php Typecho_Widget::widget('WeChatHelper_Widget_CustomReply')->to($customreply); ?>
                            <?php if($customreply->have()): ?>
                                <?php while ($customreply->next()): ?>
                                    <tr id="rid-customreply-<?php $customreply->rid(); ?>">
                                        <td><input type="checkbox" value="<?php $customreply->rid(); ?>" name="rid[]"></td>
                                        <td>
                                            <a href="<?php _e(Helper::url('WeChatHelper/Page/CustomReply.php').'&page='.$customreply->getCurrentPage().'&rid='.$customreply->rid) ?>"><?php $customreply->keywords(); ?></a>
                                        </td>
                                        <td><?php _e(Utils::getMsgType($customreply->type)) ?></td>
                                        <td><?php $customreply->content(); ?></td>
                                        <td><?php $customreply->status ? _e('<span style="color:#070">激活</span>') : _e('<span style="color:#B94A48">冻结</span>'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center"><?php _e('没有任何自定义回复'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </form>
                <div class="typecho-list-operate clearfix">
                    <?php if($customreply->have()): ?>
                    <ul class="typecho-pager">
                        <?php $customreply->pageNav(); ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-mb-12 col-tb-4" role="form">
                <?php Typecho_Widget::widget('WeChatHelper_Widget_CustomReply')->form()->render(); ?>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
(function () {
    $(document).ready(function () {
        $('.typecho-list-table').tableSelectable({
            checkEl     :   'input[type=checkbox]',
            rowEl       :   'tr',
            selectAllEl :   '.typecho-table-select-all',
            actionEl    :   '.dropdown-menu a'
        });

        $('.btn-drop').dropdownMenu({
            btnEl       :   '.dropdown-toggle',
            menuEl      :   '.dropdown-menu'
        });

        $('input[name=type]').click(function() {
            if($("input[name=type]:checked").val() == 'system'){
                var s = $('select[name=syscmdSelect]').find("option:selected");
                $('textarea[name=content]').attr('readonly', true).text(s.text());
                $('input[name=command]').val(s.val());
            }else if($("input[name=type]:checked").val() == 'addons'){
                var s = $('select[name=addonsSelect]').find("option:selected");
                $('textarea[name=content]').attr('readonly', true).text(s.text());
                $('input[name=command]').val(s.val());
            }else{
                $('textarea[name=content]').attr('readonly', false).text('');
                $('input[name=command]').val('');
            }
        });

        $('select[name=syscmdSelect]').change(function(){
            $('textarea[name=content]').text($(this).val());
        })
    })
})();
</script>

<?php include 'footer.php';?>