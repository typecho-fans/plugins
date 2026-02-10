<?php

/**
 * 友情链接插件 [TF 社区维护版本]
 * @package Links Plus
 * @author LHL,HANNY
 * @version 1.3.1
 * @dependence 14.10.10-*
 * @link https://github.com/typecho-fans/plugins/tree/master/Links
 * 
 * version 1.3.1 at 2025-02-09 by LHL
 * 优化 一些细节
 * 
 * version 1.3.0 at 2025-02-09 by LHL
 * 优化 UI - Material Design 3
 * 添加 Links Plus到菜单，更加方便操作
 * 添加 模板，能够更灵活地自定义输出结构，支持 CSS/JS 注入
 * 添加 正文重写，免去修改/重新开发主题的步骤
 * 移除 懵仙兔兔 的广告内容（一键添加TA的友链）
 * 
 * version 1.2.7 at 2024-06-21 by 泽泽社长
 * 解决php8.2一处报错问题
 * 
 * version 1.2.6 at 2023-05-15 by 泽泽社长
 * 支持主题作者自定义友链 html 结构
 * 
 * version 1.2.5 at 2023-03-27 by 懵仙兔兔
 * 友链添加 noopener 外链属性
 * 内置友链邮箱解析头像链接 api 接口调整为仅内部调用
 * Action 和内置友链邮箱解析头像链接 api 接口使用加盐地址
 * 文本字段入库过滤 XSS
 * 增加图片尺寸参数支持
 * 增加规则和默认图片尺寸设置选项
 * 修复历史遗留问题更新 lid 导致报错
 * 
 * version 1.2.3 at 2023-03-26 by 懵仙兔兔
 * 修复没有一条友链时，Typecho 1.2 友链设置界面报错问题（虽然报错不影响功能）
 * 调整表格间距
 * 删除失效链接，隐藏界面多余 input 标签
 * 修复友链邮箱解析头像链接功能，内置 api 接口
 * 
 * version 1.2.2 at 2020-03-11 by 懵仙兔兔
 * 修复一个小 BUG
 * 
 * version 1.2.1 at 2020-03-03 by 懵仙兔兔
 * 修复邮箱头像解析问题
 * 优化逻辑问题
 * 
 * version 1.2.0 at 2020-02-16 by 懵仙兔兔
 * 增加友链禁用功能
 * 增加友链邮箱功能
 * 增加友链邮箱解析头像链接功能
 * 修正数据表的占用大小问题
 * 
 * 历史版本 by 懵仙兔兔（第三方维护者）
 * 
 * version 1.1.3 at 2020-02-08 by 懵仙兔兔
 * 修复已存在表激活失败、表检测失败
 * 
 * version 1.1.2 at 2019-08-26 by 泽泽社长
 * 修复越权漏洞
 * 
 * version 1.1.1 at 2014-12-14
 * 修改支持 Typecho 1.0
 * 修正 Typecho 1.0 下不能删除的 BUG
 * 
 * 历史版本 by Hanny（原作者）
 * 
 * version 1.1.0 at 2013-12-08
 * 修改支持 Typecho 0.9
 * 
 * version 1.0.4 at 2010-06-30
 * 修正数据表的前缀问题
 * 在 Pattern 里加上所有的数据表字段
 * 
 * version 1.0.3 at 2010-06-20
 * 修改友链图片的支持方式。
 * 增加友链分类功能
 * 增加自定义字段，以便用户自定义扩展
 * 增加多种友链输出方式。
 * 增加较详细的帮助文档
 * 增加在自定义页面引用标签，方便友情链接页面的引用
 * 
 * version 1.0.2 at 2010-05-16
 * 增加SQLite支持
 * 
 * version 1.0.1 at 2009-12-27
 * 增加显示友链描述
 * 增加首页友链数量限制功能
 * 增加友链图片功能
 * 
 * version 1.0.0 at 2009-12-12
 * 实现友情链接的基本功能
 * 包括: 添加 删除 修改 排序
 */

