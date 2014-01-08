<?php
include 'common.php';
include 'header.php';
include 'menu.php';
$short_name = trim(Duoshuo_Action::getOption('short_name'));
$secret = trim(Duoshuo_Action::getOption('secret'));
$synchronized = intval(Duoshuo_Action::getOption('synchronized'));
$act = Duoshuo_Action::ActMap($request->get('do'));
if(!$short_name || !$secret) $act = 'comments';			//若未绑定，则锁定在绑定页面
if(!$synchronized && $short_name && $secret && is_numeric($synchronized)) $act = 'setting';
if($short_name && $secret) $token = array('short_name' => $short_name,'user_key' => 1,'name' => $user->name);
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
		<div class="clearfix" style="padding-left: 20px;">
			<ul class="typecho-option-tabs">
				<?php if($act == 'setting'):?>
					<li class="setting"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-comments')?>">初始化设置</a></li>
            	<?php endif ?>
        		<li class="comments"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-comments')?>">评论管理</a></li>
                <li class="theme"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-theme')?>">主题管理</a></li>
                <li class="profile"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-profile')?>">个人资料</a></li>
                <li class="preferences"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-preferences')?>">个性化设置</a></li>
                <li class="plugin"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-plugin')?>">扩展功能</a></li> 
                <li class="statistics"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-statistics')?>">数据统计</a></li>
                <li class="getcode"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-getcode')?>">获取代码</a></li>
                <li class="about"><a href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-about')?>">说明一下</a></li>

            </ul>
		</div>
		<div class="col-group typecho-page-main">
			<?php 
			switch ($act) {
				case 'comments':
						if($short_name && $secret){	//已绑定
							echo '<iframe id="duoshuo-remote-window" src="http://'.$short_name.'.duoshuo.com/admin/?jwt='.Duoshuo_Action::encodeJWT($token, $secret).'" style="width: 100%;border:0"></iframe>';
						}else{
							if($request->get('short_name') && $request->get('secret')){//资料已填，写入
								if(strpos(getenv("HTTP_REFERER"),'http://duoshuo.com/continue-site/?' != false))
									throw new Typecho_Widget_Exception(_t('非法操作'), 404);   //验证来源页是否为多说
								Duoshuo_Action::updateOption('short_name',$request->get('short_name'));
								Duoshuo_Action::updateOption('secret',$request->get('secret'));
								//刷新整个页面
								$options->widget('Widget_Notice')->set(_t('绑定成功！','',''), NULL, 'success');
								echo '<script language="javascript">parent.location.replace(parent.location.href);</script>'; 
							}else{						
				           		$url = 'http://duoshuo.com/connect-site/';
								$arr = array(
									'url'=>$options->siteUrl,
									'siteurl'=>$options->siteUrl,
									'admin_email'=>$user->mail,
									'timezone'=>'Asia/Shanghai',
									'use_smilies'=>'1',
									'name'=>$options->title,
									'description'=>$options->description,
									'system_theme'=>$options->theme,
									'system_version'=>str_replace('typecho ', '', $options->generator),
									'plugin_version'=>'1.0',
									'local_api_url'=>$options->index.'/DuoShuoSync',
									'oauth_proxy_url'=>$options->index.'/DuoShuoOauth',
									'system'=>'typecho',
									'callback'=>Typecho_Common::url('extending.php?panel=Duoshuo/manage-duoshuo.php', $options->adminUrl),
									'user_key'=>'1',
									'user_name'=>$user->name,
									'sync_log'=>'1'
								);
								$query = http_build_query($arr);
								echo '<iframe id="duoshuo-remote-window" src="'.$url.'?'.$query.'"  class="conbox"></iframe>';
							}
						}
					break;
				case 'theme':
					echo '<div id="duoshuo-remote-window"  class="conbox"><ul class="ds-themes"></ul><p class="other">多说的CSS样式已经开源啦！ <a href="https://github.com/duoshuo/duoshuo-embed.css" target="_blank">github:duoshuo/duoshuo-embed.css</a></p><p>你可以打造属于自己的主题，在<a href="http://dev.duoshuo.com/discussion" target="_blank">开发者中心</a>分享你的主题，还有可能被官方推荐哟！</p></div>'."\r\n";
					break;
				case 'profile':
					echo '<iframe id="duoshuo-remote-window" src="http://duoshuo.com/settings/?jwt='.Duoshuo_Action::encodeJWT($token, $secret).'" style="width: 100%;border:0;padding:20px"></iframe>';
					break;
				case 'preferences':
					echo '<iframe id="duoshuo-remote-window" src="http://'.$short_name.'.duoshuo.com/admin/settings/?jwt='.Duoshuo_Action::encodeJWT($token, $secret).'" style="width: 100%;border:0;"></iframe>';
					break;
				case 'plugin':
					echo '<div id="duoshuo-remote-window" class="conbox">
						<p class="title">手动同步</p>
						<p id="ds-export">
							<button value="Export" class="primary" id="btn-export" onclick="fireExport();">导出本地所有评论到多说</button>
							<button value="Import" class="primary" id="btn-import" onclick="fireSyncLog();">同步多说评论到本地数据库</button>
							<button value="ImportAll" id="btn-importall" readonly>同步多说所有评论到本地数据库[未完成]</button>
							<div class="tip"><span>*</span> 同步多说评论到博客功能，仅用于多说服务器延迟时，手动同步评论，并不能将多说上所有评论导回博客</div>
						</p>
						<p class="title">删除临时评论</p>
						<p>
							<button value="DComments" class="primary" id="btn-dcomments" onclick="deletecomments()">手动删除回收站评论</button>
							<div class="tip"><span>*</span> 删除状态为"已删除"的评论，若删除此处，则多说评论管理中，"已删除"中的评论将无法在本地被恢复</div>
						</p>
						<p class="title">导出JSON文件</p>
						<p>
							<button value="ExportJson" class="primary" id="btn-exportjson" onclick="exprotjson();">导出博客评论为多说JSON格式</button>
						</p>
						<p class="title">清空多说站点配置</p>
						<p>
							<button value="Reset" class="primary" id="btn-reset" onclick="resetduoshuo();">清空多说站点配置，用于重新绑定多说帐号</button>
						</p>
						</div>
						<iframe src="" width="0" height="0" id="exportjson-frm" border="0"></iframe>
						';
					break;
				case 'statistics':
					echo '<iframe id="duoshuo-remote-window" src="http://'.$short_name.'.duoshuo.com/admin/statistics/?jwt='.Duoshuo_Action::encodeJWT($token, $secret).'" style="width: 100%;border:0"></iframe>';
					break;
				case 'getcode':
					$code = '<div id="comments"> '."\r".'<?php if($this->allow("comment")): ?>'."\r".'<!-- Duoshuo Comment BEGIN -->'."\r".'	<div class="ds-thread" data-thread-key="<?php echo $this->cid;?>" '."\r".'	data-title="<?php echo $this->title;?>" data-author-key="<?php echo $this->authorId;?>" data-url=""></div>'."\r".'	<script type="text/javascript">'."\r".'	var duoshuoQuery = {short_name:"'.$short_name.'",theme:"<?php echo ($this->options->Duoshuo_theme) ? $this->options->Duoshuo_theme : \'default\'?>"};'."\r".'	(function() {'."\r".'		var ds = document.createElement("script");'."\r".'		ds.type = "text/javascript";ds.async = true;'."\r".'		ds.src = "http://static.duoshuo.com/embed.js";'."\r".'		ds.charset = "UTF-8";'."\r".'		(document.getElementsByTagName("head")[0] '."\r".'		|| document.getElementsByTagName("body")[0]).appendChild(ds);'."\r".'	})();'."\r".'	</script>'."\r".'<!-- Duoshuo Comment END -->'."\r".'<?php else: ?>'."\r".'<h4><?php _e("评论已关闭"); ?></h4> '."\r".'<?php endif; ?> '."\r".'</div>';
					if(!Duoshuo_Action::_engine()) {
						$button = '<button value="tcomment" class="primary" id="btn-tcomment" onclick="tcomment();">一键写入主题comments.php</button>';
					}else{
						$button = '<button value="tcomment" id="btn-tcomment" disabled>当前为云环境，不支持读写，请自行备份主题comments.php之后将代码替换</button>';
					}
 					echo '<div id="duoshuo-remote-window" class="conbox">复制以下代码，并粘帖到您主题的comments.php模版中，直接覆盖原内容即可<br/><textarea wrap="off" readonly="readonly" style="height: 480px;width:100%;font-size: 14px;line-height: 20px;color: #555;background:#eee;margin-top:5px;font-family:Georgia;border-radius:6px" onclick="this.select();">'.$code.'</textarea><p style="padding-top:5px">'.$button.'<span style="padding-left:15px"><font color="#ff0000">*</font>注：为了支持部分功能，此处代码与多说官方所给略有不同，但不影响使用</span></p></div>';
					break;
				case 'setting':
					echo '<div id="duoshuo-remote-window" class="conbox">
							<p class="title">初始化设置</p>
							<p id="ds-export">
							<button value="Export" class="primary" id="btn-export" onclick="fireExport();">点我同步一下</button>
							<!--<button value="Setting" id="btn-setting" onclick="Setting();">已同步过，直接跳过</button>-->
							</p>
							<div class="tip">
								<p><span>*</span>这将会使本地用户，文章[不会上传文章内容]，评论直接同步至多说,支持断点上传，如果出现中断，请刷新后再次点击...</p>
								<p><span>*</span>已经同步过的用户不必担心，重复同步并不会造成数据叠加，请耐心等候</p>
								<!--<p><span>*</span>如果已经同步过，误点了清空，请选后者...</p>-->
							</div>
						</div>';

					break;
				default:
					echo '<div id="duoshuo-remote-window" class="conbox"><p>多说实时同步插件 V1.1.3[基于多说官方SDK V0.3]</p><p>环境要求：PHP5.2+ typecho0.9</p><p>最新版本：<a href="http://ysido.com/duoshuo.html" target="_blank">点我查看一下</a></p><p>作　　者：<a href="http://ysido.com/" target="_blank">阳光</a> [xux851@gmail.com]</p><p>最后更新：2014年1月4日</p><p>特别感谢：<a href="http://www.y2sky.com" target="_blank">燕儿</a><a href="http://www.binjoo.net/" target="_blank">冰剑</a><a href="http://mui.me" target="_blank">西西妹</a><a href="http://shang.qq.com/wpa/qunwpa?idkey=a5a8afedf099e18ddf9b530db9217251e39001d52aace42888bf470d9b6cb86a" target="_blank">Typecho机油群</a></p><p>其它说明：<ul class="tips"><li>1.有时候会出现不能实时更新的问题，请稍后片刻，多说的服务器回PING有延时，若着急同步，请至扩展页点击同步即可</li><li>2.所有bug及反馈建议，请至 <a href="http://ysido.com" target="_blank">http://ysido.com/duoshuo.html</a></li><li>3.如果comments.php用官方默认代码，则无法更换多说主题</li><li>4.如若方便，请尽量在本地导出数据，远程可能会使你的主机不堪重负而罢工</li><li>5.因为插件暂时不支持本地操作同步远程，请用多说界面管理</li><li>6.如果无聊，请点击上方TE机油群，一起吹吹水</li><li>7.作者只是在工作闲余时间做开发，有时候反应不及时敬请谅解.</li></ul></p></div>';
					break;
			}
			?>
		</div>
	</div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>
