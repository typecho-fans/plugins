<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
$jqueryScriptUrl = Helper::options()->pluginUrl . '/Like/js/jquery.js';
$macaroonScriptUrl = Helper::options()->pluginUrl . '/Like/js/jquery.fs.macaroon.js';
$settings = Helper::options()->plugin('Like');
$thumbUpImg = Helper::options()->pluginUrl . '/Like/thumb_up.png';
if($settings->jquery){
   echo '<script src="'.$jqueryScriptUrl.'"></script>'; 
}
?>
    <style type='text/css'>
		.fa-thumbs-up{margin-right: 5px;top: 2px;width: 16px;height: 16px;display: inline-block; background: url(<?php echo $thumbUpImg; ?>) no-repeat left center; }
    </style>

<script type="text/javascript" src="<?php echo $macaroonScriptUrl; ?>"></script>
<script>
    $(".<?php echo $settings->likeClass; ?>").on("click", function(){
    	var th = $(this);
		var id = th.attr('data-pid');
		var cookies = $.macaroon('_syan_like') || "";
		if (!id || !/^\d{1,10}$/.test(id)) return;
		if (-1 !== cookies.indexOf("," + id + ",")) return alert("您已经赞过了！");
		cookies ? cookies.length >= 160 ? (cookies = cookies.substring(0, cookies.length - 1), cookies = cookies.substr
(1).split(","), cookies.splice(0, 1), cookies.push(id), cookies = cookies.join(","), $.macaroon("_syan_like", "," + cookies + 
",")) : $.macaroon("_syan_like", cookies + id + ",") : $.macaroon("_syan_like", "," + id + ",");
		$.post('<?php Helper::options()->index('/action/like?up'); ?>',{
		cid:id
		},function(data){
		th.addClass('actived');
		var zan = th.find('span').text();
		th.find('span').text(parseInt(zan) + 1);
		},'json');
	});
</script>
