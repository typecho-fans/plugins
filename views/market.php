<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();
    include TYPEHO_ADMIN_PATH.'common.php';
    $menu->title = _t('应用商店');
    include TYPEHO_ADMIN_PATH.'header.php';
    include TYPEHO_ADMIN_PATH.'menu.php';
?>
<link rel="stylesheet" href="<?php echo $options->pluginUrl('AppStore/static/css/font-awesome.min.css'); ?>">
<style>
    .as-description {
        height: 4.2em;
        overflow: hidden;
    }
    .as-status {
        float:right;
        margin-right: 1em;
    }
    .as-status i {
        color: #ccc;
        margin: 0 0.2em;
        font-size: 1.5em;
    }
    .as-status i.active {
        color: green
    }
</style>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2><?php echo $menu->title; ?> <small><cite>The missing plugins store for Typecho</cite></small></h2>
            </div>
        </div>
        <div class="colgroup typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <?php if ($result): ?>
                    <?php foreach ($result->packages as $plugin): ?>
                    <div class="col-mb-12 col-tb-3 as-card" data-name="<?php echo $plugin->name; ?>" data-existed="<?php echo $plugin->existed ?>">
                        <h3><?php echo $plugin->name; ?></h3>
                        <p class="as-description" title="<?php echo $plugin->versions[0]->description; ?>">
                            <?php echo $plugin->versions[0]->description; ?>
                        </p>
                        <p class="as-author">
                            <?php echo _t('作者'); ?>:
                            <cite><?php echo $plugin->versions[0]->author; ?></cite>
                        </p>
                        <p class="as-versions">
                            <?php echo _t('版本'); ?>:
                            <select class="as-version-selector">
                                <?php foreach ($plugin->versions as $version): ?>
                                    <option value="<?php echo $version->version; ?>" data-activated="<?php echo $version->activated; ?>" data-author="<?php echo $version->author; ?>" data-require="<?php echo $version->require; ?>" data-description="<?php echo $version->description; ?>"><?php echo $version->version; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="as-require">
                            <?php echo _t('版本要求'); ?>:
                            <cite><?php echo $plugin->versions[0]->require; ?></cite>
                        </p>
                        <p class="as-operations">
                            <button class="btn-s as-install"><?php echo _t('安装'); ?></button>
                            <span class="as-status" style="display:none">
                                <i class="fa fa-meh-o as-existed active"></i>
                                <i class="fa fa-check-circle as-activated"></i>
                            </span>
                        </p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="message" style="width:20em;text-align: center;margin:0 auto">
                        <p><i class="fa fa-frown-o" style="font-size: 5em"></i></p>
                        <h3>没有找到任何插件</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
    include TYPEHO_ADMIN_PATH.'copyright.php';
    include TYPEHO_ADMIN_PATH.'common-js.php';
    include 'js.php';
    include TYPEHO_ADMIN_PATH.'footer.php';
?>