<style>.conbox{width:1000px;border:0;padding:20px;border:1px solid #ccc;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;-webkit-box-shadow:0 0 5px rgba(0,0,0,0.1);-moz-box-shadow:0 0 5px rgba(0,0,0,0.1);box-shadow:0 0 5px rgba(0,0,0,0.1);background:#fff;margin:20px;overflow:auto}.ds-themes li{display:inline-block;margin-right:10px;overflow:hidden;padding:10px 10px 10px 0;vertical-align:top;width:280px;text-align:center}.other{border-top:#dfdfdf solid 1px;padding-top:15px}#duoshuo-remote-window p{margin:10px 20px}#duoshuo-remote-window .title{font-weight: bold;border-bottom:solid 1px #ccc;padding-bottom:8px;margin-top:25px;}.tips li{list-style:none;}.conbox .tip{margin:10px 20px;color:#aaa;}.conbox .tips{line-height: 22px;}.conbox p a{padding:0 5px;}</style>
<script>
ajaxurl = '<?php $options->index('/action/duoshuo-edit')?>';
jQuery(document).ready(function($) {
	jQuery('.<?php echo $act?>').addClass('current');
	iframe = jQuery('#duoshuo-remote-window');
	resetIframeHeight = function(){
		iframe.height(jQuery(window).height() - iframe.offset().top - 70);
	};
	resetIframeHeight();
	jQuery(window).resize(resetIframeHeight);
});
<?php 
//主题方面的JS处理
if($act == 'theme'): 
?>
function loadDuoshuoThemes(json){
	jQuery(function(){
		var html = '<li>'
				+ '<div style="width:280px;height:225px;border:1px #CCC solid;"></div>'
				+ '<h3>不使用主题</h3>'
				+ '<p>作者：<a href="http://www.duoshuo.com" target="_blank">多说官方</a></p>'
				+ '<div class="action-links">'
					+ ( "none" == "<?php echo $options->Duoshuo_theme?>"	? '<span class="">当前主题</span>'
						: '<a href="<?php $options->adminUrl('extending.php')?>?panel=Duoshuo/manage-duoshuo.php&do=manage-theme&Duoshuo_theme=none&rand=<?php echo time()?>" class="activatelink" title="停用主题">启用</a>')
				+ '</div>'
				+ '</li>';
		jQuery.each(json.response, function(key, theme){
			html += '<li>'
				+ '<img src="' + theme.screenshot + '" width="280" height="225" style="border:1px #CCC solid;" />'
				+ '<h3>' + theme.name + '</h3>'
				+ '<p>作者：<a href="' + theme.author_url + '" target="_blank">' + theme.author_name + '</a></p>'
				+ '<div class="action-links">'
					+ ( key == "<?php echo $options->Duoshuo_theme?>"	? '<span class="">当前主题</span>'
							: '<a href="<?php $options->index('/action/duoshuo-edit?do=theme'); ?>&theme=' + key + '" class="activatelink" title="启用 “' + theme.name + '”">启用</a>')
				+ '</div>'
				+ '</li>';
		});
		html +='<div style="clear:box"></div>';
		jQuery('.ds-themes').html(html);
	});
}
<?php endif ?>
<?php
//扩展用JS
if($act == 'plugin' || $act == 'setting'):
?>
function fireExport(){
	var $ = jQuery;
	$('#btn-export').removeClass('primary').attr("disabled", true);
    $('#btn-export').html('开始同步 <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
    var exportProgress = function (){
    	var rdigit = /\d/;
        $.ajax({
            url:ajaxurl,
            data:{do: 'fireExport'},
            error:duoshuoOnError,
            success:function(response) {
            	if (response.code == 0){
            		if (rdigit.test(response.progress) && !isNaN(response.progress)){
            			<?php if($act == 'setting'):?>
            				$('#btn-export').html('同步完成,请更改主题Comments.php内容以启用多说');
            				window.location.href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-getcode')?>";
            			<?php else: ?>
            				$('#btn-export').html('同步完成');
            			<?php endif ?>
            		}else{
            			var lang = {'user':'用户', 'post':'文章', 'comment':'评论'}, progress = response.progress.split('/');
            			$('#btn-export').html('正在同步' + lang[progress[0]] + '/' + progress[1] + ' <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
            			exportProgress();
            		}
            	}
            	else{
                    alert(response.errorMessage);
            	}
            },
            dataType:'json'
        });
    };
    exportProgress();
    return false;
}
function fireSyncLog(progress){
	var $ = jQuery, total = 0;
	$('#btn-import').removeClass('primary').attr("disabled", true);
    $('#btn-import').html('开始同步 <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
    var syncProgress = function () {
		$.ajax({
	        url:ajaxurl,
	        data:{do: 'Duoshuo_sync_log'},
	        error:duoshuoOnError,
	        success:function(response) {
	        	if (response.code == 0){
	        		if (response.count){
		        		total += response.count;
		        		$('#btn-import').html('已经同步了' + total + '条记录');
		        		syncProgress();
	        		}
	        		else{
	        			$('#btn-import').html('全部同步完成');
	        		}
	        	}
	        	else{
	        		alert(response.errorMessage);
	        	}
	        },
	        dataType:'json'
	    });
	};
	syncProgress();
}
function resetduoshuo(){
	if(!confirm('你确定要清空多说站点配置吗？')) return false;
	var $ = jQuery;
	$('#btn-reset').removeClass('primary').attr("disabled", true);
    $('#btn-reset').html('开始清除 <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
    var resetProgress = function () {
    	var rdigit = /\d/;
		$.ajax({
	        url:ajaxurl,
	        data:{do: 'Duoshuo_reset'},
	        error:duoshuoOnError,
	        success:function(response) {
	        	if (response.code == 0){
           	    	alert('重置完成，清刷新本页重新绑定');
            	    location.reload();
	        	}else{
	        		alert(response.errorMessage);
	        	}
	        },
	        dataType:'json'
	    });
	};
	resetProgress();
}
function deletecomments(){
	if(!confirm('你确定要清空在多说中标记为“已删除”的评论么？这将使多说中改变已删除评论的操作失效！')) return false;
	var $ = jQuery;
	$('#btn-dcomments').removeClass('primary').attr("disabled", true);
    $('#btn-dcomments').html('开始清除 <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
    var resetProgress = function () {
    	var rdigit = /\d/;
		$.ajax({
	        url:ajaxurl,
	        data:{do: 'delete_comments'},
	        error:duoshuoOnError,
	        success:function(response) {
	        	if (response.code == 0){
           	    	 $('#btn-dcomments').html('清除完成！共清除'+response.progress+'条');
	        	}else{
	        		alert(response.errorMessage);
	        	}
	        },
	        dataType:'json'
	    });
	};
	resetProgress();
}
function exprotjson(){
	$('#btn-exportjson').removeClass('primary').attr("disabled", true);
    $('#btn-exportjson').html('开始导出，稍候将自动开始下载 <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
	$('#exportjson-frm').attr("src",ajaxurl + '?do=exportjson');
	setTimeout("$('#btn-exportjson').html('导出完成')",3000);
}
function Setting(){
	var $ = jQuery;
	$('#btn-setting').removeClass('primary').attr("disabled", true);
    $('#btn-setting').html('开始进行必要检查 <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
    var settingProgress = function (){
        $.ajax({
            url:ajaxurl,
            data:{do: 'setting'},
            error:duoshuoOnError,
            success:function(response) {
            	if (response.code == 0){
            		if (response.progress == 'success'){
            			$('#btn-setting').html('初始化成功！');
            			window.location.href="<?php $options->adminUrl('extending.php?panel=Duoshuo/manage-duoshuo.php&do=manage-getcode')?>";
            		}else{
            			$('#btn-setting').html('初始化失败，请点击左侧同步按钮手动初始化...');
            		}
            	}else{
                    alert(response.errorMessage);
            	}
            },
            dataType:'json'
        });
    };
    settingProgress()
    return false;
}
<?php endif;?>
<?php if ($act == 'getcode' && !Duoshuo_Action::_engine()):?>
function tcomment(){
	var $ = jQuery;
	$('#btn-tcomment').removeClass('primary').attr("disabled", true);
    $('#btn-tcomment').html('开始写入 <img src="../usr/plugins/Duoshuo/images/waiting.gif" />');
    var exportProgress = function (){
        $.ajax({
            url:ajaxurl,
            data:{do: 'writecomments'},
            error:duoshuoOnError,
            success:function(response) {
            	if (response.code == 0){

            		$('#btn-tcomment').html('写入完成，备份文件已生成');
            	}else{
                    alert(response.errorMessage);
            	}
            },
            dataType:'json'
        });
    };
    exportProgress();
    return false;
}
<?php endif?>
function duoshuoOnError(jqXHR, textStatus){
    switch(textStatus){
    case 'parsererror':
    	alert('解析错误，联系多说客服帮助解决问题：' + jqXHR.responseText);
    	break;
    case "abort":
    	break;
    case "notmodified":
    case "error":
    case "timeout":
    default:
    	var dict = {
    		notmodified	: '没有变化',
        	error		: '出错了',
        	timeout		: '超时了'
        };
        alert(dict[textStatus] + ', 联系多说客服帮助解决问题');
    }
}
</script>
<?php if($act == 'theme'): ?>
<script src="http://<?php echo $short_name?>.duoshuo.com/api/sites/themes.jsonp?callback=loadDuoshuoThemes"></script>
<?php endif?>
<?php include 'footer.php'; ?>