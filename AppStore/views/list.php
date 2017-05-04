<div class="col-mb-12 typecho-list">
    <h4 class="typecho-list-table-title">已安装的插件</h4>
    <div class="typecho-table-wrap">
        <?php if ($result): ?>
            <table class="typecho-list-table">
                <thead>
                    <?php include 'row-title.php'; ?>
                </thead>
                <tbody>
                    <?php foreach ($result->packages as $plugin):
                        if ($plugin->existed) {
                            include 'row.php';
                        }
                    endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="message" style="width:20em;text-align: center;margin:0 auto">
                <h3 style="font-size: 2em">没有找到任何插件</h3>
            </div>
        <?php endif; ?>
    </div>
    <h4 class="typecho-list-table-title">未安装的插件</h4>
    <div class="typecho-table-wrap">
        <?php if ($result): ?>
            <table class="typecho-list-table">
                <thead>
                    <?php include 'row-title.php'; ?>
                </thead>
                <tbody>
                    <?php foreach ($result->packages as $plugin):
                        if (!$plugin->existed) {
                            include 'row.php';
                        }
                    endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="message" style="width:20em;text-align: center;margin:0 auto">
                <h3 style="font-size: 2em">没有找到任何插件</h3>
            </div>
        <?php endif; ?>
    </div>
</div>