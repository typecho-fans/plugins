<?php
include_once 'common.php';
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2>高级设置</h2>
            </div>
        </div>
        <div class="colgroup typecho-page-main manage-metas">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2" role="main">
                <?php Typecho_Widget::widget('WeChatHelper_Widget_Config')->deluxeForm()->render(); ?>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>