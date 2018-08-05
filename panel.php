<?php
include 'header.php';
include 'menu.php';
?>
<div class="main">
	<div class="body container">
		<?php include 'page-title.php'; ?>
		<div class="container typecho-page-main">
			<div class="col-mb-12 typecho-list">
				<div class="typecho-option-tabs">
					<ul class="typecho-option-tabs clearfix">
						<li class="current">
						   <form action="<?php $options->index('/action/shortlinks?add'); ?>" method="post" >
						  &nbsp;&nbsp;&nbsp;&nbsp;KEY:<input name="key" id="key" type="text" value="" />&nbsp;&nbsp;&nbsp;&nbsp;
						  目标:<input name="target" id="target" type="text" value="http://" />
						  <input type="submit" class="btn-s primary" value="添加" />  
						   </form> 
						</li>
			   
					<li class="right current">					
						<?php $ro = Typecho_Router::get('go'); ?>
							自定义链接：<input id="links" name="links" value="<?php echo $ro['url'] ?>" type="text">
						<button id="qlinks" type="button">修改</button>
					</li>
					 </ul>
			   </div>			   
				<div class="typecho-table-wrap">
				<table class="typecho-list-table">
					<colgroup>					   
						<col width="15%"/>
						<col width="25%"/>
						<col width="47%"/>
						<col width="5%"/>
						<col width="8%"/>
					</colgroup>
					<thead>
						<tr>							
							<th><?php _e('KEY'); ?></th>
							<th><?php _e('站内链接'); ?></th>
							<th><?php _e('目标链接'); ?> </th>
							<th><?php _e('统计'); ?> </th>
							<th><?php _e('操作'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $page = isset($request->page) ? $request->page : 1 ; ?>
						<?php $links = $db->fetchAll($db->select()->from('table.shortlinks')->page($page, 15)->order('table.shortlinks.id', Typecho_Db::SORT_DESC)); ?>
						<?php foreach($links as $link): ?>
						<tr class="even" id="<?php _e($link['id']); ?>" >						   
							<td>
								<?php _e($link['key']); ?> 
								</td>
								<td>
								<?php $rourl = str_replace('[key]', $link['key'], $ro['url']); ?>
								<?php $options->index($rourl);?>
							</td>
							<td id="e-<?php _e($link['id']); ?>"><?php _e($link['target']); ?></td>
							<td><?php _e($link['count']); ?></td>
							<td>
								<a href="#<?php _e($link['id']); ?>" class="operate-edit">修改</a>
								<a lang="<?php _e('你确认要删除该链接吗?'); ?>" href="<?php $options->index('/action/shortlinks?del=' . $link['id']); ?>" class="operate-delete"><?php _e('删除'); ?></a>
							</td>
						</tr>
						<?php endforeach;?>
					</tbody>
				</table>
				</div>			   
				<div class="typecho-pager">
					<div class="typecho-pager-content">
						<ul>							
							<?php $total = $db->fetchObject($db->select(array('COUNT(id)' => 'num'))->from('table.shortlinks'))->num; ?>
							<?php for($i=1;$i<=ceil($total/15);$i++): ?>
							<li class='current'><a href="<?php $options->adminUrl('extending.php?panel=ShortLinks%2Fpanel.php&page='.$i); ?>" style= 'cursor:pointer;' title='第 <?php _e($i); ?> 页'> <?php _e($i); ?> </a></li>
							<?php endfor; ?>

						</ul>
					</div>
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
	$('.operate-edit').click(function () {
	   var tr = $(this).parents('tr'), t = $(this), id = tr.attr('id');
	   var value = $('#e-'+id).html();
	   $('#e-'+id).html('<input type="text" id="t-'+id +'" size="55" value="'+ value + '" />  <button type="submit" id="u-'+ id +'" class="btn-s primary"><?php _e('确认'); ?></button>  <button type="button" id="c-'+ id +'" class="btn-s cancel"><?php _e('取消'); ?></button>');
	   $("[href='#"+id+"']").hide();
	   
	   //确认
	   $('#u-'+id).click(function(){
			$.ajax({
			   url: '<?php $options->index('/action/shortlinks?edit'); ?>',
			   data:'id='+id+'&url='+$('#t-'+id).val(),
			   dataType:"json",
			   success:function(data){
				if(data==='success'){ 
					$('#e-'+id).html($('#t-'+id).val());
					$("[href='#"+id+"']").show();
				}else{
					alert('请输入有效链接');
				}
			   }
			});
	   });
	   //取消
	   $('#c-'+id).click(function(){
			$('#e-'+id).html(value);
			$("[href='#"+id+"']").show();
	   });
	}); 
	
	$('#qlinks').click(function(){	   
		$.ajax({
			url:'<?php $options->index('/action/shortlinks?resetLink'); ?>',
			data:'link='+$('#links').val(),
			dataType:'json',
			success:function(data){
				if('success' === data){				   
					location.reload();
				}
			}
		});
	});
});
</script>
