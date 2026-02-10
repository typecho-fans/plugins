<?php

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');

/** 注册一个初始化插件 */
Typecho_Plugin::factory('admin/common.php')->begin();

Typecho_Widget::widget('Widget_Options')->to($options);
Typecho_Widget::widget('Widget_User')->to($user);
Typecho_Widget::widget('Widget_Security')->to($security);
Typecho_Widget::widget('Widget_Menu')->to($menu);

/** 初始化上下文 */
$request = $options->request;
$response = $options->response;
include 'header.php';
include 'menu.php';
?>


<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        
        <style>
            :root {
                --md-primary: #0061a4;
                --md-primary-container: #d1e4ff;
                --md-on-primary-container: #001d36;
                --md-surface: #fdfcff;
                --md-surface-variant: #e1e2ec;
                --md-outline: #74777f;
                --md-radius: 12px;
                --md-surface-1: #f0f4f8; /* 背景色微调 */
                --md-outline-variant: rgba(0,0,0,.12);
                --md-surface-container: #f3f4f7;
            }

            .md3-wrap {
                max-width: 1280px;
                margin: 0 auto;
            }

            /* 顶部 App Bar */
            .md3-appbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px 18px;
                border: 1px solid var(--md-outline-variant);
                background: var(--md-surface);
                border-radius: 16px;
                box-shadow: 0 1px 2px rgba(0,0,0,.08), 0 1px 3px rgba(0,0,0,.12);
                margin: 8px 0 18px;
            }
            .md3-appbar-title {
                display: flex;
                flex-direction: column;
                min-width: 0;
            }
            .md3-appbar-title b {
                font-size: 15px;
                color: #111827;
            }
            .md3-appbar-title span {
                font-size: 12px;
                color: #6b7280;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .md3-appbar-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            .md3-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 14px;
                border-radius: 999px;
                border: 1px solid var(--md-outline-variant);
                background: var(--md-surface);
                color: #1f2937;
                text-decoration: none;
                font-weight: 600;
                font-size: 13px;
                transition: box-shadow .15s, background .15s, border-color .15s;
            }
            .md3-btn:hover {
                text-decoration: none;
                background: var(--md-surface-container);
                box-shadow: 0 1px 2px rgba(0,0,0,.10);
            }
            .md3-btn.primary {
                background: var(--md-primary);
                color: #fff;
                border-color: transparent;
            }
            .md3-btn.primary:hover {
                background: #055a96;
            }
            .md3-btn.tonal {
                background: var(--md-primary-container);
                border-color: transparent;
                color: var(--md-on-primary-container);
            }
            .md3-btn.tonal:hover {
                background: rgba(209,228,255,.7);
            }

            /* 容器与布局 */
            .typecho-page-main {
                display: flex;
                flex-wrap: wrap;
                gap: 24px;
            }
            .col-mb-12 {
                float: none;
                width: 100%;
                padding: 0;
            }
            .md3-card {
                background: var(--md-surface);
                border-radius: var(--md-radius);
                padding: 0;
                box-shadow: 0 1px 2px rgba(0,0,0,.08), 0 1px 3px rgba(0,0,0,.12);
                border: 1px solid var(--md-outline-variant);
                overflow: hidden;
                margin-bottom: 0;
            }

            /* 左侧列表面板 */
            .manage-list-panel {
                flex: 2;
                min-width: 0; /* 防止 flex 子项溢出 */
            }
            .manage-list-header {
                padding: 16px 24px;
                border-bottom: 1px solid var(--md-surface-variant);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
            }

            /* 操作栏 */
            .typecho-list-operate {
                margin: 0;
                padding: 0;
            }
            .btn-group .dropdown-toggle {
                border-radius: 20px;
                padding: 6px 16px;
                font-size: 13px;
                border: 1px solid var(--md-outline-variant);
                color: #1f2937;
                background: var(--md-surface);
                font-weight: 700;
            }
            .btn-group .dropdown-toggle:hover {
                background-color: var(--md-surface-variant);
            }
            .dropdown-menu {
                border-radius: 14px;
                border: 1px solid var(--md-outline-variant);
                box-shadow: 0 8px 24px rgba(0,0,0,.12);
                padding: 8px;
            }
            .dropdown-menu li a {
                border-radius: 10px;
                padding: 8px 10px;
                font-weight: 600;
                color: #111827;
            }
            .dropdown-menu li a:hover {
                background: var(--md-surface-container);
            }

            /* 表格优化 */
            .typecho-list-table {
                border: none;
            }
            .typecho-list-table th {
                border-bottom: 1px solid var(--md-surface-variant);
                color: #666;
                font-weight: 600;
                padding: 16px 12px;
                background: #fff;
            }
            .typecho-list-table td {
                padding: 16px 12px;
                border-bottom: 1px solid #f0f0f0;
                vertical-align: middle;
            }
            .typecho-list-table tr:hover td {
                background-color: var(--md-surface-1);
            }
            
            /* 图片与状态标签 */
            .avatar {
                border-radius: 8px;
                border: 1px solid #eee;
            }
            .status-tag {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }
            .status-normal {
                background-color: #e6f4ea;
                color: #1e8e3e;
            }
            .status-ban {
                background-color: #fce8e6;
                color: #d93025;
            }

            /* 链接样式 */
            .edit-link {
                color: var(--md-primary);
                font-weight: 500;
                text-decoration: none;
            }
            .edit-link:hover {
                text-decoration: underline;
            }

            /* 右侧编辑面板 */
            .editor-panel {
                flex: 1;
                min-width: 320px;
            }
            .editor-container {
                padding: 24px;
            }
            
            /* 表单元素 MD3 化 */
            .typecho-label {
                font-size: 13px;
                color: var(--md-primary);
                margin-bottom: 8px;
                font-weight: 600;
            }
            input[type="text"], textarea {
                width: 100%;
                border: 1px solid var(--md-outline-variant);
                border-radius: 12px;
                padding: 10px 12px;
                font-size: 14px;
                transition: all 0.2s;
                background-color: #fff;
                box-sizing: border-box; /* 确保 padding 不撑大 */
            }
            input[type="text"]:focus, textarea:focus {
                border-color: var(--md-primary);
                box-shadow: 0 0 0 3px rgba(0, 97, 164, 0.18);
                outline: none;
            }
            .description {
                font-size: 12px;
                color: #666;
                margin-top: 4px;
                margin-bottom: 16px;
            }
            
            /* 按钮 */
            .btn.primary {
                background-color: var(--md-primary);
                color: #fff;
                border: none;
                border-radius: 20px;
                padding: 10px 24px;
                font-weight: 500;
                cursor: pointer;
                transition: box-shadow 0.2s;
                height: auto;
                line-height: normal;
            }
            .btn.primary:hover {
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }

            /* 小屏优化：改为上下布局 */
            @media (max-width: 960px) {
                .typecho-page-main {
                    flex-direction: column;
                }
                .editor-panel {
                    min-width: 0;
                }
                .md3-appbar {
                    flex-direction: column;
                    align-items: stretch;
                }
                .md3-appbar-actions {
                    justify-content: flex-start;
                }
            }
        </style>

        <div class="md3-wrap">
            <div class="md3-appbar">
                <div class="md3-appbar-title">
                    <b><?php _e('友情链接'); ?></b>
                    <span><?php _e('拖拽排序 / 批量操作 / 右侧编辑'); ?></span>
                </div>
                <div class="md3-appbar-actions">
                    <a class="md3-btn tonal" href="<?php $options->adminUrl('options-plugin.php?config=Links'); ?>"><?php _e('设置'); ?></a>
                    
                    <a class="md3-btn" href="https://blog.lhl.one/artical/902.html " target="_blank"><?php _e('帮助'); ?></a>
                    <a class="md3-btn primary" href="<?php $security->index('/action/links-edit?do=rewrite'); ?>" title="将指定 cid 文章正文中的 {{links_plus}} 占位符替换为友链 HTML" onclick="return confirm('确认要执行正文重写吗？该操作会直接修改文章/页面正文内容。');"><?php _e('执行重写'); ?></a>
                </div>
            </div>

        <div class="row typecho-page-main manage-metas">
                <!-- 左侧：列表 -->
                <div class="manage-list-panel md3-card" role="main">
                    <?php
                        $prefix = $db->getPrefix();
                        $links = $db->fetchAll($db->select()->from($prefix.'links')->order($prefix.'links.order', Typecho_Db::SORT_ASC));
                    ?>
                    <form method="post" name="manage_categories" class="operate-form">
                    
                    <div class="manage-list-header">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <i class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('你确认要删除这些友链吗?'); ?>" href="<?php $security->index('/action/links-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
                                    <li><a lang="<?php _e('你确认要启用这些友链吗?'); ?>" href="<?php $security->index('/action/links-edit?do=enable'); ?>"><?php _e('启用'); ?></a></li>
                                    <li><a lang="<?php _e('你确认要禁用这些友链吗?'); ?>" href="<?php $security->index('/action/links-edit?do=prohibit'); ?>"><?php _e('禁用'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                        <!-- 可以放搜索或其他 -->
                    </div>

                    <div class="typecho-table-wrap">
                        <table class="typecho-list-table">
                            <colgroup>
                                <col width="40"/>
                                <col width="25%"/>
                                <col width=""/>
                                <col width="15%"/>
                                <col width="60"/>
                                <col width="80"/>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th> </th>
                                    <th><?php _e('友链名称'); ?></th>
                                    <th><?php _e('友链地址'); ?></th>
                                    <th><?php _e('分类'); ?></th>
                                    <th><?php _e('图片'); ?></th>
                                    <th><?php _e('状态'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($links)): $alt = 0;?>
                                <?php foreach ($links as $link): ?>
                                <tr id="lid-<?php echo $link['lid']; ?>">
                                    <td><input type="checkbox" value="<?php echo $link['lid']; ?>" name="lid[]"/></td>
                                    <td><a class="edit-link" href="<?php echo $request->makeUriByRequest('lid=' . $link['lid']); ?>" title="<?php _e('点击编辑'); ?>"><?php echo $link['name']; ?></a>
                                    <td><a href="<?php echo $link['url']; ?>" target="_blank" style="color:#888; text-decoration:none;"><i class="i-exlink"></i></a> <?php echo $link['url']; ?></td>
                                    <td><?php echo $link['sort']; ?></td>
                                    <td><?php
                                        if ($link['image']) {
                                            echo '<a href="'.$link['image'].'" title="'._t('点击放大').'" target="_blank"><img class="avatar" src="'.$link['image'].'" alt="'.$link['name'].'" width="32" height="32"/></a>';
                                        } else {
                                            $options = Typecho_Widget::widget('Widget_Options');
                                            $nopic_url = Typecho_Common::url('usr/plugins/Links/nopic.png', $options->siteUrl);
                                            echo '<img class="avatar" src="'.$nopic_url.'" alt="NOPIC" width="32" height="32"/>';
                                        }
                                    ?></td>
                                    <td><?php
                                        if ($link['state'] == 1) {
                                            echo '<span class="status-tag status-normal">正常</span>';
                                        } elseif ($link['state'] == 0) {
                                            echo '<span class="status-tag status-ban">禁用</span>';
                                        }
                                    ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何友链'); ?></h6></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </form>
                </div>

                <!-- 右侧：编辑表单 -->
                <div class="editor-panel md3-card" role="form">
                    <div class="editor-container">
                         <?php Links_Plugin::form()->render(); ?>
                    </div>
                </div>
        </div>
    </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script>
$('input[name="email"]').blur(function() {
    var _email = $(this).val();
    var _image = $('input[name="image"]').val();
    if (_email != '' && _image == '') {
        var k = "<?php $security->index('/action/links-edit'); ?>";
        $.post(k, {"do": "email-logo", "type": "json", "email": $(this).val()}, function (result) {
            var k = jQuery.parseJSON(result).url;
            $('input[name="image"]').val(k);
        });
    }
    return false;
});
</script>
<script type="text/javascript">
(function () {
    $(document).ready(function () {
        var table = $('.typecho-list-table').tableDnD({
            onDrop : function () {
                var ids = [];

                $('input[type=checkbox]', table).each(function () {
                    ids.push($(this).val());
                });

                $.post('<?php $security->index('/action/links-edit?do=sort'); ?>',
                    $.param({lid : ids}));

                $('tr', table).each(function (i) {
                    if (i % 2) {
                        $(this).addClass('even');
                    } else {
                        $(this).removeClass('even');
                    }
                });
            }
        });

        table.tableSelectable({
            checkEl     :   'input[type=checkbox]',
            rowEl       :   'tr',
            selectAllEl :   '.typecho-table-select-all',
            actionEl    :   '.dropdown-menu a'
        });

        $('.btn-drop').dropdownMenu({
            btnEl       :   '.dropdown-toggle',
            menuEl      :   '.dropdown-menu'
        });

        $('.dropdown-menu button.merge').click(function () {
            var btn = $(this);
            btn.parents('form').attr('action', btn.attr('rel')).submit();
        });

        <?php if (isset($request->lid)): ?>
        $('.typecho-mini-panel').effect('highlight', '#AACB36');
        <?php endif; ?>
    });
})();
</script>
<?php include 'footer.php'; ?>

<?php /** Links by 懵仙兔兔 */ ?>
