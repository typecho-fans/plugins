<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>

<?php
	$page = $pageOrPost;

	$db = Typecho_Db::get();
	$prefix = $db->getPrefix();
	$table = $prefix . 'verion_plugin';
	$rows = $db->fetchAll($db->select()->from($table)->where("cid = ? ", $page->cid)->order('time', Typecho_Db::SORT_DESC));

?>

<div id="tab-verions" class="tab-content hidden p">
	<p><label class="typecho-label" style="color: #e8c957;"><?php _e('提示: 鼠标悬停以显示更多操作'); ?></label></p>
	<p><label class="typecho-label" style="color: #e88657;"><?php _e('注意: 自动保存的版本会在手动保存后被自动删除'); ?></label></p>
	
	<div class="version-plugin-view hidden">
		<div class="version-plugin-view-tip">点击四周的空白处可以退出预览</div>
		<div class="version-plugin-view-container">
			<textarea autocomplete="off" readonly class="w-100 mono pastable version-plugin-text"></textarea>
		</div>
			
	</div>
	
	<div class="typecho-table-wrap version-plugin-table-wrap-narrow">
		<table class="typecho-list-table">
			<colgroup>
				<col width="60%"/>
				<col width="40%"/>
			</colgroup>

			<thead>
				<tr>
					<th><?php _e('时间'); ?></th>
					<th><?php _e('编辑'); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php foreach($rows as $row): ?>
				<?php 
					$_time = date("Y年m月d H:i", $row['time']);
					$_user = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', $row['modifierid']));
					$_artical = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $row['cid']));
					$auto = $row['auto']=='auto';
					
					$hightlight = $auto?'version-plugin-highlight':'';
				?>
					<tr class="version-plugin-row" title="<?php _e($_time."   vid: ".$row['vid']) ?>">
						<td>
							<div class="version-plugin-row-short-time <?php _e($hightlight) ?>" <?php $auto&&false?_e('style="display: inline-block"'):'' ?> >
								<?php _e($_time); ?>
							</div>

							<div class="version-plugin-row-description hidden <?php _e($hightlight) ?>">
							<?php $_content = !$auto?$row['comment']:'自动保存版本'; ?>
								<?php if(!$auto || true): ?>
									<input type="text" <?php if($auto) _e('disabled'); ?> autocomplete="off" rows="1" class="w-100 mono pastable version-plugin-no-listening-input version-plugin-desc-textarea" last="<?php _e($_content); ?>" value="<?php _e($_content); ?>"></input>
								<?php endif; ?>
							</div>
							
						</td>
						<td>
							<div class="version-plugin-modifier">
								<?php _e($_user['screenName']); ?>
							</div>

							<div class="version-plugin-actions"
									artical-name="<?php _e($_artical['title']); ?>"
									version-id="<?php _e($row['vid']); ?>" 
									modifier="<?php _e($_user['screenName']); ?>"
									time="<?php _e($_time); ?>"
									is-auto="<?php _e($row['auto']); ?>"
							>

								<button type="button" class="btn primary version-plugin-btn version-plugin-btn-revert" title="回退到这个版本"><?php _e('回'); ?></button>
								
								<button type="button" class="btn primary version-plugin-btn version-plugin-btn-preview" title="预览这个版本的内容"><?php _e('阅'); ?></button>

								<button type="button" class="btn primary version-plugin-btn version-plugin-btn-delete" title="从数据库删除这个版本"><?php _e('删'); ?></button>

							</div>

						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			
		</table>
	</div>
	
</div>