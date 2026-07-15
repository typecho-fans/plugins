<?php
namespace TypechoPlugin\Spoiler;

use Widget\Options;

// $archiveContent 和 $archiveText 由调用方设置
// $archiveContent: 页面 HTML 内容
// $archiveText: 页面纯文本内容
if (!isset($archiveContent)) $archiveContent = '';
if (!isset($archiveText))   $archiveText   = '';

$htmlSpoilerText = "";


// 检测 warning/danger note 的存在
if (Options::alloc()->plugin('Spoiler')->enableNoteTags == "0") {
    $contentHtml = $archiveContent;

    $dom = new \DOMDocument();

    // 用户文章的HTML可能不规范, 用libxml内部错误处理避免PHP Warning
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$contentHtml.'</div>');
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);

    // 经典的query trick
    $queryWarning = "//*[contains(concat(' ', normalize-space(@class), ' '), ' note ')
                and
                contains(concat(' ', normalize-space(@class), ' '), ' warning ')]";

    $queryDanger = "//*[contains(concat(' ', normalize-space(@class), ' '), ' note ')
                and
                contains(concat(' ', normalize-space(@class), ' '), ' danger ')]";

    $node = $xpath->query($queryDanger)->item(0);

    if (!$node) $node = $xpath->query($queryWarning)->item(0);

    if ($node) {
        // 仅提取纯文本并转义，防止通过 note 标签注入恶意 HTML/JS
        $htmlSpoilerText = htmlspecialchars($node->textContent, ENT_QUOTES, 'UTF-8');
    }
}



// 检测 SPOILER 注释
if (Options::alloc()->plugin('Spoiler')->enableHtmlComments == "0") {
    $pageText = $archiveText;

    if (preg_match('/<!--SPOILER\s*(.*?)\s*-->/s', $pageText, $matches)) {
        $spoilerText = $matches[1];
        $htmlSpoilerText = nl2br(htmlspecialchars($spoilerText));
    }
}



// 如果有了Spoiler，则插入
if ($htmlSpoilerText !== "") {
    echo <<<HTML
<div class="spoiler-overlay">
    <div class="spoiler-message">
{$htmlSpoilerText}
    </div>

    <div class="spoiler-actions">
        <button type="button" class="spoiler-back-btn">返回</button>
        <button type="button" class="spoiler-continue-btn">继续</button>
    </div>
</div>

<script>
    (() => {
        // 在PJAX主题下防止多次加载
        if (window.SpoilerPluginLoaded) return;
        window.SpoilerPluginLoaded = true;

        document.addEventListener("click", function(e) {
            const continueBtn = e.target.closest(".spoiler-continue-btn");
            if (continueBtn) {
                continueBtn.closest(".spoiler-overlay").style.display = "none";
            }
            const backBtn = e.target.closest(".spoiler-back-btn");
            if (backBtn) {
                if (window.history.length <= 1) {
                    window.location.href = "/";
                }
                else {
                    window.history.back();
                }

            }
        })
    })();

</script>

<style>
    /* 遮罩 */
    .spoiler-overlay {
        position: absolute;
        inset: 0;
        /* 等同于 top/right/bottom/left: 0 */
        z-index: 90;

        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(20px);
        color: #fff;

        padding: 48px;
        box-sizing: border-box;
    }

    /* 提示文字 */
    .spoiler-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;

        text-align: center;
        white-space: pre-wrap;
        word-break: break-word;

        font-size: 16px;

        max-width: 80%;
        margin: 24px auto;
    }

    /* 右下角按钮 */
    .spoiler-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .spoiler-actions button {
        min-width: 88px;
        padding: 8px 18px;

        border: none;
        border-radius: 4px;

        cursor: pointer;

        font-size: 14px;
    }

    .spoiler-actions button:first-child {
        background: #7e878f;
        color: #fff;
    }

    .spoiler-actions button:last-child {
        background: #0d6efd;
        color: #fff;
    }
</style>
HTML;
}