<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 生成图片水印
 *
 * @package Watermark
 * @author DEFE, NHPT
 * @version 1.3.0
 * @dependence 1.2.0-*
 * @link http://defe.me
 */
class Watermark_Plugin implements Typecho_Plugin_Interface
{
    const VERSION = '1.3.0';
    const CACHE_RELATIVE_DIR = 'usr/cache/watermark';
    const UPLOAD_RELATIVE_DIR = 'usr/uploads';

    /**
     * 激活插件
     *
     * @return string
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        if (!function_exists('gd_info')) {
            throw new Typecho_Plugin_Exception(_t('对不起，您的 PHP 环境没有开启 GD 扩展'));
        }

        // Typecho 1.2/1.3 在 Markdown 渲染完成后处理正文。
        Typecho_Plugin::factory('Widget\\Base\\Contents')->contentEx = array(
            'Watermark_Plugin',
            'parseContent'
        );
        // 保留旧版 Typecho 的内容过滤入口。
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array(
            'Watermark_Plugin',
            'parseLegacy'
        );
        Helper::addAction('Watermark', 'Watermark_Action');

        if (!self::ensureCacheDirectory()) {
            return _t('插件已经激活，但缓存目录不可写；请关闭缓存或检查 usr/cache 目录权限');
        }

        return _t('插件已经激活，请完成插件设置');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        Helper::removeAction('Watermark');
    }

    /**
     * 插件配置
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $vmType = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'vm_type',
            array('pic' => _t('图片'), 'text' => _t('文字')),
            array('pic'),
            _t('水印类型')
        );
        $form->addInput($vmType);

        $positions = array(
            _t('随机'),
            _t('顶端左侧'),
            _t('顶端中间'),
            _t('顶端右侧'),
            _t('中部左侧'),
            _t('正中'),
            _t('中部右侧'),
            _t('底部左侧'),
            _t('底部中间'),
            _t('底部右侧')
        );

        $vmPosPic = new Typecho_Widget_Helper_Form_Element_Select(
            'vm_pos_pic',
            $positions,
            9,
            _t('水印图片位置')
        );
        $form->addInput($vmPosPic);

        $vmPosText = new Typecho_Widget_Helper_Form_Element_Select(
            'vm_pos_text',
            $positions,
            9,
            _t('水印文字位置')
        );
        $form->addInput($vmPosText);

        $images = array();
        $fonts = array();
        $fileList = @scandir(__DIR__);
        if (is_array($fileList)) {
            foreach ($fileList as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, array('ttf', 'ttc'), true)) {
                    $fonts[] = $file;
                }
                if (in_array($extension, array('png', 'gif', 'jpg', 'jpeg', 'webp'), true)) {
                    $images[] = $file;
                }
            }
        }

        $imageMessage = $images
            ? _t('可用图片：%s', implode('、', $images))
            : _t('插件目录中没有可用的水印图片');
        $fontMessage = $fonts
            ? _t('可用字体：%s', implode('、', $fonts))
            : _t('插件目录中没有可用的字体文件');

        $vmPic = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_pic',
            NULL,
            'WM.png',
            _t('水印图片'),
            $imageMessage
        );
        $vmPic->input->setAttribute('class', 'mini');
        $form->addInput($vmPic);

        $vmText = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_text',
            NULL,
            'Typecho)))',
            _t('水印文字')
        );
        $form->addInput($vmText);

        $vmFont = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_font',
            NULL,
            'lh.ttf',
            _t('文字字体'),
            $fontMessage
        );
        $vmFont->input->setAttribute('class', 'mini');
        $form->addInput($vmFont);

        $vmSize = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_size',
            NULL,
            '16',
            _t('文字大小')
        );
        $vmSize->input->setAttribute('class', 'mini');
        $form->addInput($vmSize->addRule('isInteger', _t('必须是整数')));

        $vmColor = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_color',
            NULL,
            '255,0,0',
            _t('文字颜色'),
            _t('格式：255,255,255 或 #FF0000')
        );
        $vmColor->input->setAttribute('class', 'mini');
        $form->addInput($vmColor);

        $vmMX = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_m_x',
            NULL,
            '0',
            _t('水平微调'),
            _t('输入整数，可以为负数')
        );
        $vmMX->input->setAttribute('class', 'mini');
        $form->addInput($vmMX->addRule('isInteger', _t('必须是整数')));

        $vmMY = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_m_y',
            NULL,
            '0',
            _t('竖直微调'),
            _t('输入整数，可以为负数')
        );
        $vmMY->input->setAttribute('class', 'mini');
        $form->addInput($vmMY->addRule('isInteger', _t('必须是整数')));

        $vmWidth = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_width',
            NULL,
            '0',
            _t('调整图片宽度'),
            _t('设为 0 表示不调整；大于 0 时仅缩小宽度超过该值的图片')
        );
        $vmWidth->input->setAttribute('class', 'mini');
        $form->addInput($vmWidth->addRule('isInteger', _t('必须是整数')));

        $vmAlpha = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_alpha',
            NULL,
            '0',
            _t('图片透明度'),
            _t('取 0-100 之间的整数，0 为不透明，100 为全透明')
        );
        $vmAlpha->input->setAttribute('class', 'mini');
        $form->addInput($vmAlpha->addRule('isInteger', _t('必须是整数')));

        $options = Typecho_Widget::widget('Widget_Options');
        $security = Typecho_Widget::widget('Widget_Security');
        $clearUrl = $options->index . '/action/Watermark?clear=1&_='
            . rawurlencode($security->getToken('watermark-clear'));
        $cacheMessage = self::ensureCacheDirectory()
            ? _t(
                '缓存目录：%s。<a href="%s" target="_blank">清除水印缓存</a>',
                self::CACHE_RELATIVE_DIR,
                htmlspecialchars($clearUrl, ENT_QUOTES, 'UTF-8')
            )
            : _t('缓存目录不可写，请检查 usr/cache 目录权限');

        $vmCache = new Typecho_Widget_Helper_Form_Element_Radio(
            'vm_cache',
            array('cache' => _t('使用缓存'), 'nocache' => _t('不使用缓存')),
            'nocache',
            _t('使用缓存'),
            $cacheMessage
        );
        $form->addInput($vmCache);
    }

    /**
     * 个人配置
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 处理 Typecho 1.2/1.3 已渲染的正文 HTML。
     *
     * @param string $content
     * @return string
     */
    public static function parseContent($content)
    {
        if (
            !is_string($content)
            || (false === stripos($content, '<img') && false === stripos($content, '<source'))
        ) {
            return $content;
        }

        $result = '';
        $position = 0;
        $length = strlen($content);
        while ($position < $length) {
            $start = strpos($content, '<', $position);
            if (false === $start) {
                break;
            }
            $result .= substr($content, $position, $start - $position);

            if ('<!--' === substr($content, $start, 4)) {
                $commentEnd = strpos($content, '-->', $start + 4);
                if (false === $commentEnd) {
                    $position = $start;
                    break;
                }
                $position = $commentEnd + 3;
                $result .= substr($content, $start, $position - $start);
                continue;
            }

            $end = self::findTagEnd($content, $start + 1);
            if (false === $end) {
                $position = $start;
                break;
            }

            $tag = substr($content, $start, $end - $start + 1);
            if (preg_match('/^<(?:img|source)(?=[\s\/>])/i', $tag)) {
                $tag = self::rewriteTagAttributes($tag);
            }
            $result .= $tag;
            $position = $end + 1;

            if (preg_match('/^<(script|style|textarea)(?=[\s>])/i', $tag, $rawTag)) {
                $pattern = '/<\/' . preg_quote($rawTag[1], '/') . '(?=[\s\/>])/i';
                if (
                    !preg_match(
                        $pattern,
                        $content,
                        $closeMatch,
                        PREG_OFFSET_CAPTURE,
                        $position
                    )
                ) {
                    break;
                }
                $closeStart = $closeMatch[0][1];
                $closeEnd = self::findTagEnd($content, $closeStart + 2);
                if (false === $closeEnd) {
                    break;
                }
                $result .= substr($content, $position, $closeEnd - $position + 1);
                $position = $closeEnd + 1;
            }
        }

        return $result . substr($content, $position);
    }

