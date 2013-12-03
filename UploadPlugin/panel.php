<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 typecho-list">
                <ul class="typecho-option-tabs clearfix">
                    <li <?php if(!isset($request->p)){ ?>class="current"<?php }?>><a href="<?php $options->adminUrl('extending.php?panel=UploadPlugin%2Fpanel.php&'); ?>"><?php _e('插件管理'); ?></a></li>
                    <li <?php if(1 == $request->p){ ?>class="current"<?php }?>><a href="<?php $options->adminUrl('extending.php?panel=UploadPlugin%2Fpanel.php&p=1'); ?>"><?php _e('模板管理'); ?></a></li>
                    <li><?php  _e(' %s选择文件上传%s', '<a href="###" class="upload-file">', '</a>'); ?></li>
                </ul>
                
                <!---插件管理--->
                <?php if(!isset($request->p)): ?>
                <?php Typecho_Widget::widget('Widget_Plugins_List_Deactivated')->to($deactivatedPlugins); ?>
                
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
                                <td><a href="<?php $options->index('/action/upload-plugin?del=' . $deactivatedPlugins->name); ?>" class="operate-delete"><?php _e('删除'); ?></a></td>
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
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'upload-js.php';
include 'footer.php';
?>
