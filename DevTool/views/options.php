<?php
include TYPEHO_ADMIN_PATH . 'common.php';
$menu->title = _t(DevTool_Plugin::NAME);
include TYPEHO_ADMIN_PATH . 'header.php';
include TYPEHO_ADMIN_PATH . 'menu.php';
?>

<style type="text/css">
    .serialize{color: blue; cursor: pointer;}
    .org-value{display: none;}
</style>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2><?php echo $menu->title; ?></h2>
            </div>
        </div>

        <div class="row typecho-page-main" role="form">
            <div class="col-mb-12 typecho-list">
                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <a target="_self" href="<?php $options->adminUrl('dev-tool/options'); ?>?keywords=plugin"><?php _e('插件相关'); ?></a>
                        <a target="_self" href="<?php $options->adminUrl('dev-tool/options'); ?>?keywords=routingTable"><?php _e('路由'); ?></a>
                        <a target="_self" href="<?php $options->adminUrl('dev-tool/options'); ?>?keywords=panel"><?php _e('Panel'); ?></a>
                        <div class="search" role="search">
                            <?php if ('' != $request->keywords): ?>
                            <a href="<?php $options->adminUrl('dev-tool/options'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                            <?php endif; ?>
                            <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>" name="keywords" />
                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                        </div>
                    </form>
                </div>

                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="25%">
                            <col width="45%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>名称</th>
                                <th>值</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if( $optData ): ?>
                                <?php foreach ($optData as $opt): ?>
                                    <?php $opt = (object)$opt; ?>
                                    <?php if( $request->keywords == '' || false !== stripos($opt->name, $request->keywords )): ?>
                                    <tr>
                                        <td><?php echo $opt->name; ?></td>
                                        <td>
                                        <?php if(preg_match('/(.+)\:(.+)\:\{(.+)\}/', $opt->value) > 0): ?>
                                        <span class="serialize">serialize value</span>
                                        <span class="org-value"><pre><?php print_r(unserialize($opt->value)); ?></pre></span>
                                        <?php else: ?>
                                        <?php echo $opt->value; ?>
                                        <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
    include TYPEHO_ADMIN_PATH.'copyright.php';
    include TYPEHO_ADMIN_PATH.'common-js.php';
    include TYPEHO_ADMIN_PATH.'footer.php';
?>

<script type="text/javascript">
    $(function(){
        $('.serialize').on('click', function(){
            $(this).hide().parent().find('.org-value').show();
        });

        $('.org-value').on('click', function(){
            $(this).hide().parent().find('.serialize').show();
        });
    });
</script>
