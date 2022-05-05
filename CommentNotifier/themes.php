<div class="typecho-table-wrap">
    <table class="typecho-list-table typecho-theme-list">
        <colgroup>
            <col width="35%"/>
            <col/>
        </colgroup>

        <thead>
        <th><?php _e('截图'); ?></th>
        <th><?php _e('详情'); ?></th>
        </thead>

        <tbody>
        <?php

        use Typecho\Plugin;
        use Typecho\Db;
        use Widget\{Options, Notice};

        $template = Options::alloc()->plugin('CommentNotifier')->template;
        /* @var $request */
        /* @var $response */
        /* @var $options */
        if ($request->change) {
            $db = Db::get();
            $select = $db->select('value')->from('table.options')->where('name = ?', 'plugin:CommentNotifier')->limit(1);
            $aftersitting = $db->fetchRow($select);
            $setting = unserialize($aftersitting['value']);
            $setting['template'] = $request->change;
            $setting = serialize($setting);
            $update = $db->update('table.options')->rows(array('value' => $setting))->where('name = ?', 'plugin:CommentNotifier');
            $updateRows = $db->query($update);
            Notice::alloc()->set(_t("邮件模板启动成功"), 'success');
            $template = $request->change;
            $response->redirect($options->adminUrl . 'extending.php?panel=' . CommentNotifier_Plugin::$panel);
        }


        function getMailTheme(): array
        {
            return glob(__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/CommentNotifier/template/*', GLOB_ONLYDIR);
        }//获取模板
        $themes = getMailTheme();
        $html = '';
        $ding = '';
        $cite = '';
        foreach ($themes as $key => $theme) {
            $themeFile = $theme . '/owner.html';

            if (file_exists($themeFile)) {//判断是否存在模板
                $name = basename($theme);
                $info = Plugin::parseInfo($themeFile);

                $screen = array_filter(glob($theme . '/*'), function ($path) {
                    return preg_match("/screenshot\.(jpg|png|gif|bmp|jpeg|webp)$/i", $path);
                });

                if ($screen) {
                    $img = $options->pluginUrl . '/CommentNotifier/template/' . $name . '/' . basename(current($screen));
                } else {
                    $img = Common::url('noscreen.png', $options->adminStaticUrl('img'));
                }


                if ($info['author']) {
                    $cite = '作者：' . $info['author'] . '&nbsp;&nbsp;';
                }
                if ($info['author'] && $info['homepage']) {
                    $cite = '作者：<a href="' . $info['homepage'] . '">' . $info['author'] . '</a>&nbsp;&nbsp;';
                }
                if ($info['version']) {
                    $cite = $cite . '版本: ' . $info['version'];
                }

                if ($template == $name) {
                    $ding = '<tr>
 <td valign="top"><img src="' . $img . '"></td>
<td valign="top">
    <h3>' . $info['title'] . '</h3>
<cite>' . $cite . '</cite>
<p>' . nl2br($info['description']) . '</p>
</td>
</tr>';
                } else {

                    $html = $html . '<tr>
 <td valign="top"><img src="' . $img . '"></td>
<td valign="top">
    <h3>' . $info['title'] . '</h3>
<cite>' . $cite . '</cite>
<p>' . nl2br($info['description']) . '</p>
<p>
<a class="activate" href="' . $options->adminUrl . 'extending.php?panel=' . CommentNotifier_Plugin::$panel . '&act=index' . '&change=' . $name . '">启用</a>
</p>
</td>
</tr>';
                }
            }
        }

        $html = $ding . $html;
        echo $html;
        ?>
        </tbody>
    </table>