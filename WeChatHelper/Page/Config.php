<?php
include_once 'common.php';
include 'header.php';
include 'menu.php';
$currentUrl = Helper::url("WeChatHelper/Page/Config.php");
$type = $request->type ? $request->type : '1';
?>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2><?php _e($menu->title);?></h2>
            </div>
        </div>
        <div class="colgroup typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2" role="main">
            <ul class="typecho-option-tabs clearfix">
                <li <?php $type == '1' ? _e('class="current"') : ''?>><a href="<?php _e($currentUrl.'&type=1')?>">基础设置</a></li>
                <li <?php $type == '2' ? _e('class="current"') : ''?>><a href="<?php _e($currentUrl.'&type=2')?>">高级功能</a></li>
                <li <?php $type == '3' ? _e('class="current"') : ''?>><a href="<?php _e($currentUrl.'&type=3')?>">第三方平台</a></li>
                <li <?php $type == '4' ? _e('class="current"') : ''?>><a href="<?php _e($currentUrl.'&type=4')?>">积分设置</a></li>
            </ul>
            <?php if($type == '1') : ?>
                <?php Typecho_Widget::widget('WeChatHelper_Widget_Config')->baseForm()->render(); ?>
            <?php elseif($type == '2'): ?>
                <?php Typecho_Widget::widget('WeChatHelper_Widget_Config')->deluxeForm()->render(); ?>
            <?php elseif($type == '3'): ?>
                <?php Typecho_Widget::widget('WeChatHelper_Widget_Config')->thirdPartyForm()->render(); ?>
            <?php elseif($type == '4'): ?>
                <?php Typecho_Widget::widget('WeChatHelper_Widget_Config')->creditForm()->render(); ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>