<?php
include 'header.php';
include 'menu.php';
use Typecho\Widget;
use Widget\Notice;
use Widget\Options;
\Widget\Options::alloc()->to($options);

/** 初始化上下文 */
$request = $options->request;
$response = $options->response;
$current = $request->get('act', 'theme');
$theme = $request->get('file', 'guest.html');
$title = '编辑邮件模板 ' . $theme;
if($request->is('do=editTheme')){
editTheme($request->edit);  
}
function editTheme($file)
    {
        $path = dirname(__FILE__) . '/' . $file;
        if (file_exists($path) && is_writeable($path)) {
            $handle = fopen($path, 'wb');
            if ($handle && fwrite($handle,  \Widget\Options::alloc()->request->content)) {
                fclose($handle);
            \Widget\Notice::alloc()->set(_t("文件 %s 的更改已经保存", $file), 'success');
            } else {
            \Widget\Notice::alloc()->set(_t("文件 %s 无法被写入", $file), 'error');
            }
            \Widget\Options::alloc()->response->goBack();
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
        $files = glob($this->_dir . '/*.{html,HTML}', GLOB_BRACE);
        $this->_currentFile = $this->request->get('file', 'guest.html');

        if (preg_match("/^([_0-9a-z-\.\ ])+$/i", $this->_currentFile)
            && file_exists($this->_dir . '/' . $this->_currentFile)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $file = basename($file);
                    $this->push(array(
                        'file'      =>  $file,
                        'current'   =>  ($file == $this->_currentFile)
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
    public function getMenuTitle()
    {
        return _t('编辑文件 %s', $this->_currentFile);
    }

    /**
     * 获取文件内容
     *
     * @access public
     * @return string
     */
    public function currentContent()
    {
        return htmlspecialchars(file_get_contents($this->_dir . '/' . $this->_currentFile));
    }

    /**
     * 获取文件是否可读
     *
     * @access public
     * @return string
     */
    public function currentIsWriteable()
    {
        return is_writeable($this->_dir . '/' . $this->_currentFile);
    }

    /**
     * 获取当前文件
     *
     * @access public
     * @return string
     */
    public function currentFile()
    {
        return $this->_currentFile;
    }
}
?>

<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?=$title?></h2>
        </div>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs fix-tabs clearfix">
                    <li class="current"><a href="<?php $options->adminUrl('extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=theme'); ?>">
                    <?php _e('编辑邮件模板'); ?>
                    </a></li>
                    <li><a href="<?php $options->adminUrl('options-plugin.php?config=CommentNotifier') ?>"><?php _e('插件设置'); ?></a></li>
                </ul>
            </div>
            
            <?php 
                Widget::widget('CommentNotifier_Console')->to($files);
            ?>
            <div class="typecho-edit-theme">
                <div class="col-mb-12 col-tb-8 col-9 content">
                    <form method="post" name="theme" id="theme" action="<?php $options->adminUrl('extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=theme' . '&file=' . $files->file); ?>">
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
                    <a href="<?php $options->adminUrl('extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=theme' . '&file=' . $files->file); ?>"><?php $files->file(); ?></a></li>
                    <?php endwhile; ?>
                    <li><strong>参数说明</strong></li>
                    <li>文章标题：{title}</li>
                    <li>评论发出时间：{time}</li>
                    <li>评论内容：{commentText}</li>
                    <li>评论人昵称：{author}</li>
                    <li>评论者邮箱：{mail}</li>
                    <li>评论楼层链接：{permalink}</li>
                    <li>网站地址：{siteUrl}</li>
                    <li>网站标题：{siteTitle}</li>
                    <li>父评论昵称：{Pname}</li>
                    <li>父评论内容：{Ptext}</li>
                    <li>父评论邮箱：{Pmail}</li>
                    <li><strong>文件说明</strong></li>
                    <li>notice.html：待审核评论通知模板</li>
                    <li>owner.html：文章作者邮件提醒模板</li>
                    <li>guest：游客评论回复提醒模板</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
