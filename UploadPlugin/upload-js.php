<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<script>
$(document).ready(function() {
    var errorWord = '<?php $val = function_exists('ini_get') ? trim(ini_get('upload_max_filesize')) : 0;
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        $val = number_format(ceil($val / (1024 *1024)));
        _e('文件上传失败, 请确认文件尺寸没有超过 %s 并且服务器文件目录可以写入', "{$val}Mb"); ?>',
        loading = $('<img src="<?php $options->adminUrl('img/ajax-loader.gif'); ?>" style="display:none" />')
            .appendTo(document.body);

    function fileUploadStart () { popupDiv("loading"); }

    function fileUploadComplete (id,url,data) { 
        hideDiv("loading"); 
        alert(data);
        window.location.reload();
    } 

    $('.upload-file').fileUpload({
        url         :   '<?php  $options->index('/action/upload-plugin?upload');?>',
        types       :    [".zip"],
        typesError  :   '<?php _e('文件 %s 的类型不被支持'); ?>',
        onUpload    :   fileUploadStart,
        onError     :   function (id,word) {
            hideDiv("loading");
            alert(word);
        },
        onComplete  :   fileUploadComplete
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

