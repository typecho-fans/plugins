<?php
include TYPEHO_ADMIN_PATH . 'common.php';
$menu->title = _t(DevTool_Plugin::NAME);
include TYPEHO_ADMIN_PATH . 'header.php';
include TYPEHO_ADMIN_PATH . 'menu.php';
?>

<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2><?php echo $menu->title; ?></h2>
            </div>
        </div>

        <div class="row typecho-page-main" role="form">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2">            
                <ul class="typecho-option" id="typecho-option-item-title-0">
                    <li>
                        <a href="<?php $options->adminUrl('dev-tool/options'); ?>">查看Option数据</a>
                    </li>
                    <li>
                        <a href="<?php $options->adminUrl('dev-tool/post'); ?>">生成文章</a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>

<?php
    include TYPEHO_ADMIN_PATH.'copyright.php';
    include TYPEHO_ADMIN_PATH.'common-js.php';
    include TYPEHO_ADMIN_PATH.'footer.php';
?>

