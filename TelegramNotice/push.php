<?php
if (!defined('__TYPECHO_ADMIN__')) {
    exit;
}

use Typecho\Db;

// 后台权限校验（管理员）
\Typecho\Widget::widget('Widget_User')->pass('administrator');

$options = \Utils\Helper::options();
$opt = $options->plugin('TelegramNotice');

$db = Db::get();
$prefix = $db->getPrefix();

function tg_req(string $key, string $default = ''): string
{
    try {
        $v = \Typecho\Widget::widget('Widget_Request')->get($key);
        if ($v !== null && $v !== '')
            return trim((string) $v);
    } catch (\Throwable $e) {
        // ignore
    }
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

$q = tg_req('q', '');
$page = (int) tg_req('page', '1');
if ($page < 1)
    $page = 1;

$perPage = 10;
$ajax = tg_req('ajax', '') === '1';

$cntSel = $db->select(['COUNT(*)' => 'cnt'])
    ->from($prefix . 'contents')
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish');

if ($q !== '') {
    $like = '%' . $q . '%';
    $cntSel->where('(title LIKE ? OR text LIKE ?)', $like, $like);
}
$total = (int) ($db->fetchObject($cntSel)->cnt ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages)
    $page = $totalPages;

$offset = ($page - 1) * $perPage;

$sel = $db->select('cid', 'title', 'created')
    ->from($prefix . 'contents')
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish');

if ($q !== '') {
    $like = '%' . $q . '%';
    $sel->where('(title LIKE ? OR text LIKE ?)', $like, $like);
}

$sel->order('created', Db::SORT_DESC)->limit($perPage)->offset($offset);
$list = $db->fetchAll($sel);

// AJAX
if ($ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    $items = [];
    foreach ($list as $row) {
        $items[] = [
            'cid' => (int) $row['cid'],
            'title' => (string) $row['title'],
            'created' => (int) $row['created'],
            'createdText' => date('Y-m-d H:i:s', (int) $row['created']),
        ];
    }
    echo json_encode([
        'ok' => true,
        'q' => $q,
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'items' => $items,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 当前模板
$pushTpl = (string) ($opt->pushTpl ?? "📰 <b>{title}</b>\n\n{excerpt}\n\n<a href=\"{permalink}\">点击阅读</a>");
$pushChatId = (string) ($opt->pushChatId ?? '');

include 'header.php';
include 'menu.php';

$panelPath = 'TelegramNotice/push.php';
$panelUrlBase = $options->adminUrl . 'extending.php?panel=' . rawurlencode($panelPath);
?>
<div class="main">
    <div class="body container">
        <h2><?php _e('Telegram 文章推送'); ?></h2>

        <style>
            .tg-card {
                margin: 12px;
                padding: 12px;
                border: 1px solid #e5e5e5;
                border-radius: 6px;
                background-color: #f2f2f2
            }

            .tg-card h3 {
                margin: 0 0 10px;
                font-size: 14px;
            }

            .tg-row {
                margin: 12px;
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
            }

            .tg-grow {
                flex: 1 1 260px;
                min-width: 220px;
            }

            .tg-muted {
                color: #777;
            }

            .tg-right {
                margin-left: auto;
            }

            .tg-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 999px;
                background: #f3f3f3;
                color: #555;
                font-size: 12px;
                line-height: 18px;
            }

            .tg-stickybar {
                margin: 12px;
                padding: 12px;
                border: 1px solid #e5e5e5;
                border-radius: 6px;
                background-color: #f2f2f2;
                position: sticky;
                top: 0;
                z-index: 5;
                border-bottom: 1px solid #eee;
                padding: 8px 0;
                margin-bottom: 10px;
            }

            .tg-input {
                max-width: 460px;
                width: 100%;
                height: 32px;
                box-sizing: border-box;
                padding: 0 10px;
                border: 1px solid #d9d9d9;
                border-radius: 4px;
            }

            .tg-actions .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                height: 32px;
                line-height: 32px;
                padding: 0 12px;
                box-sizing: border-box;
            }

            .tg-table {
                table-layout: fixed;
            }

            .tg-table td {
                vertical-align: middle;
            }

            .tg-titlecell {
                max-width: 640px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .tg-mini {
                font-size: 12px;
            }

            .tg-danger {
                background: #fff5f5;
                border-color: #ffd6d6;
            }

            #tg-clear {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                height: 32px;
                padding: 0 12px;
                line-height: 32px;
                box-sizing: border-box;
            }

            /* 修复：你当前表格只有 4 列（CID/标题/发布时间/操作），
            nth-child 索引应为：1=CID 2=标题 3=发布时间 4=操作
            所以“先隐藏发布时间，再隐藏CID”应该是隐藏第 3 列，再隐藏第 1 列。
        */
            @media (max-width: 980px) {

                .tg-table th:nth-child(3),
                .tg-table td:nth-child(3) {
                    display: none;
                }

                .tg-table colgroup col:nth-child(3) {
                    display: none;
                }
            }

            @media (max-width: 720px) {

                .tg-table th:nth-child(1),
                .tg-table td:nth-child(1) {
                    display: none;
                }

                .tg-table colgroup col:nth-child(1) {
                    display: none;
                }
            }

            .tg-btn-loading {
                opacity: .65;
                pointer-events: none;
            }

            .tg-toast {
                position: fixed;
                right: 18px;
                bottom: 18px;
                z-index: 9999;
                background: rgba(0, 0, 0, .82);
                color: #fff;
                padding: 10px 12px;
                border-radius: 6px;
                font-size: 13px;
                line-height: 18px;
                max-width: 66vw;
                box-sizing: border-box;
            }

            /* 让“操作”列在窄屏时更窄，标题列优先展示 */
            .tg-table th:last-child,
            .tg-table td:last-child {
                white-space: nowrap;
            }

            .tg-actions .btn {
                padding: 0 10px;
                /* 默认稍微收一点 */
                min-width: 0;
            }

            /* 窄屏：进一步压缩操作列按钮，只显示“推”字（标题获得更多空间） */
            @media (max-width: 560px) {
                .tg-table colgroup col:last-child {
                    width: 72px !important;
                }

                /* 操作列变窄 */
                .tg-actions .btn {
                    padding: 0 8px;
                    font-size: 12px;
                    height: 28px;
                    line-height: 28px;
                }

                .tg-actions .btn::after {
                    content: "推送";
                }

                /* 兜底（避免空） */
                .tg-actions .btn {
                    position: relative;
                }

                .tg-actions .btn span {
                    display: none;
                }
            }

            /* 更窄：按钮文字隐藏，只留一个字 */
            @media (max-width: 420px) {
                .tg-table colgroup col:last-child {
                    width: 56px !important;
                }

                .tg-actions .btn {
                    padding: 0 6px;
                }
            }
        </style>

        <!-- 置顶操作栏：搜索 + 状态 -->
        <div class="tg-stickybar">
            <div class="tg-row">
                <input class="tg-input tg-grow" type="text" id="tg-q"
                    value="<?php echo htmlspecialchars($q, ENT_QUOTES); ?>" placeholder="<?php _e('按标题/内容搜索'); ?>">
                <span class="tg-badge"
                    id="tg-search-hint"><?php echo $total ? ('共 ' . (int) $total . ' 篇') : '无结果'; ?></span>
                <a class="btn" id="tg-clear"
                    href="<?php echo htmlspecialchars($panelUrlBase, ENT_QUOTES); ?>"><?php _e('清空'); ?></a>
            </div>
        </div>


        <section class="tg-card">
            <h2 style="margin:5px 10px;"><?php _e('文章列表'); ?></h2>

            <table class="typecho-list-table tg-table">
                <colgroup>
                    <col width="80">
                    <col>
                    <col width="180">
                    <col width="160">
                </colgroup>
                <thead>
                    <tr>
                        <th><?php _e('CID'); ?></th>
                        <th><?php _e('标题'); ?></th>
                        <th><?php _e('发布时间'); ?></th>
                        <th><?php _e('操作'); ?></th>
                    </tr>
                </thead>
                <tbody id="tg-tbody">
                    <?php foreach ($list as $row): ?>
                        <tr>
                            <td><?php echo (int) $row['cid']; ?></td>
                            <td class="tg-titlecell"
                                title="<?php echo htmlspecialchars((string) $row['title'], ENT_QUOTES); ?>">
                                <?php echo htmlspecialchars((string) $row['title'], ENT_QUOTES); ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', (int) $row['created']); ?></td>
                            <td class="tg-actions">
                                <form method="post" style="display:inline"
                                    action="<?php echo htmlspecialchars($options->index . '/action/telegram-comment?do=pushPost', ENT_QUOTES); ?>">
                                    <input type="hidden" name="cid" value="<?php echo (int) $row['cid']; ?>">
                                    <button class="btn primary" type="submit"><span><?php _e('推送'); ?></span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$list): ?>
                        <tr>
                            <td colspan="4">
                                <p class="description tg-muted"><?php _e('没有匹配的文章'); ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="tg-row tg-actions" style="margin:6px 0 10px;">
                <span class="tg-muted tg-mini" id="tg-selected-hint" style="display:none;"></span>

                <div class="tg-right tg-row tg-actions">
                    <a class="btn" id="tg-prev"
                        href="<?php echo htmlspecialchars($panelUrlBase . '&q=' . rawurlencode($q) . '&page=' . max(1, $page - 1), ENT_QUOTES); ?>"><?php _e('上一页'); ?></a>
                    <a class="btn" id="tg-next"
                        href="<?php echo htmlspecialchars($panelUrlBase . '&q=' . rawurlencode($q) . '&page=' . min($totalPages, $page + 1), ENT_QUOTES); ?>"><?php _e('下一页'); ?></a>
                    <span class="tg-badge" id="tg-pageinfo">
                        <?php echo '第 ' . (int) $page . ' / ' . (int) $totalPages . ' 页 · 共 ' . (int) $total . ' 篇'; ?>
                    </span>
                </div>
            </div>
        </section>

        <section class="tg-card">
            <h3><?php _e('推送配置'); ?></h3>

            <p class="description tg-muted">
                <?php _e('推送目标 Chat ID 在插件设置中配置（pushChatId）。'); ?>
            </p>

            <div class="tg-row">
                <div class="tg-badge">
                    <b><?php _e('推送ChatId：'); ?></b>
                    <span><?php echo htmlspecialchars($pushChatId ?: '(未配置)', ENT_QUOTES); ?></span>
                </div>
                <?php if (trim($pushChatId) === ''): ?>
                    <div class="tg-badge tg-danger"><?php _e('未配置 pushChatId，推送会失败'); ?></div>
                <?php endif; ?>
            </div>

            <form method="post"
                action="<?php echo htmlspecialchars($options->index . '/action/telegram-comment?do=pushTplSave', ENT_QUOTES); ?>"
                style="margin-top:10px;">
                <p class="tg-mini tg-muted" style="margin:0 0 6px;"><?php _e('文章推送模板（HTML）'); ?></p>
                <p style="margin:0;">
                    <textarea name="pushTpl"
                        style="width:100%;min-height:140px;"><?php echo htmlspecialchars($pushTpl, ENT_QUOTES); ?></textarea>
                </p>
                <p class="description tg-muted" style="margin-top:6px;">
                    <?php _e('变量：{title} {excerpt} {permalink} {created} {cid}'); ?>
                </p>
                <p class="tg-actions" style="margin-top:8px;">
                    <button class="btn primary" type="submit"><?php _e('保存模板'); ?></button>
                </p>
            </form>
        </section>
    </div>
</div>

<script>
    (function () {
        var panel = <?php echo json_encode($panelPath, JSON_UNESCAPED_UNICODE); ?>;

        var qEl = document.getElementById('tg-q');
        var hintEl = document.getElementById('tg-search-hint');
        var tbody = document.getElementById('tg-tbody');
        var pageInfo = document.getElementById('tg-pageinfo');
        var prevBtn = document.getElementById('tg-prev');
        var nextBtn = document.getElementById('tg-next');

        function toast(msg, ms) {
            ms = ms || 1800;
            var el = document.createElement('div');
            el.className = 'tg-toast';
            el.textContent = msg;
            document.body.appendChild(el);
            setTimeout(function () {
                try { el.parentNode && el.parentNode.removeChild(el); } catch (e) { }
            }, ms);
        }

        function bindPushButtons() {
            var forms = document.querySelectorAll('form[action*="do=pushPost"]');
            for (var i = 0; i < forms.length; i++) {
                (function (form) {
                    form.addEventListener('submit', async function (e) {
                        e.preventDefault();

                        var btn = form.querySelector('button[type="submit"]');
                        if (btn && btn.getAttribute('data-loading') === '1') return; // 防二次提交
                        if (btn) {
                            btn.setAttribute('data-loading', '1');
                            btn.classList.add('tg-btn-loading');
                            btn.textContent = '推送中...';
                        }

                        try {
                            var fd = new FormData(form);
                            var res = await fetch(form.action, {
                                method: 'POST',
                                body: fd,
                                credentials: 'same-origin'
                            });

                            // 兼容：如果 Action 返回 JSON（推荐），则读取并提示；否则仅按 HTTP 状态提示
                            var txt = await res.text();
                            var data = null;
                            try { data = JSON.parse(txt); } catch (e) { data = null; }

                            if (data && typeof data === 'object') {
                                if (data.ok === true) {
                                    toast(data.message || '推送成功');
                                } else {
                                    toast((data.message || '推送失败') + (data.error ? ('（' + data.error + '）') : ''), 2500);
                                }
                            } else {
                                // 非 JSON：按状态码给个结果（避免无反馈）
                                toast(res.ok ? '推送完成' : '推送失败', res.ok ? 1800 : 2500);
                            }
                        } catch (err) {
                            toast('推送失败：网络错误', 2500);
                        } finally {
                            if (btn) {
                                btn.removeAttribute('data-loading');
                                btn.classList.remove('tg-btn-loading');
                                btn.textContent = '推送';
                            }
                        }
                    });
                })(forms[i]);
            }
        }

        function buildRow(item) {
            var cid = item.cid;
            var title = item.title || '';
            var createdText = item.createdText || '';
            var actionUrl = <?php echo json_encode($options->index . '/action/telegram-comment?do=pushPost', JSON_UNESCAPED_UNICODE); ?>;

            return ''
                + '<tr>'
                + '  <td>' + cid + '</td>'
                + '  <td class="tg-titlecell" title="' + escapeAttr(title) + '">' + escapeHtml(title) + '</td>'
                + '  <td>' + escapeHtml(createdText) + '</td>'
                + '  <td class="tg-actions">'
                + '    <form method="post" style="display:inline" action="' + escapeAttr(actionUrl) + '">'
                + '      <input type="hidden" name="cid" value="' + cid + '">'
                + '      <button class="btn primary" type="submit"><span>推送</span></button>'
                + '    </form>'
                + '  </td>'
                + '</tr>';
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]);
            });
        }
        function escapeAttr(s) { return escapeHtml(s); }

        var t = null;
        function debounce(fn, ms) {
            return function () {
                var args = arguments;
                if (t) clearTimeout(t);
                t = setTimeout(function () { fn.apply(null, args); }, ms);
            };
        }

        function setHint(text) {
            if (!hintEl) return;
            hintEl.textContent = text || '';
        }

        async function loadList(q, page) {
            q = String(q || '');

            setHint('加载中...');
            var url = new URL(window.location.href);
            url.searchParams.set('panel', panel);
            url.searchParams.set('ajax', '1');
            url.searchParams.set('q', q);
            url.searchParams.set('page', String(page || 1));

            var res = await fetch(url.toString(), { credentials: 'same-origin' });
            var data = await res.json();

            if (!data || data.ok !== true) {
                setHint('加载失败');
                return;
            }

            var html = '';
            if (data.items && data.items.length) {
                for (var i = 0; i < data.items.length; i++) html += buildRow(data.items[i]);
            } else {
                html = '<tr><td colspan="4"><p class="description">没有匹配的文章</p></td></tr>';
            }
            tbody.innerHTML = html;

            // 表格重绘后需要重新绑定推送按钮
            bindPushButtons();

            if (pageInfo) pageInfo.textContent = '第 ' + data.page + ' / ' + data.totalPages + ' 页 · 共 ' + data.total + ' 篇';

            var base = <?php echo json_encode($panelUrlBase, JSON_UNESCAPED_UNICODE); ?>;
            var prev = Math.max(1, data.page - 1);
            var next = Math.min(data.totalPages, data.page + 1);

            if (prevBtn) prevBtn.href = base + '&q=' + encodeURIComponent(data.q || '') + '&page=' + prev;
            if (nextBtn) nextBtn.href = base + '&q=' + encodeURIComponent(data.q || '') + '&page=' + next;

            var newUrl = base + '&q=' + encodeURIComponent(data.q || '') + '&page=' + data.page;
            window.history.replaceState(null, '', newUrl);

            setHint(data.total ? ('共 ' + data.total + ' 篇') : '无结果');
        }

        var doSearch = debounce(function () {
            var q = qEl ? qEl.value : '';
            loadList(q, 1);
        }, 250);

        if (qEl) qEl.addEventListener('input', function () { doSearch(); });

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = new URL(prevBtn.href, window.location.origin);
                loadList(url.searchParams.get('q') || '', parseInt(url.searchParams.get('page') || '1', 10));
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = new URL(nextBtn.href, window.location.origin);
                loadList(url.searchParams.get('q') || '', parseInt(url.searchParams.get('page') || '1', 10));
            });
        }

        // 首屏绑定一次
        bindPushButtons();
    })();
</script>

<?php
include 'footer.php';