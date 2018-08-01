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
                <h2><?php _e($menu->title);?></h2>
            </div>
        </div>
        <div class="colgroup typecho-page-main">
            <div class="col-mb-12 typecho-list">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="15%">
                            <col width="15%">
                            <col width="40%">
                            <col width="10%">
                            <col width="10%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?php _e('插件名'); ?></th>
                                <th><?php _e('文件包'); ?></th>
                                <th><?php _e('介绍'); ?></th>
                                <th><?php _e('版本号'); ?></th>
                                <th><?php _e('作者'); ?></th>
                                <th><?php _e('参数'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $addons = Typecho_Widget::widget('WeChatHelper_Widget_Addons')->getAddons();?>
                            <?php if(isset($addons) && $addons != NULL): ?>
                                <?php foreach ($addons as $key => $row) { ?>
                                    <tr id="addons-<?php _e($key); ?>">
                                        <td><?php _e($row['name']); ?></td>
                                        <td><?php _e($row['package']); ?></td>
                                        <td><?php _e($row['description']); ?></td>
                                        <td><?php _e($row['version']); ?></td>
                                        <td><a href="<?php _e($row['link']); ?>" target="_blank"><?php _e($row['author']); ?></a></td>
                                        <td><?php _e($row['param'] == 'true' ? '是' : '否'); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center"><?php _e('没有任何插件扩展'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
