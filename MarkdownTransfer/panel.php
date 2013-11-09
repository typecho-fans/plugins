<?php if(!defined('__DIR__')) exit; ?>
<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <?php if($notice->have() && in_array($notice->noticeType, array('success', 'notice', 'error'))): ?>
        <div class="message <?php $notice->noticeType(); ?> popup">
            <ul>
                <?php $notice->lists(); ?>
            </ul>
        </div>
        <?php endif; ?>
        <div class="col-group">
            <div class="typecho-page-title col-mb-12">
                <h2>Markdown 转换设置</h2>
            </div>
        </div>
        <div class="col-group typecho-page-main" role="form">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2">
                <form action="<?php $options->index('/action/MarkdownTransfer?transform'); ?>" method="post" enctype="application/x-www-form-urlencoded">
                    <ul class="typecho-option" id="typecho-option-item-rewrite-0">
                        <li>
                            <label class="typecho-label">是否使用 Markdown 转换功能</label>
                            <span>
                                <input name="rewrite" type="radio" value="1" id="rewrite-0" checked="true">
                                <label for="rewrite-0">启用</label>
                            </span>
                            <span>
                                <input name="rewrite" type="radio" value="0" id="rewrite-1" disabled="disabled">
                                <label for="rewrite-1">不启用请离开页面</label>
                            </span>
                            <p class="description">因为该功能不是很完善，为了保证十足的安全，请在启用转换功能前，备份你的数据库！</p>
                        </li>
                    </ul>
                    <ul class="typecho-option typecho-option-submit" id="typecho-option-item-submit-1">
                        <li>
                            <button type="submit" class="primary">我还是按捺不住内心的骚动决定要转换啊</button>
                        </li>
                    </ul>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
?>

<?php include 'footer.php'; ?>
