<?php
/**
 * 投稿页面模板
 *
 * @package custom
 */

/* 插件地址 */
$resUrl = Typecho_Common::url('Contribute/', Helper::options()->pluginUrl);

/* 载入分类 */
$this->widget('Widget_Metas_Category_List')->to($category);
?>
<!DOCTYPE HTML>
<html class="no-js">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
        <meta name="renderer" content="webkit">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php $this->archiveTitle(array(
            'category'  =>  _t('分类 %s 下的文章'),
            'search'    =>  _t('包含关键字 %s 的文章'),
            'tag'       =>  _t('标签 %s 下的文章'),
            'author'    =>  _t('%s 发布的文章')
        ), '', ' - '); ?><?php $this->options->title(); ?></title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="stylesheet" href="<?php echo $resUrl . 'css/normalize.css'; ?>">
        <link rel="stylesheet" href="<?php echo $resUrl . 'css/grid.css'; ?>">
        <link rel="stylesheet" href="<?php echo $resUrl . 'css/style.css'; ?>">
    </head>
<body>
<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2>撰写新稿件 <span><a href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a></span></h2>
        </div>
        <div class="row typecho-page-main typecho-post-area">
            <form action="<?php $this->options->index('/action/contribute?write'); ?>" method="post" name="write_post">
                <div class="col-mb-12 col-tb-9" id="main" role="main">
                    <p class="title">
                        <label for="title" class="sr-only"><?php _e('标题'); ?></label>
                        <input type="text" id="title" name="title" autocomplete="off" value="" placeholder="标题" class="w-100 text title">
                    </p>
                    <?php $permalink = Typecho_Common::url($this->options->routingTable['post']['url'], $this->options->index);
                    list ($scheme, $permalink) = explode(':', $permalink, 2);
                    $permalink = ltrim($permalink, '/');
                    $permalink = preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $permalink);
                    ?>
                    <p class="mono url-slug">
                        <label for="slug" class="sr-only"><?php _e('网址缩略名'); ?></label>
                       <?php echo preg_replace("/\{slug\}/i", '<input type="text" id="slug" name="slug" autocomplete="off" value="" class="mono" style="width: 147px;">', $permalink); ?>
                    </p>
                    <p>
                        <label for="text" class="sr-only"><?php _e('文章内容'); ?></label>
                        <textarea style="height: 455px" autocomplete="off" id="text" name="text" class="w-100 mono"></textarea>
                    </p>
                    <p class="submit clearfix">
                        <span class="right">
                            <button type="submit" name="do" value="publish" class="primary" id="btn-submit"><?php _e('提交稿件'); ?></button>
                            <?php if ($this->options->markdown): ?>
                            <input type="hidden" name="markdown" value="1" />
                            <?php endif; ?>
                        </span>
                    </p>
                </div><!-- end #main -->
                <div id="edit-secondary" class="col-mb-12 col-tb-3" role="complementary">
                    <div id="tab-advance" class="tab-content">
                        <section class="typecho-post-option" role="application">
                            <label for="author" class="typecho-label"><?php _e('撰稿人'); ?></label>
                            <p><input class="typecho-author w-100" type="text" name="author" id="author" value="" /></p>
                        </section>
                        <section class="typecho-post-option" role="application">
                            <label for="date" class="typecho-label"><?php _e('发布日期'); ?></label>
                            <p><input class="typecho-date w-100" type="text" name="date" id="date" value="<?php echo date('Y-m-d H:i', $this->options->gmtTime + $this->options->timezone - $this->options->serverTimezone); ?>" /></p>
                        </section>
                        <section class="typecho-post-option category-option">
                            <label class="typecho-label"><?php _e('分类'); ?></label>
                            <ul>
                                <?php while($category->next()): ?>
                                <li><input type="checkbox" id="category-<?php $category->mid(); ?>" value="<?php $category->mid(); ?>" name="category[]" />
                                <label for="category-<?php $category->mid(); ?>"><?php $category->name(); ?></label></li>
                                <?php endwhile; ?>
                            </ul>
                        </section>
                        <section class="typecho-post-option">
                            <label for="token-input-tags" class="typecho-label"><?php _e('标签'); ?></label>
                            <p><input id="tags" name="tags" type="text" value="" class="w-100 text" /></p>
                            <p class="description"><?php _e('多个标签，请用英文逗号" , "隔开'); ?></p>
                        </section>
                    </div>
                </div><!-- end #edit-secondary -->
            </form>
        </div>
    </div>
</div>
<div class="typecho-foot" role="contentinfo">
    <div class="copyright">
        <p><?php _e('投稿由 <a href="https://github.com/typecho-fans/plugins/tree/master/Contribute" target="_blank">Contribute</a> 插件提供'); ?></p>
    </div>
    <nav class="resource">
        <a href="https://github.com/typecho-fans/plugins/blob/master/Contribute/plugins/Contribute/README.md" target="_blank">插件说明</a> •
        <a href="https://github.com/typecho-fans/plugins/issues" target="_blank">报告错误</a> •
        <a href="https://github.com/typecho-fans/plugins" target="_blank">插件下载</a>
    </nav>