class Links_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 固定占位符（写入文章正文中用于替换）
     */
    const REWRITE_PLACEHOLDER = '{{links_plus}}';

    /**
     * 重写块标记（用于二次重写时定位并替换旧内容）
     */
    const REWRITE_BLOCK_START = '<!-- LINKS_PLUS_START -->';
    const REWRITE_BLOCK_END = '<!-- LINKS_PLUS_END -->';

    /** 模板目录（相对插件目录） */
    const TEMPLATE_DIR = 'templates';

    /**
     * 获取插件绝对路径
     */
    public static function getPluginDir()
    {
        return dirname(__FILE__);
    }

    /**
     * 获取模板根目录绝对路径
     */
    public static function getTemplateRoot()
    {
        return self::getPluginDir() . DIRECTORY_SEPARATOR . self::TEMPLATE_DIR;
    }

    /**
     * 列出所有文件模板（读取 templates/<name>/manifest.json）
     *
     * @return array<string,array>
     */
    public static function listTemplates()
    {
        $root = self::getTemplateRoot();
        $list = array();
        if (!is_dir($root)) {
            return $list;
        }
        $dirs = glob($root . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        if (!$dirs) {
            return $list;
        }
        foreach ($dirs as $dir) {
            $manifestFile = $dir . DIRECTORY_SEPARATOR . 'manifest.json';
            if (!is_file($manifestFile)) {
                continue;
            }
            $json = @file_get_contents($manifestFile);
            if (!$json) {
                continue;
            }
            $manifest = @json_decode($json, true);
            if (!is_array($manifest)) {
                continue;
            }
            $name = isset($manifest['name']) ? (string)$manifest['name'] : basename($dir);
            if ($name === '') {
                continue;
            }
            $manifest['_dir'] = $dir;
            $list[$name] = $manifest;
        }
        return $list;
    }

    /**
     * 读取模板文件内容
     */
    public static function readTemplateFile($templateName, $file)
    {
        $templateName = trim((string)$templateName);
        $file = trim((string)$file);
        if ($templateName === '' || $file === '') {
            return null;
        }
        // 简单防穿越：只允许 [A-Za-z0-9_-]
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $templateName)) {
            return null;
        }
        $path = self::getTemplateRoot() . DIRECTORY_SEPARATOR . $templateName . DIRECTORY_SEPARATOR . $file;
        if (!is_file($path)) {
            return null;
        }
        return file_get_contents($path);
    }

    /**
     * 注入模板 CSS/JS（同一模板同一请求只注入一次）
     */
    public static function injectTemplateAssetsOnce($templateName, array $manifest)
    {
        static $injected = array();
        $key = 'tpl:' . $templateName;
        if (isset($injected[$key])) {
            return;
        }
        $injected[$key] = true;

        $inject = isset($manifest['inject']) && is_array($manifest['inject']) ? $manifest['inject'] : array();
        $injectCss = !empty($inject['css']);
        $injectJs = !empty($inject['js']);

        if ($injectCss) {
            $css = self::readTemplateFile($templateName, 'style.css');
            if ($css && trim($css) !== '') {
                echo '<style id="links-plus-tpl-' . htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') . '">' . $css . '</style>';
            }
        }

        if ($injectJs) {
            $js = self::readTemplateFile($templateName, 'script.js');
            if ($js && trim($js) !== '') {
                echo '<script id="links-plus-tpl-' . htmlspecialchars($templateName, ENT_QUOTES, 'UTF-8') . '">' . $js . '</script>';
            }
        }
    }
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return string
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        $info = Links_Plugin::linksInstall();
        try {
            $menuIndex = Helper::addMenu('Links Plus');
            Helper::addPanel($menuIndex, 'Links/manage-links.php', _t('友情链接'), _t('管理友情链接'), 'administrator');
        } catch (Exception $e) {
            Helper::addPanel(3, 'Links/manage-links.php', _t('友情链接'), _t('管理友情链接'), 'administrator');
        } catch (Throwable $e) {
            Helper::addPanel(3, 'Links/manage-links.php', _t('友情链接'), _t('管理友情链接'), 'administrator');
        }
        
    Helper::addAction('links-edit', 'Links_Action');
        // Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Links_Plugin', 'parse');
        // Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Links_Plugin', 'parse');
        // Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('Links_Plugin', 'parse');
        // Typecho_Plugin::factory('Widget_Archive')->callLinks = array('Links_Plugin', 'output_str');
        return _t($info);
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
    Helper::removeAction('links-edit');
        try {
            $menuIndex = Helper::removeMenu('Links Plus');
            if ($menuIndex !== null) {
                Helper::removePanel($menuIndex, 'Links/manage-links.php');
            }
        } catch (Exception $e) {
            // ignore
        } catch (Throwable $e) {
            // ignore
        }

        // 兼容旧注册方式
        Helper::removePanel(3, 'Links/manage-links.php');
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        echo '
<style>
:root {
    --md-primary: #0061a4;
    --md-on-primary: #ffffff;
    --md-primary-container: #d1e4ff;
    --md-on-primary-container: #001d36;
    --md-surface: #fdfcff;
    --md-surface-variant: #e1e2ec;
    --md-surface-container: #f3f4f7;
    --md-outline: #74777f;
    --md-outline-variant: rgba(0,0,0,.12);
    --md-radius: 12px;
}
.md3-wrap {
    max-width: 1080px;
}
.md3-card {
    background: var(--md-surface);
    border-radius: var(--md-radius);
    padding: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,.08), 0 1px 3px rgba(0,0,0,.12);
    margin-bottom: 24px;
    border: 1px solid var(--md-outline-variant);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
.md3-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--md-on-primary-container);
    margin-bottom: 16px;
    padding-left: 12px;
    border-left: 4px solid var(--md-primary);
    line-height: 1.2;
}
.md3-subtitle {
    font-size: .95rem;
    font-weight: 600;
    color: #374151;
    margin: 18px 0 10px;
}
.md3-body {
    color: #58606b;
    line-height: 1.75;
}
.md3-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}
@media (min-width: 980px) {
    .md3-grid.two {
        grid-template-columns: 1fr 1fr;
    }
}
.md3-header-actions {
    display: flex;
    gap: 16px;
    margin-top: 16px;
    flex-wrap: wrap;
}
.md3-btn-text {
    color: var(--md-primary);
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 20px;
    background-color: var(--md-primary-container);
    transition: opacity 0.2s;
}
.md3-btn-text:hover {
    opacity: 0.9;
    color: var(--md-primary);
    text-decoration: none;
}
.lp-update-note {
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--md-outline-variant);
    background: var(--md-surface-container);
    color: #374151;
    font-size: 13px;
    line-height: 1.65;
}
.lp-update-note a { color: var(--md-primary); text-decoration: none; font-weight: 600; }
.lp-update-note a:hover { text-decoration: underline; }
.lp-update-note.is-ok { border-color: rgba(0,97,164,.25); }
.lp-update-note.is-warn { border-color: rgba(245,158,11,.35); }
.lp-update-note.is-err { border-color: rgba(239,68,68,.35); }
.lp-update-note .lp-update-title { font-weight: 700; margin-bottom: 4px; }
.md3-btn-text.is-disabled { opacity: .55; pointer-events: none; }
.md3-chip {
    display: inline-flex;
    align-items: center;
    height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: var(--md-surface-container);
    border: 1px solid var(--md-outline-variant);
    color: #374151;
    font-size: 12px;
    gap: 6px;
}
.md3-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.md3-table th {
    text-align: left;
    padding: 12px 16px;
    color: #555;
    background-color: var(--md-surface-variant);
    font-weight: 600;
    border-radius: 4px;
}
.md3-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    color: #333;
    
}
.md3-table tr:last-child td {
    border-bottom: none;
}
.field-tag {
    display: inline-block;
    padding: 2px 8px;
    background: #f0f0f0;
    border-radius: 4px;
    font-family: monospace;
    color: #d63384;
}
/* 覆盖 Typecho 原生表单样式 */
.typecho-option-submit button {
    background-color: var(--md-primary) !important;
    border-radius: 20px !important;
    padding: 0 24px !important; 
    height: 40px !important;
}
textarea, input[type="text"], select {
    border: 1px solid var(--md-outline-variant);
    border-radius: 12px;
    padding: 10px 12px;
    height:auto;
    transition: box-shadow .15s, border-color .15s;
    background: #fff;
}
textarea:focus, input[type="text"]:focus, select:focus {
    border-color: rgba(0, 97, 164, .45);
    box-shadow: 0 0 0 3px rgba(0, 97, 164, 0.18);
    outline: none;
}
.typecho-option {
    border-bottom: 1px dashed rgba(0,0,0,.06);
}
.typecho-option:last-child { border-bottom: 0; }
.typecho-option label {
    font-weight: 600;
    color: #1f2937;
}
.description {
    color: #6b7280;
}
    </style>
