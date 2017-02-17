<?php
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 typecho-list">
                <div class="clearfix">
                 <ul class="typecho-option-tabs right">
                      <li>插件/主题地址，仅支持zip压缩包：<input type="text" id="adrs" style="padding: 5px;width: 350px;" /></li>
                      <li><button type="submit" id="inst" class="btn-s"><?php _e('安装'); ?></button></li>
                </ul>
                
                <ul class="typecho-option-tabs">
                    <li <?php if(!isset($request->p)){ ?>class="current"<?php }?>><a href="<?php $options->adminUrl('extending.php?panel=UploadPlugin%2Fpanel.php'); ?>"><?php _e('插件管理'); ?></a></li>
                    <li <?php if(1 == $request->p){ ?>class="current"<?php }?>><a href="<?php $options->adminUrl('extending.php?panel=UploadPlugin%2Fpanel.php&p=1'); ?>"><?php _e('模板管理'); ?></a></li>
                    <li><a href='###'><span id="singleupload">上传插件/主题</span></a></li>                
                 </ul>
                </div>
                
                <!---插件管理--->
                <?php if(!isset($request->p)): ?>
                <?php Typecho_Widget::widget('Widget_Plugins_List@unactivated', 'activated=0')->to($deactivatedPlugins); ?>
                
                <h4 class="typecho-list-table-title"><?php _e('可删除的插件'); ?></h4>
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="25%"/>
                            <col width="45%"/>
                            <col width="8%"/>
                            <col width="10%"/>
                            <col width=""/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?php _e('名称'); ?></th>
                                <th><?php _e('描述'); ?></th>
                                <th><?php _e('版本'); ?></th>
                                <th><?php _e('作者'); ?></th>
                                <th class="typecho-radius-topright"><?php _e('操作'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($deactivatedPlugins->have()): ?>
                            <?php while ($deactivatedPlugins->next()): ?>
                            <tr id="plugin-<?php $deactivatedPlugins->name(); ?>">
                                <td><?php $deactivatedPlugins->title(); ?></td>
                                <td><?php $deactivatedPlugins->description(); ?></td>
                                <td><?php $deactivatedPlugins->version(); ?></td>
                                <td><?php echo empty($deactivatedPlugins->homepage) ? $deactivatedPlugins->author : '<a href="' . $deactivatedPlugins->homepage
                                . '">' . $deactivatedPlugins->author . '</a>'; ?></td>
                                <td><a lang="<?php _e('你确认要删除 %s 插件吗?', $deactivatedPlugins->name); ?>" href="<?php $options->index('/action/upload-plugin?del=' . $deactivatedPlugins->name); ?>" class="operate-delete"><?php _e('删除'); ?></a></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                            	<td colspan="5"><h6 class="typecho-list-table-title"><?php _e('没有可以删除的插件，请在删除前禁用拟删除的插件。'); ?></h6></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
              <?php Typecho_Widget::widget('Widget_Plugins_List@activated', 'activated=1')->to($activatedPlugins); ?>
                <?php if ($activatedPlugins->have() || !empty($activatedPlugins->activatedPlugins)): ?>
                <h4 class="typecho-list-table-title"><?php _e('启用的插件'); ?></h4>
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table deactivate">
                        <colgroup>
                            <col width="25%"/>
                            <col width="45%"/>
                            <col width="8%"/>
                            <col width="10%"/>
                            <col width=""/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?php _e('名称'); ?></th>
                                <th><?php _e('描述'); ?></th>
                                <th><?php _e('版本'); ?></th>
                                <th><?php _e('作者'); ?></th>
                                <th><?php _e('操作'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($activatedPlugins->next()): ?>
                            <tr id="plugin-<?php $activatedPlugins->name(); ?>">
                                <td><?php $activatedPlugins->title(); ?>
                                <?php if (!$activatedPlugins->dependence): ?>
                                <img src="<?php $options->adminUrl('images/notice.gif'); ?>" title="<?php _e('%s 无法在此版本的typecho下正常工作', $activatedPlugins->title); ?>" alt="<?php _e('%s 无法在此版本的typecho下正常工作', $activatedPlugins->title); ?>" class="tiny" />
                                <?php endif; ?>
                                </td>
                                <td><?php $activatedPlugins->description(); ?></td>
                                <td><?php $activatedPlugins->version(); ?></td>
                                <td><?php echo empty($activatedPlugins->homepage) ? $activatedPlugins->author : '<a href="' . $activatedPlugins->homepage
                                . '">' . $activatedPlugins->author . '</a>'; ?></td>
                                <td>
                                    <?php if ($activatedPlugins->activate || $activatedPlugins->deactivate || $activatedPlugins->config || $activatedPlugins->personalConfig): ?>
                                        <?php if ($activatedPlugins->activated): ?>                                           
                                            <a lang="<?php _e('你确认要禁用插件 %s 吗?', $activatedPlugins->name); ?>" href="<?php $options->index('/action/plugins-edit?deactivate=' . $activatedPlugins->name); ?>"><?php _e('禁用'); ?></a>
                                        <?php else: ?>
                                            <a href="<?php $options->index('/action/plugins-edit?activate=' . $activatedPlugins->name); ?>"><?php _e('启用'); ?></a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="important"><?php _e('即插即用'); ?><a href="<?php $options->index('/action/upload-plugin?del=' . $activatedPlugins->name); ?>" class="operate-delete"><?php _e('删除'); ?></a></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if (!empty($activatedPlugins->activatedPlugins)): ?>
                            <?php foreach ($activatedPlugins->activatedPlugins as $key => $val): ?>
                            <tr>
                            <td><?php echo $key; ?></td>
                            <td colspan="3"><span class="warning"><?php _e('此插件文件已经损坏或者被不安全移除, 强烈建议你禁用它'); ?></span></td>
                            <td><a lang="<?php _e('你确认要禁用插件 %s 吗?', $key); ?>" href="<?php $options->index('/action/plugins-edit?deactivate=' . $key); ?>"><?php _e('禁用'); ?></a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <h4 class="typecho-list-table-title"><?php _e('可删除的模板'); ?></h4>
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="15%"/>
                            <col width="50%"/>
                            <col width="8%"/>
                            <col width="15%"/>
                            <col width=""/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?php _e('名称'); ?></th>
                                <th><?php _e('描述'); ?></th>
                                <th><?php _e('版本'); ?></th>
                                <th><?php _e('作者'); ?></th>
                                <th class="typecho-radius-topright"><?php _e('操作'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php Typecho_Widget::widget('Widget_Themes_List')->to($themes); ?>
                            <?php if( $themes->length >= 2 ):?>
                            <?php while($themes->next()): ?>
                            <?php if($themes->activated)  continue; ?>
                                <tr id="theme-<?php $themes->name(); ?>">
                                    <td><?php $themes->name(); ?></td>
                                    <td><?php echo nl2br($themes->description); ?></td>                                    
                                    <td><?php $themes->version(); ?></td>
                                    <td><?php echo empty($themes->homepage) ? $themes->author : '<a href="' . $themes->homepage
                                . '">' . $themes->author . '</a>'; ?></td>
                                    <td><a lang="<?php _e('你确认要删除 %s 模板吗?', $themes->name); ?>" class="operate-delete" href="<?php $options->index('/action/upload-plugin?delTheme=' . $themes->name); ?>"><?php _e('删除'); ?></a></td>
                                </tr> 
                            <?php endwhile; ?>
                            <?php else: ?>
				<tr>
                                    <td colspan="5"><h6 class="typecho-list-table-title"><?php _e('没有可以删除的模板'); ?></h6></td>
				</tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <!---模板管理结束--->
            </div>
        </div>
    </div>
    <div align="center" id="loading" style=" z-index: 9999;padding-top: 30px; display: none;  position: absolute; width: 200px;height: 100px;background: #FFF; border:solid 1px #6e8bde; ">        
        <img src="<?php $options->pluginUrl('/UploadPlugin/load.gif');?>" />
        <p>正在拼命安装...</p>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'upload-js.php';
?>
<script type="text/javascript">
$(document).ready(function () {
    $('.operate-delete').click(function () {
        var t = $(this), href = t.attr('href'), tr = t.parents('tr');

        if (confirm(t.attr('lang'))) {
            tr.fadeOut(function () {
                window.location.href = href;
            });
        }

        return false;
    });
});
</script>
<?php
include 'footer.php';
?>
