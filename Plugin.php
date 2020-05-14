<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Markdown 编辑器 <a href="https://pandao.github.io/editor.md/" target="_blank">Editor.md</a> for Typecho
 * 
 * @package EditorMD
 * @author DT27
 * @version 1.4.0
 * @link https://dt27.org/php/editormd-for-typecho/
 */
class EditorMD_Plugin implements Typecho_Plugin_Interface
{
    public static $count = 0;
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/write-post.php')->richEditor = array('EditorMD_Plugin', 'Editor');
        Typecho_Plugin::factory('admin/write-page.php')->richEditor = array('EditorMD_Plugin', 'Editor');

        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('EditorMD_Plugin', 'content');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerpt = array('EditorMD_Plugin', 'excerpt');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('EditorMD_Plugin','footerJS');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $emoji = new Typecho_Widget_Helper_Form_Element_Radio('emoji',
            array(
                '1' => '是',
                '0' => '否',
            ),'1', _t('启用 Emoji 表情'), _t('启用后可在编辑器里插入 Emoji 表情符号，前台会加载13KB的js文件将表情符号转为表情图片(图片来自Staticfile CDN)'));
        $form->addInput($emoji);

        $isActive = new Typecho_Widget_Helper_Form_Element_Radio('isActive',
            array(
                '1' => '是',
                '0' => '否',
            ),'1', _t('接管前台Markdown解析'), _t('启用后，插件将接管前台 Markdown 解析，使用与后台编辑器一致的 <a href="https://github.com/chjj/marked" target="_blank">marked.js</a> 解析器。'));
        $form->addInput($isActive);

