<?php
include TYPEHO_ADMIN_PATH . 'common.php';
$menu->title = _t('TE应用商店');
include TYPEHO_ADMIN_PATH . 'header.php';
include TYPEHO_ADMIN_PATH . 'menu.php';
?>

<style type="text/css">
.uninstall{
	color: red;
}
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
                        <div class="search" role="search">
                            <?php if ('' != $request->keywords): ?>
                            <a href="<?php $options->adminUrl('extending.php?panel=TeStore%2Fmarket.php'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
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
                            <col width="8%">
                            <col width="10%">
                            <col width="">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>名称</th>
                                <th>描述</th>
                                <th>版本</th>
                                <th>作者</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php if( $pluginInfo ): ?>
                        	<?php foreach( $pluginInfo as $plugin): ?>
                        	<?php if( '' == trim($request->keywords) || false !== stripos($plugin->pluginName, $request->keywords) ): ?>
                            <tr id="plugin-<?php echo strip_tags($plugin->pluginName); ?>" style="" class="plugin">
                                <td><a href="<?php echo $plugin->pluginUrl; ?>"><?php echo strip_tags($plugin->pluginName); ?></a></td>
                                <td><?php echo strip_tags($plugin->desc); ?></td>
                                <td><?php echo strip_tags($plugin->version); ?></td>
                                <td><a href="<?php echo $plugin->site; ?>"><?php echo strip_tags($plugin->author); ?></a></td>
                                <td>
                                	<?php if( in_array($plugin->pluginName, $activatedPlugins) ): ?>
										<span class="uninstall"><?php _e('卸载');?></span>
                                	<?php else: ?>
										<span class="install"><?php _e('安装');?></span>
                                	<?php endif; ?>                                	
                                </td>
                            </tr>
                        	<?php endif; ?>
                        	<?php endforeach; ?>
                        	<?php else: ?>
								<tr>
									<td colspan="5" style="text-align: center; font-weight: bold;">
									<?php echo _t('没有找到任何插件，去试试<a href="/admin/options-plugin.php?config=TeStore">修改设置</a>吧！'); ?>
									</td>
								</tr>
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