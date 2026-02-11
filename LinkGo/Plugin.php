<?php
/**
 * 外部链接自动跳转插件
 * 
 * @package LinkGo
 * @author LHL
 * @version 1.0.0
 * @link https://github.com/lhl77/Typecho-Plugin-LinkGo
 */
class LinkGo_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        // 旧式/兼容注册（适配老版本或部分主题）
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('LinkGo_Plugin', 'convertLinks');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('LinkGo_Plugin', 'convertLinks');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('LinkGo_Plugin', 'convertLinks');

        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('LinkGo_Plugin', 'convertCommentLinks');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->content = array('LinkGo_Plugin', 'convertCommentLinks');
        // 尝试修改评论者链接字段（部分主题会读取 comment.url）
        Typecho_Plugin::factory('Widget_Abstract_Comments')->filter = array('LinkGo_Plugin', 'convertAuthorUrl');

        // Namespaced 注册（Typecho 新版/文档中常见写法）
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->contentEx = ['LinkGo_Plugin', 'convertLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->excerptEx = ['LinkGo_Plugin', 'convertLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->content = ['LinkGo_Plugin', 'convertLinks'];

        \Typecho\Plugin::factory('Widget\\Base\\Comments')->contentEx = ['LinkGo_Plugin', 'convertCommentLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Comments')->content = ['LinkGo_Plugin', 'convertCommentLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Comments')->filter = ['LinkGo_Plugin', 'convertAuthorUrl'];
        // 兜底：在 Archive 渲染后再运行一次替换，覆盖绕开过滤器的主题实现
        \Typecho\Plugin::factory('Widget\\Archive')->afterRender = ['LinkGo_Plugin', 'applyToArchive'];

        // 输出缓冲：尝试在 Archive 的 header/footer 阶段捕获全部输出并处理
        \Typecho\Plugin::factory('Widget\\Archive')->header = ['LinkGo_Plugin', 'startBuffer'];
        \Typecho\Plugin::factory('Widget\\Archive')->footer = ['LinkGo_Plugin', 'endBuffer'];

        // 在插件激活时注册路由，让 /go 能够由 Typecho 路由到插件 Action
        try {
            if (class_exists('Typecho\Widget\Helper')) {
                \Typecho\Widget::widget('Widget_Options')->plugin('LinkGo');
            }
        } catch (Exception $e) {
            // ignore
        }

        // 使用 Helper::addRoute 注册路由（带参数目标），优先使用常见的命名空间实现
        $routePath = '/go/[target]';
        if (class_exists('\Typecho\\Helper') && method_exists('\Typecho\\Helper', 'addRoute')) {
            \Typecho\Helper::addRoute('linkgo', $routePath, 'LinkGo_Action', 'index');
        } elseif (class_exists('Helper') && method_exists('Helper', 'addRoute')) {
            Helper::addRoute('linkgo', $routePath, 'LinkGo_Action', 'index');
        } elseif (class_exists('Utils\\Helper') && method_exists('Utils\\Helper', 'addRoute')) {
            \Utils\Helper::addRoute('linkgo', $routePath, 'LinkGo_Action', 'index');
        }

        return '插件已激活';
    }

    public static function deactivate()
    {
        // 注销前面可能添加的路由
        if (class_exists('\Typecho\\Helper') && method_exists('\Typecho\\Helper', 'removeRoute')) {
            \Typecho\Helper::removeRoute('linkgo');
        } elseif (class_exists('Helper') && method_exists('Helper', 'removeRoute')) {
            Helper::removeRoute('linkgo');
        } elseif (class_exists('Utils\\Helper') && method_exists('Utils\\Helper', 'removeRoute')) {
            \Utils\Helper::removeRoute('linkgo');
        }

        return '插件已禁用';
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 注入简洁的 Material Design 3 风格样式（非破坏性，仅覆盖少数控件样式以改善外观）
        echo '<style>';
        // 使用中性偏蓝的主色，避免黄色强调
        echo ':root{--lg-primary:#3b82c4;--lg-on-primary:#ffffff;--lg-surface:#fff;--lg-muted:#6b7280;--lg-text:#0f172a}';
        echo '.typecho-page-main .linkgo-md3{max-width:820px;margin:18px auto;padding:18px;background:linear-gradient(180deg,rgba(255,255,255,0.7),rgba(255,255,255,0.9));border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,0.06);position:relative}';
        echo '.typecho-page-main .linkgo-md3 .typecho-label{font-weight:600;color:var(--lg-text);display:block;margin-bottom:6px}';
        // 卡片头部样式（flex 布局，适配示例图）
        echo '.typecho-page-main .linkgo-card-header{background:linear-gradient(135deg,#7c3aed 0%,#3b82c4 100%);border-radius:12px;padding:18px;color:#ffffff;margin-bottom:12px;box-shadow:0 10px 30px rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:space-between;gap:12px}';
        echo '.typecho-page-main .linkgo-card-header .left{display:flex;align-items:center;gap:14px}';
        echo '.typecho-page-main .linkgo-card-header .logo{width:64px;height:64px;border-radius:16px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px}';
        echo '.typecho-page-main .linkgo-card-header .title{font-size:22px;font-weight:800;margin-bottom:2px}';
        echo '.typecho-page-main .linkgo-card-header .subtitle{font-size:13px;opacity:0.95}';
    // header 内显示 actions（放入卡片内部）
    echo '.typecho-page-main .linkgo-card-header .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px}';
    // actions 单独行样式（浅背景，圆角，支持换行）
    echo '.typecho-page-main .linkgo-actions-row{margin-top:12px;padding:10px;border-radius:12px;background:#f6fbff;border:1px solid #e6f4ff;display:flex;gap:8px;flex-wrap:wrap;align-items:center}';
    // chips 在浅色行上的样式（浅蓝色背景与深蓝文字），并保持单行显示
    echo '.typecho-page-main .linkgo-actions-row .linkgo-chip{background:#e6f4ff;color:#0366d6;padding:6px 10px;border-radius:999px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(3,102,214,0.08);white-space:nowrap}';
    // 保留卡片主题下的深色 chip（如果被其他区域使用）
    echo '.typecho-page-main .linkgo-card-header .linkgo-chip{background:rgba(255,255,255,0.12);color:#fff;padding:6px 10px;border-radius:999px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,0.06)}';
        echo '.typecho-page-main .linkgo-info{margin-top:12px;padding:14px;border-radius:10px;background:#f8fbff;border:1px solid #e6f1ff;color:#0f172a}';
        echo '.typecho-page-main .linkgo-info .title{font-weight:700;margin-bottom:8px}';
        echo '.typecho-page-main .linkgo-success{margin-top:12px;padding:12px;border-radius:10px;background:linear-gradient(90deg,#10b981,#059669);color:#fff;font-weight:700}';
        echo '.typecho-page-main .linkgo-md3 .description{color:var(--lg-muted);margin-bottom:8px;font-size:13px}';
        echo '.typecho-page-main .linkgo-md3 input[type=text], .typecho-page-main .linkgo-md3 select{height: auto;width:100%;padding:10px 12px;border-radius:10px;border:1px solid #e6eef8;background:var(--lg-surface);box-shadow:0 2px 6px rgba(59,130,246,0.06);margin-bottom:12px}';
        echo '.typecho-page-main .linkgo-md3 .typecho-submit{background:var(--lg-primary);color:var(--lg-on-primary);border-radius:10px;padding:10px 18px;border:0;font-weight:700}';
        echo '.typecho-page-main .linkgo-md3 .btn{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;text-decoration:none;border:1px solid transparent;font-weight:600}';
        echo '.typecho-page-main .linkgo-md3 .btn--primary, .typecho-page-main .linkgo-md3 .btn.btn--primary, .typecho-page-main .linkgo-md3 .linkgo-btn--primary{background:linear-gradient(90deg,var(--lg-primary),#2563eb);color:var(--lg-on-primary);border-color:rgba(37,99,235,0.12);box-shadow:0 8px 24px rgba(59,130,246,0.12)}';
        echo '.typecho-page-main .linkgo-md3 .btn--primary:hover{filter:brightness(0.96)}';
        echo '.typecho-page-main .linkgo-md3 .typecho-radio{display:flex;gap:12px;align-items:center;margin-bottom:12px}';
        echo '.typecho-page-main .linkgo-md3 .typecho-radio label{margin-right:8px}';
        echo '</style>';

        echo <<<'LG_PLUGIN_CONFIG_SCRIPT'
<script>
document.addEventListener("DOMContentLoaded", function(){
    var f = document.querySelector(".typecho-page-main form");
    if (f && !f.classList.contains("linkgo-md3")) { f.classList.add("linkgo-md3"); }
        if (f && !document.querySelector(".linkgo-card-header")) {
        var header = document.createElement("div");
        header.className = "linkgo-card-header";
        header.innerHTML = '<div class="left"><div class="logo">🔗</div><div><div class="title">LinkGo</div><div class="subtitle">外部链接中间跳转插件 · 安全提示页</div></div></div>';
        f.parentNode.insertBefore(header, f);

    // 在卡片 header 内插入 actions（包含同步按钮）
    var actions = document.createElement('div');
    actions.className = 'actions';
    actions.innerHTML = '<a class="linkgo-chip" href="https://github.com/lhl77/Typecho-Plugin-LinkGo" target="_blank" rel="noopener noreferrer">GitHub 仓库</a><a class="linkgo-chip" href="https://blog.lhl.one/artical/949.html#主题开发" target="_blank" rel="noopener noreferrer">主题开发文档</a><a class="linkgo-chip" href="https://blog.lhl.one/artical/949.html#主题" target="_blank" rel="noopener noreferrer">更多主题</a>';
    header.appendChild(actions);
    }
});
</script>
LG_PLUGIN_CONFIG_SCRIPT;

        // 站点显示标题（用于跳转页）
        $siteTitle = new Typecho_Widget_Helper_Form_Element_Text('siteTitle', null, '', _t('跳转页面站点标题'));
        $form->addInput($siteTitle);

        // Logo 图片 URL
        $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', null, '', _t('Logo 图片 URL'));
        $form->addInput($logoUrl);

        // 起始年份
        $startYear = new Typecho_Widget_Helper_Form_Element_Text('startYear', null, '2026', _t('起始年份（页脚）'));
        $form->addInput($startYear);

        // 主题选择：扫描插件目录下的 page/themes 子目录作为可选主题
        $themeOptions = array();
        try {
            $themeDir = __DIR__ . '/page/themes';
            if (is_dir($themeDir)) {
                $items = scandir($themeDir);
                foreach ($items as $it) {
                    if ($it === '.' || $it === '..')
                        continue;
                    if (is_dir($themeDir . DIRECTORY_SEPARATOR . $it)) {
                        $themeOptions[$it] = $it;
                    }
                }
            }
        } catch (Exception $e) {
            $themeOptions = array();
        }
        if (empty($themeOptions)) {
            $themeOptions = array('Default' => 'Default');
        }
        $themeName = new Typecho_Widget_Helper_Form_Element_Select('themeName', $themeOptions, 'Default', _t('跳转页主题'));
        $form->addInput($themeName);

        // 外部链接是否在新标签打开
        $openNew = new Typecho_Widget_Helper_Form_Element_Radio(
            'openInNewTab',
            array('1' => '是（_blank）', '0' => '否（当前窗口）'),
            '1',
            _t('外部链接打开方式')
        );
        $form->addInput($openNew);

        // 重写监控开关：当主题使用 AJAX/客户端渲染时推荐开启
        $enableClient = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableClientRewrite',
            array('1' => '是（推荐）', '0' => '否'),
            '1',
            _t('AJAX兼容（当主题使用 AJAX 时推荐开启）')
        );
        $form->addInput($enableClient);

    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function convertLinks($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);

        // 调试：如果需要验证钩子是否被调用，取消下一行注释以把信息写入 PHP 错误日志
        // error_log('[LinkGo] convertLinks called for widget: ' . (is_object($widget) ? get_class($widget) : 'unknown'));

        // 支持属性顺序任意，href 单双引号
        return preg_replace_callback(
            '/<a\s+([^>]*?)href=("|\')(.*?)\2([^>]*)>/i',
            function ($matches) use ($siteHost, $siteUrl) {
                $beforeAttrs = $matches[1];
                $href = $matches[3];
                $afterAttrs = $matches[4];

                // 如果 href 为空，直接返回原始标签
                if (empty($href)) {
                    return $matches[0];
                }

                $targetHost = parse_url($href, PHP_URL_HOST);
                $isExternal = $targetHost && strcasecmp($targetHost, $siteHost) !== 0;

                if ($isExternal) {
                    // 读取插件设置（如果可用）
                    $pluginOptions = null;
                    try {
                        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                    } catch (Exception $e) {
                        $pluginOptions = null;
                    }

                    $openNew = isset($pluginOptions->openInNewTab) ? ($pluginOptions->openInNewTab === '1') : true;

                    // 使用 URL-safe base64（替换 +/ 为 -_ 并移除尾部 =），放在路径中
                    $encodedUrl = rtrim(strtr(base64_encode($href), '+/', '-_'), '=');
                    // 使用路径形式 /go/<encoded>
                    $newHref = rtrim($siteUrl, '/') . '/go/' . $encodedUrl;

                    // rel 一律加上安全项
                    $rel = 'nofollow noopener noreferrer';
                    $targetAttr = $openNew ? ' target="_blank"' : '';
                    // 保持原始其他属性
                    return '<a ' . $beforeAttrs . 'href="' . $newHref . '"' . $afterAttrs . $targetAttr . ' rel="' . $rel . '">';
                } else {
                    // 内部链接，保持不变
                    return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . '>';
                }
            },
            $content
        );
    }

    public static function convertCommentLinks($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);

        // 调试日志（取消注释以启用）
        // error_log('[LinkGo] convertCommentLinks called for widget: ' . (is_object($widget) ? get_class($widget) : 'unknown'));

        return preg_replace_callback(
            '/<a\s+([^>]*?)href=("|\')(.*?)\2([^>]*)>/i',
            function ($matches) use ($siteHost, $siteUrl) {
                $beforeAttrs = $matches[1];
                $href = $matches[3];
                $afterAttrs = $matches[4];

                if (empty($href))
                    return $matches[0];

                $targetHost = parse_url($href, PHP_URL_HOST);
                $isExternal = $targetHost && strcasecmp($targetHost, $siteHost) !== 0;

                if ($isExternal) {
                    $pluginOptions = null;
                    try {
                        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                    } catch (Exception $e) {
                        $pluginOptions = null;
                    }
                    $openNew = isset($pluginOptions->openInNewTab) ? ($pluginOptions->openInNewTab === '1') : true;

                    $encodedUrl = rtrim(strtr(base64_encode($href), '+/', '-_'), '=');
                    $newHref = rtrim($siteUrl, '/') . '/go/' . $encodedUrl;
                    $rel = 'nofollow noopener noreferrer';
                    $targetAttr = $openNew ? ' target="_blank"' : '';
                    return '<a ' . $beforeAttrs . 'href="' . $newHref . '"' . $afterAttrs . $targetAttr . ' rel="' . $rel . '">';
                } else {
                    return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . '>';
                }
            },
            $content
        );
    }

    public static function convertAuthorUrl($comment, $widget)
    {
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);

        // 调试：取消注释以记录评论数组，以确认钩子被触发
        // error_log('[LinkGo] convertAuthorUrl comment url: ' . (isset($comment['url']) ? $comment['url'] : ''));

        $url = isset($comment['url']) ? $comment['url'] : '';
        if (!empty($url)) {
            $targetHost = parse_url($url, PHP_URL_HOST);
            if ($targetHost && strcasecmp($targetHost, $siteHost) !== 0) {
                $pluginOptions = null;
                try {
                    $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                } catch (Exception $e) {
                    $pluginOptions = null;
                }
                $openNew = isset($pluginOptions->openInNewTab) ? ($pluginOptions->openInNewTab === '1') : true;

                $encodedUrl = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
                // 仅把 URL 字段改为中间跳转地址（路径格式）
                $comment['url'] = rtrim($siteUrl, '/') . '/go/' . $encodedUrl;
            }
        }

        return $comment;
    }

    /**
     * 兜底：在 Archive 渲染后处理已渲染的内容
     * 许多主题会在渲染阶段做自定义输出，afterRender 是最后阶段的补充
     */
    public static function applyToArchive($archive)
    {
        if (isset($archive->content) && !empty($archive->content)) {
            $archive->content = self::convertLinks($archive->content, $archive, null);
        }

        if (isset($archive->excerpt) && !empty($archive->excerpt)) {
            $archive->excerpt = self::convertLinks($archive->excerpt, $archive, null);
        }

        return $archive;
    }

    // 开始输出缓冲
    public static function startBuffer()
    {
        if (!headers_sent() && !in_array('ob_active', get_defined_vars())) {
            ob_start();
        }
    }

    // 结束缓冲并处理输出 HTML
    public static function endBuffer()
    {
        if (ob_get_level() > 0) {
            $html = ob_get_clean();
            // 运行替换
            $processed = self::convertLinks($html, null, null);

            // 根据配置决定是否注入前端重写脚本（用于 AJAX/客户端渲染场景）
            $injectClient = false;
            try {
                $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                if (isset($pluginOptions->enableClientRewrite) && $pluginOptions->enableClientRewrite === '1') {
                    $injectClient = true;
                }
            } catch (Exception $e) {
                $injectClient = false;
            }

            if ($injectClient) {
                // 站点 URL（注入到脚本中以避免脚本自行猜测）
                try {
                    $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
                } catch (Exception $e) {
                    $siteUrl = '';
                }
                $siteJson = json_encode(rtrim($siteUrl, '/'));

                $script = <<<JS
<script>
(function(){
    var siteBase = {$siteJson} || (window.location.origin || '');

    function urlSafeBase64Encode(str){
        try{var b64 = btoa(unescape(encodeURIComponent(str)));return b64.replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');}catch(e){return null}
    }

    function isExternalHref(href){
        if(!href) return false;
        if(/^(mailto|tel|javascript|#)/i.test(href)) return false;
        try{var u=new URL(href, location.href);return u.protocol.indexOf('http')===0 && u.host !== location.host;}catch(e){return false}
    }

    function rewriteAnchor(a){
        if(!a || !a.getAttribute) return;
        if(a.dataset && a.dataset.linkgoRewritten==='1') return;
        var href = a.getAttribute('href') || a.href;
        if(!isExternalHref(href)) return;
        var enc = urlSafeBase64Encode(href);
        if(!enc) return;
        a.setAttribute('href', siteBase.replace(/\/$/, '') + '/go/' + enc);
        var rel = (a.getAttribute('rel')||'').split(/\s+/).filter(Boolean);
        ['nofollow','noopener','noreferrer'].forEach(function(r){ if(rel.indexOf(r)===-1) rel.push(r); });
        a.setAttribute('rel', rel.join(' '));
        if(a.dataset) a.dataset.linkgoRewritten='1';
    }

    function rewriteWithin(root){
        if(!root) return; var nodes = root.querySelectorAll ? root.querySelectorAll('a[href]') : [];
        for(var i=0;i<nodes.length;i++){ try{ rewriteAnchor(nodes[i]); }catch(e){} }
        if(root.nodeName==='A' && root.getAttribute && root.getAttribute('href')) rewriteAnchor(root);
    }

    // 初次运行
    try{ rewriteWithin(document); }catch(e){}

    // 监听动态插入
    try{
        var mo = new MutationObserver(function(muts){ for(var m=0;m<muts.length;m++){ var add = muts[m].addedNodes; if(!add) continue; for(var n=0;n<add.length;n++){ var node = add[n]; if(node.nodeType===1) rewriteWithin(node); } } });
        mo.observe(document.documentElement||document.body, { childList:true, subtree:true });
    }catch(e){}

    // jQuery Ajax 补充
    if(window.jQuery) (function($){ $(document).ajaxComplete(function(){ try{ rewriteWithin(document); }catch(e){} }); })(window.jQuery);

    window.LinkGoRewrite = rewriteWithin;
})();
</script>
JS;

                $processed .= $script;
            }

            echo $processed;
        }
    }
}