<!doctype html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#F6EDFF">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle ?? '', ENT_QUOTES); ?></title>
    <!-- Font Awesome（图标） -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <?php
    // 主题样式表，优先从插件目录的 page/themes/<theme>/style.css 引用
    $themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css';
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
</head>

<body>
    <div class="wrap">
        <div class="md-card" role="group" aria-labelledby="jump-title">
            <div class="md-card__header">
                <div class="logo" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </div>
                <div>
                    <div id="jump-title" class="title"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?> · 安全跳转</div>
                    <div class="subtitle">为您确认目标链接安全性并提示潜在风险</div>
                </div>
            </div>

            <div class="md-card__body">
                <div class="notice">
                    <div class="notice__icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
                    <div class="notice__content">
                        <div class="h">您即将离开本站并访问：</div>
                        <p class="safety-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($url, ENT_QUOTES); ?></p>
                        <p style="margin-top:12px;color:#6B6470">该网页不属于本站页面。我们无法确认该网页是否安全，可能包含未知安全隐患，请注意保护个人信息。</p>
                    </div>
                </div>
            </div>

            <div class="md-card__actions" style="justify-content:center;">
                <a class="btn btn--primary" id="continueBtn" href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" rel="nofollow noopener" aria-label="继续访问">
                    <span class="btn__icon"><i class="fa-solid fa-arrow-right-long"></i></span>
                    <span class="btn__text">继续访问</span>
                </a>
            </div>
            <style>
                /* 覆盖：确保按钮在容器中正确显示和垂直居中 */
                .md-card__actions { display:flex; justify-content:center; align-items:center; }
                .btn { display:inline-flex; align-items:center; gap:10px; padding:12px 18px; border-radius:12px; text-decoration:none; }
                .btn__icon { display:inline-flex; align-items:center; }
                @media (max-width:640px) {
                    .md-card__actions { padding:14px; }
                    .btn { width:100%; justify-content:center; box-sizing:border-box; }
                }
            </style>
    </div>
    <div class="site-footer">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></div>
    </div>

</body>

</html>
