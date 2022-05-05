<?php
include 'header.php';
include 'menu.php';

use Typecho\Widget;
use Widget\Notice;
use Widget\Options;
use TypechoPlugin\CommentNotifier\Plugin;

/* @var Options $options */
Options::alloc()->to($options);

/** 初始化上下文 */
$request = $options->request;
$response = $options->response;
$current = $request->get('act', 'index');
$theme = $request->get('file', 'owner.html');
$title = '编辑邮件模板 ' . $theme;
if ($current == 'index') {
    $title = '邮件发信模板';
}

if ($request->is('do=editTheme')) {
    editTheme($request->edit);
}
function editTheme($file)
{
    $template = Plugin::configStr('template', 'default');
    $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . $file;
    if (file_exists($path) && is_writeable($path)) {
        $handle = fopen($path, 'wb');
        if ($handle && fwrite($handle, Options::alloc()->request->content)) {
            fclose($handle);
            Notice::alloc()->set(_t("文件 %s 的更改已经保存", $file), 'success');
        } else {
            Notice::alloc()->set(_t("文件 %s 无法被写入", $file), 'error');
        }
        Options::alloc()->response->goBack();
    } else {
        throw new Typecho_Widget_Exception(_t('您编辑的模板文件不存在'));
    }
}

class CommentNotifier_Console extends Typecho_Widget
{
    /** @var  模板文件目录 */
    private $_dir;
    /**
     * 当前文件
     *
     * @access private
     * @var string
     */
    private $_currentFile;

    /**
     * 执行函数
     *
     * @access public
     * @return void
     * @throws Typecho_Widget_Exception
     */
    public function execute()
    {
        $this->_dir = dirname(__FILE__);
        $template = Plugin::configStr('template', 'default');
        $path = '/template/' . $template;
        $files = glob($this->_dir . $path . '/*.{html,HTML}', GLOB_BRACE);

        $this->_currentFile = $this->request->get('file', 'owner.html');

        if (preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->_currentFile)
            && file_exists($this->_dir . $path . '/' . $this->_currentFile)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $file = basename($file);
                    $this->push(array(
                        'file' => $file,
                        'current' => ($file == $this->_currentFile)
                    ));
                }
            }

            return;
        }

        throw new Typecho_Widget_Exception('模板文件不存在', 404);
    }

    /**
     * 获取菜单标题
     *
     * @access public
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('编辑文件 %s', $this->_currentFile);
    }

    /**
     * 获取文件内容
     *
     * @access public
     * @return string
     */
    public function currentContent(): string
    {
        $template = Plugin::configStr('template', 'default');
        $path = '/template/' . $template;
        return htmlspecialchars(file_get_contents($this->_dir . $path . '/' . $this->_currentFile));
    }

    /**
     * 获取文件是否可读
     *
     * @access public
     * @return bool
     */
    public function currentIsWriteable(): bool
    {
        $template = Plugin::configStr('template', 'default');
        $path = '/template/' . $template;
        return is_writeable($this->_dir . $path . '/' . $this->_currentFile);
    }

    /**
     * 获取当前文件
     *
     * @access public
     * @return string
     */
    public function currentFile(): string
    {
        return $this->_currentFile;
    }
}

?>

<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?= $title ?></h2>
        </div>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs fix-tabs clearfix">
                    <li<?= ($current == 'index' ? ' class="current"' : '') ?>><a
                            href="<?php $options->adminUrl('extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=index'); ?>">
                            <?php _e('模板列表'); ?>
                        </a></li>
                    <li<?= ($current == 'theme' ? ' class="current"' : '') ?>><a
                            href="<?php $options->adminUrl('extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=theme'); ?>">
                            <?php _e('编辑邮件模板'); ?>
                        </a></li>
                    <li>
                        <a href="<?php $options->adminUrl('options-plugin.php?config=CommentNotifier') ?>"><?php _e('插件设置'); ?></a>
                    </li>
                </ul>
            </div>

            <?php if ($current == 'index'): ?>

                <?php include(dirname(__FILE__) . '/themes.php'); ?>


            <?php else: ?>
                <?php
                /** @var CommentNotifier_Console $files */
                Widget::widget('CommentNotifier_Console')->to($files);
                ?>
                <div class="typecho-edit-theme">
                    <div class="col-mb-12 col-tb-8 col-9 content">
                        <form method="post" name="theme" id="theme"
                              action="<?php $options->adminUrl('extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=theme' . '&file=' . $files->file); ?>">
                            <label for="content" class="sr-only"><?php _e('编辑源码'); ?></label>
                            <textarea name="content" id="content" class="w-100 mono"
                                      <?php if (!$files->currentIsWriteable()): ?>readonly<?php endif; ?>><?php echo $files->currentContent(); ?></textarea>
                            <p class="submit">
                                <?php if ($files->currentIsWriteable()): ?>
                                    <input type="hidden" name="do" value="editTheme"/>
                                    <input type="hidden" name="edit" value="<?php echo $files->currentFile(); ?>"/>
                                    <button type="submit" class="btn primary"><?php _e('保存文件'); ?></button>
                                <?php else: ?>
                                    <em><?php _e('此文件无法写入'); ?></em>
                                <?php endif; ?>
                            </p>
                        </form>
                    </div>
                    <ul class="col-mb-12 col-tb-4 col-3">
                        <li><strong><?php _e("模板文件"); ?></strong></li>
                        <?php while ($files->next()): ?>
                            <li<?php if ($files->current): ?> class="current"<?php endif; ?>>
                                <a href="<?php $options->adminUrl('extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=theme' . '&file=' . $files->file); ?>"><?php $files->file(); ?></a>
                            </li>
                        <?php endwhile; ?>
                        <li><strong><?php _e("参数说明"); ?></strong></li>
                        <li><?php _e("文章标题：{title}"); ?></li>
                        <li><?php _e("评论发出时间：{time}"); ?></li>
                        <li><?php _e("评论内容：{commentText}"); ?></li>
                        <li><?php _e("评论人昵称：{author}"); ?></li>
                        <li><?php _e("评论者邮箱：{mail}"); ?></li>
                        <li><?php _e("评论楼层链接：{permalink}"); ?></li><?php if ($request->file == 'guest.html'): ?>
                            <li><?php _e("父评论昵称：{Pname}"); ?></li>
                            <li><?php _e("父评论内容：{Ptext}"); ?></li>
                            <li><?php _e("父评论邮箱：{Pmail}"); ?></li><?php endif; ?>
                        <li><?php _e("网站地址：{siteUrl}"); ?></li>
                        <li><?php _e("网站标题：{siteTitle}"); ?></li>
                        <li><?php _e("当前模板文件夹路径：{url}"); ?></li>
                        <li><strong><?php _e("文件说明"); ?></strong></li>
                        <li><?php _e("notice.html：待审核评论通知模板"); ?></li>
                        <li><?php _e("owner.html：文章作者邮件提醒模板"); ?></li>
                        <li><?php _e("guest.html：游客评论回复提醒模板"); ?></li>
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
