<?php
include_once 'common.php';
include 'header.php';
include 'menu.php';

$db = Typecho_Db::get();
$posts = $db->fetchAll($db->select()->from('table.contribute')
    ->order('cid', Typecho_Db::SORT_DESC));
Typecho_Widget::widget('Widget_Metas_Category_List')->to($category);
?>
<link rel="stylesheet" href="<?php echo Typecho_Common::url('/css/component.css', $options->pluginUrl('Contribute')); ?>">

<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?php _e('管理投稿'); ?></h2>
        </div>
        <div class="row typecho-page-main" role="main">
            <div class="typecho-list-operate clearfix">
                <form method="get">
                    <div class="operate">
                        <label><i class="sr-only">全选</i><input type="checkbox" class="typecho-table-select-all"></label>
                        <div class="btn-group btn-drop">
                            <button class="dropdown-toggle btn-s" type="button"><i class="sr-only">操作</i>选中项 <i class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                                <li><a href="<?php $options->index('/action/contribute?approved'); ?>">通过</a></li>
                                <li><a lang="你确认要删除这些投稿吗?" href="<?php $options->index('/action/contribute?delete'); ?>">删除</a></li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
            <form method="post" name="manage_posts" class="operate-form">
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="20">
                            <col width="45%">
                            <col width="">
                            <col width="18%">
                            <col width="16%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th> </th>
                                <th>标题</th>
                                <th>撰稿人</th>
                                <th>分类</th>
                                <th>日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($posts)): ?>
                            <?php foreach ($posts as $post): ?>
                            <tr id="post-<?php echo $post['cid']; ?>">
                                <td><input type="checkbox" value="<?php echo $post['cid']; ?>" name="cid[]"></td>
                                <td><a href="#" class="md-trigger post-title" data-modal="modal-12"><?php echo $post['title']; ?></a><a href="#" class="md-trigger" data-modal="modal-12"><i class="i-exlink"></i></a></td>
                                <td><?php echo $post['author']; ?></td>
                                <td><?php $categories = unserialize($post['category']); while ($category->next()): ?><?php if(in_array($category->mid, $categories)): ?><?php $category->name(); ?><?php endif; ?><?php endwhile; ?></td>
                                <td><?php $date = new Typecho_Date($post['created']); ?><?php _e($date->word()); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何稿件'); ?></h6></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

        <div class="md-modal md-effect-12" id="modal-12">
            <div class="md-content">
                <h1></h1>
                <div>
                    <div class="md-content-main"></div>
                    <button class="md-close"><?php _e('关闭'); ?></button>
                </div>
            </div>
        </div>
        <div class="md-overlay"></div>
    </div>
</div>
<script src="<?php echo Typecho_Common::url('/js/panel/classie.js', $options->pluginUrl('Contribute')); ?>"></script>
<script src="<?php echo Typecho_Common::url('/js/panel/modalEffects.js', $options->pluginUrl('Contribute')); ?>"></script>
<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
include 'preview-ajax.php';
include 'footer.php';
?>