    /**
     * 查找不在引号内的标签结束符。
     *
     * @param string $content
     * @param int $position
     * @return int|false
     */
    private static function findTagEnd($content, $position)
    {
        $quote = null;
        $length = strlen($content);
        for (; $position < $length; $position++) {
            $character = $content[$position];
            if (null !== $quote) {
                if ($character === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ('"' === $character || "'" === $character) {
                $quote = $character;
            } elseif ('>' === $character) {
                return $position;
            }
        }

        return false;
    }

    /**
     * 扫描并改写图片标签中的 URL 属性。
     *
     * @param string $tag
     * @return string
     */
    private static function rewriteTagAttributes($tag)
    {
        $length = strlen($tag);
        $position = 1;
        while ($position < $length && !ctype_space($tag[$position]) && '>' !== $tag[$position]) {
            $position++;
        }

        $replacements = array();
        while ($position < $length) {
            while ($position < $length && ctype_space($tag[$position])) {
                $position++;
            }
            if ($position >= $length || '>' === $tag[$position] || '/' === $tag[$position]) {
                break;
            }

            $nameStart = $position;
            while (
                $position < $length
                && !ctype_space($tag[$position])
                && false === strpos("=/>", $tag[$position])
            ) {
                $position++;
            }
            $name = strtolower(substr($tag, $nameStart, $position - $nameStart));
            while ($position < $length && ctype_space($tag[$position])) {
                $position++;
            }
            if ($position >= $length || '=' !== $tag[$position]) {
                continue;
            }

            $position++;
            while ($position < $length && ctype_space($tag[$position])) {
                $position++;
            }
            if ($position >= $length) {
                break;
            }

            $quote = null;
            if ('"' === $tag[$position] || "'" === $tag[$position]) {
                $quote = $tag[$position];
                $position++;
            }
            $valueStart = $position;
            if (null !== $quote) {
                while ($position < $length && $tag[$position] !== $quote) {
                    $position++;
                }
            } else {
                while (
                    $position < $length
                    && !ctype_space($tag[$position])
                    && '>' !== $tag[$position]
                ) {
                    $position++;
                }
            }
            $valueEnd = $position;
            if (null !== $quote && $position < $length) {
                $position++;
            }

            if (!in_array($name, array('src', 'data-src', 'srcset', 'data-srcset'), true)) {
                continue;
            }

            $value = html_entity_decode(
                substr($tag, $valueStart, $valueEnd - $valueStart),
                ENT_QUOTES,
                'UTF-8'
            );
            $rewritten = false !== strpos($name, 'srcset')
                ? self::rewriteSrcset($value)
                : self::buildWatermarkUrl($value);
            if ($rewritten === $value) {
                continue;
            }

            $escaped = htmlspecialchars($rewritten, ENT_QUOTES, 'UTF-8');
            $replacements[] = array(
                $valueStart,
                $valueEnd - $valueStart,
                null === $quote ? '"' . $escaped . '"' : $escaped
            );
        }

        for ($index = count($replacements) - 1; $index >= 0; $index--) {
            $replacement = $replacements[$index];
            $tag = substr_replace(
                $tag,
                $replacement[2],
                $replacement[0],
                $replacement[1]
            );
        }

        return $tag;
    }

    /**
     * 按 srcset 候选语法扫描 URL 和 descriptor，保留 URL 内部逗号。
     *
     * @param string $srcset
     * @return string
     */
    private static function rewriteSrcset($srcset)
    {
        $length = strlen($srcset);
        $position = 0;
        $candidates = array();

        while ($position < $length) {
            while (
                $position < $length
                && (false !== strpos(" \t\n\r\f,", $srcset[$position]))
            ) {
                $position++;
            }
            if ($position >= $length) {
                break;
            }

            $urlStart = $position;
            while (
                $position < $length
                && false === strpos(" \t\n\r\f", $srcset[$position])
            ) {
                $position++;
            }
            $url = substr($srcset, $urlStart, $position - $urlStart);

            $hasTrailingComma = ',' === substr($url, -1);
            if ($hasTrailingComma) {
                $url = rtrim($url, ',');
                if ('' !== $url) {
                    $candidates[] = self::buildWatermarkUrl($url);
                }
                continue;
            }

            while (
                $position < $length
                && false !== strpos(" \t\n\r\f", $srcset[$position])
            ) {
                $position++;
            }

            $descriptorStart = $position;
            $parentheses = 0;
            while ($position < $length) {
                $character = $srcset[$position];
                if ('(' === $character) {
                    $parentheses++;
                } elseif (')' === $character && $parentheses > 0) {
                    $parentheses--;
                } elseif (',' === $character && 0 === $parentheses) {
                    break;
                }
                $position++;
            }

            $descriptor = trim(substr(
                $srcset,
                $descriptorStart,
                $position - $descriptorStart
            ));
            if ($position < $length && ',' === $srcset[$position]) {
                $position++;
            }

            if ('' !== $url) {
                $candidate = self::buildWatermarkUrl($url);
                $candidates[] = $candidate . ('' !== $descriptor ? ' ' . $descriptor : '');
            }
        }

        return implode(', ', $candidates);
    }

    /**
     * 兼容旧版 Typecho 的原始内容过滤。
     *
     * @param array $row
     * @return array
     */
    public static function parseLegacy($row)
    {
        if (!is_array($row) || !isset($row['text'])) {
            return $row;
        }

        if (!empty($row['isMarkdown'])) {
            $row['text'] = self::parseMarkdown($row['text']);
        } else {
            $row['text'] = self::parseContent($row['text']);
        }

        return $row;
    }

    /**
     * 处理旧版 Typecho 的 Markdown 图片地址。
     *
     * @param string $content
     * @return string
     */
    private static function parseMarkdown($content)
    {
        $content = preg_replace_callback(
            '/(!\[[^\]]*\]\(\s*)([^)\s]+)([^)]*\))/',
            function ($matches) {
                return $matches[1] . self::buildWatermarkUrl($matches[2]) . $matches[3];
            },
            $content
        );

        return preg_replace_callback(
            '/^(\s*\[[^\]]+\]:\s*)(\S+)(.*)$/m',
            function ($matches) {
                return $matches[1] . self::buildWatermarkUrl($matches[2]) . $matches[3];
            },
            $content
        );
    }

    /**
     * 将本地上传图片地址转换为水印地址。
     *
     * @param string $url
     * @return string
     */
    public static function buildWatermarkUrl($url)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $source = self::resolveSourceUrl($url, $options);
        if (false === $source || self::isAnimatedGif($source['absolute'])) {
            return $url;
        }

        $config = self::pluginOptions($options);
        if (!self::watermarkTypes($config)) {
            return $url;
        }
        if ('cache' === self::configValue($config, 'vm_cache', 'nocache')) {
            $cacheFile = self::cacheFile($source['relative'], $source['absolute'], $config);
            if (false !== $cacheFile && is_file($cacheFile)) {
                return rtrim($options->siteUrl, '/') . '/'
                    . self::CACHE_RELATIVE_DIR . '/' . basename($cacheFile);
            }
        }

        $encoded = self::encodePath($source['relative']);
        $signature = self::signPath($source['relative'], $options);

        return $options->index . '/action/Watermark?mark='
            . rawurlencode($encoded) . '&signature=' . rawurlencode($signature);
    }

    /**
     * 解析正文图片 URL，并限制在默认上传目录中。
     *
     * @param string $url
     * @param object $options
     * @return array|false
     */
    private static function resolveSourceUrl($url, $options)
    {
        if (!is_string($url) || '' === $url || 0 === stripos($url, 'data:')) {
            return false;
        }
        if (false !== strpos($url, '/action/Watermark') || false !== strpos($url, self::CACHE_RELATIVE_DIR)) {
            return false;
        }

        $parts = @parse_url($url);
        if (false === $parts || empty($parts['path'])) {
            return false;
        }
        if (
            !empty($parts['scheme'])
            && !in_array(strtolower($parts['scheme']), array('http', 'https'), true)
        ) {
            return false;
        }

        if (!empty($parts['host'])) {
            $siteParts = @parse_url($options->siteUrl);
            if (!is_array($siteParts) || empty($siteParts['host'])) {
                return false;
            }

            $siteScheme = strtolower(isset($siteParts['scheme']) ? $siteParts['scheme'] : 'http');
            $sourceScheme = strtolower(isset($parts['scheme']) ? $parts['scheme'] : $siteScheme);
            if (
                0 !== strcasecmp($parts['host'], $siteParts['host'])
                || $sourceScheme !== $siteScheme
                || self::effectivePort($parts, $sourceScheme)
                    !== self::effectivePort($siteParts, $siteScheme)
            ) {
                return false;
            }
        }

        $path = rawurldecode($parts['path']);
        $sitePath = parse_url($options->siteUrl, PHP_URL_PATH);
        if ($sitePath && '/' !== $sitePath) {
            $sitePath = '/' . trim($sitePath, '/');
            if (0 === strpos($path, $sitePath . '/')) {
                $path = substr($path, strlen($sitePath));
            }
        }

        return self::resolveImagePath('/' . ltrim($path, '/'));
    }

    /**
     * 获取 URL 的有效端口。
     *
     * @param array $parts
     * @param string $scheme
     * @return int
     */
    private static function effectivePort(array $parts, $scheme)
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return 'https' === $scheme ? 443 : 80;
    }

