<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();
    include TYPEHO_ADMIN_PATH.'common.php';
    $menu->title = _t('应用商店');
    include TYPEHO_ADMIN_PATH.'header.php';
    include TYPEHO_ADMIN_PATH.'menu.php';
    list($version, $buildVersion) = explode('/', Typecho_Common::VERSION);
    $typechoVersion = floatval($version);
?>
<style>
    .as-name,
    .as-require,
    .as-author,
    .as-operations {
        white-space: nowrap;
    }
</style>
<?php if ($typechoVersion <= 0.8): ?>
    <div class="main">
        <div class="body container">
            <div class="typecho-page-title">
                <div class="column-24">
                    <h2><?php echo $menu->title; ?> <small><cite>The missing plugins' store for Typecho</cite></small></h2>
                    <div>
                        <a href="https://github.com/chekun/AppStore/issues" target="_blank"><?php echo _t('提建议/吐槽专用'); ?></a>
                    </div>
                </div>
            </div>
            <div class="row typecho-page-main" role="main">
                <?php include 'list.php'; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="main">
        <div class="body container">
            <div class="typecho-page-title">
                <h2>
                    <?php echo $menu->title; ?> <small><cite>The missing plugins' store for Typecho</cite></small>
                    <div style="float:right">
                        <a href="https://github.com/chekun/AppStore/issues" target="_blank"><?php echo _t('提建议/吐槽专用'); ?></a>
                    </div>
                </h2>
            </div>
            <div class="row typecho-page-main" role="main">
                <?php include 'list.php'; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php
    include TYPEHO_ADMIN_PATH.'copyright.php';
    include TYPEHO_ADMIN_PATH.'common-js.php';
    include 'js.php';
    include TYPEHO_ADMIN_PATH.'footer.php';
?>
