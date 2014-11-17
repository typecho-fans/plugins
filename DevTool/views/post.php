<?php
include TYPEHO_ADMIN_PATH . 'common.php';
$menu->title = _t(DevTool_Plugin::NAME);
include TYPEHO_ADMIN_PATH . 'header.php';
include TYPEHO_ADMIN_PATH . 'menu.php';
?>

<div class="main">
    <div class="body container">
        <p>成功生成 <?php echo count($insertId); ?>条文章</p>
    </div>
</div>

<?php
    include TYPEHO_ADMIN_PATH.'copyright.php';
    include TYPEHO_ADMIN_PATH.'common-js.php';
    include TYPEHO_ADMIN_PATH.'footer.php';
?>