</div>
<script src="<?php echo $resUrl . 'js/LAB.min.js'; ?>"></script>
<script>
$LAB.script("<?php echo $resUrl . 'js/jquery.js'; ?>").wait()
    .script("<?php echo $resUrl . 'js/jquery-ui.js'; ?>")
    .script("<?php echo $resUrl . 'js/notice.js'; ?>")
    .script("<?php echo $resUrl . 'js/jquery.mask.input.js'; ?>")
    .script("<?php echo $resUrl . 'js/timepicker.js'; ?>")
    .script("<?php echo $resUrl . 'js/jquery.resize.js'; ?>")
<?php if ($this->options->markdown): ?>
    .script("<?php echo $resUrl . 'js/pagedown.js'; ?>")
    .script("<?php echo $resUrl . 'js/pagedown-extra.js'; ?>")
    .script("<?php echo $resUrl . 'js/diff.js'; ?>")
<?php endif; ?>
    .wait(function() {

        $(function() {
            // 处理消息机制
            (function () {
                var prefix = '<?php echo Typecho_Cookie::getPrefix(); ?>',
                    cookies = {
                        notice      :   $.cookie(prefix + '__typecho_notice'),
                        noticeType  :   $.cookie(prefix + '__typecho_notice_type'),
                        highlight   :   $.cookie(prefix + '__typecho_notice_highlight')
                    },
                    path = '<?php $parts = parse_url($this->options->siteUrl);
                        echo empty($parts['path']) ? '/' : $parts['path']; ?>';

                if (!!cookies.notice && 'success|notice|error'.indexOf(cookies.noticeType) >= 0) {
                    var head = $('.typecho-head-nav'),
                        p = $('<div class="message popup ' + cookies.noticeType + '">'
                        + '<ul><li>' + $.parseJSON(cookies.notice).join('</li><li>')
                        + '</li></ul></div>'), offset = 0;

                    if (head.length > 0) {
                        p.insertAfter(head);
                        offset = head.outerHeight();
                    } else {
                        p.prependTo(document.body);
                    }

                    function checkScroll () {
                        if ($(window).scrollTop() >= offset) {
                            p.css({
                                'position'  :   'fixed',
                                'top'       :   0
                            });
                        } else {
                            p.css({
                                'position'  :   'absolute',
                                'top'       :   offset
                            });
                        }
                    }

                    $(window).scroll(function () {
                        checkScroll();
                    });

                    checkScroll();

                    p.slideDown(function () {
                        var t = $(this), color = '#C6D880';

                        if (t.hasClass('error')) {
                            color = '#FBC2C4';
                        } else if (t.hasClass('notice')) {
                            color = '#FFD324';
                        }

                        t.effect('highlight', {color : color})
                            .delay(5000).slideUp(function () {
                            $(this).remove();
                        });
                    });


                    $.cookie(prefix + '__typecho_notice', null, {path : path});
                    $.cookie(prefix + '__typecho_notice_type', null, {path : path});
                }

                if (cookies.highlight) {
                    $('#' + cookies.highlight).effect('highlight', 1000);
                    $.cookie(prefix + '__typecho_notice_highlight', null, {path : path});
                }
            })();

            // 日期时间控件
            $('#date').mask('9999-99-99 99:99').datetimepicker({
                currentText     :   '现在',
                prevText        :   '上一月',
                nextText        :   '下一月',
                monthNames      :   ['一月', '二月', '三月', '四月',
                    '五月', '六月', '七月', '八月',
                    '九月', '十月', '十一月', '十二月'],
                dayNames        :   ['星期日', '星期一', '星期二',
                    '星期三', '星期四', '星期五', '星期六'],
                dayNamesShort   :   ['周日', '周一', '周二', '周三',
                    '周四', '周五', '周六'],
                dayNamesMin     :   ['日', '一', '二', '三',
                    '四', '五', '六'],
                closeText       :   '完成',
                timeOnlyTitle   :   '选择时间',
                timeText        :   '时间',
                hourText        :   '时',
                amNames         :   ['上午', 'A'],
                pmNames         :   ['下午', 'P'],
                minuteText      :   '分',
                secondText      :   '秒',

                dateFormat      :   'yy-mm-dd',
                hour            :   (new Date()).getHours(),
                minute          :   (new Date()).getMinutes()
            });

            // 聚焦
            $('#title').select();

            // 自动拉伸
            function editorResize(id, url) {
                $('#' + id).resizeable({
                    minHeight   :   100,
                    afterResize :   function (h) {
                        $.post(url, {size : h});
                    }
                })
            }
            editorResize('text', '<?php $this->options->index('/action/ajax?do=editorResize'); ?>');

            // 窗口检测

            var submitted = false, form = $('form[name=write_post],form[name=write_page]').submit(function () {
                submitted = true;
            }), savedData = null;

            // 自动保存

            // 自动检测离开页
            var lastData = form.serialize();

            $(window).bind('beforeunload', function () {
                if (!!savedData) {
                    lastData = savedData;
                }

                if (form.serialize() != lastData && !submitted) {
                    return '内容已经改变尚未保存, 您确认要离开此页面吗?';
                }
            });

<?php if ($this->options->markdown): ?>
            // Markdown编辑器
            var textarea = $('#text'),
                toolbar = $('<div class="editor" id="wmd-button-bar" />').insertBefore(textarea.parent())
                preview = $('<div id="wmd-preview" class="wmd-hidetab" />').insertAfter('.editor');

            var options = {}, isMarkdown = <?php echo intval($this->options->markdown); ?>;

            options.strings = {
                bold: '加粗 <strong> Ctrl+B',
                boldexample: '加粗文字',

                italic: '斜体 <em> Ctrl+I',
                italicexample: '斜体文字',

                link: '链接 <a> Ctrl+L',
                linkdescription: '请输入链接描述',

                quote:  '引用 <blockquote> Ctrl+Q',
                quoteexample: '引用文字',

                code: '代码 <pre><code> Ctrl+K',
                codeexample: '请输入代码',

                image: '图片 <img> Ctrl+G',
                imagedescription: '请输入图片描述',

                olist: '数字列表 <ol> Ctrl+O',
                ulist: '普通列表 <ul> Ctrl+U',
                litem: '列表项目',

                heading: '标题 <h1>/<h2> Ctrl+H',
                headingexample: '标题文字',

                hr: '分割线 <hr> Ctrl+R',
                more: '摘要分割线 <!--more--> Ctrl+M',

                undo: '撤销 - Ctrl+Z',
                redo: '重做 - Ctrl+Y',
                redomac: '重做 - Ctrl+Shift+Z',

                fullscreen: '全屏 - Ctrl+J',
                exitFullscreen: '退出全屏 - Ctrl+E',
                fullscreenUnsupport: '此浏览器不支持全屏操作',

                imagedialog: '<p><b>插入图片</b></p><p>请在下方的输入框内输入要插入的远程图片地址</p><p>您也可以使用附件功能插入上传的本地图片</p>',
                linkdialog: '<p><b>插入链接</b></p><p>请在下方的输入框内输入要插入的链接地址</p>',

                ok: '确定',
                cancel: '取消',

                help: 'Markdown语法帮助'
            };

            var converter = new Markdown.Converter(),
                editor = new Markdown.Editor(converter, '', options),
                diffMatch = new diff_match_patch(), last = '', preview = $('#wmd-preview'),
                mark = '@mark' + Math.ceil(Math.random() * 100000000) + '@',
                span = '<span class="diff" />';

            // 设置markdown
            Markdown.Extra.init(converter, {
                extensions  :   ["tables", "fenced_code_gfm", "def_list", "attr_list", "footnotes"]
            });

            var input = $('#text'), th = textarea.height(), ph = preview.height();

            editor.hooks.chain('enterFakeFullScreen', function () {
                th = textarea.height();
                ph = preview.height();
                $(document.body).addClass('fullscreen');
                var h = $(window).height() - toolbar.outerHeight();

                textarea.css('height', h);
                preview.css('height', h);
            });

            editor.hooks.chain('enterFullScreen', function () {
                $(document.body).addClass('fullscreen');

                var h = window.screen.height - toolbar.outerHeight();
                textarea.css('height', h);
                preview.css('height', h);
            });

            editor.hooks.chain('exitFullScreen', function () {
                $(document.body).removeClass('fullscreen');
                textarea.height(th);
                preview.height(ph);
            });

            function initMarkdown() {
                editor.run();

                var imageButton = $('#wmd-image-button'),
                    linkButton = $('#wmd-link-button');

                // 编辑预览切换
                var edittab = $('.editor').prepend('<div class="wmd-edittab"><a href="#wmd-editarea" class="active">撰写</a><a href="#wmd-preview">预览</a></div>'),
                    editarea = $(textarea.parent()).attr("id", "wmd-editarea");

                $(".wmd-edittab a").click(function() {
                    $(".wmd-edittab a").removeClass('active');
                    $(this).addClass("active");
                    $("#wmd-editarea, #wmd-preview").addClass("wmd-hidetab");

                    var selected_tab = $(this).attr("href"),
                        selected_el = $(selected_tab).removeClass("wmd-hidetab");

                    // 预览时隐藏编辑器按钮
                    if (selected_tab == "#wmd-preview") {
                        $("#wmd-button-row").addClass("wmd-visualhide");
                    } else {
                        $("#wmd-button-row").removeClass("wmd-visualhide");
                    }

                    // 预览和编辑窗口高度一致
                    $("#wmd-preview").outerHeight($("#wmd-editarea").innerHeight());

                    return false;
                });
            }

            if (isMarkdown) {
                initMarkdown();
            }
<?php endif; ?>
        });

    });
</script>
</body>
</html>