<?php
include_once 'common.php';
include 'header.php';
include 'menu.php';
$currentUrl = Helper::url("WeChatHelper/Page/Menus.php");
?>
<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?php _e($menu->title);?><a href="<?php _e($currentUrl) ?>">新增</a></h2>
        </div>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-8" role="main">
                <div class="typecho-list-operate clearfix">
                    <form action="<?php _e($currentUrl) ?>" method="get">
                        <div class="operate">
                            <label><i class="sr-only">全选</i><input type="checkbox" class="typecho-table-select-all"></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only">操作</i>选中项 <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="如果你勾选了一级菜单，那么也会删除菜单下所有的二级菜单，你确认要删除这些自定义菜单吗？" href="<?php $security->index('/action/WeChat?menus&do=delete') ?>">删除</a></li>
                                </ul>
                            </div>  
                        </div>
                        <div class="search" role="search">
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only">操作</i>微信接口操作 <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="你确认要创建这些自定义菜单吗？" href="<?php $security->index('/action/WeChat?menus&do=create') ?>">创建自定义菜单</a></li>
                                    <li><a lang="你确认要已存在的自定义菜单吗？" href="<?php $security->index('/action/WeChat?menus&do=remove') ?>">删除自定义菜单</a></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>

                <form method="post" name="manage_categories" class="operate-form">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="5%">
                            <col width="25%">
                            <col width="15%">
                            <col width="45%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th></th>
                                <th><?php _e('标题'); ?></th>
                                <th><?php _e('类型'); ?></th>
                                <th><?php _e('Key / URL'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php Typecho_Widget::widget('WeChatHelper_Widget_Menus')->to($menus);?>
                            <?php if($menus->have()): ?>
                                <?php while ($menus->next()): ?>
                                    <tr id="menus-mid-<?php $menus->mid(); ?>" <?php _e($menus->tr) ?>>
                                        <td><input type="checkbox" value="<?php _e($menus->mid); ?>" name="mid[]" /><input type="hidden" value="<?php _e($menus->level); ?>" name="level[]" /></td>
                                        <td><?php _e($menus->levelVal) ?> <a href="<?php _e($currentUrl.'&mid='.$menus->mid) ?>"><?php _e($menus->name) ?></a></td>
                                        <td><?php _e($menus->type) ?></td>
                                        <td><?php _e($menus->value) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center"><?php _e('没有任何用户'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </form>
            </div>
            <div class="col-mb-12 col-tb-4" role="form">
                <?php Typecho_Widget::widget('WeChatHelper_Widget_Menus')->form()->render(); ?>
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
            actionEl    :   '.dropdown-menu a,button.btn-operate'
        });

        $('.btn-drop').dropdownMenu({
            btnEl       :   '.dropdown-toggle',
            menuEl      :   '.dropdown-menu'
        });
    })
})();
</script>

<?php include 'footer.php';?>