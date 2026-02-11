<?php
$title = isset($title) ? $title : '跳转页';
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$logoUrl = isset($logoUrl) ? $logoUrl : '';
$url = isset($url) ? $url : '';
$display_url = isset($display_url) ? $display_url : $url;
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Zhihu';
$siteUrl = isset($siteUrl) ? $siteUrl : rtrim((isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/');
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
</head>
<body class="lg-zhihu">
    <header class="zh-header">
        <div class="zh-title">安全跳转</div>
    </header>

    <main class="zh-main">
        <section class="zh-card" role="group" aria-labelledby="zh-card-title">
            <h2 id="zh-card-title" class="zh-card__title">即将离开<?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></h2>
            <div class="zh-card__body">
                <p class="zh-note">您即将离开本站，请注意您的账号和财产安全。</p>
                <p class="zh-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($display_url, ENT_QUOTES); ?></p>
            </div>
            <div class="zh-card__actions">
                <a class="zh-btn zh-btn--primary" href="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>" rel="nofollow noopener noreferrer" aria-label="继续访问">继续访问</a>
            </div>
        </section>
    </main>

    <footer class="zh-footer">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></footer>
</body>
</html>