    /**
     * 将上传目录中的相对路径解析为安全的真实路径。
     *
     * @param string $relativePath
     * @return array|false
     */
    public static function resolveImagePath($relativePath)
    {
        if (!is_string($relativePath) || false !== strpos($relativePath, "\0")) {
            return false;
        }

        $relativePath = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        $uploadPrefix = '/' . self::UPLOAD_RELATIVE_DIR . '/';
        if (0 !== strpos($relativePath, $uploadPrefix)) {
            return false;
        }

        $uploadRoot = realpath(__TYPECHO_ROOT_DIR__ . '/' . self::UPLOAD_RELATIVE_DIR);
        $absolutePath = realpath(__TYPECHO_ROOT_DIR__ . $relativePath);
        if (false === $uploadRoot || false === $absolutePath || !is_file($absolutePath)) {
            return false;
        }

        $allowedPrefix = rtrim($uploadRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (0 !== strpos($absolutePath, $allowedPrefix)) {
            return false;
        }

        $imageInfo = @getimagesize($absolutePath);
        $allowedTypes = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
        if (defined('IMAGETYPE_WEBP')) {
            $allowedTypes[] = IMAGETYPE_WEBP;
        }
        if (!$imageInfo || !in_array($imageInfo[2], $allowedTypes, true)) {
            return false;
        }

        $relative = $uploadPrefix . str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            substr($absolutePath, strlen($allowedPrefix))
        );

        return array('relative' => $relative, 'absolute' => $absolutePath);
    }

