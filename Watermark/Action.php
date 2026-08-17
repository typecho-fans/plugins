<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Watermark action.
 *
 * @copyright Copyright (c) 2013 DEFE
 * @license GNU General Public License 2.0
 */
class Watermark_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 输出水印图片。
     *
     * @param string $encodedPath
     * @param string $signature
     */
    public function mark($encodedPath, $signature)
    {
        $options = $this->widget('Widget_Options');
        $relativePath = Watermark_Plugin::decodePath($encodedPath);
        if (
            false === $relativePath
            || !is_string($signature)
            || !hash_equals(Watermark_Plugin::signPath($relativePath, $options), $signature)
        ) {
            $this->notFound();
            return;
        }

        $source = Watermark_Plugin::resolveImagePath($relativePath);
        if (false === $source) {
            $this->notFound();
            return;
        }

        $config = Watermark_Plugin::pluginOptions($options);
        if (
            !Watermark_Plugin::isImageEligible($source, $config)
            || Watermark_Plugin::isAnimatedGif($source['absolute'])
        ) {
            $this->outputFile($source['absolute']);
            return;
        }

        $types = Watermark_Plugin::watermarkTypes($config);
        $useImage = in_array('pic', $types, true);
        $useText = in_array('text', $types, true);

        $watermarkFile = false;
        if ($useImage) {
            $watermarkFile = Watermark_Plugin::resolvePluginAsset(
                Watermark_Plugin::configValue($config, 'vm_pic', 'WM.png'),
                array('gif', 'jpg', 'jpeg', 'png', 'webp')
            );
            $useImage = false !== $watermarkFile;
        }

        $fontFile = false;
        if ($useText) {
            $fontFile = Watermark_Plugin::resolvePluginAsset(
                Watermark_Plugin::configValue($config, 'vm_font', 'lh.ttf'),
                array('ttf', 'ttc')
            );
            $useText = false !== $fontFile
                && function_exists('imagettfbbox')
                && function_exists('imagettftext');
        }

        if (!$useImage && !$useText) {
            $this->outputFile($source['absolute']);
            return;
        }

        $cacheFile = false;
        if (
            'cache' === Watermark_Plugin::configValue($config, 'vm_cache', 'nocache')
            && Watermark_Plugin::ensureCacheDirectory()
        ) {
            $candidate = Watermark_Plugin::cacheFile(
                $source['relative'],
                $source['absolute'],
                $config
            );
            if (false !== $candidate) {
                $cacheFile = $candidate;
                if (is_file($cacheFile)) {
                    $this->outputFile($cacheFile);
                    return;
                }
            }
        }

        require_once __DIR__ . '/class.php';

        try {
            $watermark = new WaterMark();
            $width = max(0, (int) Watermark_Plugin::configValue($config, 'vm_width', 0));
            if (!$watermark->setImSrc($source['absolute'], $width)) {
                $this->outputFile($source['absolute']);
                return;
            }

            if ($useImage && !$watermark->setImWater($watermarkFile)) {
                $useImage = false;
            }

            if ($useText) {
                $watermark->setFont(
                    $fontFile,
                    (string) Watermark_Plugin::configValue($config, 'vm_text', 'Typecho)))'),
                    max(1, (int) Watermark_Plugin::configValue($config, 'vm_size', 16)),
                    (string) Watermark_Plugin::configValue($config, 'vm_color', '255,0,0')
                );
            }

            if (!$useImage && !$useText) {
                $watermark->output();
                $watermark->clean();
                return;
            }

            $watermark->mark(
                $useImage,
                $this->position(Watermark_Plugin::configValue($config, 'vm_pos_pic', 9)),
                $useText,
                $this->position(Watermark_Plugin::configValue($config, 'vm_pos_text', 9)),
                (int) Watermark_Plugin::configValue($config, 'vm_m_x', 0),
                (int) Watermark_Plugin::configValue($config, 'vm_m_y', 0),
                min(100, max(0, (int) Watermark_Plugin::configValue($config, 'vm_alpha', 0))),
                $cacheFile,
                'tile' === Watermark_Plugin::configValue($config, 'vm_layout', 'single')
                    ? 'tile'
                    : 'single',
                min(180, max(-180, (int) Watermark_Plugin::configValue($config, 'vm_angle', 0))),
                max(0, (int) Watermark_Plugin::configValue($config, 'vm_gap_x', 80)),
                max(0, (int) Watermark_Plugin::configValue($config, 'vm_gap_y', 60)),
                min(100, max(0, (int) Watermark_Plugin::configValue(
                    $config,
                    'vm_text_alpha',
                    0
                )))
            );
        } catch (Throwable $exception) {
            if (isset($watermark)) {
                $watermark->clean();
            }
            $this->outputFile($source['absolute']);
        }
    }

    /**
     * 清除插件专用缓存。
     */
    public function clear()
    {
        $this->widget('Widget_User')->pass('administrator');
        $security = $this->widget('Widget_Security');
        $token = (string) $this->request->get('_');
        if (!hash_equals($security->getToken('watermark-clear'), $token)) {
            $this->response->setStatus(403);
            echo _t('安全校验失败，请返回插件设置页重试');
            return;
        }

        echo _t('已清除 %d 个水印缓存文件', Watermark_Plugin::clearCache());
    }

    /**
     * 分发请求。
     */
    public function action()
    {
        if ($this->request->is('mark')) {
            $this->mark(
                (string) $this->request->get('mark'),
                (string) $this->request->get('signature')
            );
            return;
        }

        if ($this->request->is('clear')) {
            $this->clear();
            return;
        }

        $this->notFound();
    }

    /**
     * 约束水印位置。
     *
     * @param mixed $value
     * @return int
     */
    private function position($value)
    {
        $value = (int) $value;
        return $value >= 0 && $value <= 9 ? $value : 9;
    }

    /**
     * 输出原始或缓存图片。
     *
     * @param string $path
     */
    private function outputFile($path)
    {
        $imageInfo = @getimagesize($path);
        if (!$imageInfo || empty($imageInfo['mime'])) {
            $this->notFound();
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $imageInfo['mime']);
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }

    /**
     * 返回 404。
     */
    private function notFound()
    {
        $this->response->setStatus(404);
        echo 'Not Found';
    }
}
