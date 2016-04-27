<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
include 'common.php';

$menu->title = _t('重置密码');

include __ADMIN_DIR__ . '/header.php';
?>
<style>
    body {
        font-family: "Microsoft YaHei", tahoma, arial, 'Hiragino Sans GB', '\5b8b\4f53', sans-serif;
    }
    .typecho-logo {
        margin: 50px 0 30px;
        text-align: center;
    }
    .typecho-table-wrap {
        padding: 50px 30px;
    }
    .typecho-page-title h2 {
        margin: 0 0 30px;
        font-weight: 500;
        font-size: 20px;
        text-align: center;
    }
    label:after {
        content: " *";
        color: #ed1c24;
    }
    .btn {
        width: 100%;
        height: auto;
        padding: 10px 16px;
        font-size: 18px;
        line-height: 1.33;
    }
</style>
<div class="body container">
    <div class="typecho-logo">
        <h1><a href="<?php $options->siteUrl(); ?>"><?php $options->title(); ?></a></h1>
    </div>

    <div class="row typecho-page-main">
        <div class="col-mb-12 col-tb-6 col-tb-offset-3 typecho-content-panel">
            <div class="typecho-table-wrap">
                <div class="typecho-page-title">
                    <h2>重置密码</h2>
                </div>
                <?php @$this->resetForm()->render(); ?>
            </div>
        </div>
    </div>
</div>
<?php
include __ADMIN_DIR__ . '/common-js.php';
?>
<script>
</script>
<?php
include __ADMIN_DIR__ . '/footer.php';
?>
