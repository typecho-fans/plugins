<?php
include 'header.php';
include 'menu.php';
?>
<style>
.TEReplace .tongzhi{padding: 5px;font-size: 16px;border-radius: 2px;}
.TEReplace code{color: red;background: #d1e8ff;padding: 2px 3px;margin: 0 3px;border-radius: 5px;}
</style>
<div class="main TEReplace">
<div class="body container">
<div class="row">
<div class="typecho-page-title">
<h2>内容替换控制台</h2>
</div>
<?php
if (isset($_GET['xixinyanjiu'])) {
if(empty($_POST['oldurl']) || empty($_POST['newurl'])){
echo '<div class="tongzhi error">输入框存在未填写项，请认真填写后重试！</div>';
} else{
$old=$_POST['oldurl'];
$new=$_POST['newurl'];
$lei=$_POST['lei'];
if($lei=='video-thumb'||$lei=='video-mp4'||$lei=='video-fm'||$lei=='post-text'||$lei=='post-title'||$lei=='page-text'||$lei=='page-title'||$lei=='comments-text'||$lei=='comments-url'){
  
$db = Typecho_Db::get();
$prefix = $db->getPrefix();
$data_name=$prefix.'contents';
//文章内容或标题
if($lei=='post-text'||$lei=='post-title'){
if($lei=='post-text'){$lei='text';}
if($lei=='post-title'){$lei='title';}
$db->query("UPDATE `{$data_name}` SET `{$lei}`=REPLACE(`{$lei}`,'{$old}','{$new}') WHERE type='post'");
}
//独立页面内容或标题
if($lei=='page-text'||$lei=='page-title'){
if($lei=='page-text'){$lei='text';}
if($lei=='page-title'){$lei='title';}
$db->query("UPDATE `{$data_name}` SET `{$lei}`=REPLACE(`{$lei}`,'{$old}','{$new}') WHERE type='page'");
}  
  
//自定义字段
if($lei=='video-thumb'||$lei=='video-mp4'||$lei=='video-fm'){
$data_name=$prefix.'fields';
if($lei=='video-fm'){$lei='fm';}
if($lei=='video-thumb'){$lei='thumb';}
if($lei=='video-mp4'){$lei='mp4';}
$db->query("UPDATE `{$data_name}` SET `str_value`=REPLACE(`str_value`,'{$old}','{$new}')  WHERE name='{$lei}'");
}
//评论内容
if($lei=='comments-text'||$lei=='comments-url'){
$data_name=$prefix.'comments';
if($lei=='comments-text'){$lei='text';}
if($lei=='comments-url'){$lei='url';}
if($new=='null'||$new=='NULL'){$new='';}
$db->query("UPDATE `{$data_name}` SET `{$lei}`=REPLACE(`{$lei}`,'{$old}','{$new}')");
}
}else{
echo '<div class="tongzhi error">表单参数疑似被篡改提交异常！</div>';
}
?>
<div class="tongzhi success">内容替换完成！请等待自动刷新！</div>
<script language="JavaScript">window.setTimeout("location=\'<?php Helper::options()->adminUrl('extending.php?panel=TEReplace/console.php'); ?>\'", 1800);</script>
<?php
}
}
?>

<div class="typecho-page-main" role="main">
<form class="protected" action="<?php $options->adminUrl('extending.php?panel=TEReplace/console.php&xixinyanjiu=1'); ?>" method="post">
<ul class="typecho-option">
<li>
<label class="typecho-label">
旧内容</label>
<input name="oldurl" type="text" class="w-100 mono">
<p class="description">
输入你需要替换的目标内容.</p>
</li>
</ul>  
<ul class="typecho-option">
<li>
<label class="typecho-label">
新内容</label>
<input name="newurl" type="text" class="w-100 mono">
<p class="description">
输入你希望替换成的内容.【在替换评论网站地址时，如果想替换为空这里填写<code>null</code>即可】</p>
</li>
</ul>  
<ul class="typecho-option">
<li>
<label class="typecho-label">
替换项目</label>
<select name="lei" style="width: 100%;">
<option value="post-text" selected>
文章内容</option>
<option value="post-title">
文章标题</option>
<option value="page-text">
独立页面内容</option>
<option value="page-title">
独立页面标题</option>
<option value="comments-text">
评论内容</option>
<option value="comments-url">
评论网站地址</option>
<option value="video-fm">
封面地址（fm字段【Violet主题】）</option>
<option value="video-thumb">
封面地址（thumb字段【影视一号/二号主题】）</option>
<option value="video-mp4">
视频地址（mp4字段【Violet/影视一号/二号主题】）</option>
</select>
<p class="description">
插件涉及数据库操作，使用前建议备份数据库！！！因插件原因导致数据库内容丢失本插件不负任何责任!!!</p>
</li>
</ul>
<ul class="typecho-option typecho-option-submit" id="typecho-option-item-submit-8">
<li>
<button type="submit" class="btn primary">提交操作</button>
</li>
</ul> 

</form>

</div>
</div>
</div>
</div>


<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>