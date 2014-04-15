<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$success = true;
try {
    $dbConfig = $options->plugin('EmlogToTypecho');

    /** 初始化一个db */
    if (Typecho_Db_Adapter_Mysql::isAvailable()) {
        $emlogDb = new Typecho_Db('Mysql', $dbConfig->prefix);
    } else {
        $emlogDb = new Typecho_Db('Pdo_Mysql', $dbConfig->prefix);
    }

    /** 只读即可 */
    $emlogDb->addServer(array(
        'host' => $dbConfig->host,
        'user' => $dbConfig->user,
        'password' => $dbConfig->password,
        'charset' => 'utf8',
        'port' => $dbConfig->port,
        'database' => $dbConfig->database
    ), Typecho_Db::READ);

    $rows = $emlogDb->fetchAll($emlogDb->select()->from('table.options'));
    $static = array();
    foreach ($rows as $row) {
        $static[$row['option_name']] = $row['option_value'];
    }

} catch (Typecho_Db_Exception $e) {
    $success = false;
}

include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="form">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2">
                <?php if ($success): ?>
                <div class="message notice">
                    <form action="<?php $options->index('/action/emlog-to-typecho'); ?>" method="post">
                        <?php _e('我们检测到了 Emlog 系统信息, 点击下方的按钮开始数据转换, 数据转换可能会耗时较长.'); ?>
                        <ul>
                            <li><strong><?php echo $static['blogname']; ?></strong></li>
                            <li><?php echo $static['bloginfo']; ?></li>
                            <li><code><?php echo $static['blogurl']; ?></code></li>
                        </ul>
                        <button type="submit" class="btn primary"><?php _e('开始数据转换 &raquo;'); ?></button>
                    </form>
                </div>
                <?php else: ?>
                <div class="message error">
                    <?php _e('我们在连接到 Emlog 的数据库时发生了错误, 请<a href="%s">重新设置</a>你的信息.',
                        Typecho_Common::url('options-plugin.php?config=EmlogToTypecho', $options->adminUrl)); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>