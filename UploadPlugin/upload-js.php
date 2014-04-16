<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<script src="<?php $options->pluginUrl('/UploadPlugin/jquery.form.min.js');?>"></script>
<script src="<?php $options->pluginUrl('/UploadPlugin/jquery.uploadfile.min.js');?>"></script>
<script>
$(document).ready(function()
{
	$("#singleupload").uploadFile({
		url:'<?php  $options->index('/action/upload-plugin?upload');?>',	
		allowedTypes:"zip",	
		showStatusAfterSuccess:false,
		showAbort:false,
		showDone:false,
		dragDropStr: "",
		uploadButtonClass:"",
		extErrorStr:"仅支持文件类型:",
		onSubmit:function(files)
		{
			 popupDiv("loading");
		},
		onSuccess:function(files,data,xhr)
		{
			hideDiv("loading");
			window.location.reload();
		},
		onError: function(files,status,errMsg){
			alert(errMsg);
		}
	});
	
	$("#inst").click(function(){        
        var flink = $("#adrs").val();
        var url = "<?php $options->index('/action/upload-plugin'); ?>";
        if(flink === ""){
            alert("地址为空");
        }else{
            var i = flink.length;
            var e = flink.substring(i-3);
            var ck = false;
            if(e !== "zip"){                
                if(confirm("链接中未包含zip文件名，你确认此链接能获得zip文件?")) ck = true;              
            }else{
                ck = true;
            }
            if(ck){
                popupDiv("loading");
                $.get(url, { upload: flink },
                  function(data){
                    hideDiv("loading");
                    alert(data);
                    window.location.reload();
                  });
            }
        }
    });
	
	function popupDiv(div_id) {   
		var div_obj = $("#"+div_id);  
		var windowWidth = document.body.clientWidth;       
		var windowHeight = document.body.clientHeight;  
		var popupHeight = div_obj.height();       
		var popupWidth = div_obj.width();    

		div_obj.css({"position": "absolute"})   
		.animate({left: windowWidth/2-popupWidth/2,    
		top: 250, opacity: "show" }, "slow");                           
    }
    function hideDiv(div_id) {   
        $("#mask").remove();   
        $("#" + div_id).animate({left: 0, top: 0, opacity: "hide" }, "normal");   
    }

});
</script>