    /**
     * 解析插件目录内的水印图片或字体。
     *
     * @param string $file
     * @param array $extensions
     * @return string|false
     */
    public static function resolvePluginAsset($file, array $extensions)
    {
        $file = basename((string) $file);
        if ('' === $file || !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true)) {
            return false;
        }

        $pluginRoot = realpath(__DIR__);
        $path = realpath(__DIR__ . '/' . $file);
        if (false === $pluginRoot || false === $path || !is_file($path)) {
            return false;
        }

        $prefix = rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return 0 === strpos($path, $prefix) ? $path : false;
    }

    /**
     * 获取启用的水印类型。
     *
     * @param object $config
     * @return array
     */
    public static function watermarkTypes($config)
    {
        $types = self::configValue($config, 'vm_type', array());
        if (!is_array($types)) {
            $types = array();
        }

        return array_values(array_intersect($types, array('pic', 'text')));
    }

    /**
     * 安全读取插件配置，兼容刚启用但尚未保存设置的状态。
     *
     * @param object|null $options
     * @return object
     */
    public static function pluginOptions($options = null)
    {
        $options = $options ?: Typecho_Widget::widget('Widget_Options');
        try {
            return $options->plugin('Watermark');
        } catch (Throwable $exception) {
            return new stdClass();
        }
    }

    /**
     * 获取配置值。
     *
     * @param object $config
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function configValue($config, $name, $default = null)
    {
        return is_object($config) && isset($config->{$name}) ? $config->{$name} : $default;
    }

    /**
     * 创建缓存目录。
     *
     * @return bool
     */
    public static function ensureCacheDirectory()
    {
        $directory = self::cacheDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
            return false;
        }

        return is_writable($directory);
    }

    /**
     * 获取缓存目录。
     *
     * @return string
     */
    public static function cacheDirectory()
    {
        return __TYPECHO_ROOT_DIR__ . '/' . self::CACHE_RELATIVE_DIR;
    }

    /**
     * 仅清理插件生成的缓存文件。
     *
     * @return int
     */
    public static function clearCache()
    {
        $directory = self::cacheDirectory();
        $count = 0;
        $files = is_dir($directory) ? @scandir($directory) : false;
        if (!is_array($files)) {
            return $count;
        }

        foreach ($files as $file) {
            if (!preg_match('/^[a-f0-9]{64}\.(gif|jpg|png|webp|fingerprint)$/', $file)) {
                continue;
            }
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_file($path) && @unlink($path)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 生成缓存文件路径。
     *
     * @param string $relativePath
     * @param string $sourcePath
     * @param object $config
     * @return string|false
     */
    public static function cacheFile($relativePath, $sourcePath, $config)
    {
        $settings = array(
            self::VERSION,
            self::configValue($config, 'vm_type', array()),
            self::configValue($config, 'vm_pos_pic', 9),
            self::configValue($config, 'vm_pos_text', 9),
            self::configValue($config, 'vm_pic', 'WM.png'),
            self::configValue($config, 'vm_text', 'Typecho)))'),
            self::configValue($config, 'vm_font', 'lh.ttf'),
            self::configValue($config, 'vm_size', 16),
            self::configValue($config, 'vm_color', '255,0,0'),
            self::configValue($config, 'vm_m_x', 0),
            self::configValue($config, 'vm_m_y', 0),
            self::configValue($config, 'vm_width', 0),
            self::configValue($config, 'vm_alpha', 0)
        );

        $watermarkFile = self::resolvePluginAsset(
            self::configValue($config, 'vm_pic', 'WM.png'),
            array('gif', 'jpg', 'jpeg', 'png', 'webp')
        );
        $fontFile = self::resolvePluginAsset(
            self::configValue($config, 'vm_font', 'lh.ttf'),
            array('ttf', 'ttc')
        );
        $types = self::watermarkTypes($config);
        $watermarkFingerprint = in_array('pic', $types, true) && $watermarkFile
            ? self::fileFingerprint($watermarkFile)
            : '';
        if (false === $watermarkFingerprint) {
            return false;
        }
        $fontFingerprint = in_array('text', $types, true) && $fontFile
            ? self::fileFingerprint($fontFile)
            : '';
        if (false === $fontFingerprint) {
            return false;
        }
        $settings[] = $watermarkFingerprint;
        $settings[] = $fontFingerprint;

        $imageInfo = @getimagesize($sourcePath);
        $extension = self::extensionByType($imageInfo ? $imageInfo[2] : 0);
        $sourceFingerprint = self::fileFingerprint($sourcePath);
        if (false === $sourceFingerprint) {
            return false;
        }
        $key = hash(
            'sha256',
            $relativePath . '|' . $sourceFingerprint . '|'
                . serialize($settings)
        );

        return self::cacheDirectory() . '/' . $key . '.' . $extension;
    }

    /**
     * 获取可跨请求复用的文件内容指纹。
     *
     * @param string $path
     * @return string|false
     */
    private static function fileFingerprint($path)
    {
        static $fingerprints = array();
        static $pathKeys = array();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $state = self::fileVersion($path);
            if (false === $state) {
                return false;
            }

            $key = $path . '|' . $state['version'];
            if ($state['stable'] && isset($fingerprints[$key])) {
                return $fingerprints[$key];
            }

            $fingerprint = $state['stable']
                ? self::readFingerprintMetadata($path, $state['version'])
                : false;
            $persist = false;
            if (false === $fingerprint) {
                $fingerprint = @hash_file('sha256', $path);
                $persist = true;
            }
            if (false === $fingerprint) {
                return false;
            }

            $current = self::fileVersion($path);
            if (false === $current || $current['version'] !== $state['version']) {
                continue;
            }

            if ($persist && $current['stable']) {
                self::writeFingerprintMetadata($path, $state['version'], $fingerprint);
            }
            if (isset($pathKeys[$path]) && $pathKeys[$path] !== $key) {
                unset($fingerprints[$pathKeys[$path]]);
            }
            $pathKeys[$path] = $key;
            $fingerprints[$key] = $fingerprint;

            return $fingerprint;
        }

        return false;
    }

    /**
     * 获取文件的快速版本，并避开同秒内仍可能变化的状态。
     *
     * @param string $path
     * @return array|false
     */
    private static function fileVersion($path)
    {
        clearstatcache(true, $path);
        $stat = @stat($path);
        if (!$stat) {
            return false;
        }

        return array(
            'version' => hash(
                'sha256',
                implode(':', array(
                    $stat['mtime'],
                    $stat['ctime'],
                    $stat['size'],
                    isset($stat['ino']) ? $stat['ino'] : 0,
                    self::fileSampleFingerprint($path, $stat['size'])
                ))
            ),
            // ctime 由文件系统维护；稳定后再复用跨请求元数据。
            'stable' => time() - (int) $stat['ctime'] >= 2
        );
    }

    /**
     * 读取持久化文件指纹。
     *
     * @param string $path
     * @param string $version
     * @return string|false
     */
    private static function readFingerprintMetadata($path, $version)
    {
        $file = self::fingerprintMetadataFile($path);
        $content = is_file($file) ? @file_get_contents($file) : false;
        if (false === $content) {
            return false;
        }

        $metadata = json_decode($content, true);
        if (
            !is_array($metadata)
            || !isset($metadata['format'], $metadata['version'], $metadata['fingerprint'])
            || 2 !== $metadata['format']
            || $version !== $metadata['version']
            || !preg_match('/^[a-f0-9]{64}$/', $metadata['fingerprint'])
        ) {
            return false;
        }

        return $metadata['fingerprint'];
    }

    /**
     * 原子写入持久化文件指纹。
     *
     * @param string $path
     * @param string $version
     * @param string $fingerprint
     */
    private static function writeFingerprintMetadata($path, $version, $fingerprint)
    {
        if (!self::ensureCacheDirectory()) {
            return;
        }

        $content = json_encode(array(
            'format' => 2,
            'version' => $version,
            'fingerprint' => $fingerprint
        ));
        if (false === $content) {
            return;
        }

        $directory = self::cacheDirectory();
        $temporary = @tempnam($directory, '.fingerprint-');
        if (false === $temporary) {
            return;
        }

        $written = @file_put_contents($temporary, $content, LOCK_EX);
        $valid = false !== $written && $written === strlen($content);
        if ($valid && '\\' !== DIRECTORY_SEPARATOR && !@chmod($temporary, 0644)) {
            $valid = false;
        }

        $file = self::fingerprintMetadataFile($path);
        if ($valid && @rename($temporary, $file)) {
            return;
        }
        if ($valid && is_file($file)) {
            @unlink($temporary);
            return;
        }

        @unlink($temporary);
    }

    /**
     * 获取文件指纹元数据路径。
     *
     * @param string $path
     * @return string
     */
    private static function fingerprintMetadataFile($path)
    {
        return self::cacheDirectory() . '/' . hash('sha256', $path) . '.fingerprint';
    }

    /**
     * 使用文件头尾样本快速检测同秒、同大小的内容替换。
     *
     * @param string $path
     * @param int $size
     * @return string
     */
    private static function fileSampleFingerprint($path, $size)
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return '';
        }

        $sampleSize = 8192;
        $context = hash_init('sha256');
        hash_update($context, (string) fread($handle, $sampleSize));
        if ($size > $sampleSize) {
            fseek($handle, max(0, $size - $sampleSize), SEEK_SET);
            hash_update($context, (string) fread($handle, $sampleSize));
        }
        fclose($handle);

        return hash_final($context);
    }

    /**
     * 将图片类型转换为输出扩展名。
     *
     * @param int $type
     * @return string
     */
    public static function extensionByType($type)
    {
        switch ($type) {
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_JPEG:
                return 'jpg';
            case IMAGETYPE_WEBP:
                return 'webp';
            default:
                return 'png';
        }
    }

    /**
     * 编码图片路径。
     *
     * @param string $path
     * @return string
     */
    public static function encodePath($path)
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    /**
     * 解码图片路径。
     *
     * @param string $encoded
     * @return string|false
     */
    public static function decodePath($encoded)
    {
        if (!is_string($encoded) || !preg_match('/^[A-Za-z0-9_-]+$/', $encoded)) {
            return false;
        }

        $padding = strlen($encoded) % 4;
        if ($padding) {
            $encoded .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($encoded, '-_', '+/'), true);
    }

    /**
     * 签名图片路径。
     *
     * @param string $path
     * @param object $options
     * @return string
     */
    public static function signPath($path, $options)
    {
        return hash_hmac('sha256', $path, (string) $options->secret);
    }

    /**
     * 安全检测 GIF 是否为动画。
     *
     * @param string $filename
     * @return bool
     */
    public static function isAnimatedGif($filename)
    {
        $imageInfo = is_file($filename) ? @getimagesize($filename) : false;
        if (!$imageInfo || IMAGETYPE_GIF !== $imageInfo[2]) {
            return false;
        }

        $handle = @fopen($filename, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 6);
        $screen = fread($handle, 7);
        if (
            !in_array($header, array('GIF87a', 'GIF89a'), true)
            || 7 !== strlen($screen)
        ) {
            fclose($handle);
            return true;
        }

        $packed = ord($screen[4]);
        if ($packed & 0x80) {
            $colorTableSize = 3 * (1 << (($packed & 0x07) + 1));
            if (0 !== fseek($handle, $colorTableSize, SEEK_CUR)) {
                fclose($handle);
                return true;
            }
        }

        $frames = 0;
        while (!feof($handle)) {
            $marker = fread($handle, 1);
            if ('' === $marker || false === $marker) {
                break;
            }

            $marker = ord($marker);
            if (0x3B === $marker) {
                fclose($handle);
                return $frames > 1;
            }

            if (0x21 === $marker) {
                $label = fread($handle, 1);
                if (false === $label || '' === $label || !self::skipGifSubBlocks($handle)) {
                    fclose($handle);
                    return true;
                }
                continue;
            }

            if (0x2C === $marker) {
                $descriptor = fread($handle, 9);
                if (9 !== strlen($descriptor)) {
                    fclose($handle);
                    return true;
                }

                $frames++;
                if ($frames > 1) {
                    fclose($handle);
                    return true;
                }

                $imagePacked = ord($descriptor[8]);
                if ($imagePacked & 0x80) {
                    $colorTableSize = 3 * (1 << (($imagePacked & 0x07) + 1));
                    if (0 !== fseek($handle, $colorTableSize, SEEK_CUR)) {
                        fclose($handle);
                        return true;
                    }
                }

                $codeSize = fread($handle, 1);
                if (false === $codeSize || '' === $codeSize || !self::skipGifSubBlocks($handle)) {
                    fclose($handle);
                    return true;
                }
                continue;
            }

            fclose($handle);
            return true;
        }

        fclose($handle);
        return $frames > 1;
    }

    /**
     * 跳过 GIF 数据子块。
     *
     * @param resource $handle
     * @return bool
     */
    private static function skipGifSubBlocks($handle)
    {
        while (!feof($handle)) {
            $length = fread($handle, 1);
            if ('' === $length || false === $length) {
                return false;
            }

            $length = ord($length);
            if (0 === $length) {
                return true;
            }
            if (0 !== fseek($handle, $length, SEEK_CUR)) {
                return false;
            }
        }

        return false;
    }
}
