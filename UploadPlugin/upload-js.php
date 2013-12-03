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

    function fileUploadStart () {  }

    function fileUploadComplete () { }

    $('.upload-file').fileUpload({
        url         :   '<?php  $options->index('/action/upload-plugin?upload');?>',
        types       :    [".zip"],
        typesError  :   '<?php _e('文件 %s 的类型不被支持'); ?>',
        onUpload    :   fileUploadStart,
        onError     :   function (id) {            
            alert(errorWord);
        },
        onComplete  :   fileUploadComplete
    });
    
});
</script>

