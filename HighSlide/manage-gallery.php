<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$fileurl = HighSlide_Plugin::filedata()->url;

//异步加载上传列表
if(isset($_GET["action"])&&$_GET["action"]=="loadlist") {
	$lists = HighSlide_Plugin::filelist();
	if ($lists) {
		foreach ($lists as $list) {
?>
		<li id="queue-<?php echo $list['id']; ?>" data-name="<?php echo $list['name']; ?>">
			<?php if(false===strpos($list['name'],'thumb_')): ?>
			<img id="uploadimg-queue-<?php echo $list['id']; ?>" class="preview" style="max-width:442px;" src="<?php echo $fileurl.$list['name']; ?>" alt="<?php echo $list['name']; ?>" />
			<div class="info" style="position:relative;">
				<?php echo $list['size']; ?> Kb
				<a class="crop" href="###" title="<?php _e('截取缩略图'); ?>"><i class="mime-application"></i></a>
				<a class="delete" href="###" title="<?php _e('删除原图(同时删除其缩略图)'); ?>"><i class="i-delete"></i></a>
				<input type="text" id="image-url" style="padding:0;width:347px;float:right;overflow:hidden;position:absolute;" value="<?php echo $fileurl.$list['name']; ?>" readonly /><div style="clear:both;"></div>
			</div>
			<input type="hidden" name="x1" value="" id="x1" />
			<input type="hidden" name="y1" value="" id="y1" />
			<input type="hidden" name="x2" value="" id="x2" />
			<input type="hidden" name="y2" value="" id="y2" />
			<input type="hidden" name="w" value="" id="w" />
			<input type="hidden" name="h" value="" id="h" />
			<?php else: ?>
			<img class="preview" style="max-width:250px;float:left;overflow:hidden;" src="<?php echo $fileurl.$list['name']; ?>" alt="<?php echo $list['name']; ?>"/>
			<textarea id="thumb-url" style="padding:0;float:right;word-break:break-all;" readonly ><?php echo $fileurl.$list['name']; ?></textarea>
			<div class="info" style="clear:both;">
				<?php echo $list['size']; ?> Kb
				<a class="addto" href="#tab-forms" title="<?php _e('添加图片'); ?>"><i class="i-exlink"></i></a>
				<a class="delete" href="###" title="<?php _e('删除缩略图'); ?>"><i class="i-delete"></i></a>
			</div>
			<?php endif; ?>
		</li>
<?php
		}
	}
} else {
include'header.php';
include'menu.php';

$phpMaxFilesize = function_exists('ini_get')?trim(ini_get('upload_max_filesize')):0;
if (preg_match("/^([0-9]+)([a-z]{1,2})$/i",$phpMaxFilesize,$matches)) {
	$phpMaxFilesize = strtolower($matches[1].$matches[2].(1 == strlen($matches[2])?'b':''));
}

$settings = $options->plugin('HighSlide');

//获取相册数据
$datas1 = $db->fetchAll($db->select('sort')->from('table.gallery')->order('order',Typecho_Db::SORT_ASC));
foreach ($datas1 as $data1) {
	$sorts[] = $data1['sort'];
}
if(!empty($sorts)) {
	$groups = array_unique($sorts);
	sort($groups);
	$group1 = array_shift($groups);
	$galleries1 = $db->fetchAll($db->select()->from('table.gallery')->where('sort=?',$group1)->order('order',Typecho_Db::SORT_ASC));
}

//获取缩略比例
$ratio = ($settings->thumbfix=='fixedratio')?$settings->fixedratio:'false';
?>
<div class="main">
	<div class="body container">
		<?php include 'page-title.php'; ?>
		<div class="row typecho-page-main manage-galleries">

			<div class="col-mb-12 typecho-list">

				<div class="clearfix">
					<ul class="typecho-option-tabs right">
						<li<?php if(!isset($request->tab)||'images'==$request->get('tab')): ?> class="active w-50"<?php else: ?> class="w-50"<?php endif; ?>><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php'); ?>"><?php _e('图片编辑'); ?></a></li>
						<li<?php if('settings'==$request->get('tab')): ?> class="active w-50"<?php else: ?> class="w-50"<?php endif; ?>><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php&tab=settings'); ?>" id="tab-files-btn"><?php _e('相册设置'); ?></a></li>
					</ul>
					<ul class="typecho-option-tabs">
						<?php if(!empty($sorts)): ?>
						<li<?php if(!isset($request->group)||$group1==$request->get('group')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php'); ?>"><span class="balloon"><?php echo count($galleries1); ?></span> <?php echo _e('相册组-'.$group1); ?></a></li>
						<?php foreach ($groups as $group):
						$galleries = $db->fetchAll($db->select('gid')->from('table.gallery')->where('sort=?',$group)); ?>
						<li<?php if($group==$request->get('group')): ?> class="current"<?php endif; ?>><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php&group='.$group); ?>"><span class="balloon"><?php echo count($galleries); ?></span> <?php echo _e('相册组-'.$group); ?></a></li>
						<?php endforeach; else: ?>
						<li class="current"><a href="<?php $options->adminUrl('extending.php?panel=HighSlide%2Fmanage-gallery.php'); ?>"><?php _e('相册组'); ?></a></li>
						<?php endif; ?>
						<li><a href="http://www.jzwalk.com/archives/net/highslide-for-typecho/" title="查看页面相册使用帮助" target="_blank"><?php _e('帮助'); ?></a></li>
					</ul>
				</div>

				<div class="col-mb-12 col-tb-7" role="main">
					<form method="post" name="manage_galleries" class="operate-form">

					<div class="typecho-list-operate clearfix">
						<div class="operate">
							<label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
							<div class="btn-group btn-drop">
								<button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
								<ul class="dropdown-menu">
									<li><a lang="<?php _e('你确认要移除这些图片吗?'); ?>" href="<?php $security->index('/action/gallery-edit?do=delete'); ?>"><?php _e('移除'); ?></a></li>
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
								<?php if(!empty($sorts)):
									if(!isset($request->group)||$group1==$request->get('group')) {
									foreach ($galleries1 as $gallery1): ?>
									<tr id="gid-<?php echo $gallery1['gid']; ?>">
										<td><input type="checkbox" value="<?php echo $gallery1['gid']; ?>" name="gid[]"/></td>
										<td><a href="<?php echo $gallery1['image']; ?>" title="<?php _e('点击查看原图'); ?>" target="_blank"><img style="max-width:100px;" src="<?php echo $gallery1['thumb']; ?>" alt="<?php echo $gallery1['name']; ?>"/></a></td>
										<td><a class="balloon-button" href="<?php echo $request->makeUriByRequest('gid='.$gallery1['gid']); ?>" title="<?php _e('点击修改图片'); ?>"><?php echo $gallery1['name']; ?></a>
										<td><?php echo $gallery1['description']; ?></td>
									</tr>
									<?php endforeach;} else {
									foreach ($groups as $group) {
									$galleries = $db->fetchAll($db->select()->from('table.gallery')->where('sort=?',$group)->order('order',Typecho_Db::SORT_ASC));
										if($group==$request->get('group')) {
										foreach ($galleries as $gallery): ?>
									<tr id="gid-<?php echo $gallery['gid']; ?>">
										<td><input type="checkbox" value="<?php echo $gallery['gid']; ?>" name="gid[]"/></td>
										<td><a href="<?php echo $gallery['image']; ?>" title="<?php _e('点击查看原图'); ?>" target="_blank"><img style="max-width:100px;" src="<?php echo $gallery['thumb']; ?>" alt="<?php echo $gallery['name']; ?>"/></a></td>
										<td><a class="balloon-button" href="<?php echo $request->makeUriByRequest('gid='.$gallery['gid']); ?>" title="<?php _e('点击修改图片'); ?>"><?php echo $gallery['name']; ?></a>
										<td><?php echo $gallery['description']; ?></td>
									</tr>
									<?php endforeach;}}}
								else: ?>
								<tr>
									<td colspan="4"><h6 class="typecho-list-table-title"><?php _e('没有任何图片'); ?></h6></td>
								</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					</form>
				</div>

				<div class="col-mb-12 col-tb-5" role="complementary">

				<?php if(!isset($request->tab)||'images'==$request->get('tab')): ?>
					<link rel="stylesheet" type="text/css" media="all" href="<?php $options->pluginUrl('HighSlide/css/imgareaselect-animated.css'); ?>" />
					<div id="tab-files" class="tab-content">
						<div id="upload-panel" class="p">
							<div class="upload-area" draggable="true"><?php _e('拖放文件到这里<br>或者 %s选择文件上传%s','<a href="###" class="upload-file">','</a>'); ?></div>
							<div style="margin-left:10px;"><strong><?php _e('如何将上传后的图片录入相册:'); ?></strong><br/><?php _e('1. 在图片上拖动鼠标, 点击左下角图标截取缩略图'); ?><br/><?php _e('2. 复制图片地址到下方表单, 填写各项后点击添加'); ?></div>
							<ul id="file-list"></ul>
						</div>
					</div>

					<div id="tab-forms"><?php HighSlide_Plugin::form()->render(); ?></div>
				<?php else: ?>
					<div id="tab-settings" class="typecho-content-panel">
						<?php HighSlide_Plugin::form('sync','2')->render(); ?>
					</div><!-- end #tab-advance -->
				<?php endif; ?>

			</div>

		</div>
	</div>
</div>

<?php
include'copyright.php';
include'common-js.php';
include'form-js.php';
?>

<script src="<?php $options->adminUrl('js/moxie.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminUrl('js/plupload.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->pluginUrl('HighSlide/js/imgareaselect.js'); ?>"></script>
	
<script type="text/javascript">
$(document).ready(function() {
	var table = $('.typecho-list-table').tableDnD({
		onDrop:function() {
			var ids = [];
			$('input[type=checkbox]',table).each(function() {
				ids.push($(this).val());
			});
			$.post('<?php $security->index('/action/gallery-edit?do=sort'); ?>',
				$.param({gid:ids}));
			$('tr',table).each(function(i) {
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
	$('.dropdown-menu button.merge').click(function(){
		var btn = $(this);
		btn.parents('form').attr('action', btn.attr('rel')).submit();
	});
	<?php if (isset($request->mid)): ?>
	$('.typecho-mini-panel').effect('highlight', '#AACB36');
	<?php endif; ?>

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

	var list = $('#file-list');
	$.ajax({
		type:'post',
		url:location.href+'&action=loadlist',
		beforeSend: function() {
			list.html('<li class="loading">列表加载中...</li>');
		},
		error:function() {
			list.text('列表加载失败, 请刷新页面重试');
		},
		success:function(content) {
			list.html(content)
			.find('li').each(function() {
				iasEffectEvent(this);
				imageAddtoEvent(this);
				imageDeleteEvent(this);
				thumbCropEvent(this);
				autoSelectEvent(this);
			});
		}
	});

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
		if (exist.length>0){
			li = exist.removeClass('loading').html(fileError);
		} else {
			li = $('<li>'+fileError+'<br />'+word+'</li>').prependTo('#file-list');
		}
		li.effect('highlight',{color:'#FBC2C4'},2000,function(){
			$(this).remove();
		});
	}

	var completeFile = null;
	function fileUploadComplete(id,data) {
		var li = $('#'+id).removeClass('loading').data('name',data.name)
			.html('<input type="hidden" name="imgname" value="'+data.name+'" />'
				+'<img id="uploadimg-'+id+'" class="preview" src="<?php echo $fileurl; ?>'+data.name+'" alt="'+data.title+'" style="max-width:442px;"/><div class="info" style="position:absolute;">'+data.bytes
				+' <a class="crop" href="###" title="<?php _e('截取缩略图'); ?>"><i class="mime-application"></i></a>'
				+' <a class="delete" href="###" title="<?php _e('删除原图(同时删除其缩略图)'); ?>"><i class="i-delete"></i></a></div>'
				+' <input type="text" id="image-url" style="padding:0;width:347px;float:right;overflow:hidden;position:relative;" value="<?php echo $fileurl; ?>'+data.name+'" readonly /><div style="clear:both;"></div>'
				+'<input type="hidden" name="x1" value="" id="x1" />'
				+'<input type="hidden" name="y1" value="" id="y1" />'
				+'<input type="hidden" name="x2" value="" id="x2" />'
				+'<input type="hidden" name="y2" value="" id="y2" />'
				+'<input type="hidden" name="w" value="" id="w" />'
				+'<input type="hidden" name="h" value="" id="h" />')
			.effect('highlight',1000);
		$('#image-0-1').val('<?php echo $fileurl; ?>'+data.name+'');
		iasEffectEvent(li);
		imageDeleteEvent(li);
		thumbCropEvent(li);
		autoSelectEvent(li);
		if (!completeFile) {
			completeFile = data;
		}
	}

		var uploader = new plupload.Uploader({
			browse_button   :   $('.upload-file').get(0),
			url			 :   '<?php $security->index('/action/gallery-edit?do=upload'); ?>',
			runtimes		:   'html5,flash,html4',
			flash_swf_url   :   '<?php $options->adminUrl('js/Moxie.swf'); ?>',
			drop_element	:   $('.upload-area').get(0),
			filters		 :   {
				max_file_size	   :   '<?php echo $phpMaxFilesize ?>',
				mime_types		  :   [{'title' : '<?php _e('允许上传的文件'); ?>', 'extensions' : 'gif,jpg,jpeg,png,tiff,bmp'}],
				prevent_duplicates  :   true
			},

			init			:   {
				FilesAdded	  :   function (up, files) {
					plupload.each(files, function(file) {
						fileUploadStart(file);
					});

					completeFile = null;
					uploader.start();
				},

				UploadComplete  :   function () {
				},

				FileUploaded	:   function (up, file, result) {
					if (200 == result.status) {
						var data = $.parseJSON(result.response);

						if (data) {
							fileUploadComplete(file.id, data[0], data[1]);
							return;
						}
					}

					fileUploadError({
						code : plupload.HTTP_ERROR,
						file : file
					});
				},

				Error		   :   function (up, error) {
					fileUploadError(error);
				}
			}
		});

		uploader.init();

	function imageAddtoEvent(el) {
		$('.addto',el).click(function () {
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				parent = name.substr(6);
				$('#image-0-1').val('<?php echo $fileurl; ?>'+parent+'');
				$('#thumb-0-2').val('<?php echo $fileurl; ?>'+name+'');
			return false;
		});
	}

	function imageDeleteEvent(el) {
		$('.delete',el).click(function () {
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				id = pli.attr('id');
			if (confirm('<?php _e('确认删除图片 %s 吗?'); ?>'.replace('%s',name))) {
				$.post('<?php $security->index('/action/gallery-edit'); ?>',
				{'do':'remove','imgname':name},
				function() {
					$('#uploadimg-'+id+'').imgAreaSelect({remove:true});
					$(el).fadeOut(function() {
						$(this).remove();
					});
					$('li[data-name="thumb_'+name+'"]').fadeOut(function() {
						$(this).remove();
					});
				});
			}
			return false;
		});
	}

	function thumbCropEvent(el) {
		$('.crop',el).click(function() {
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				li = $('li[data-name="thumb_'+name+'"]'),
				x1 = $('#x1',el).val(),
				y1 = $('#y1',el).val(),
				x2 = $('#x2',el).val(),
				y2 = $('#y2',el).val(),
				w = $('#w',el).val(),
				h = $('#h',el).val();
			if(x1==""||y1==""||x2==""||y2==""||w==""||h=="") {
				alert("请先拖选图片区域");
				return false;
			}
			if (li.length==0) {
				$('<li data-name="thumb_'+name+'" class="loading"></li>').appendTo(pli);
			} else {
				li.empty().addClass('loading');
			}
			$('img[id^="uploadimg-"]').imgAreaSelect({hide:true});
			$.post('<?php $security->index('/action/gallery-edit'); ?>',
				{'do':'crop','imgname':name,'x1':x1,'y1':y1,'w':w,'h':h},
				function(data) {
					var li = $('li[data-name="thumb_'+name+'"]').removeClass('loading').html('<img class="preview" style="max-width:250px;float:left;overflow:hidden;" src="<?php echo $fileurl.'thumb_'; ?>'+name+'?u='+Math.floor(Math.random()*100)+'" alt="thumb_'+name+'" />'
					+'<textarea id="thumb-url" style="padding:0;float:right;word-break:break-all;" readonly ><?php echo $fileurl.'thumb_'; ?>'+name+'</textarea><div class="info" style="clear:both;">'+data.bytes
					+' <a class="addto" href="#tab-forms" title="<?php _e('添加图片'); ?>"><i class="i-exlink"></i></a>'
					+' <a class="delete" href="###" title="<?php _e('删除缩略图'); ?>"><i class="i-delete"></i></a></div>')
					.effect('highlight',1000);
				$('#image-0-1').val('<?php echo $fileurl; ?>'+name+'');
				$('#thumb-0-2').val('<?php echo $fileurl.'thumb_'; ?>'+name+'');
				imageAddtoEvent(li);
				imageDeleteEvent(li);
				autoSelectEvent(li);
				});
			return false;
		});
	}

	function iasEffectEvent(el) {
		var id = $(el).attr('id');
		$('#uploadimg-'+id+'',el).imgAreaSelect({
			handles:true,
			instance:true,
			classPrefix:'ias-'+id+' ias',
			aspectRatio:'<?php echo $ratio; ?>',
			onSelectEnd:function(img,selection) {
				$('#x1',el).val(selection.x1);
				$('#y1',el).val(selection.y1);
				$('#x2',el).val(selection.x2);
				$('#y2',el).val(selection.y2);
				$('#w',el).val(selection.width);
				$('#h',el).val(selection.height);
			}
		});
	}

	function autoSelectEvent(el) {
		$('#image-url,#thumb-url',el).click(function() {
			$(this).select();
		});
	}

	var local = $('#typecho-option-item-local-11'),
		qiniu = $('#typecho-option-item-qiniubucket-12,#typecho-option-item-qiniudomain-13,#typecho-option-item-qiniuaccesskey-14,#typecho-option-item-qiniusecretkey-15,#typecho-option-item-qiniuprefix-16'),
		upyun = $('#typecho-option-item-upyunbucket-17,#typecho-option-item-upyundomain-18,#typecho-option-item-upyunuser-19,#typecho-option-item-upyunpwd-20,#typecho-option-item-upyunkey-21,#typecho-option-item-upyunprefix-22'),
		bcs = $('#typecho-option-item-bcsbucket-23,#typecho-option-item-bcsapikey-24,#typecho-option-item-bcssecretkey-25,#typecho-option-item-bcsprefix-26');
	$("#storage-local").click(function() {
		local.show();
		qiniu.hide();
		upyun.hide();
		bcs.hide();
	});
	$("#storage-qiniu").click(function() {
		local.hide();
		qiniu.show();
		upyun.hide();
		bcs.hide();
	});
	$("#storage-upyun").click(function() {
		local.hide();
		qiniu.hide();
		upyun.show();
		bcs.hide();
	});
	$("#storage-bcs").click(function() {
		local.hide();
		qiniu.hide();
		upyun.hide();
		bcs.show();
	});

	$('#file-list').css({'max-height':'5000px'});
});
</script>

<?php include'footer.php';

} ?>