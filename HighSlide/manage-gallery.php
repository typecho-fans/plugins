<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$route = HighSlide_Plugin::route();
$fileurl = $route->url;
$settings = $options->plugin('HighSlide');
$datas = $db->fetchAll($db->select('gid,image,thumb,sort')->from('table.gallery'));
$isgid = isset($request->gid);

//输出图片列表
if ($request->is('action=loadlist')) {
	$filedir = $route->dir;
	$files = array();

	//按储存获取数据
	switch ($settings->storage) {
		case 'local' :
		$lists = glob(__TYPECHO_ROOT_DIR__.'/'.$filedir.'*[0-9].{gif,jpg,jpeg,png,tiff,bmp,GIF,JPG,JPEG,PNG,TIFF,BMP}',GLOB_BRACE|GLOB_NOSORT);
		foreach ($lists as $list) {
			$files[] = array('key'=>$list,'fsize'=>filesize($list));
		}
		break;

		case 'qiniu' :
		$qiniu = HighSlide_Plugin::qiniuset($settings->qiniuak,$settings->qiniusk);
		$bmgr = new Qiniu\Storage\BucketManager($qiniu);
		$result = $bmgr->listFiles($settings->qiniubucket,$filedir);
		if ($result['0']['items']) {
			$files = $result['0']['items'];
		} elseif ($result['1']) {
			echo '<p style="text-align:center;background-color:#FBE3E4;color:#8A1F11;">'._t('七牛云反馈报错:').' <br/><strong>'.$result['1']->message().'</strong></p>';
		}
		break;

		case 'scs' :
		set_error_handler(function($errno,$errstr,$errfile,$errline){
			if ($errno == E_USER_WARNING) {
				echo '<p style="text-align:center;background-color:#FBE3E4;color:#8A1F11;">'._t('新浪云SCS反馈报错:').' <br/><strong>'.$errstr.'</strong></p>';
			}
		});
		$lists = HighSlide_Plugin::scsset($settings->scsak,$settings->scssk)->getBucket($settings->scsbucket,$filedir);
		if ($lists) {
			foreach ($lists as $list) {
				$files[] = array('key'=>$list['name'],'fsize'=>$list['size']);
			}
		}
		break;

		case 'nos' :
		try {
			$lists = HighSlide_Plugin::nosset($settings->nosak,$settings->nosas,$settings->nosep)
				->listObjects($settings->nosbucket,array('prefix'=>$filedir))
				->getObjectList();
			foreach ($lists as $list) {
				$files[] = array('key'=>$list->getKey(),'fsize'=>$list->getSize());
			}
		} catch (Exception $e) {
			echo '<p style="text-align:center;background-color:#FBE3E4;color:#8A1F11;">'._t('网易云NOS反馈报错:').' <br/><strong>'.$e->getMessage().'</strong></p>';
		}
		break;

		case 'cos' :
		$cos = HighSlide_Plugin::cosset($settings->cosrg)->listFolder($settings->cosbucket,$filedir);
		if ($cos['message']=='SUCCESS') {
			$lists = $cos['data']['infos'];
			foreach ($lists as $list) {
				$files[] = array('key'=>$list['name'],'fsize'=>$list['filesize']);
			}
		} else {
			echo '<p style="text-align:center;background-color:#FBE3E4;color:#8A1F11;">'._t('腾讯云COS反馈报错:').' <br/><strong>'.$cos['message'].'</strong></p>';
		}
	}

	$lists = array();
	//整理列表数据
	$orders = array();
	if ($files) {
		foreach ($files as $file) {
			$file['key'] = basename($file['key']);
			$orders[0===strpos($file['key'],'thumb_') ? substr($file['key'],6,5)+1 : substr($file['key'],0,5)] = $file; //配对排序
		}
		ksort($orders);
		$id = 0;
		foreach ($orders as $order) {
			$lists[$fileurl.$order['key']] = array(
				'id'=>++$id,
				'name'=>$order['key'],
				'size'=>number_format(ceil($order['fsize']/1024))
			);
		}
	}

	//整理相册数据
	$iname = array();
	$tname = array();
	$gid = $isgid ? $request->filter('int')->gid : '';
	$urls = array();
	foreach ($datas as $data) {
		$iname[$data['image']] = array();
		$tname[$data['thumb']] = array();
		if ($gid==$data['gid']) { //修改模式
			$urls = array(array('id'=>'i','url'=>$data['image']),array('id'=>'t','url'=>$data['thumb']));
		}
	}

	//按模式选择数据
	$lists = $isgid ? $urls : array_diff_key($lists,array_merge($iname,$tname)); //键名对比
	if ($lists) {
		$url = '';
		$name = '';
		$size = '';
		$parse = array();
		$path = '';
		$from = $route->from;
		foreach ($lists as $list) {
			if ($isgid) {
				$url = $list['url'];
				$name = $list['id']=='i' ? basename($url) : 'thumb_'.basename($lists['0']['url']);
				$size = number_format(ceil(strlen(@file_get_contents($url))/1024)); //直接获取大小
				$parse = parse_url($url);
				$path = $parse['path'];
				$from = HighSlide_Plugin::route($url)->from; //判断url支持
			} else {
				$name = $list['name'];
				$url = $fileurl.$name;
				$size = $list['size'];
			}
?>
			<li id="queue-<?php echo $list['id']; ?>" data-name="<?php echo $name; ?>" data-url="<?php echo $url; ?>">
				<?php if ($list['id']=='i' ||  !$isgid&&false===strpos($name,'thumb_')) { ?>
				<img id="uploadimg-queue-<?php echo $list['id']; ?>" style="max-width:100%;" src="<?php echo $url.'?r='.rand(0,200); ?>" alt="<?php echo $name; ?>"/>
				<div class="info">
					<?php echo $size; ?> Kb
					<a class="crop" href="###" title="<?php _e('截取缩略图'); ?>"><i class="mime-application"></i></a>
					<?php if ($from) { ?>
					<a class="delete" href="###" title="<?php _e('删除原图(同时删除其缩略图)'); ?>"><i class="i-delete"></i></a>
					<?php } ?>
					<input type="text" id="image-url" style="padding:0;width:77%;" value="<?php echo $url; ?>" readonly/>
				</div>
				<input type="hidden" name="x1" value="" id="x1"/>
				<input type="hidden" name="y1" value="" id="y1"/>
				<input type="hidden" name="x2" value="" id="x2"/>
				<input type="hidden" name="y2" value="" id="y2"/>
				<input type="hidden" name="w" value="" id="w"/>
				<input type="hidden" name="h" value="" id="h"/>
				<?php } else { ?>
				<img style="max-width:50%;" src="<?php echo $url.($settings->thumbapi ? '' : '?r='.rand(0,200)); ?>" alt="<?php echo $name; ?>"/>
				<div class="info">
					<?php echo $size; ?> Kb
					<a class="addto" href="#tab-forms" title="<?php _e('自动填写地址'); ?>"><i class="mime-text"></i></a>
					<?php if ($from && empty($parse['query']) && false==strpos($path,'--') && false==strpos($path,'|')) { ?>
					<a class="delete" href="###" title="<?php _e('删除缩略图'); ?>"><i class="i-delete"></i></a>
					<?php } ?>
					<input type="text" id="thumb-url" style="padding:0;width:77%;" value="<?php echo $url; ?>" readonly/>
				</div>
				<?php } ?>
			</li>
<?php
		}
	}

//输出相册页面
} else {
include 'header.php';
include 'menu.php';

//获取上传限制
$phpMaxFilesize = function_exists('ini_get') ? trim(ini_get('upload_max_filesize')) : 0;
if (preg_match('/^([0-9]+)([a-z]{1,2})$/i',$phpMaxFilesize,$matches)) {
	$phpMaxFilesize = strtolower($matches['1'].$matches['2'].(1==strlen($matches['2']) ? 'b' : ''));
}
?>
<div class="main">
	<div class="body container">
		<?php include 'page-title.php'; ?>
		<div class="row typecho-page-main manage-galleries">

			<div class="col-mb-12 typecho-list">

				<div class="clearfix">
					<ul class="typecho-option-tabs right">
						<li<?php if (!isset($request->tab) || 'images'==$request->get('tab')) { ?> class="active w-50"<?php } else { ?> class="w-50"<?php } ?>><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php'); ?>"><?php _e('图片编辑'); ?></a></li>
						<li<?php if ('settings'==$request->get('tab')) { ?> class="active w-50"<?php } else { ?> class="w-50"<?php } ?>><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php&tab=settings'); ?>" id="tab-files-btn"><?php _e('相册设置'); ?></a></li>
					</ul>
					<ul class="typecho-option-tabs">
						<?php 
						//整理组别数据
						$sorts = array();
						foreach ($datas as $data) {
							$sorts[] = $data['sort'];
						}
						$sorts = array_unique($sorts);
						sort($sorts);
						$dsort = HighSlide_Plugin::defaultsort();

						//显示组别标签
						if ($dsort) {
							foreach ($sorts as $sort) {
								$gids = $db->fetchAll($db->select('gid')->from('table.gallery')->where('sort = ?',$sort)); ?>
						<li<?php if (!isset($request->group)&&$sort==$dsort || $sort==$request->get('group')) { ?> class="current"<?php } ?>><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php'.($sort==$dsort ? '' : '&group='.$sort)); ?>"><span class="balloon"><?php echo count($gids); ?></span> <?php echo _e('相册组-%s','<strong>'.$sort.'</strong>'); ?></a></li>
						<?php }} else { ?>
						<li class="current"><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php'); ?>"><?php _e('相册组'); ?></a></li>
						<?php } ?>
						<li><a href="http://www.yzmb.me/archives/net/highslide-for-typecho/" title="查看插件主页说明" target="_blank"><?php _e('帮助'); ?></a></li>
					</ul>
				</div>

				<div class="col-mb-12 col-tb-7" role="main">
					<form method="post" name="manage_galleries" class="operate-form">

					<div class="typecho-list-operate clearfix">
						<div class="operate">
							<label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all"/></label>
							<div class="btn-group btn-drop">
								<button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
								<ul class="dropdown-menu">
									<li><a lang="<?php _e('确定要移除这些图片吗?'); ?>" href="<?php $security->index('/action/gallery-edit?do=delete'); ?>"><?php _e('从相册组移除'); ?></a></li>
								</ul>
							</div>
						</div>
					</div>

					<div class="typecho-table-wrap">
						<table class="typecho-list-table">
							<colgroup>
								<col width="20"/>
								<col width="20%"/>
								<col width="25%"/>
								<col width="55%"/>
							</colgroup>
							<thead>
								<tr class="nodrag">
									<th> </th>
									<th><?php _e('缩略图'); ?></th>
									<th><?php _e('图片名称'); ?></th>
									<th><?php _e('图片描述'); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php
							//显示组别内容
							if ($dsort) {
								$galleries = $db->fetchAll($db->select()->from('table.gallery')->where('sort = ?',isset($request->group) ? $request->group : $dsort)->order('order',Typecho_Db::SORT_ASC));
								foreach ($galleries as $gallery) {
									$checked = isset($request->gid) && $gallery['gid']==$request->get('gid'); ?>
								<tr id="gid-<?php echo $gallery['gid']; ?>"<?php if ($checked) { ?> class="checked"<?php } ?>>
									<td><input type="checkbox" value="<?php echo $gallery['gid']; ?>" name="gid[]"<?php if ($checked) { ?> checked="true"<?php } ?>/></td>
									<td><a href="<?php echo $gallery['image']; ?>" title="<?php _e('查看原图'); ?>" target="_blank"><img style="max-width:100px;" src="<?php echo $gallery['thumb']; ?>" alt="<?php echo $gallery['name']; ?>"/></a></td>
									<td><a class="balloon-button" href="<?php echo $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php&group='.$gallery['sort'].'&gid='.$gallery['gid']); ?>" title="<?php _e('点击修改图片'); ?>"><?php echo $gallery['name']; ?></a>
									<td><?php echo $gallery['description']; ?></td>
								</tr>
								<?php }} else { ?>
								<tr>
									<td colspan="4"><h5 class="typecho-list-table-title"><?php _e('没有任何图片'); ?></h5></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>

					</form>
				</div>

				<div class="col-mb-12 col-tb-5" role="complementary">

				<?php
				//显示右侧表单
				if (!isset($request->tab) || 'images'==$request->get('tab')) { ?>
					<div id="tab-files" class="tab-content">
						<div id="upload-panel" class="p">
							<div class="upload-area" draggable="true"><?php _e('拖放文件到这里或<br>%s选择文件上传%s','<a href="###" class="upload-file"><i class="i-upload"></i>','</a>'); ?></div>
							<div style="margin-left:10px;"><strong><?php _e('如何将上传后的图片录入相册:'); ?></strong><br/><?php _e('1. 在图片上拖动鼠标, 点击左下角图标截取缩略图'); ?><br/><?php _e('2. 复制图片地址到下方表单, 填写各项后点击添加'); ?></div>
							<ul id="file-list" style="<?php if ($isgid) { ?>background-color:#FFF9E8;<?php } ?>max-height:1000px;"></ul>
						</div>
					</div>
					<div id="tab-forms"><?php HighSlide_Plugin::form()->render(); ?></div>
				<?php } else { ?>
					<div id="tab-settings" class="typecho-content-panel">
						<?php HighSlide_Plugin::form('sync','s')->render(); ?>
					</div>
				<?php } ?>

			</div>

		</div>
	</div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
?>

<script src="<?php $options->adminUrl('js/moxie.js?v='.$suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('js/plupload.js?v='.$suffixVersion); ?>"></script>
<script src="<?php $options->pluginUrl('HighSlide/js/imgareaselect.js'); ?>"></script>
<link rel="stylesheet" type="text/css" media="all" href="<?php $options->pluginUrl('HighSlide/css/imgareaselect-animated.css'); ?>"/>

<script type="text/javascript">
$(document).ready(function(){
<?php
	//核心提示窗口
	if ($settings->mode=='highslide.packed.js' && !isset($request->tab) && !isset($request->group)) {
?>
	$('body').append('<div id="gpanel">' +
	'<div class="wmd-prompt-background" style="position:absolute;z-index:1000;opacity:0.5;top:0px;left:0px;width:100%;height:954px;"></div>' +
	'<div class="wmd-prompt-dialog"><div><p><b><?php _e("当前选择的“基础版”核心不支持页面相册功能！"); ?></b></p>' +
		'<p><?php _e("你可以继续在此配置相关功能但相册标签不会被解析，正常使用页面相册功能请前往插件%s设置%s选择“全功能版”核心。","<a href=".$options->adminUrl."options-plugin.php?config=HighSlide>","</a>"); ?></p></div>' +
		'<form><button type="button" class="btn btn-s primary" id="ok"><?php _e("知道了"); ?></button>' +
	'</div></div>');
	$('#ok').click(function(){
		$('#gpanel').remove();
	});
<?php
	}
?>

	//加载图片列表
	var list = $('#file-list');
	$.ajax({
		type:'post',
		url:location.href+'&action=loadlist',
		beforeSend:function(){
			list.html('<li class="loading"><span style="text-align:center;"><?php _e('图片列表加载中...'); ?></span></li>');
		},
		error:function(){
			list.html('<li><span style="background-color:#FBE3E4;color:#8A1F11;"><?php _e('图片列表加载失败, 请刷新页面重试'); ?></span></li>');
		},
		success:function(content){
			list.html(content)
			.children('li').each(function(){
				iasEffectEvent(this);
				imageAddtoEvent(this);
				imageDeleteEvent(this);
				thumbCropEvent(this);
				autoSelectEvent(this);
			});
		}
	});

	//typecho表格效果
	var table = $('.typecho-list-table').tableDnD({
		onDrop:function(){
			var ids = [];
			$('input[type=checkbox]',table).each(function(){
				ids.push($(this).val());
			});
			$.post('<?php $security->index('/action/gallery-edit?do=sort'); ?>',
				$.param({gid:ids}));
			$('tr',table).each(function(i){
				if (i%2) {
					$(this).addClass('even');
				} else {
					$(this).removeClass('even');
				}
			});
		}
	});
	table.tableSelectable({
		checkEl:'input[type=checkbox]',
		rowEl:'tr',
		selectAllEl:'.typecho-table-select-all',
		actionEl:'.dropdown-menu a'
	});
	$('.btn-drop').dropdownMenu({
		btnEl:'.dropdown-toggle',
		menuEl:'.dropdown-menu'
	});

	//typecho拖拽效果
	$('.upload-area').bind({
		dragenter:function(){
			$(this).parent().addClass('drag');
		},
		dragover:function(e){
			$(this).parent().addClass('drag');
		},
		drop:function(){
			$(this).parent().removeClass('drag');
		},
		dragend:function(){
			$(this).parent().removeClass('drag');
		},
		dragleave:function(){
			$(this).parent().removeClass('drag');
		}
	});

	//typecho上传提示
	function fileUploadStart(file){
		$('<li id="'+file.id+'" class="loading">'
			+file.name+'</li>').prependTo('#file-list');
	}
	function fileUploadError(error){
		var file = error.file,code = error.code,word;
		switch (code) {
			case plupload.FILE_SIZE_ERROR:
				word = '<?php _e('文件大小超过限制'); ?>';
				break;
			case plupload.FILE_EXTENSION_ERROR:
				word = '<?php _e('文件扩展名不被支持'); ?>';
				break;
			case plupload.FILE_DUPLICATE_ERROR:
				word = '<?php _e('文件已经上传过'); ?>';
				break;
			case plupload.HTTP_ERROR:
			default:
				word = '<?php _e('上传出现错误'); ?>';
				break;
		}
		var fileError = '<?php _e('%s 上传失败'); ?>'.replace('%s',file.name),
			li,exist = $('#'+file.id);
		if (exist.length>0) {
			li = exist.removeClass('loading').html(fileError);
		} else {
			li = $('<li>'+fileError+'<br/>'+word+'</li>').prependTo('#file-list');
		}
		li.effect('highlight',{color:'#FBC2C4'},2000,function(){
			$(this).remove();
		});
	}

	//上传完成事件
	var completeFile = null;
	function fileUploadComplete(id,data){
		var url = '<?php echo $fileurl; ?>'+data.name,
			li = $('#'+id).removeClass('loading').data('name',data.name).data('url',url)
			.html('<input type="hidden" name="imgname" value="'+data.name+'"/>'
				+'<img id="uploadimg-'+id+'" src="'+url+'?r='+Math.floor(Math.random()*200)+'" alt="'+data.title+'" style="max-width:100%;"/><div class="info">'+data.bytes
				+' <a class="crop" href="###" title="<?php _e('截取缩略图'); ?>"><i class="mime-application"></i></a>'
				+' <a class="delete" href="###" title="<?php _e('删除原图(同时删除其缩略图)'); ?>"><i class="i-delete"></i></a>'
				+' <input type="text" id="image-url" style="padding:0;width:77%;" value="<?php echo $fileurl; ?>'+data.name+'" readonly/></div>'
				+'<input type="hidden" name="x1" value="" id="x1"/>'
				+'<input type="hidden" name="y1" value="" id="y1"/>'
				+'<input type="hidden" name="x2" value="" id="x2"/>'
				+'<input type="hidden" name="y2" value="" id="y2"/>'
				+'<input type="hidden" name="w" value="" id="w"/>'
				+'<input type="hidden" name="h" value="" id="h"/>')
			.effect('highlight',1000);
		$('#image-0-1').val(url);
		iasEffectEvent(li);
		imageDeleteEvent(li);
		thumbCropEvent(li);
		autoSelectEvent(li);
		if (!completeFile) {
			completeFile = data;
		}
	}

	//typecho上传事件
	var uploader = new plupload.Uploader({
		browse_button:$('.upload-file').get(0),
		url:'<?php $security->index('/action/gallery-edit?do=upload'); ?>',
		runtimes:'html5,flash,html4',
		flash_swf_url:'<?php $options->adminUrl('js/Moxie.swf'); ?>',
		drop_element:$('.upload-area').get(0),
		filters:{
			max_file_size:'<?php echo $phpMaxFilesize ?>',
			mime_types:[{'title':'<?php _e('允许上传的文件'); ?>','extensions':'gif,jpg,jpeg,png,tiff,bmp'}],
			prevent_duplicates:true
		},
		init:{
			FilesAdded:function(up,files){
				plupload.each(files,function(file){
					fileUploadStart(file);
				});

				completeFile = null;
				uploader.start();
			},
			UploadComplete:function(){
			},
			FileUploaded:function(up,file,result){
				if (200==result.status) {
					var data = $.parseJSON(result.response);

					if (data) {
						fileUploadComplete(file.id,data[0],data[1]);
						return;
					}
				}

				fileUploadError({
					code:plupload.HTTP_ERROR,
					file:file
				});
			},
			Error:function(up,error){
				fileUploadError(error);
			}
		}
	});
	uploader.init();

	//裁切蒙板事件
	function iasEffectEvent(el){
		var id = $(el).attr('id');
		$('#uploadimg-'+id,el).imgAreaSelect({
			handles:true,
			instance:true,
			classPrefix:'ias-'+id+' ias',
			aspectRatio:'<?php echo $settings->thumbfix=='fixedratio' ? $settings->fixedratio : 'false'; ?>', //宽高比设置
			onSelectEnd:function(img,selection){
				$('#x1',el).val(selection.x1);
				$('#y1',el).val(selection.y1);
				$('#x2',el).val(selection.x2);
				$('#y2',el).val(selection.y2);
				$('#w',el).val(selection.width);
				$('#h',el).val(selection.height);
			}
		});
	}

	//生成缩略图事件
	function thumbCropEvent(el){
		$('.crop',el).click(function(){
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				url = pli.data('url'),
				id = pli.attr('id'),
				li = $('li[data-name="thumb_'+name+'"]'),
				x1 = $('#x1',el).val(),
				y1 = $('#y1',el).val(),
				x2 = $('#x2',el).val(),
				y2 = $('#y2',el).val(),
				w = $('#w',el).val(),
				h = $('#h',el).val();
			if ('<?php echo $settings->thumbapi; ?>'==1 && url.indexOf('<?php echo $route->site; ?>')!==0) {
				alert('<?php _e('原图不在当前云储存, 无法使用API'); ?>');
				return false;
			}
			if (x1=='' || y1=='' || x2=='' || y2=='' || w=='' || h=='') {
				alert('<?php _e('请先拖选图片区域'); ?>');
				return false;
			}
			if (li.length==0) {
				pli.after('<li data-name="thumb_'+name+'" class="loading"></li>');
			} else {
				li.empty().addClass('loading');
			}
			$('img[id^="uploadimg-"]').imgAreaSelect({hide:true});
			$.post('<?php $security->index('/action/gallery-edit'); ?>'
					+(id=='queue-i' ? '&url='+url : ''), //修改模式
				{'do':'crop','imgname':name,'x1':x1,'y1':y1,'w':w,'h':h},
				function(data){
					var t = '<?php echo $fileurl.'thumb_'; ?>'+name,
						a = data.aurl ? data.aurl : t,
						li = $('li[data-name="thumb_'+name+'"]').removeClass('loading').html('<img src="'+(data.aurl ? data.aurl : t+'?r='+Math.floor(Math.random()*200))+'" alt="thumb_'+name+'" style="max-width:50%;"/><div class="info">'+data.bytes
					+' <a class="addto" href="#tab-forms" title="<?php _e('自动填写地址'); ?>"><i class="mime-text"></i></a>'
					+(data.aurl ? ' ' : ' <a class="delete" href="###" title="<?php _e('删除缩略图'); ?>"><i class="i-delete"></i></a>')
					+' <input type="text" id="thumb-url" style="padding:0;width:77%;" value="'+a+'" readonly/></div>')
					.data('url',a) //添加数据
					.effect('highlight',1000);
				$('#image-0-1').val(url);
				$('#thumb-0-2').val(a);
				imageAddtoEvent(li);
				imageDeleteEvent(li);
				autoSelectEvent(li);
				}).error(function(XMLHttpRequest,textStatus,errorThrown){ //报错信息
					var mes = '<span style="background-color:#FBE3E4;color:#8A1F11;"><?php _e('缩略图截取出错: '); ?>'+textStatus+'</span>';
					if (li.length==0) {
						pli.after(mes);
					} else {
						li.removeClass('loading').html(mes);
					}
				});
			return false;
		});
	}

	//删除图片事件
	function imageDeleteEvent(el){
		$('.delete',el).click(function(){
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				id = pli.attr('id');
			if (confirm('<?php _e('确定要删除图片 %s 吗?'); ?>'.replace('%s',name))) {
				$.post('<?php $security->index('/action/gallery-edit'); ?>',
				{'do':'remove','imgname':name,'url':id==('queue-i' || 'queue-t') ? pli.data('url') : ''},
				function(){
					$('#uploadimg-'+pli.attr('id')).imgAreaSelect({remove:true});
					$(el).fadeOut(function(){
						$(this).remove();
					});
					$('li[data-name="thumb_'+name+'"]').fadeOut(function(){
						$(this).remove();
					});
				});
			}
			return false;
		});
	}

	//自动填写事件
	function imageAddtoEvent(el){
		$('.addto',el).click(function(){
			var pli = $(this).parents('li');
				$('#image-0-1').val(pli.prev('li').data('url'));
				$('#thumb-0-2').val(pli.data('url'));
			return false;
		});
	}

	//自动选择事件
	function autoSelectEvent(el){
		$('#image-url,#thumb-url',el).click(function(){
			$(this).select();
		});
	}

	//储存表单切换
	var q = $('ul[id^="typecho-option-item-qiniu"]'),
		u = $('ul[id^="typecho-option-item-scs"]'),
		n = $('ul[id^="typecho-option-item-nos"]'),
		c = $('ul[id^="typecho-option-item-cos"]'),
		s = $('#typecho-option-item-storage-11'),
		a = $('#thumbapi-1'),
		h = $('#typecho-option-item-cloudtoo-33');
	allhide();
	if ($('#storage-local').is(':checked')) $('#cloudtoo-0-34').val('0');
	$('input',s).each(function(){
		var t = $(this),
			v = t.val();
		if (t.prop('checked')) {
			caseshow(v);
		}
		t.click(function(){
			allhide();
			caseshow(v);
		});
	});
	function allhide(){
		q.add(u).add(n).add(c).hide();
	}
	function caseshow(el){
		switch (el) {
			case 'local':
			$('#thumbapi-0').prop('checked','true');
			h.hide();
			a.attr('disabled','true');
			a.next('label').attr('style','color:#999');
			break;
			case 'qiniu':q.add(h).show();enable();
			break;
			case 'scs':u.add(h).show();enable();
			break;
			case 'nos':n.add(h).show();enable();
			break;
			case 'cos':c.add(h).show();enable();
		}
	}
	function enable(){
		a.removeAttr('disabled');
		a.next('label').removeAttr('style');
	}

	//云储存提示说明
	var sp = $('span',s),
		tt = $('#tooltip');
	$('label[for!="storage-local"]',sp).hover(function(){
		var f = $(this).attr('for');
		switch (f) {
			case 'storage-qiniu':tt.html('<?php _e('免费额度：10G空间，10G月流量(邀请最高至40G)'); ?>');
			break;
			case 'storage-scs':tt.html('<?php _e('免费额度：1G空间，9G月流量(邀请奖励云豆充值)'); ?>');
			break;
			case 'storage-nos':tt.html('<?php _e('免费额度：50G空间，20G月流量'); ?>');
			break;
			case 'storage-cos':tt.html('<?php _e('免费额度：50G空间，10G(直连/CDN)月流量'); ?>');
		}
		tt.fadeToggle(200);
	});
});
</script>
<style type="text/css">
#tooltip {border:2px solid #D9D9D6;background:#fff;margin-left:3px;padding:1px;color:#999;font-size:12px;font-weight:normal;position:absolute;bottom:26px;left:58px;}
</style>

<?php include 'footer.php';

} ?>