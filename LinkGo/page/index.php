<?php
// 安全性检查
if (strlen($_SERVER['REQUEST_URI']) > 255 ||
    strpos($_SERVER['REQUEST_URI'], "eval(") ||
    strpos($_SERVER['REQUEST_URI'], "base64")) {
    @header("HTTP/1.1 414 Request-URI Too Long");
    @header("Status: 414 Request-URI Too Long");
    @header("Connection: Close");
    @exit;
}

// 获取 Base64 编码的目标链接：优先从路径 /go/<encoded> 提取（URL-safe base64），否则回退到 ?target=
// 如果是从 Action include 的情况下，Action 可能已经设置了 $encodedTarget，避免覆盖它
if (!isset($encodedTarget)) {
    $encodedTarget = '';
}

// 1) 尝试从 PATH_INFO / REQUEST_URI 中提取形如 /go/<encoded>
if (!empty($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    if (isset($parts[0]) && $parts[0] === 'go' && isset($parts[1])) {
        $encodedTarget = $parts[1];
    }
}

// 另一个常见位置：REQUEST_URI 或 REDIRECT_URL（例如 Nginx/重写）
if (empty($encodedTarget) && !empty($_SERVER['REQUEST_URI'])) {
    // 匹配 /go/xxxx 或 /go/xxxx?...
    if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REQUEST_URI'], $m)) {
        $encodedTarget = $m[1];
    }
}

if (empty($encodedTarget) && !empty($_SERVER['REDIRECT_URL'])) {
    if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REDIRECT_URL'], $m)) {
        $encodedTarget = $m[1];
    }
}

// 2) 回退到旧的 query 参数
if (empty($encodedTarget)) {
    $encodedTarget = $_GET['target'] ?? '';

    // 额外尝试：有些托管/重写规则会导致 PHP 的 $_GET 为空，但 QUERY_STRING 或 REQUEST_URI 中仍包含 target
    if (empty($encodedTarget) && !empty($_SERVER['QUERY_STRING'])) {
        if (preg_match('/(?:^|&)target=([^&]+)/', $_SERVER['QUERY_STRING'], $qm)) {
            $encodedTarget = urldecode($qm[1]);
        }
    }

    if (empty($encodedTarget) && !empty($_SERVER['REQUEST_URI'])) {
        if (preg_match('/[?&]target=([^&]+)/', $_SERVER['REQUEST_URI'], $rm)) {
            $encodedTarget = urldecode($rm[1]);
        }
    }
}

// 验证 encodedTarget 是否看起来像是 URL-safe base64（只包含 A-Za-z0-9-_ 且有合理长度）
if (!empty($encodedTarget) && !preg_match('/^[A-Za-z0-9\-_]{8,}$/', $encodedTarget)) {
    if (function_exists('error_log')) {
        error_log('[LinkGo:index] invalid encodedTarget captured and ignored: ' . $encodedTarget);
    }
    $encodedTarget = '';
}

// 解码目标链接：支持 URL-safe base64（-_, 无 =）与标准 base64
$target = '';
if (!empty($encodedTarget)) {
    // 把 URL-safe base64 恢复为标准 base64
    $b64 = strtr($encodedTarget, '-_', '+/');
    // 补回被移除的 '='
    $pad = strlen($b64) % 4;
    if ($pad > 0) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $target = base64_decode($b64);
}

// 处理目标链接
if (!empty($target) && filter_var($target, FILTER_VALIDATE_URL)) {
    $parsed_url = parse_url($target);
    if (isset($parsed_url['scheme']) && in_array($parsed_url['scheme'], ['http', 'https'])) {
        $url = $target;
        $title = '安全跳转';
    } else {
        $url = 'https://' . $_SERVER['HTTP_HOST'];
        $title = '非法 URL，正在返回首页...';
    }
} else {
    $title = '非法 URL，正在返回首页...';
    $url = 'https://' . $_SERVER['HTTP_HOST'];
}

// 调试信息：记录请求相关字段，便于排查 target 是否被传递
if (function_exists('error_log')) {
    error_log('[LinkGo:index] debug: GET=' . json_encode($_GET, JSON_UNESCAPED_UNICODE) . ' QUERY_STRING=' . ($_SERVER['QUERY_STRING'] ?? '') . ' REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '') . ' encoded=' . ($encodedTarget ?? ''));
}

// 提取目标域名用于显示
$parsed_url = parse_url($url);
$display_url = $parsed_url['host'] ?? $url;

// 动态内容：优先使用外部（例如 Action.php）传入的变量；若不存在则尝试读取 Typecho 插件设置；最后使用默认值
$siteUrl = 'https://' . $_SERVER['HTTP_HOST']; // 站点 URL

// 默认值
$defaultTitle = 'LHL\'s Blog';
$defaultLogo = 'https://cdn.sa.net/2025/04/18/KXpf8u5SQYNPkA3.jpg';
$defaultStartYear = 2021;

// 如果 Action 已经设置了这些变量（例如 LinkGo_Action 会在 include 前设置），优先保留
$siteTitle = (isset($siteTitle) && !empty($siteTitle)) ? $siteTitle : $defaultTitle;
$logoUrl = (isset($logoUrl) && !empty($logoUrl)) ? $logoUrl : $defaultLogo;
$startYear = (isset($startYear) && !empty($startYear)) ? (int)$startYear : $defaultStartYear;

// 尝试从 Typecho 插件配置中读取（仅当模板没有被外部覆盖时，会替换默认值）
if (class_exists('Typecho_Widget')) {
    try {
        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
        if ((!isset($siteTitle) || $siteTitle === $defaultTitle) && !empty($pluginOptions->siteTitle)) {
            $siteTitle = $pluginOptions->siteTitle;
        }
        if ((!isset($logoUrl) || $logoUrl === $defaultLogo) && !empty($pluginOptions->logoUrl)) {
            $logoUrl = $pluginOptions->logoUrl;
        }
        if ((!isset($startYear) || $startYear === $defaultStartYear) && !empty($pluginOptions->startYear)) {
            $startYear = (int)$pluginOptions->startYear;
        }
    } catch (Exception $e) {
        // 忽略无法读取配置的情况（例如在不在 Typecho 环境中直接打开模板时）
    }
}

$currentYear = date('Y'); // 当前年份
$displayYear = ($currentYear > $startYear) ? ($startYear . ' - ' . $currentYear) : $currentYear;
?>
<?php
// 主题名：优先使用由 Action 传入的 $themeName，其次从插件配置读取，最后回退为 'Default'
$themeName = isset($themeName) && !empty($themeName) ? $themeName : 'Default';
try {
    if (class_exists('Typecho_Widget')) {
        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
        if ((!isset($themeName) || $themeName === 'Default') && !empty($pluginOptions->themeName)) {
            $themeName = $pluginOptions->themeName;
        }
    }
} catch (Exception $e) {
    // ignore
}

// 包含主题模板
$themeTemplate = __DIR__ . '/themes/' . $themeName . '/template.php';
if (file_exists($themeTemplate)) {
    include $themeTemplate;
} else {
    // 兜底：如果主题不存在，回退到 Default 模板（已存在）
    $fallback = __DIR__ . '/themes/Default/template.php';
    if (file_exists($fallback)) {
        include $fallback;
    } else {
        echo '<p>跳转页面模板未找到。</p>';
    }
}
