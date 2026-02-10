<?php

class Links_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $options;
    private $prefix;

    public function insertLink()
    {
        if (Links_Plugin::form('insert')->validate()) {
            $this->response->goBack();
        }
        /** 取出数据 */
        $link = $this->request->from('email', 'image', 'url', 'state');

        /** 过滤XSS */
        $link['name'] = $this->request->filter('xss')->name;
        $link['sort'] = $this->request->filter('xss')->sort;
        $link['description'] = $this->request->filter('xss')->description;
        $link['user'] = $this->request->filter('xss')->user;
        $link['order'] = $this->db->fetchObject($this->db->select(array('MAX(order)' => 'maxOrder'))->from($this->prefix . 'links'))->maxOrder + 1;

        /** 插入数据 */
        $link_lid = $this->db->query($this->db->insert($this->prefix . 'links')->rows($link));

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('link-' . $link_lid);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t(
            '友链 <a href="%s">%s</a> 已经被增加',
            $link['url'],
            $link['name']
        ), null, 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }


    public function updateLink()
    {
        if (Links_Plugin::form('update')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $link = $this->request->from('email', 'image', 'url', 'state');
        $link_lid = $this->request->from('lid');

        /** 过滤XSS */
        $link['name'] = $this->request->filter('xss')->name;
        $link['sort'] = $this->request->filter('xss')->sort;
        $link['description'] = $this->request->filter('xss')->description;
        $link['user'] = $this->request->filter('xss')->user;

        /** 更新数据 */
        $this->db->query($this->db->update($this->prefix . 'links')->rows($link)->where('lid = ?', $link_lid));

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('link-' . $link_lid);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t(
            '友链 <a href="%s">%s</a> 已经被更新',
            $link['url'],
            $link['name']
        ), null, 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function deleteLink()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $deleteCount = 0;
        if ($lids && is_array($lids)) {
            foreach ($lids as $lid) {
                if ($this->db->query($this->db->delete($this->prefix . 'links')->where('lid = ?', $lid))) {
                    $deleteCount++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(
            $deleteCount > 0 ? _t('友链已经删除') : _t('没有友链被删除'),
            null,
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function enableLink()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $enableCount = 0;
        if ($lids && is_array($lids)) {
            foreach ($lids as $lid) {
                if ($this->db->query($this->db->update($this->prefix . 'links')->rows(array('state' => '1'))->where('lid = ?', $lid))) {
                    $enableCount++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(
            $enableCount > 0 ? _t('友链已经启用') : _t('没有友链被启用'),
            null,
            $enableCount > 0 ? 'success' : 'notice'
        );

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function prohibitLink()
    {
        $lids = $this->request->filter('int')->getArray('lid');
        $prohibitCount = 0;
        if ($lids && is_array($lids)) {
            foreach ($lids as $lid) {
                if ($this->db->query($this->db->update($this->prefix . 'links')->rows(array('state' => '0'))->where('lid = ?', $lid))) {
                    $prohibitCount++;
                }
            }
        }
        /** 提示信息 */
        $this->widget('Widget_Notice')->set(
            $prohibitCount > 0 ? _t('友链已经禁用') : _t('没有友链被禁用'),
            null,
            $prohibitCount > 0 ? 'success' : 'notice'
        );

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', $this->options->adminUrl));
    }

    public function sortLink()
    {
        $links = $this->request->filter('int')->getArray('lid');
        if ($links && is_array($links)) {
            foreach ($links as $sort => $lid) {
                $this->db->query($this->db->update($this->prefix . 'links')->rows(array('order' => $sort + 1))->where('lid = ?', $lid));
            }
        }
    }

    public function emailLogo()
    {
        /* 邮箱头像解API接口 by 懵仙兔兔 */
        $type = $this->request->type;
        $email = $this->request->email;

        if ($email == null || $email == '') {
            $this->response->throwJson('请提交邮箱链接 [email=abc@abc.com]');
            exit;
        } else if ($type == null || $type == '' || ($type != 'txt' && $type != 'json')) {
            $this->response->throwJson('请提交type类型 [type=txt, type=json]');
            exit;
        } else {
            $f = str_replace('@qq.com', '', $email);
            $email = $f . '@qq.com';
            if (is_numeric($f) && strlen($f) < 11 && strlen($f) > 4) {
                stream_context_set_default([
                    'ssl' => [
                        'verify_host' => false,
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);
                $geturl = 'https://s.p.qq.com/pub/get_face?img_type=3&uin=' . $f;
                $headers = get_headers($geturl, TRUE);
                if ($headers) {
                    $g = $headers['Location'];
                    $g = str_replace("http:", "https:", $g);
                } else {
                    $g = 'https://q.qlogo.cn/g?b=qq&nk=' . $f . '&s=100';
                }
            } else {
                $g = 'https://cdn.helingqi.com/wavatar/' . md5($email) . '?d=mm';
            }
            $r = array('url' => $g);
            if ($type == 'txt') {
                $this->response->throwJson($g);
                exit;
            } else if ($type == 'json') {
                $this->response->throwJson(json_encode($r));
                exit;
            }
        }
    }

    /**
     * 按插件设置的 cid 列表，重写文章/页面正文：
     * - 用 {{links_plus}} 占位符替换为插件生成的友链 HTML
     */
    public function rewriteContents()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $settings = $options->plugin('Links');

        $raw = isset($settings->rewrite_cids) ? trim((string)$settings->rewrite_cids) : '';
        if ($raw === '') {
            $this->widget('Widget_Notice')->set(_t('请先在插件设置中填写需要重写的 cid'), null, 'notice');
            $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
        }

        $cids = array_filter(array_map('trim', explode(',', $raw)), function ($v) {
            return $v !== '';
        });
        $cids = array_values(array_unique(array_map('intval', $cids)));
        $cids = array_filter($cids, function ($v) {
            return $v > 0;
        });

        if (!$cids) {
            $this->widget('Widget_Notice')->set(_t('cid 格式不正确，请使用英文逗号分隔的数字'), null, 'notice');
            $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
        }

        $placeholder = Links_Plugin::REWRITE_PLACEHOLDER;
    $blockStart = Links_Plugin::REWRITE_BLOCK_START;
    $blockEnd = Links_Plugin::REWRITE_BLOCK_END;
        $html = Links_Plugin::buildRewriteHtml();
        if ($html === null || trim($html) === '') {
            $this->widget('Widget_Notice')->set(_t('重写已取消：未生成任何友链输出（可能没有启用的友链，或输出模板为空）'), null, 'notice');
            $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
        }

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $table = $prefix . 'contents';

        $total = 0;
        $hit = 0;
        $miss = 0;
        $fail = 0;

        foreach ($cids as $cid) {
            $total++;
            try {
                $row = $db->fetchRow($db->select()->from($table)->where('cid = ?', $cid)->limit(1));
                if (!$row) {
                    $fail++;
                    continue;
                }
                $text = (string)($row['text'] ?? '');

                if ($text === '') {
                    $miss++;
                    continue;
                }

                $wrappedHtml = $blockStart . "\n" . $html . "\n" . $blockEnd;
                $newText = null;

                // 若存在历史重写块，则直接替换块内容（支持重复重写）
                if (strpos($text, $blockStart) !== false && strpos($text, $blockEnd) !== false) {
                    $pattern = '/' . preg_quote($blockStart, '/') . '.*?' . preg_quote($blockEnd, '/') . '/s';
                    $newText = preg_replace($pattern, $wrappedHtml, $text, 1);
                }

                // 否则用占位符替换
                if ($newText === null) {
                    // 一些编辑器/主题会把占位符包裹在行内标签里，或做 HTML 转义，
                    // 例如：<style>{{links_plus}}</style> / &lbrace;&lbrace;links_plus&rbrace;&rbrace;
                    // 为了让“重写”更稳，这里把几种常见等价写法都当作占位符处理。
                    $placeholderCandidates = array_values(array_unique(array_filter(array(
                        $placeholder,
                        // HTML 实体转义后的样式（部分编辑器会这么存）
                        htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'),
                        // 全角花括号（中文输入法常见）
                        str_replace(array('{', '}'), array('｛', '｝'), $placeholder),
                        // 被代码标记包裹
                        '`' . $placeholder . '`',
                        '<code>' . $placeholder . '</code>',
                        // 兼容用户把占位符写成 {{ links_plus }}
                        '{{ links_plus }}',
                        '{{ links_plus}}',
                        '{{links_plus }}',
                    ), function ($v) {
                        return is_string($v) && $v !== '';
                    })));

                    $foundCandidate = null;
                    foreach ($placeholderCandidates as $cand) {
                        if (strpos($text, $cand) !== false) {
                            $foundCandidate = $cand;
                            break;
                        }
                    }

                    if ($foundCandidate === null) {
                        // 兼容历史：如果正文已被替换成“裸 HTML”（没有占位符，也没有标记块），
                        // 仍允许通过查找一次生成的 html 片段来包裹标记块。
                        // 这里采用宽松策略：只要正文包含当前生成的 html（trim 后），则包裹它。
                        $plain = trim($html);
                        if ($plain !== '' && strpos($text, $plain) !== false) {
                            $newText = str_replace($plain, $wrappedHtml, $text);
                        } else {
                            $miss++;
                            continue;
                        }
                    }

                    // 命中占位符（或等价写法）则替换
                    if ($newText === null) {
                        $newText = str_replace($foundCandidate, $wrappedHtml, $text);
                    }
                }

                if ($newText === null || $newText === $text) {
                    $miss++;
                    continue;
                }

                $db->query($db->update($table)->rows(array('text' => $newText))->where('cid = ?', $cid));
                $hit++;
            } catch (Exception $e) {
                $fail++;
            }
        }

        $this->widget('Widget_Notice')->set(
            _t('重写完成：目标 %d 篇，命中替换 %d 篇，未发现占位符 %d 篇，失败 %d 篇。', $total, $hit, $miss, $fail),
            null,
            $hit > 0 ? 'success' : 'notice'
        );

        $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
    }

    public function action()
    {
        Helper::security()->protect();
        $user = Typecho_Widget::widget('Widget_User');
        $user->pass('administrator');
        $this->db = Typecho_Db::get();
        $this->prefix = $this->db->getPrefix();
        $this->options = Typecho_Widget::widget('Widget_Options');
        $this->on($this->request->is('do=insert'))->insertLink();
        $this->on($this->request->is('do=update'))->updateLink();
        $this->on($this->request->is('do=delete'))->deleteLink();
        $this->on($this->request->is('do=enable'))->enableLink();
        $this->on($this->request->is('do=prohibit'))->prohibitLink();
        $this->on($this->request->is('do=sort'))->sortLink();
        $this->on($this->request->is('do=email-logo'))->emailLogo();
    $this->on($this->request->is('do=rewrite'))->rewriteContents();
        $this->on($this->request->is('do=update_templates'))->updateTemplates();
        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * 从 GitHub 下载 templates 并覆盖本地 templates 目录
     */
    public function updateTemplates()
    {
        // 仅管理员可操作（action() 已授权）
        $zipUrl = 'https://github.com/lhl77/Typecho-Plugin-LinksPlus/archive/refs/heads/main.zip';
        $tmpZip = sys_get_temp_dir() . '/links_templates_' . time() . '.zip';
        $tmpDir = sys_get_temp_dir() . '/links_templates_dir_' . time();

        // 下载 ZIP
        $context = stream_context_create(array('http' => array('timeout' => 30)));
        $data = @file_get_contents($zipUrl, false, $context);
        if (!$data) {
            $this->widget('Widget_Notice')->set(_t('下载失败：无法从 GitHub 获取模板压缩包。'), null, 'notice');
            $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
            return;
        }
        file_put_contents($tmpZip, $data);

        // 解压到临时目录
        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            $this->widget('Widget_Notice')->set(_t('解压失败：无法打开下载的压缩包。'), null, 'notice');
            $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
            return;
        }
        @mkdir($tmpDir, 0755, true);
        $zip->extractTo($tmpDir);
        $zip->close();

        // 源模板目录（zip 中的路径）
        $srcTemplates = $tmpDir . '/Typecho-Plugin-LinksPlus-main/templates';
        $dstTemplates = dirname(__FILE__) . '/templates';

        if (!is_dir($srcTemplates)) {
            // 清理
            @unlink($tmpZip);
            // 递归删除 tmpDir
            $this->rrmdir($tmpDir);
            $this->widget('Widget_Notice')->set(_t('模板包中未找到 templates 目录，操作已取消。'), null, 'notice');
            $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
            return;
        }

        // 备份现有 templates（如存在）
        if (is_dir($dstTemplates)) {
            $backup = dirname(__FILE__) . '/templates_backup_' . date('YmdHis');
            @rename($dstTemplates, $backup);
        }

        // 复制新模板到目标
        $ok = $this->rcopy($srcTemplates, $dstTemplates);

        // 清理临时文件
        @unlink($tmpZip);
        $this->rrmdir($tmpDir);

        if ($ok) {
            $this->widget('Widget_Notice')->set(_t('模板已成功更新并覆盖到 plugins/Links/templates（旧模板已备份）。'), null, 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t('模板更新失败，请检查文件权限。'), null, 'notice');
        }

        $this->response->redirect(Typecho_Common::url('options-plugin.php?config=Links', $this->options->adminUrl));
    }

    // 递归复制目录
    private function rcopy($src, $dst)
    {
        if (!is_dir($src)) return false;
        @mkdir($dst, 0755, true);
        $dir = opendir($src);
        if (!$dir) return false;
        while (false !== ($file = readdir($dir))) {
            if ($file == '.' || $file == '..') continue;
            $s = $src . DIRECTORY_SEPARATOR . $file;
            $d = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($s)) {
                $this->rcopy($s, $d);
            } else {
                @copy($s, $d);
            }
        }
        closedir($dir);
        return true;
    }

    // 递归删除目录
    private function rrmdir($dir)
    {
        if (!is_dir($dir)) return;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
        @rmdir($dir);
    }
}

