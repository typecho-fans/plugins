<?php
$db = Typecho_Db::get();
// 表前缀
$dbPrefix = $db->getPrefix();
$prefixLength = strlen($dbPrefix);
// 数据表
$tables = array();
$resource = $db->fetchAll($db->query('SHOW TABLES'));
foreach ($resource as $value) {
    foreach ($value as $tableName) {
        if ($dbPrefix == substr($tableName, 0, $prefixLength)) {
            $tables[] = $tableName;
        }
    }
}

// 获取备份目录并设置文件
$exportConfig = $options->plugin('DbManager');
$path = __TYPECHO_ROOT_DIR__ . '/' . trim($exportConfig->path, '/');
if (is_dir($path)) {
    $filePaths = glob($path . '/*.sql', GLOB_BRACE);
    $files = array();
    for ($i = 0; $i < sizeof($filePaths); $i++) {
        $files[$i]['name'] = basename($filePaths[$i]);
        $files[$i]['time'] = date('Y年m月d日', filemtime($filePaths[$i]));
        $files[$i]['size'] = ceil(filesize($filePaths[$i]) / 1024) . ' KB';
    }
}

include_once 'common.php';
include 'header.php';
include 'menu.php';
?>

<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?php _e('数据备份'); ?></h2>
        </div>
        <div class="row typecho-page-main" role="form">
            <div id="dbmanager-plugin" class="col-mb-12 col-tb-8 col-tb-offset-2">
                <ul class="typecho-option-tabs clearfix">
                    <li style="width:33%;" class="active"><a href="#tab-export"><?php _e('备份'); ?></a></li>
                    <li style="width:33%;"><a href="#tab-import" id="tab-files-btn"><?php _e('导入'); ?></a></li>
                    <li style="width:33%;"><a href="#tab-optimize" id="tab-files-btn"><?php _e('优化'); ?></a></li>
                </ul>
                <div id="tab-export" class="tab-content">
                    <form action="<?php $options->index('/action/dbmanager?export'); ?>" method="post" enctype="application/x-www-form-urlencoded">
                        <ul class="typecho-option" id="typecho-option-item-tableSelect-0">
                            <li>
                                <label class="typecho-label" for="tableSelect-0"><?php _e('数据表'); ?></label>
                                <select name="tableSelect[]" id="tableSelect-0" size="10" multiple="multiple" class="w-100 mono" style="height: 100%;">
                                    <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo $table; ?>" selected="selected"><?php echo $table; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </li>
                        </ul>
                        <ul class="typecho-option" id="typecho-option-item-filename-1">
                            <li>
                                <label class="typecho-label" for="filename-0-1"><?php _e('备份文件名'); ?></label>
                                <input id="filename-0-1" name="fileName" type="text" class="w-100" value="<?php echo 'typecho_' . date('YmdHi', Typecho_Date::gmtTime() + (Typecho_Date::$timezoneOffset - Typecho_Date::$serverTimezoneOffset)) . '_' . sprintf('%u', crc32(uniqid())) . '.sql'; ?>">
                                <p class="description"><?php _e('备份文件默认生成在插件的 backup 文件夹下'); ?></p>
                            </li>
                        </ul>
                        <ul class="typecho-option" id="typecho-option-item-bakplace-2">
                            <li>
                                <label class="typecho-label"><?php _e('备份保存'); ?></label>
                                <span>
                                    <input name="bakplace" type="radio" value="0" id="bakplace-0" checked="true">
                                    <label for="bakplace-0"><?php _e('本地'); ?></label>
                                </span>
                                <span>
                                    <input name="bakplace" type="radio" value="1" id="bakplace-1">
                                    <label for="bakplace-1"><?php _e('服务器'); ?></label>
                                </span>
                                <p class="description"></p>
                            </li>
                        </ul>
                        <ul class="typecho-option typecho-option-submit" id="typecho-option-item-submit-3">
                            <li>
                                <button type="submit" class="primary"><?php _e('开始备份'); ?></button>
                            </li>
                        </ul>
                    </form>
                </div>
                <div id="tab-import" class="tab-content hidden">
                    <div class="typecho-list">
                        <div class="typecho-list-operate clearfix">
                            <form method="get">
                                <div class="operate">
                                    <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all"></label>
                                    <div class="btn-group btn-drop">
                                        <button class="dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                        <ul class="dropdown-menu">
                                            <li><a lang="<?php _e('你确认要导入这些备份吗?'); ?>" href="<?php $options->index('/action/dbmanager?import'); ?>"><?php _e('导入'); ?></a></li>
                                            <li><a lang="<?php _e('你确认要删除这些备份吗?'); ?>" href="<?php $options->index('/action/dbmanager?delete'); ?>"><?php _e('删除'); ?></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <form method="post" name="manageBackup" class="operate-form">
                            <div class="typecho-table-wrap">
                                <table class="typecho-list-table">
                                    <colgroup>
                                        <col width="8">
                                        <col width="50%">
                                        <col width="27%">
                                        <col width="15%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th> </th>
                                            <th><?php _e('备份文件'); ?></th>
                                            <th><?php _e('备份时间'); ?></th>
                                            <th><?php _e('文件大小'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!empty($files)): ?>
                                    <?php foreach ($files as $key => $file): ?>
                                        <tr id="bid-<?php echo $key; ?>">
                                            <td><input type="checkbox" value="<?php echo $file['name']; ?>" name="bid[]"></td>
                                            <td><?php echo $file['name']; ?></td>
                                            <td><?php echo $file['time']; ?></td>
                                            <td><?php echo $file['size']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr id="bid-no">
                                            <td></td>
                                            <td colspan="4"><?php _e('暂无备份文件'); ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
                <div id="tab-optimize" class="tab-content hidden">
                    <form action="<?php $options->index('/action/dbmanager?optimize'); ?>" method="post" enctype="application/x-www-form-urlencoded">
                        <ul class="typecho-option" id="typecho-option-item-tableSelect-0">
                            <li>
                                <label class="typecho-label" for="tableSelect-0"><?php _e('数据表'); ?></label>
                                <select name="tableSelect[]" id="tableSelect-0" size="10" multiple="multiple" class="w-100 mono" style="height: 100%;">
                                    <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo $table; ?>" selected="selected"><?php echo $table; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </li>
                        </ul>
                        <ul class="typecho-option typecho-option-submit" id="typecho-option-item-submit-1">
                            <li>
                                <button type="submit" class="primary"><?php _e('开始优化'); ?></button>
                            </li>
                        </ul>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'dbmanager-js.php';
include 'table-js.php';
include 'footer.php';
?>
