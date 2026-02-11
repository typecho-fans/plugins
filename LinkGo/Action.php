<?php
/**
 * LinkGo 路由 Action
 * 负责处理 /go 路径并渲染跳转页面
 */
use Typecho\Widget\Helper as TypechoHelper;

class LinkGo_Action extends Typecho_Widget
{
    public function index()
    {
        // 把 page/index.php 的逻辑内联到这里，避免重复文件依赖
        // 安全性检查
        if (strlen($_SERVER['REQUEST_URI']) > 255 ||
            strpos($_SERVER['REQUEST_URI'], "eval(") ||
            strpos($_SERVER['REQUEST_URI'], "base64")) {
            @header("HTTP/1.1 414 Request-URI Too Long");
            @header("Status: 414 Request-URI Too Long");
            @header("Connection: Close");
            @exit;
        }

        // 获取 Base64 编码的目标链接：优先从 Typecho 请求对象或路径中提取（URL-safe base64），否则回退到 ?target=
        $encodedTarget = '';

        // 1) 优先尝试 Typecho 的请求对象（Action 中通常可用）
        if (isset($this->request) && method_exists($this->request, 'get')) {
            try {
                $encodedTarget = (string)$this->request->get('target', '');
            } catch (Exception $e) {
                $encodedTarget = '';
            }
        }

        // 2) PATH_INFO
        if (empty($encodedTarget) && !empty($_SERVER['PATH_INFO'])) {
            $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if (isset($parts[0]) && $parts[0] === 'go' && isset($parts[1])) {
                $encodedTarget = $parts[1];
            }
        }

        // 3) REQUEST_URI
        if (empty($encodedTarget) && !empty($_SERVER['REQUEST_URI'])) {
            if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REQUEST_URI'], $m)) {
                $encodedTarget = $m[1];
            }
        }

        // 4) REDIRECT_URL
        if (empty($encodedTarget) && !empty($_SERVER['REDIRECT_URL'])) {
            if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REDIRECT_URL'], $m)) {
                $encodedTarget = $m[1];
            }
        }

        // 5) 最后回退到 GET
        if (empty($encodedTarget)) {
            $encodedTarget = $_GET['target'] ?? '';
        }

        // 调试日志，便于定位请求是否包含 target
        if (function_exists('error_log')) {
            error_log('[LinkGo] debug: GET=' . json_encode($_GET, JSON_UNESCAPED_UNICODE) . ' PATH_INFO=' . ($_SERVER['PATH_INFO'] ?? '') . ' REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '') . ' encoded=' . $encodedTarget);
        }

        $target = '';
        if (!empty($encodedTarget)) {
            $b64 = strtr($encodedTarget, '-_', '+/');
            $pad = strlen($b64) % 4;
            if ($pad > 0) {
                $b64 .= str_repeat('=', 4 - $pad);
            }
            $target = base64_decode($b64);
        }

        if (!empty($target) && filter_var($target, FILTER_VALIDATE_URL)) {
            $parsed_url = parse_url($target);
            if (isset($parsed_url['scheme']) && in_array($parsed_url['scheme'], ['http', 'https'])) {
                $url = $target;
                $title = '安全跳转';
            } else {
                $url = 'https://' . $_SERVER['HTTP_HOST'];
                $title = '非法 URL';
            }
        } else {
            $title = '非法 URL';
            $url = 'https://' . $_SERVER['HTTP_HOST'];
        }

        $parsed_url = parse_url($url);
        $display_url = $parsed_url['host'] ?? $url;

        $siteUrl = 'https://' . $_SERVER['HTTP_HOST'];
        $siteTitle = 'LHL\'s Blog';
        $logoUrl = 'https://cdn.sa.net/2025/04/18/KXpf8u5SQYNPkA3.jpg';
        $startYear = 2021;
        $currentYear = date('Y');
        $displayYear = ($currentYear > $startYear) ? ($startYear . ' - ' . $currentYear) : $currentYear;

        // 渲染视图：复用 page/index.php 的模板（直接 include 以便保留样式）
        $template = __DIR__ . '/page/index.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<p>跳转页面模板未找到。</p>';
        }
    }
}