        $isToc = new Typecho_Widget_Helper_Form_Element_Radio('isToc',
            array(
                '1' => '是',
                '0' => '否',
            ),'1', _t('启用自动生成目录(下拉菜单) ToC/ToCM功能'), _t('Table of Contents (ToC)'));
        $form->addInput($isToc);
        $isTask = new Typecho_Widget_Helper_Form_Element_Radio('isTask',
            array(
                '1' => '是',
                '0' => '否',
            ),'1', _t('启用Github Flavored Markdown task lists'), _t(''));
        $form->addInput($isTask);
        $isTex = new Typecho_Widget_Helper_Form_Element_Radio('isTex',
            array(
                '1' => '是',
                '0' => '否',
            ),'1', _t('启用科学公式 TeX'), _t('TeX/LaTeX (Based on KaTeX)'));
        $form->addInput($isTex);
        $isFlow = new Typecho_Widget_Helper_Form_Element_Radio('isFlow',
            array(
                '1' => '是',
                '0' => '否',
            ),'0', _t('启用流程图'), _t('FlowChart example'));
        $form->addInput($isFlow);
        $isSeq = new Typecho_Widget_Helper_Form_Element_Radio('isSeq',
            array(
                '1' => '是',
                '0' => '否',
            ),'0', _t('启用时序/序列图'), _t('Sequence Diagram example'));
        $form->addInput($isSeq);

    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插入编辑器
     */
    public static function Editor()
    {
        $options = Helper::options();
        $cssUrl = $options->pluginUrl.'/EditorMD/css/editormd.min.css';
        $jsUrl = $options->pluginUrl.'/EditorMD/js/editormd.min.js';
        $editormd = Typecho_Widget::widget('Widget_Options')->plugin('EditorMD');
        ?>
        <link rel="stylesheet" href="<?php echo $cssUrl; ?>" />
        <script>
            var emojiPath = '<?php echo $options->pluginUrl; ?>';
            var uploadURL = '<?php Helper::security()->index('/action/upload?cid=CID'); ?>';
        </script>
        <script type="text/javascript" src="<?php echo $jsUrl; ?>"></script>
        <script>
            $(document).ready(function() {

                var textarea = $('#text').parent("p");
                var isMarkdown = $('[name=markdown]').val()?1:0;
                if (!isMarkdown) {
                    var notice = $('<div class="message notice"><?php _e('本文Markdown解析已禁用！'); ?> '
                        + '<button class="btn btn-xs primary yes"><?php _e('启用'); ?></button> '
                        + '<button class="btn btn-xs no"><?php _e('保持禁用'); ?></button></div>')
                        .hide().insertBefore(textarea).slideDown();

                    $('.yes', notice).click(function () {
                        notice.remove();
                        $('<input type="hidden" name="markdown" value="1" />').appendTo('.submit');
                    });

                    $('.no', notice).click(function () {
                        notice.remove();
                    });
                }
                    $('#text').wrap("<div id='text-editormd'></div>");
                    postEditormd = editormd("text-editormd", {
                        width: "100%",
                        height: 640,
                        path: '<?php echo $options->pluginUrl ?>/EditorMD/lib/',
                        toolbarAutoFixed: false,
                        htmlDecode: true,
                        emoji: <?php echo $editormd->emoji ? 'true' : 'false'; ?>,
                        tex: <?php echo $editormd->isTex ? 'true' : 'false'; ?>,
                        toc: <?php echo $editormd->isToc ? 'true' : 'false'; ?>,
                        tocm: <?php echo $editormd->isToc ? 'true' : 'false'; ?>,    // Using [TOCM]
                        taskList: <?php echo $editormd->isTask ? 'true' : 'false'; ?>,
                        flowChart: <?php echo $editormd->isFlow ? 'true' : 'false'; ?>,  // 默认不解析
                        sequenceDiagram: <?php echo $editormd->isSeq ? 'true' : 'false'; ?>,
                        toolbarIcons: function () {
                            return ["undo", "redo", "|", "bold", "del", "italic", "quote", "h1", "h2", "h3", "h4", "|", "list-ul", "list-ol", "hr", "|", "link", "reference-link", "image", "code", "preformatted-text", "code-block", "table", "datetime"<?php echo $editormd->emoji ? ', "emoji"' : ''; ?>, "html-entities", "more", "|", "goto-line", "watch", "preview", "fullscreen", "clear", "|", "help", "info", "|", "isMarkdown"]
                        },
                        toolbarIconsClass: {
                            more: "fa-newspaper-o",  // 指定一个FontAawsome的图标类
                            isMarkdown: "fa-power-off fun"
                        },
                        // 自定义工具栏按钮的事件处理
                        toolbarHandlers: {
                            /**
                             * @param {Object}      cm         CodeMirror对象
                             * @param {Object}      icon       图标按钮jQuery元素对象
                             * @param {Object}      cursor     CodeMirror的光标对象，可获取光标所在行和位置
                             * @param {String}      selection  编辑器选中的文本
                             */
                            more: function (cm, icon, cursor, selection) {
                                cm.replaceSelection("<!--more-->");
                            },
                            isMarkdown: function (cm, icon, cursor, selection) {
                                if(!$("div.message.notice").html()){
                                var isMarkdown = $('[name=markdown]').val()?$('[name=markdown]').val():0;
                                if (isMarkdown==1) {
                                    var notice = $('<div class="message notice"><?php _e('本文Markdown解析已启用！'); ?> '
                                        + '<button class="btn btn-xs no"><?php _e('禁用'); ?></button> '
                                        + '<button class="btn btn-xs primary yes"><?php _e('保持启用'); ?></button></div>')
                                        .hide().insertBefore(textarea).slideDown();

                                    $('.yes', notice).click(function () {
                                        notice.remove();
                                    });

                                    $('.no', notice).click(function () {
                                        notice.remove();
                                        $("[name=markdown]").val(0);
                                        postEditormd.unwatch();
                                    });
                                } else {
                                    var notice = $('<div class="message notice"><?php _e('本文Markdown解析已禁用！'); ?> '
                                        + '<button class="btn btn-xs primary yes"><?php _e('启用'); ?></button> '
                                        + '<button class="btn btn-xs no"><?php _e('保持禁用'); ?></button></div>')
                                        .hide().insertBefore(textarea).slideDown();

                                    $('.yes', notice).click(function () {
                                        notice.remove();
                                        postEditormd.watch();
                                        if(!$("[name=markdown]").val())
                                            $('<input type="hidden" name="markdown" value="1" />').appendTo('.submit');
                                        else
                                            $("[name=markdown]").val(1);
                                    });

                                    $('.no', notice).click(function () {
                                        notice.remove();
                                    });
                                }
                            }
                            }
                        },
                        lang: {
                            toolbar: {
                                more: "插入摘要分隔符",
                                isMarkdown: "非Markdown模式"
                            }
                        },
                    });

                    // 优化图片及文件附件插入 Thanks to Markxuxiao
                    Typecho.insertFileToEditor = function (file, url, isImage) {
                        html = isImage ? '![' + file + '](' + url + ')'
                            : '[' + file + '](' + url + ')';
                        postEditormd.insertValue(html);
                    };

                    // 支持黏贴图片直接上传
                    $(document).on('paste', function(event) {
                        event = event.originalEvent;
                        var cbd = event.clipboardData;
                        var ua = window.navigator.userAgent;
                        if (!(event.clipboardData && event.clipboardData.items)) {
                            return;
                        }

                        if (cbd.items && cbd.items.length === 2 && cbd.items[0].kind === "string" && cbd.items[1].kind === "file" &&
                            cbd.types && cbd.types.length === 2 && cbd.types[0] === "text/plain" && cbd.types[1] === "Files" &&
                            ua.match(/Macintosh/i) && Number(ua.match(/Chrome\/(\d{2})/i)[1]) < 49){
                            return;
                        }

                        var itemLength = cbd.items.length;

                        if (itemLength == 0) {
                            return;
                        }

                        if (itemLength == 1 && cbd.items[0].kind == 'string') {
                            return;
                        }

                        if ((itemLength == 1 && cbd.items[0].kind == 'file')
                                || itemLength > 1
                            ) {
                            for (var i = 0; i < cbd.items.length; i++) {
                                var item = cbd.items[i];

                                if(item.kind == "file") {
                                    var blob = item.getAsFile();
                                    if (blob.size === 0) {
                                        return;
                                    }
                                    var ext = 'jpg';
                                    switch(blob.type) {
                                        case 'image/jpeg':
                                        case 'image/pjpeg':
                                            ext = 'jpg';
                                            break;
                                        case 'image/png':
                                            ext = 'png';
                                            break;
                                        case 'image/gif':
                                            ext = 'gif';
                                            break;
                                    }
                                    var formData = new FormData();
                                    formData.append('blob', blob, Math.floor(new Date().getTime() / 1000) + '.' + ext);
                                    var uploadingText = '![图片上传中(' + i + ')...]';
                                    var uploadFailText = '![图片上传失败(' + i + ')]'
                                    postEditormd.insertValue(uploadingText);
                                    $.ajax({
                                        method: 'post',
                                        url: uploadURL.replace('CID', $('input[name="cid"]').val()),
                                        data: formData,
                                        contentType: false,
                                        processData: false,
                                        success: function(data) {
                                            if (data[0]) {
                                                postEditormd.setValue(postEditormd.getValue().replace(uploadingText, '![](' + data[0] + ')'));
                                            } else {
                                                postEditormd.setValue(postEditormd.getValue().replace(uploadingText, uploadFailText));
                                            }
                                        },
                                        error: function() {
                                            postEditormd.setValue(postEditormd.getValue().replace(uploadingText, uploadFailText));
                                        }
                                    });
                                }
                            }

                        }

                    });

            });
        </script>
        <?php
    }
    /**
     * emoji 解析器
     */
    public static function footerJS($conent)
    {
        $options = Helper::options();
        $pluginUrl = $options->pluginUrl.'/EditorMD';
        $editormd = Typecho_Widget::widget('Widget_Options')->plugin('EditorMD');
        if($editormd->emoji){
?>
<link rel="stylesheet" href="<?php echo $pluginUrl; ?>/css/emojify.min.css" />
<?php }if($editormd->emoji || ($editormd->isActive == 1 && $conent->isMarkdown)){ ?>
<script type="text/javascript">
    window.jQuery || document.write(unescape('%3Cscript%20type%3D%22text/javascript%22%20src%3D%22<?php echo $pluginUrl; ?>/lib/jquery.min.js%22%3E%3C/script%3E'));
</script>
<?php }if($editormd->isActive == 1 && $conent->isMarkdown){ ?>
<script src="<?php echo $pluginUrl; ?>/lib/marked.min.js"></script>
<script src="<?php echo $pluginUrl; ?>/js/editormd.min.js"></script>
<?php if($editormd->isSeq == 1||$editormd->isFlow == 1){ ?>
<script src="<?php echo $pluginUrl; ?>/lib/raphael.min.js"></script>
<script src="<?php echo $pluginUrl; ?>/lib/underscore.min.js"></script>
<?php } if($editormd->isFlow == 1){ ?>
<script src="<?php echo $pluginUrl; ?>/lib/flowchart.min.js"></script>
<script src="<?php echo $pluginUrl; ?>/lib/jquery.flowchart.min.js"></script>
<?php } if($editormd->isSeq == 1){ ?>
<script src="<?php echo $pluginUrl; ?>/lib/sequence-diagram.min.js"></script>
<?php }}if($editormd->emoji){ ?>
<script src="<?php echo $pluginUrl; ?>/js/emojify.min.js"></script>
<?php }if($editormd->emoji||($editormd->isActive == 1 && $conent->isMarkdown)){?>
<script type="text/javascript">
$(function() {
<?php if($editormd->isActive == 1 && $conent->isMarkdown){ ?>
    var parseMarkdown = function () {
        var markdowns = document.getElementsByClassName("md_content");
        $(markdowns).each(function () {
            var markdown = $(this).children("#append-test").text();
            //$('#md_content_'+i).text('');
            var editormdView;
            editormdView = editormd.markdownToHTML($(this).attr("id"), {
                markdown: markdown,//+ "\r\n" + $("#append-test").text(),
                toolbarAutoFixed: false,
                htmlDecode: true,
                emoji: <?php echo $editormd->emoji ? 'true' : 'false'; ?>,
                tex: <?php echo $editormd->isTex ? 'true' : 'false'; ?>,
                toc: <?php echo $editormd->isToc ? 'true' : 'false'; ?>,
                tocm: <?php echo $editormd->isToc ? 'true' : 'false'; ?>,
                taskList: <?php echo $editormd->isTask ? 'true' : 'false'; ?>,
                flowChart: <?php echo $editormd->isFlow ? 'true' : 'false'; ?>,
                sequenceDiagram: <?php echo $editormd->isSeq ? 'true' : 'false'; ?>,
            });
        });
    };
    parseMarkdown();
    $(document).on('pjax:complete', function () {
        parseMarkdown()
    });
<?php }if($editormd->emoji){ ?>
    emojify.setConfig({
        img_dir: "//cdn.staticfile.org/emoji-cheat-sheet/1.0.0",
        blacklist: {
            'ids': [],
            'classes': ['no-emojify'],
            'elements': ['^script$', '^textarea$', '^pre$', '^code$']
        },
    });
    emojify.run();
<?php }
if(isset(Typecho_Widget::widget('Widget_Options')->plugins['activated']['APlayer'])){
    ?>
    var len = aPlayerOptions.length;
    for(var ii=0;ii<len;ii++){
        aPlayers[ii] = new APlayer({
            element: document.getElementById('player' + aPlayerOptions[ii]['id']),
            narrow: false,
            autoplay: aPlayerOptions[ii]['autoplay'],
            showlrc: aPlayerOptions[ii]['showlrc'],
            music: aPlayerOptions[ii]['music'],
            theme: aPlayerOptions[ii]['theme']
        });
        aPlayers[ii].init();
    }
    <?php
}
?>
});
</script>
<?php
}
    }
    public static function content($text, $conent){
        self::$count++;
        $editormd = Typecho_Widget::widget('Widget_Options')->plugin('EditorMD');
        $text = $conent->isMarkdown ? ($editormd->isActive == 1?$text:$conent->markdown($text))
            : $conent->autoP($text);
        if($editormd->isActive == 1 && $conent->isMarkdown)
            return '<div id="md_content_'.self::$count.'" class="md_content" style="min-height: 50px;"><textarea id="append-test" style="display:none;">'.$text.'</textarea></div>';
        else
            return $text;
    }
    public static function excerpt($text, $conent){
        self::$count++;
        $text = $conent->isMarkdown ? $conent->markdown($text)
            : $conent->autoP($text);
        return $text;
    }
}
