<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2>数据库优化面板</h2>
            </div>
        </div>
        <div class="colgroup typecho-page-main" role="main">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2">
                <div class="typecho-table-wrap">
                    <?php if(Typecho_Db::get()->getAdapterName() == 'Mysql'): ?>
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="50%">
                            <col width="25%">
                            <col width="25%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>表名</th>
                                <th>大小</th>
                                <th>冗余</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            function format_size($rawSize) {
                                if ($rawSize / 1048576 > 1) 
                                    return round($rawSize/1048576, 1) . ' MB'; 
                                else if ($rawSize / 1024 > 1) 
                                    return round($rawSize/1024, 1) . ' KB'; 
                                else 
                                    return round($rawSize, 1) . ' bytes';
                            }
                            $sum_data = 0;
                            $sum_free = 0;
                            $config = Typecho_Db::get()->getConfig();
                            $dblist = $db->fetchAll("SHOW TABLE STATUS FROM " . $config[0]->database);
                            ?>
                            <?php foreach($dblist as $row): ?>
                            <?php
                                $sum_data = $sum_data + $row['Data_length'];
                                $sum_free = $sum_free + $row['Data_free'];
                            ?>
                            <tr>
                                <td><?php _e($row['Name']); ?></td>
                                <td><?php _e(format_size($row['Data_length'])); ?></td>
                                <td><?php _e(format_size($row['Data_free'])); ?></td>
                            </tr>
                            <?php endforeach;?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><?php _e('您的数据库已存储了'.format_size($sum_data).'数据，并且存在'.format_size($sum_free).'冗余数据。'); ?></td>
                                <td><button id="optimize" type="submit" class="primary">优化数据</button></td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php else: ?>
                        对不起，本插件仅支持MySql数据库优化！
                    <?php endif; ?>
                </div>           
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
<script type="text/javascript">
$(document).ready(function () {
    $('#optimize').click(function () {
        location = '<?php $options->index("/action/OptimizeDB?optimize"); ?>';
    });
});
</script>