'
. <<<LINKS_PLUS_UPDATE_JS
<script>
(function(){
    var REPO = "lhl77/Typecho-Plugin-LinksPlus";
    // 当前版本（按 tag 口径对比）
    var CURRENT = "v1.3.1";

    function normalizeTag(tag){
        tag = (tag || "").toString().trim();
        if(!tag) return "";
        return tag.replace(/^refs\/tags\//, "");
    }

    function tagToVersion(tag){
        tag = normalizeTag(tag);
        return tag.replace(/^[vV]/, "");
    }

    function cmp(a, b){
        a = (a || "").toString();
        b = (b || "").toString();
        var as = a.split('.');
        var bs = b.split('.');
        var n = Math.max(as.length, bs.length);
        for(var i=0;i<n;i++){
            var ai = as[i] || "0";
            var bi = bs[i] || "0";
            var an = /^\d+$/.test(ai) ? parseInt(ai, 10) : null;
            var bn = /^\d+$/.test(bi) ? parseInt(bi, 10) : null;
            if(an !== null && bn !== null){
                if(an > bn) return 1;
                if(an < bn) return -1;
            } else {
                if(ai > bi) return 1;
                if(ai < bi) return -1;
            }
        }
        return 0;
    }

    function fetchJson(url, cb){
        var xhr = new XMLHttpRequest();
        xhr.open("GET", url, true);
        xhr.setRequestHeader("Accept", "application/vnd.github+json");
        xhr.onreadystatechange = function(){
            if(xhr.readyState !== 4) return;
            if(xhr.status >= 200 && xhr.status < 300){
                try { cb(null, JSON.parse(xhr.responseText)); } catch(e){ cb(e); }
            } else {
                cb(new Error("HTTP " + xhr.status));
            }
        };
        xhr.send(null);
    }

    function setBtnText(btn, text){
        if(btn){ btn.textContent = text; }
    }

    function setBusy(btn, busy){
        if(!btn) return;
        busy = !!busy;
        btn.setAttribute('aria-disabled', busy ? 'true' : 'false');
        if(busy){
            btn.classList.add('is-disabled');
            btn.dataset.busy = '1';
        } else {
            btn.classList.remove('is-disabled');
            btn.dataset.busy = '';
        }
    }

    function renderNote(host, type, title, html){
        if(!host) return;
        var cls = 'lp-update-note';
        if(type === 'ok') cls += ' is-ok';
        if(type === 'warn') cls += ' is-warn';
        if(type === 'err') cls += ' is-err';
        host.innerHTML = '<div class="' + cls + '">' +
            '<div class="lp-update-title">' + title + '</div>' +
            '<div class="lp-update-body">' + html + '</div>' +
            '</div>';
    }

    document.addEventListener("DOMContentLoaded", function(){
        var btn = document.getElementById("links-plus-check-update");
        if(!btn) return;

        var card = btn.closest ? btn.closest('.md3-card') : null;
        var out = card ? card.querySelector('.lp-update-out') : null;

        btn.addEventListener("click", function(e){
            e.preventDefault();

            if(btn.dataset && btn.dataset.busy === '1') return;
            setBusy(btn, true);

            var api = "https://api.github.com/repos/" + REPO + "/tags?per_page=100";
            var oldText = btn.textContent;
            setBtnText(btn, "检查中...");

            renderNote(out, 'ok', '检查更新', '正在查询 GitHub tags…');

            fetchJson(api, function(err, tags){
                setBtnText(btn, oldText || "检查更新");
                setBusy(btn, false);

                if(err || !Array.isArray(tags)){
                    renderNote(
                        out,
                        'err',
                        '检查失败',
                        '原因：' + (err ? err.message : '响应异常') + '<br>' +
                        '说明：此方案直接从浏览器访问 GitHub API，可能会受网络或 CORS 限制。'
                    );
                    return;
                }

                var latestTag = "";
                var latestVer = "";
                for(var i=0;i<tags.length;i++){
                    var name = tags[i] && tags[i].name ? tags[i].name : "";
                    var tag = normalizeTag(name);
                    var ver = tagToVersion(tag);
                    if(!ver) continue;
                    if(!latestVer || cmp(ver, latestVer) > 0){
                        latestVer = ver;
                        latestTag = tag;
                    }
                }

                if(!latestTag){
                    renderNote(out, 'err', '检查失败', '未发现可用的版本 tag。');
                    return;
                }

                var curVer = tagToVersion(CURRENT);
                var hasUpdate = cmp(latestVer, curVer) > 0;
                var url = "https://github.com/" + REPO + "/releases/tag/" + encodeURIComponent(latestTag);

                if(hasUpdate){
                    renderNote(
                        out,
                        'warn',
                        '发现新版本：' + latestTag,
                        '当前版本：<code>' + CURRENT + '</code><br>' +
                        '最新版本：<code>' + latestTag + '</code><br>' +
                        '<a href="' + url + '" target="_blank" rel="noopener">打开 GitHub 查看</a>'
                    );
                } else {
                    renderNote(
                        out,
                        'ok',
                        '已是最新版本',
                        '当前版本：<code>' + CURRENT + '</code><br>' +
                        '最新版本：<code>' + latestTag + '</code>'
                    );
                }
            });
        });
    });
})();
</script>
LINKS_PLUS_UPDATE_JS
. '



<div class="md3-wrap">

<div class="md3-card">
    <div class="md3-title">友情链接插件 (Links Plus)</div>
    <div class="md3-body">
        <p>欢迎使用 Links Plus 增强版。您可以在“管理”菜单下找到“友情链接”进行日常操作。</p>
        <p>本插件支持多种输出模式（文字、图片、图文混合），并支持自定义字段扩展。</p>
    </div>
    <div class="lp-update-out"></div>
    <div class="md3-header-actions">
        <a href="' . Typecho_Common::url('extending.php?panel=Links%2Fmanage-links.php', Helper::options()->adminUrl) . '" class="md3-btn-text">管理友链</a>
    <a href="https://github.com/lhl77/Typecho-Plugin-LinksPlus" target="_blank" class="md3-btn-text">GitHub</a>
    <a id="links-plus-check-update" href="#" class="md3-btn-text">检查更新</a>
    <a href="https://blog.lhl.one/artical/902.html" target="_blank" class="md3-btn-text">帮助文档</a>
    <a href="https://github.com/lhl77/Typecho-Plugin-LinksPlus/issues" target="_blank" class="md3-btn-text">反馈</a>
    </div>
</div>

<div class="md3-card">
    <div class="md3-title">模式字符串变量说明</div>
    <div style="overflow-x: auto;">
        <table class="md3-table">
            <thead>
                <tr>
                    <th style="border-radius:5px 0px 0px 5px;" width="30%">变量占位符</th>
                    <th style="border-radius:0px 5px 5px 0px">说明</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><span class="field-tag">{url}</span></td><td>友链的 URL 地址</td></tr>
                <tr><td><span class="field-tag">{name}</span></td><td>友链显示的名称</td></tr>
                <tr><td><span class="field-tag">{description}</span></td><td>友链的描述</td></tr>
                <tr><td><span class="field-tag">{image}</span></td><td>图片地址 (Logo/头像)</td></tr>
                <tr><td><span class="field-tag">{size}</span></td><td>在调用中设置的图片尺寸值 (数字)</td></tr>
                <tr><td><span class="field-tag">{sort}</span></td><td>分类名称</td></tr>
                <tr><td><span class="field-tag">{user}</span></td><td>自定义扩展数据</td></tr>
                <tr><td><span class="field-tag">{lid}</span></td><td>数据库内的 ID 编号</td></tr>
            </tbody>
        </table>
    </div>
</div>



</div>
';
        // 模板选择（文件模板 / 保留高级自定义 textarea 作为兼容）
        $templates = self::listTemplates();
        $tplOptions = array(
            '' => _t('（不使用模板，沿用下方自定义规则/旧配置）'),
        );
        if (!empty($templates)) {
            foreach ($templates as $name => $manifest) {
                $title = isset($manifest['title']) ? (string)$manifest['title'] : $name;
                $tplOptions[$name] = $title;
            }
        }

        $selectedText = new Typecho_Widget_Helper_Form_Element_Select(
            'template_text',
            $tplOptions,
            'default-text',
            _t('SHOW_TEXT 使用的模板'),
            _t('选择一个文件模板（templates 目录）。选择后，前台调用 SHOW_TEXT 会优先使用该模板渲染。')
        );
        $form->addInput($selectedText);

        $selectedImg = new Typecho_Widget_Helper_Form_Element_Select(
            'template_img',
            $tplOptions,
            'default-img',
            _t('SHOW_IMG 使用的模板'),
            _t('选择一个文件模板（templates 目录）。选择后，前台调用 SHOW_IMG 会优先使用该模板渲染。')
        );
        $form->addInput($selectedImg);

        $selectedMix = new Typecho_Widget_Helper_Form_Element_Select(
            'template_mix',
            $tplOptions,
            'default-mix',
            _t('SHOW_MIX 使用的模板'),
            _t('选择一个文件模板（templates 目录）。选择后，前台调用 SHOW_MIX 会优先使用该模板渲染。')
        );
        $form->addInput($selectedMix);

        $advHelp = new Typecho_Widget_Helper_Layout('div', array('class' => 'md3-card'));
        $advHelp->html(
            '<div class="md3-title">高级：自定义源码规则（兼容旧版本）</div>' .
            '<div class="md3-body">' .
            '<p>当你不想用模板，或需要更细粒度的自定义时，再使用下面三段规则（旧版配置项）。</p>' .
            '</div>'
        );
        $form->addItem($advHelp);

        $pattern_text = new Typecho_Widget_Helper_Form_Element_Textarea(
            'pattern_text',
            null,
            '<li><a href="{url}" title="{title}" target="_blank" rel="noopener">{name}</a></li>',
            _t('SHOW_TEXT 模式源码规则（高级）'),
            _t('当未选择模板时生效。使用 SHOW_TEXT(仅文字) 模式输出时的源码，可按上表规则替换其中字段')
        );
        $form->addInput($pattern_text);
        $pattern_img = new Typecho_Widget_Helper_Form_Element_Textarea(
            'pattern_img',
            null,
            '<li><a href="{url}" title="{title}" target="_blank" rel="noopener"><img src="{image}" alt="{name}" width="{size}" height="{size}" /></a></li>',
            _t('SHOW_IMG 模式源码规则（高级）'),
            _t('当未选择模板时生效。使用 SHOW_IMG(仅图片) 模式输出时的源码，可按上表规则替换其中字段')
        );
        $form->addInput($pattern_img);
        $pattern_mix = new Typecho_Widget_Helper_Form_Element_Textarea(
            'pattern_mix',
            null,
            '<li><a href="{url}" title="{title}" target="_blank" rel="noopener"><img src="{image}" alt="{name}" width="{size}" height="{size}" /><span>{name}</span></a></li>',
            _t('SHOW_MIX 模式源码规则（高级）'),
            _t('当未选择模板时生效。使用 SHOW_MIX(图文混合) 模式输出时的源码，可按上表规则替换其中字段')
        );
        $form->addInput($pattern_mix);
        $dsize = new Typecho_Widget_Helper_Form_Element_Text(
            'dsize',
            NULL,
            '32',
            _t('默认输出图片尺寸'),
            _t('调用时如果未指定尺寸参数默认输出的图片大小(单位px不用填写)')
        );
        $dsize->input->setAttribute('class', 'w-10');
        $form->addInput($dsize->addRule('isInteger', _t('请填写整数数字')));
        
        

        $temHelp = new Typecho_Widget_Helper_Layout('div', array('class' => 'md3-card'));
        $temHelp->html(
            '<div class="md3-title">正文重写</div>
    <div class="md3-body">
        <p>当主题没有通过 <code>$this->content()</code> 输出正文，导致 <code>&lt;links&gt;...&lt;/links&gt;</code> 不解析时，可用“正文重写工具”把正文中的占位符替换为友链 HTML。</p>
        <p>固定占位符：<span class="md3-chip" style="font-weight:bold;">' . self::REWRITE_PLACEHOLDER . '</span></p>
    <span class="md3-chip">建议</span>
        <span style="margin-left:8px">优先使用文件模板（<code>templates/</code>）来管理输出结构；旧版“源码规则”保留兼容。</span><br><br>
    <a id="links-plus-get-templates" href="' . Helper::security()->getIndex('/action/links-edit?do=update_templates') . '" class="md3-btn-text">获取最新主题</a>
    <a href="https://blog.lhl.one/artical/902.html#主题" target="_blank" class="md3-btn-text">查看全部主题/开发文档</a>
    
    <div class="lp-update-out" style="margin-top:12px"></div>
        </div>'
        );
        $form->addItem($temHelp);

        // 前端脚本：获取最新主题按钮行为 — 调用后由服务器端执行下载并覆盖 templates（会备份原有 templates）
                $script = <<<'SCRIPT'
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            var btn = document.getElementById('links-plus-get-templates');
            if(!btn) return;
            btn.addEventListener('click', function(e){
                e.preventDefault();
                if(!confirm('将从 GitHub 下载并覆盖本插件的 templates 目录（会先备份原有 templates），确定继续？')) return;
                var host = btn.closest ? btn.closest('.md3-card') : null;
                var out = host ? host.querySelector('.lp-update-out') : null;
                if(out){ out.innerHTML = '<div class="lp-update-note is-working"><div class="lp-update-title">更新中</div><div class="lp-update-body">正在从 GitHub 下载并更新模板，页面将跳转，请稍候…</div></div>'; }
                try{ btn.setAttribute('aria-disabled','true'); btn.classList.add('is-disabled'); }catch(e){}
                // 导航到 action 链接，服务器端处理下载/解压/覆盖逻辑
                window.location.href = btn.getAttribute('href');
            }, false);
        });
        </script>
        SCRIPT;
                echo $script;


        /**
         * 按 cid 重写正文输出（绕过主题不走 contentEx 的情况）
         */
    // 说明已在上方 intro card 给出，这里不再重复插入大段说明卡片，避免页面过长。
        
        $rewriteCid = new Typecho_Widget_Helper_Form_Element_Text(
            'rewrite_cids',
            null,
            '',
            _t('需要重写的 cid（可多个）'),
            _t('填写文章/页面 cid，多个用英文逗号分隔，例如：12,34,56')
        );
        $rewriteCid->input->setAttribute('class', 'w-50');
        $form->addInput($rewriteCid);
        
        $rewriteModeOptions = array(
        );
        if (!empty($templates)) {
            foreach ($templates as $name => $manifest) {
                $title = isset($manifest['title']) ? (string)$manifest['title'] : $name;
                $rewriteModeOptions['TPL:' . $name] = $title ;
            }
        }
        $rewritePattern = new Typecho_Widget_Helper_Form_Element_Select(
            'rewrite_pattern',
            $rewriteModeOptions,
            'SHOW_TEXT',
            _t('重写输出主题'),
            _t('把占位符替换成哪种模式输出,也可以直接选择某个文件模板。')
        );
        $form->addInput($rewritePattern);
        
        $rewriteNum = new Typecho_Widget_Helper_Form_Element_Text(
            'rewrite_num',
            null,
            '0',
            _t('重写输出数量'),
            _t('0 表示全部')
        );
        $rewriteNum->input->setAttribute('class', 'w-10');
        $form->addInput($rewriteNum->addRule('isInteger', _t('请填写整数数字')));

        // 开发中，小子别看了，一起开发提PR吧
        // 是否启用短代码 [links_plus]
        // $enableShortcode = new Typecho_Widget_Helper_Form_Element_Checkbox(
        //     'enable_shortcodes',
        //     array('links_plus' => _t('启用短代码 [links_plus]（前端调用，免重写）')),
        //     array(),
        //     _t('短代码支持'),
        //     _t('开启后，文章正文中使用 [links_plus] 将被动态替换为友链 HTML（不写回正文）。')
        // );
        // $form->addInput($enableShortcode);
        
        $rewriteSort = new Typecho_Widget_Helper_Form_Element_Text(
            'rewrite_sort',
            null,
            '',
            _t('重写分类（可选）'),
            _t('只输出指定分类 sort；留空为全部')
        );
        $rewriteSort->input->setAttribute('class', 'w-20');
        $form->addInput($rewriteSort);
        
        $rewriteSize = new Typecho_Widget_Helper_Form_Element_Text(
            'rewrite_size',
            null,
            '0',
            _t('重写图片尺寸（可选）'),
            _t('0 表示使用插件默认尺寸')
        );
        $rewriteSize->input->setAttribute('class', 'w-10');
        $form->addInput($rewriteSize->addRule('isInteger', _t('请填写整数数字')));

        $rewriteWrapBang = new Typecho_Widget_Helper_Form_Element_Radio(
            'rewrite_wrap_bang',
            array(
                '0' => _t('不包裹'),
                '1' => _t('使用 !!! !!! 包裹（部分主题需要）'),
            ),
            '0',
            _t('重写输出 HTML'),
            _t('有些主题/渲染器不支持直接输出 HTML，需要用 “!!!” 包裹整段 HTML 才会被当作原始 HTML 渲染。')
        );
        $form->addInput($rewriteWrapBang);
        
        // //重写按钮（GET，走 Action，带 CSRF），这里是测试的时候用的
        // $sec = Helper::security();
        // $rewriteUrl = $sec->getIndex('/action/links-edit?do=rewrite');
        // $rewriteBtn = new Typecho_Widget_Helper_Layout('p', array('class' => 'typecho-option'));
        // $rewriteBtn->html(
        //     '<a class="btn primary" href="' . $rewriteUrl . '" ' .
        //     'onclick="return confirm(\'确认要对配置的 cid 执行重写吗？该操作会直接修改文章/页面正文内容。\');">' .
        //     '执行重写</a>'
        // );
        // $form->addItem($rewriteBtn);
    }
    
    /**
     * 生成用于“重写正文”的 HTML 字符串
     */
    public static function buildRewriteHtml()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $settings = $options->plugin('Links');
        $pattern = isset($settings->rewrite_pattern) && $settings->rewrite_pattern ? $settings->rewrite_pattern : 'SHOW_TEXT';
        $num = isset($settings->rewrite_num) ? (int)$settings->rewrite_num : 0;
        $sort = isset($settings->rewrite_sort) && $settings->rewrite_sort !== '' ? (string)$settings->rewrite_sort : null;
        // size=0 表示使用插件默认尺寸（dsize）
        $size = isset($settings->rewrite_size) ? (int)$settings->rewrite_size : 0;
        if ($size <= 0) {
            $size = (int)$settings->dsize;
        }

        // 如果重写模式选择了文件模板（TPL:xxx），则把模板的 CSS/JS 一并内联写入正文，
        // 以适配“主题不加载 head 注入 / 仅渲染正文”的场景。
        $assetCss = '';
        $assetJs = '';
        if (is_string($pattern) && stripos($pattern, 'TPL:') === 0) {
            $tplName = trim(substr($pattern, 4));
            $templates = self::listTemplates();
            if ($tplName !== '' && isset($templates[$tplName])) {
                $manifest = $templates[$tplName];
                $inject = isset($manifest['inject']) && is_array($manifest['inject']) ? $manifest['inject'] : array();
                if (!empty($inject['css'])) {
                    $css = self::readTemplateFile($tplName, 'style.css');
                    if ($css && trim($css) !== '') {
                        $assetCss = "<style>\n" . $css . "\n</style>\n";
                    }
                }
                if (!empty($inject['js'])) {
                    $js = self::readTemplateFile($tplName, 'script.js');
                    if ($js && trim($js) !== '') {
                        $assetJs = "<script>\n" . $js . "\n</script>\n";
                    }
                }
            }
        }

        $html = Links_Plugin::output_str('', array($pattern, $num, $sort, $size, 'HTML'));
        // 资产写在正文前，避免部分主题/解析器只截取首段导致样式丢失
        if ($assetCss !== '' || $assetJs !== '') {
            $html = $assetCss . $assetJs . (string)$html;
        }
        $wrap = isset($settings->rewrite_wrap_bang) ? (string)$settings->rewrite_wrap_bang : '0';
        if ($wrap === '1') {
            // Trim 只去两端空白，避免破坏内部格式
            $html = trim((string)$html);
            if ($html !== '') {
                $html = "!!!\n" . $html . "\n!!!";
            }
        }

        return $html;
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function linksInstall()
    {
        $installDb = Typecho_Db::get();
        $type = explode('_', $installDb->getAdapterName());
        $type = array_pop($type);
        $prefix = $installDb->getPrefix();
        $scripts = file_get_contents('usr/plugins/Links/' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $installDb->query($script, Typecho_Db::WRITE);
                }
            }
            return _t('建立友情链接数据表，插件启用成功');
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && (1050 == $code || '42S01' == $code)) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))
            ) {
                try {
                    $script = 'SELECT `lid`, `name`, `url`, `sort`, `email`, `image`, `description`, `user`, `state`, `order` from `' . $prefix . 'links`';
                    $installDb->query($script, Typecho_Db::READ);
                    return _t('检测到友情链接数据表，友情链接插件启用成功');
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    if (('Mysql' == $type && (1054 == $code || '42S22' == $code)) ||
                        ('SQLite' == $type && ('HY000' == $code || 1 == $code))
                    ) {
                        return Links_Plugin::linksUpdate($installDb, $type, $prefix);
                    }
                    throw new Typecho_Plugin_Exception(_t('数据表检测失败，友情链接插件启用失败。错误号：') . $code);
                }
            } else {
                throw new Typecho_Plugin_Exception(_t('数据表建立失败，友情链接插件启用失败。错误号：') . $code);
            }
        }
    }

    public static function linksUpdate($installDb, $type, $prefix)
    {
        $scripts = file_get_contents('usr/plugins/Links/Update_' . $type . '.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try {
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $installDb->query($script, Typecho_Db::WRITE);
                }
            }
            return _t('检测到旧版本友情链接数据表，升级成功');
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if (('Mysql' == $type && (1060 == $code || '42S21' == $code))) {
                return _t('友情链接数据表已经存在，插件启用成功');
            }
            throw new Typecho_Plugin_Exception(_t('友情链接插件启用失败。错误号：') . $code);
        }
    }

    public static function form($action = null)
    {
        /** 构建表格 */
        $options = Typecho_Widget::widget('Widget_Options');
        $form = new Typecho_Widget_Helper_Form(
            Helper::security()->getIndex('/action/links-edit'),
            Typecho_Widget_Helper_Form::POST_METHOD
        );

        /** 友链名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('name', null, null, _t('友链名称*'));
        $form->addInput($name);

        /** 友链地址 */
        $url = new Typecho_Widget_Helper_Form_Element_Text('url', null, "http://", _t('友链地址*'));
        $form->addInput($url);

        /** 友链分类 */
        $sort = new Typecho_Widget_Helper_Form_Element_Text('sort', null, null, _t('友链分类'), _t('建议以英文字母开头，只包含字母与数字'));
        $form->addInput($sort);

        /** 友链邮箱 */
        $email = new Typecho_Widget_Helper_Form_Element_Text('email', null, null, _t('友链邮箱'), _t('填写友链邮箱'));
        $form->addInput($email);

        /** 友链图片 */
        $image = new Typecho_Widget_Helper_Form_Element_Text('image', null, null, _t('友链图片'),  _t('需要以http://或https://开头，留空表示没有友链图片'));
        $form->addInput($image);

        /** 友链描述 */
        $description =  new Typecho_Widget_Helper_Form_Element_Textarea('description', null, null, _t('友链描述'));
        $form->addInput($description);

        /** 自定义数据 */
        $user = new Typecho_Widget_Helper_Form_Element_Text('user', null, null, _t('自定义数据'), _t('该项用于用户自定义数据扩展'));
        $form->addInput($user);

        /** 友链状态 */
        $list = array('0' => '禁用', '1' => '启用');
        $state = new Typecho_Widget_Helper_Form_Element_Radio('state', $list, '1', '友链状态');
        $form->addInput($state);

        /** 友链动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 友链主键 */
        $lid = new Typecho_Widget_Helper_Form_Element_Hidden('lid');
        $form->addInput($lid);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        $request = Typecho_Request::getInstance();

        if (isset($request->lid) && 'insert' != $action) {
            /** 更新模式 */
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            $link = $db->fetchRow($db->select()->from($prefix . 'links')->where('lid = ?', $request->lid));
            if (!$link) {
                throw new Typecho_Widget_Exception(_t('友链不存在'), 404);
            }

            $name->value($link['name']);
            $url->value($link['url']);
            $sort->value($link['sort']);
            $email->value($link['email']);
            $image->value($link['image']);
            $description->value($link['description']);
            $user->value($link['user']);
            $state->value($link['state']);
            $do->value('update');
            $lid->value($link['lid']);
            $submit->value(_t('编辑友链'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('增加友链'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('必须填写友链名称'));
            $url->addRule('required', _t('必须填写友链地址'));
            $url->addRule('url', _t('不是一个合法的链接地址'));
            $email->addRule('email', _t('不是一个合法的邮箱地址'));
            $image->addRule('url', _t('不是一个合法的图片地址'));
            $name->addRule('maxLength', _t('友链名称最多包含50个字符'), 50);
            $url->addRule('maxLength', _t('友链地址最多包含200个字符'), 200);
            $sort->addRule('maxLength', _t('友链分类最多包含50个字符'), 50);
            $email->addRule('maxLength', _t('友链邮箱最多包含50个字符'), 50);
            $image->addRule('maxLength', _t('友链图片最多包含200个字符'), 200);
            $description->addRule('maxLength', _t('友链描述最多包含200个字符'), 200);
            $user->addRule('maxLength', _t('自定义数据最多包含200个字符'), 200);
        }
        if ('update' == $action) {
            $lid->addRule('required', _t('友链主键不存在'));
            $lid->addRule(array(new Links_Plugin, 'LinkExists'), _t('友链不存在'));
        }
        return $form;
    }

    public static function LinkExists($lid)
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $link = $db->fetchRow($db->select()->from($prefix . 'links')->where('lid = ?', $lid)->limit(1));
        return $link ? true : false;
    }

    /**
     * 控制输出格式
     */
    public static function output_str($widget, array $params)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $settings = $options->plugin('Links');
        if (!isset($options->plugins['activated']['Links'])) {
            return _t('友情链接插件未激活');
        }
        //验证默认参数
        $pattern = !empty($params[0]) && is_string($params[0]) ? $params[0] : 'SHOW_TEXT';
        $links_num = !empty($params[1]) && is_numeric($params[1]) ? $params[1] : 0;
        $sort = !empty($params[2]) && is_string($params[2]) ? $params[2] : null;
        $size = !empty($params[3]) && is_numeric($params[3]) ? $params[3] : $settings->dsize;
        $mode = isset($params[4]) ? $params[4] : 'FUNC';

        // 文件模板调用：TPL:template-name
        $tplManifest = null;
        $tplName = null;
        if (is_string($pattern) && stripos($pattern, 'TPL:') === 0) {
            $tplName = trim(substr($pattern, 4));
            $templates = self::listTemplates();
            if ($tplName !== '' && isset($templates[$tplName])) {
                $tplManifest = $templates[$tplName];
                $tplHtml = self::readTemplateFile($tplName, 'template.html');
                if ($tplHtml !== null && trim($tplHtml) !== '') {
                    $pattern = $tplHtml . "\n";
                }
            }
        }

        // 兼容旧模式字符串（优先模板选择，其次旧 textarea 规则）
        if ($pattern == 'SHOW_TEXT') {
            $tpl = isset($settings->template_text) ? trim((string)$settings->template_text) : '';
            if ($tpl !== '') {
                $tplName = $tpl;
                $templates = self::listTemplates();
                if (isset($templates[$tplName])) {
                    $tplManifest = $templates[$tplName];
                    $tplHtml = self::readTemplateFile($tplName, 'template.html');
                    if ($tplHtml !== null && trim($tplHtml) !== '') {
                        $pattern = $tplHtml . "\n";
                    } else {
                        $pattern = $settings->pattern_text . "\n";
                    }
                } else {
                    $pattern = $settings->pattern_text . "\n";
                }
            } else {
                $pattern = $settings->pattern_text . "\n";
            }
        } elseif ($pattern == 'SHOW_IMG') {
            $tpl = isset($settings->template_img) ? trim((string)$settings->template_img) : '';
            if ($tpl !== '') {
                $tplName = $tpl;
                $templates = self::listTemplates();
                if (isset($templates[$tplName])) {
                    $tplManifest = $templates[$tplName];
                    $tplHtml = self::readTemplateFile($tplName, 'template.html');
                    if ($tplHtml !== null && trim($tplHtml) !== '') {
                        $pattern = $tplHtml . "\n";
                    } else {
                        $pattern = $settings->pattern_img . "\n";
                    }
                } else {
                    $pattern = $settings->pattern_img . "\n";
                }
            } else {
                $pattern = $settings->pattern_img . "\n";
            }
        } elseif ($pattern == 'SHOW_MIX') {
            $tpl = isset($settings->template_mix) ? trim((string)$settings->template_mix) : '';
            if ($tpl !== '') {
                $tplName = $tpl;
                $templates = self::listTemplates();
                if (isset($templates[$tplName])) {
                    $tplManifest = $templates[$tplName];
                    $tplHtml = self::readTemplateFile($tplName, 'template.html');
                    if ($tplHtml !== null && trim($tplHtml) !== '') {
                        $pattern = $tplHtml . "\n";
                    } else {
                        $pattern = $settings->pattern_mix . "\n";
                    }
                } else {
                    $pattern = $settings->pattern_mix . "\n";
                }
            } else {
                $pattern = $settings->pattern_mix . "\n";
            }
        }
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $nopic_url = Typecho_Common::url('usr/plugins/Links/nopic.png', $options->siteUrl);
        $sql = $db->select()->from($prefix . 'links');
        if ($sort) {
            $sql = $sql->where('sort=?', $sort);
        }
        $sql = $sql->order($prefix . 'links.order', Typecho_Db::SORT_ASC);
        $links_num = intval($links_num);
        if ($links_num > 0) {
            $sql = $sql->limit($links_num);
        }
        $links = $db->fetchAll($sql);
        $str = "";
        foreach ($links as $link) {
            if ($link['image'] == null) {
                $link['image'] = $nopic_url;
                if ($link['email'] != null) {
                    $link['image'] = 'https://gravatar.helingqi.com/wavatar/' . md5($link['email']) . '?s=' . $size . '&d=mm';
                }
            }
            if ($link['state'] == 1) {
                $str .= str_replace(
                    array('{lid}', '{name}', '{url}', '{sort}', '{title}', '{description}', '{image}', '{user}', '{size}'),
                    array($link['lid'], $link['name'], $link['url'], $link['sort'], $link['description'], $link['description'], $link['image'], $link['user'], $size),
                    $pattern
                );
            }
        }

        // 注入模板资源：
        // - pattern = TPL:xxx
        // - 或 SHOW_* 映射到 template_text/img/mix 时同样需要注入
        if (!empty($tplName) && !empty($tplManifest) && is_array($tplManifest)) {
            self::injectTemplateAssetsOnce($tplName, $tplManifest);
        }

        if ($mode == 'HTML') {
            return $str;
        } else {
            echo $str;
        }
    }

    //输出
    public static function output($pattern = 'SHOW_TEXT', $links_num = 0, $sort = null, $size = 32, $mode = '')
    {
        return Links_Plugin::output_str('', array($pattern, $links_num, $sort, $size, $mode));
    }

    /**
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback($matches)
    {
    // 兼容 <links></links> 这种空参数用法：
    // - 数量/分类/尺寸为空时使用默认值
    // - 标签内容为空时默认使用 SHOW_TEXT
    $linksNum = (isset($matches[1]) && $matches[1] !== '') ? $matches[1] : 0;
    $sort = (isset($matches[2]) && $matches[2] !== '') ? $matches[2] : null;
    $size = (isset($matches[3]) && $matches[3] !== '') ? $matches[3] : 0;
    $pattern = (isset($matches[4]) && trim($matches[4]) !== '') ? trim($matches[4]) : 'SHOW_TEXT';

    return Links_Plugin::output_str('', array($pattern, $linksNum, $sort, $size, 'HTML'));
    }

    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;

        // Shortcode: [links_plus] 支持（仅当在插件配置中启用）
        try {
            $options = Typecho_Widget::widget('Widget_Options');
            $settings = $options->plugin('Links');
            $shortcodes = isset($settings->enable_shortcodes) ? $settings->enable_shortcodes : array();
        } catch (Exception $e) {
            $shortcodes = array();
        }

        // 开发中，短代码
        if (is_array($shortcodes) && in_array('links_plus', $shortcodes)) {
            // 简单匹配 [links_plus] 或带参数形式 [links_plus num=5 sort=friends size=48 template=SHOW_IMG]
            $text = preg_replace_callback(
                '/\[links_plus(?:\s+([^\]]+))?\]/i',
                function ($m) {
                    $args = array();
                    if (!empty($m[1])) {
                        // 解析 key=value 或 单纯数字的简写（作为数量）
                        $str = trim($m[1]);
                        // 支持 num=5 sort=friends size=48 template=SHOW_IMG
                        preg_match_all('/(\w+)\s*=\s*"([^"]*)"|(\w+)\s*=\s*\'([^\']*)\'|(\w+)\s*=\s*([^\s"]+)/', $str, $ms, PREG_SET_ORDER);
                        foreach ($ms as $row) {
                            foreach ($row as $r) {
                                // noop
                            }
                        }
                        // 另外也支持仅数字形式
                        if (preg_match('/^\d+$/', $str)) {
                            $args['num'] = (int)$str;
                        } else {
                            // 尝试解析常见的 key=value，容错简单实现
                            $parts = preg_split('/\s+/', $str);
                            foreach ($parts as $p) {
                                if (strpos($p, '=') !== false) {
                                    list($k, $v) = explode('=', $p, 2);
                                    $k = trim($k);
                                    $v = trim($v, " \t\n\r\0\x0B\"'");
                                    $args[$k] = $v;
                                }
                            }
                        }
                    }

                    $num = isset($args['num']) ? (int)$args['num'] : 0;
                    $sort = isset($args['sort']) ? $args['sort'] : null;
                    $size = isset($args['size']) ? (int)$args['size'] : 0;
                    $pattern = isset($args['template']) ? $args['template'] : 'SHOW_TEXT';

                    return Links_Plugin::output_str('', array($pattern, $num, $sort, $size, 'HTML'));
                },
                $text
            );
        }

        if ($widget instanceof Widget_Archive || $widget instanceof Widget_Abstract_Comments) {
            // 支持：
            // <links></links>
            // <links 10></links>
            // <links 10 friends></links>
            // <links 10 friends 48>SHOW_IMG</links>
            // 分类允许使用常见 slug（字母/数字/下划线/连字符）
            // 且允许标签的 " > " 前存在空白
            // 更健壮的短标签解析：
            // 1) 允许 <links ...> 带任意 HTML 属性（比如 <links class="x">）
            // 2) 允许参数之间/标签两侧出现任意空白与换行
            // 3) 参数定义：数量(数字) 分类(非 < > 空白) 尺寸(数字)
            //    - 分类允许中文/连字符/下划线等，只要不包含空白与尖括号
            // 4) 标签内容为 pattern（SHOW_TEXT/SHOW_IMG/SHOW_MIX 或自定义模板名）
            $regex = "/<links(?:\\s+[^>]*)?\\s*(\\d*)\\s*([^\\s<>]*)\\s*(\\d*)\\s*>\\s*(.*?)\\s*<\\/links>/is";

            return preg_replace_callback(
                $regex,
                array('Links_Plugin', 'parseCallback'),
                $text ? $text : ''
            );
        } else {
            return $text;
        }
    }
}

