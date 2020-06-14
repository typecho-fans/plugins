<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$storeUrl = Typecho_Common::url(__TYPECHO_ADMIN_DIR__.'te-store/',$this->options->index);
$keywords = htmlspecialchars($this->request->keywords);
$group = htmlspecialchars($this->request->group);
$page = htmlspecialchars($this->request->page);
define('TYPEHO_ADMIN_PATH',__TYPECHO_ROOT_DIR__.__TYPECHO_ADMIN_DIR__);

//异步加载插件列表
if ($this->request->is('action=loadlist')) {
	$this->security->protect();

	$pluginData = $this->getPluginData();
	if ($pluginData) {
		$name = '';
		$pluginDatas = array();
		//筛选关键词
		foreach ($pluginData as $plugin) {
			if (!$keywords || false!==stripos($plugin['pluginName'],$keywords) || false!==stripos($plugin['desc'],$keywords) || false!==stripos(htmlspecialchars_decode(strip_tags($plugin['authorHtml'])),$keywords)) {
				$pluginDatas[] = $plugin;
			}
		}

		$installed = $this->getLocalPlugins();
		$infos = array();
		$pluginIns = array();
		//检测已安装
		foreach ($pluginDatas as $key=>$plugin) {
			if ($infos = $this->getLocalInfos($plugin['pluginName'])) {
				if ($infos[0]==htmlspecialchars_decode(strip_tags($plugin['authorHtml']))) {
					$pluginIns[] = $plugin;
					unset($pluginDatas[$key]);
				}
			}
		}
		$pluginDatas = $group || $group=='installed' ? $pluginIns : $pluginDatas;

		//处理分页
		$pluginData = array_chunk($pluginDatas,20);
		$page = $page && isset($pluginData[$page-1]) ? $page-1 : 0;
		$nav = new Typecho_Widget_Helper_PageNavigator_Box(count($pluginDatas),$page+1,20,
			$storeUrl.'market?'.($keywords ? 'keywords='.$keywords.'&' : '').($group ? 'group='.$group.'&' : '').'page={page}');

		//准备加速用API数据
		if ($this->settings->proxy) {
			$this->ZIP_CDN();
		}
	}
?>
				<?php if ($pluginData || $keywords) : ?>
				<div class="typecho-list-operate clearfix" style="margin-top:0;">
					<ul class="typecho-option-tabs">
						<li<?php if (!$group || $group=='uninstalled') : ?> class="current"<?php endif; ?>><a href="<?php echo $storeUrl.'market'; ?>"><?php _e('未安装'); ?></a></li>
						<li<?php if ($group || $group=='installed') : ?> class="current"<?php endif; ?>><a href="<?php echo $storeUrl.'market?group=installed'; ?>"><?php _e('已安装'); ?></a></li>
						<li><a href="<?php $this->options->adminUrl('plugins.php'); ?>"><?php _e('插件管理'); ?></a></li>
						<li><a href="<?php $this->options->adminUrl('options-plugin.php?config=TeStore'); ?>"><?php _e('设置'); ?></a></li>
						<li><a rel="external noopener" href="https://www.yzmb.me/archives/net/testore-for-typecho" title="<?php _e('查看插件仓库主页说明'); ?>" target="_blank"><?php _e('帮助'); ?></a></li>
					</ul>
					<form method="get">
						<div class="search" role="search">
							<?php if ($keywords) : ?>
							<a href="<?php echo $storeUrl.'market'.($group ? '?group='.$group : ''); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
							<?php endif; ?>
							<input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($keywords); ?>" name="keywords" />
							<input type="hidden" value="<?php echo htmlspecialchars($group); ?>" name="group" />
							<button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
						</div>
					</form>
				</div>
				<?php endif; ?>

				<div class="typecho-table-wrap">
					<table class="typecho-list-table">
						<colgroup>
							<col width="20%">
							<col width="36%">
							<col width="14%">
							<col width="20%">
							<col width="10%">
						</colgroup>
						<thead>
							<tr>
								<th><?php _e('名称 (文档链接)'); ?></th>
								<th><?php _e('简介'); ?></th>
								<th><?php _e('版本'); ?></th>
								<th><?php _e('作者 (主页)'); ?></th>
								<th style="text-align:center;"><?php _e('操作'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ($pluginData) : 
								$mark = '';
								$tefans = false;
								$url = '';
								$version = '';
								$authorHtml = '';
								$author = '';
								$authors = array();
								$update = false;
								$sites = array();
								$uninstall = false;
								foreach ($pluginData[$page] as $plugin) : 
									$name = $plugin['pluginName'];
									$mark = $plugin['mark'];
									$url = $plugin['pluginUrl'];
									//社区维护版标记
									$tefans = in_array($mark,array('Download','N/A','Special'));
									$url = $url ? ($tefans ? (strpos($url,'typecho-fans') ? 'https://github.com'.$url : 'https://github.com/typecho-fans/plugins/blob/master/'.$url) : $url) : '#'; ?>
							<tr id="plugin-<?php echo $name; ?>">
								<td><a rel="external noopener" href="<?php echo $url; ?>" title="<?php _e('点击查看插件文档'); ?>"<?php if ($url!=='#') echo ' target="_blank"'; ?>><?php echo $name; ?></a>
								<?php if ($tefans) : ?>
									<a href="http://typecho-fans.github.io" title="<?php _e('Typecho-Fans社区维护版'); ?>" target="_blank"><img src="<?php $this->options->pluginUrl('TeStore/views/tf.svg'); ?>" alt="typecho-fans"/></a><?php endif;
								if ($mark=='N/A' || $mark=='不可用') : ?>
									<img src="<?php $this->options->pluginUrl('TeStore/views/na.svg'); ?>" title="<?php _e('已失效或不适用于当前版本'); ?>" alt="n/a"/>
								<?php elseif ($mark=='Special' || $mark=='特殊') : ?>
									<img src="<?php $this->options->pluginUrl('TeStore/views/sp.svg'); ?>" title="<?php _e('安装用法特殊请先阅读文档'); ?>" alt="special"/>
								<?php endif; ?></td>
								<td><?php echo $plugin['desc']; ?></td>
								<td><?php $version = $plugin['version'];
								echo $version;
								$authorHtml = $plugin['authorHtml'];
								$author = htmlspecialchars_decode(strip_tags($authorHtml));
								$infos = $this->getLocalInfos($name);
								//已安装判断升级
								$update = $infos && $infos[1]<$version;
								$version = stripos($version,'v')===0 ? substr($version,1) : $version;
								if ($update && $infos[0]==$author) : ?>
									&#8672 <span class="error"><?php _e('有新版本！'); ?></span></td>
								<?php endif; ?>
								<td>
								<?php echo $authorHtml; ?>
								</td>
								<td style="text-align:center;">
								<?php $uninstall = $infos && $infos[0]==$author && !$update;
								$authors = preg_split('/(,|&)/',$author);
								foreach ($authors as $key=>$val) : 
									$authors[$key] = trim($val);
								endforeach; ?>
									<form id="operation" action="<?php echo $storeUrl.($uninstall ? 'uninstall' : 'install').'?plugin='.$name.($uninstall ? '' : '&author='.implode('_',$authors).'&zip='.$plugin['zipFile']); ?>" method="post" enctype="application/x-www-form-urlencoded">
									<button type="submit" class="btn btn-xs <?php echo $uninstall ? 'btn-warn' : 'primary'; ?>" data-name="<?php echo $name; ?>"><?php $uninstall ? _e('删除') : ($update ? _e('升级') : _e('安装')); ?></button>
									</form>
								</td>
							</tr>
							<?php endforeach;
							elseif ($keywords) : ?>
							<tr>
								<td colspan="5" class="list-notice">
								<?php _e('没有找到符合要求的插件。'); ?>
								</td>
							</tr>
							<?php else : ?>
							<tr>
								<td colspan="5" class="list-notice">
								<?php _e('未成功读取插件信息，请检查来源%s设置%s后重试。','<a href="'.$this->options->adminUrl .'options-plugin.php?config=TeStore">','</a>'); ?>
								</td>
							</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<?php if ($pluginData) : ?>
				<div class="typecho-list-operate clearfix">
					<div class="operate">
						<a rel="external noopener" class="tflink profile-avatar notice" href="https://github.com/typecho-fans/plugins/blob/master/TESTORE.md" target="_blank"><img src="<?php $this->options->pluginUrl('TeStore/views/gh.svg'); ?>" width="16" height="16"/><?php _e('我要添加插件信息'); ?> <i class="i-exlink"></i></a>
					</div>
					<ul class="typecho-pager">
						<?php $nav->render('&laquo;','&raquo;'); ?>
					</ul>
				</div>
				<?php endif;

} else {
include TYPEHO_ADMIN_PATH.'common.php';
$menu->title = _t('TE插件仓库');
include TYPEHO_ADMIN_PATH.'header.php';
include TYPEHO_ADMIN_PATH.'menu.php';
?>
<div class="main">
	<div class="body container">
		<div class="colgroup">
			<div class="typecho-page-title col-mb-12">
				<h2><?php echo $menu->title; ?></h2>
			</div>
		</div>
		<div class="row typecho-page-main" role="form">
			<div class="col-mb-12 typecho-list">

			</div>
		</div>
	</div>
</div>
<?php
include TYPEHO_ADMIN_PATH.'copyright.php';
include TYPEHO_ADMIN_PATH.'common-js.php';
include TYPEHO_ADMIN_PATH.'footer.php';
?>
<style type="text/css">
.typecho-list-table img {
	margin-bottom:-1px;
}
.list-notice {
	text-align:center;
}
.tflink {
	padding-top:1px;
}
.tflink img {
	margin:0 4px -3px;
}
@media screen and (max-width:500px) {
	.btn-xs {font-size:1.1em;}
}
</style>
<script type="text/javascript">
$(function(){
	var list = $('.typecho-list'),
		pretag = '<div class="typecho-table-wrap"><table class="typecho-list-table"><tbody><tr><td colspan="5" class="list-notice">',
		suftag = '</td></tr></tbody></table></div>';

	$.ajax({
		type:'post',
		url:'<?php $security->index(__TYPECHO_ADMIN_DIR__.'te-store/market?action=loadlist&keywords='.$keywords.'&group='.$group.'&page='.$page); ?>',
		beforeSend:function(){
			list.html(pretag + '<span class="loading"><?php _e('插件列表加载中... 开启缓存或使用代理可减少读取耗时。'); ?></span>' + suftag);
		},
		error:function(){
			list.html(pretag + '<?php _e('插件列表加载失败，请刷新页面重试。'); ?>' + suftag);
		},
		success:function(content){
			list.html(content);
			var op = $('form#operation');
			//附加准确token
			op.each(function(){
				var form = $(this),
					action = form.attr('action');
				form.attr('action',action + '&_=<?php echo $security->getToken($request->getRequestUrl()); ?>');
			});
			op.on('submit',function(){
				var btn = $(this).children('button');
				if (!confirm('<?php _e('确定'); ?>' + btn.text() + '<?php _e('插件'); ?> ' + btn.data('name') + ' <?php _e('吗？'); ?>')) {
					return false;
				}
				//替换加载图标
				btn.replaceWith('<img src="<?php $options->adminUrl('img/ajax-loader.gif');?>" title="<?php _e('正在'); ?>' + word + '..." alt="loading"/>');
			});
		}
	});
});
</script>
<?php
}