<?php
include 'header.php';
include 'menu.php';

$current = $request->get('act', 'index');
$theme = $request->get('file', 'owner.html');
$title = $current == 'index' ? $menu->title : '编辑邮件模板 ' . $theme;
?>

<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?=$title?></h2>
        </div>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs fix-tabs clearfix">
                    <li<?=($current == 'index' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . CommentToMail_Plugin::$panel); ?>"><?php _e('邮件发送测试'); ?></a></li>
                    <li<?=($current == 'theme' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . CommentToMail_Plugin::$panel . '&act=theme'); ?>">
                    <?php _e('编辑邮件模板'); ?>
                    </a></li>
                    <li><a href="<?php $options->adminUrl('options-plugin.php?config=CommentToMail') ?>"><?php _e('插件设置'); ?></a></li>
                </ul>
            </div>
            
            <?php if ($current == 'index'): ?>
            <div class="typecho-edit-theme">
                <div class="col-mb-12 col-tb-8 col-9 content">
                    <?php Typecho_Widget::widget('CommentToMail_Console')->testMailForm()->render(); ?>
                </div>
            </div>
            <?php else:
                Typecho_Widget::widget('CommentToMail_Console')->to($files);
            ?>
            <div class="typecho-edit-theme">
                <div class="col-mb-12 col-tb-8 col-9 content">
                    <form method="post" name="theme" id="theme" action="<?php $options->index('/action/' . CommentToMail_Plugin::$action); ?>">
                        <label for="content" class="sr-only"><?php _e('编辑源码'); ?></label>
                        <textarea name="content" id="content" class="w-100 mono" <?php if(!$files->currentIsWriteable()): ?>readonly<?php endif; ?>><?php echo $files->currentContent(); ?></textarea>
                        <p class="submit">
                            <?php if($files->currentIsWriteable()): ?>
                            <input type="hidden" name="do" value="editTheme" />
                            <input type="hidden" name="edit" value="<?php echo $files->currentFile(); ?>" />
                            <button type="submit" class="btn primary"><?php _e('保存文件'); ?></button>
                            <?php else: ?>
                                <em><?php _e('此文件无法写入'); ?></em>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
                <ul class="col-mb-12 col-tb-4 col-3">
                    <li><strong>模板文件</strong></li>
                    <?php while($files->next()): ?>
                    <li<?php if($files->current): ?> class="current"<?php endif; ?>>
                    <a href="<?php $options->adminUrl('extending.php?panel=' . CommentToMail_Plugin::$panel . '&act=theme' . '&file=' . $files->file); ?>"><?php $files->file(); ?></a></li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
