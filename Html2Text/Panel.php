<?php
include 'common.php';
include 'header.php';
include 'menu.php';
require_once dirname(__FILE__) . '/markdownify/markdownify.php';
?>
<div class="main">
    <div class="body container">
        <div class="col-group">
    <div class="typecho-page-title col-mb-12">
        <h2>Html to Text</h2>
    </div>
</div>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 typecho-list">
				<div class="typecho-list-operate clearfix error" style="padding: 1em;text-align:center;font-size:14px;">
					我跟你们说，用这个的时候一定要<b>备份数据库，一定一定要备份</b>。如果没有备份，而又出错了，你来打我啊。。。
                </div>
                <div class="typecho-table-wrap">
					<table class="typecho-list-table">
                        <colgroup>
                            <col width="20%">
                            <col width="70%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>名称</th>
                                <th>描述</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        	<tr>
                                <td>打开日志MD编辑器</td>
                                <td>让升级0.9版前的日志都能够使用系统自带的markdown编辑器。</td>
                                <td><button id="act1" name="btnExe" type="submit" class="btn-s">开始执行</button></td>
                            </tr>
                        	<tr>
                                <td>转换之前日志内容</td>
                                <td>升级前0.9版前，所有日志内容都保存的是HTML代码，该功能可以把HTML代码转换成markdown格式。</td>
                                <td><button id="act2" name="btnExe" type="submit" class="btn-s">开始执行</button></td>
                            </tr>
                         </tbody>
                    </table>
                <?php 
					$action = isset($_GET['action']) ? $_GET['action'] : null;
					if($action != null){
						$db = Typecho_Db::get();
						$sql = $db->select()->from('table.contents')
								  ->where('table.contents.type = ?', 'post')
								  ->order('table.contents.created', Typecho_Db::SORT_ASC);
						$content = $db->fetchAll($sql);
						foreach($content as $one){
							if(substr($one['text'], 0, 15) != '<!--markdown-->'){
								$markdownify = new Markdownify;

								if($action == 'act1'){
									$sql = $db->update('table.contents')->rows(array('text' => '<!--markdown-->'.$one['text']))->where('cid = ?', $one['cid']);
								}else if($action == 'act2'){
									$one['text'] = $markdownify->parseString($one['text']);
									$sql = $db->update('table.contents')->rows(array('text' => '<!--markdown-->'.$one['text']))->where('cid = ?', $one['cid']);
								}

								$db->query($sql);
							}
						}
				?>
				<div class="message success popup" style="position: absolute; top: 36px; display: block;">
					<ul>
						<li>好了，执行完了，我也不知道是不是全部成功了，你自己去看看吧！</li>
					</ul>
				</div>
				<?php
					}
				?>
                </div>
				<div class="typecho-list-operate clearfix error" style="padding: 1em;text-align:center;font-size:14px;">
					我跟你们说，用这个的时候一定要<b>备份数据库，一定一定要备份</b>。如果没有备份，而又出错了，你来打我啊。。。
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
?>
<script>
$('button[name=btnExe]').click(function(){
	if(confirm("按下确认就没后悔药吃了，你真的备份了吗？")){
		window.location.href = "<?php echo $options->adminUrl('extending.php?panel=Html2Text%2FPanel.php&action=')?>" + $(this).attr('id');
	}
})
</script>
<?php
include 'footer.php';
?>