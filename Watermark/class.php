<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * GD 水印处理器。
 *
 * @copyright Copyright (c) 2013 DEFE
 * @license GNU General Public License 2.0
 */
class WaterMark
{
    private $imSrc;
    private $imSrcWidth = 0;
    private $imSrcHeight = 0;
    private $srcImage;
    private $imWater;
    private $imWaterWidth = 0;
    private $imWaterHeight = 0;
    private $waterImage;
    private $font;
    private $fontText = '';
    private $fontSize = 16;
    private $fontColor = '255,0,0';
    private $type = 0;
    private $mime = 'image/png';
    private $gifTransparentColor = null;

    /**
     * 设置原图。
     *
     * @param string $image
     * @param int $width
     * @return bool
     */
    public function setImSrc($image, $width = 0)
    {
        $imageInfo = @getimagesize($image);
        if (!$imageInfo) {
            return false;
        }

        $source = $this->createImage($image, $imageInfo[2]);
        if (!$this->isImage($source)) {
            return false;
        }

        if (IMAGETYPE_GIF === $imageInfo[2]) {
            $converted = $this->convertGifToTruecolor($source);
            if (!$this->isImage($converted)) {
                imagedestroy($source);
                return false;
            }
            imagedestroy($source);
            $source = $converted;
        }

        $this->imSrc = $image;
        $this->srcImage = $source;
        $this->type = $imageInfo[2];
        $this->mime = $imageInfo['mime'];
        $this->imSrcWidth = imagesx($source);
        $this->imSrcHeight = imagesy($source);

        if ((int) $width > 0) {
            $this->resize((int) $width);
        }

        return true;
    }

    /**
     * 设置图片水印。
     *
     * @param string $image
     * @return bool
     */
    public function setImWater($image)
    {
        $imageInfo = @getimagesize($image);
        if (!$imageInfo) {
            return false;
        }

        $watermark = $this->createImage($image, $imageInfo[2]);
        if (!$this->isImage($watermark)) {
            return false;
        }

        $this->imWater = $image;
        $this->waterImage = $watermark;
        $this->imWaterWidth = imagesx($watermark);
        $this->imWaterHeight = imagesy($watermark);

        return true;
    }

    /**
     * 设置文字水印。
     *
     * @param string $font
     * @param string $text
     * @param int $size
     * @param string $color
     */
    public function setFont($font, $text, $size, $color)
    {
        $this->font = $font;
        $this->fontText = $text;
        $this->fontSize = max(1, (int) $size);
        $this->fontColor = $color;
    }

    /**
     * 根据图片类型创建 GD 图像。
     *
     * @param string $image
     * @param int $type
     * @return mixed
     */
    private function createImage($image, $type)
    {
        switch ($type) {
            case IMAGETYPE_GIF:
                return function_exists('imagecreatefromgif') ? @imagecreatefromgif($image) : false;

            case IMAGETYPE_JPEG:
                $source = function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($image) : false;
                if (!$this->isImage($source)) {
                    return false;
                }

                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($image);
                    $orientation = is_array($exif) && isset($exif['Orientation'])
                        ? (int) $exif['Orientation']
                        : 1;
                    $source = $this->applyOrientation($source, $orientation);
                }

                return $source;

            case IMAGETYPE_PNG:
                $source = function_exists('imagecreatefrompng') ? @imagecreatefrompng($image) : false;
                if ($this->isImage($source)) {
                    imagealphablending($source, true);
                    imagesavealpha($source, true);
                }
                return $source;

            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($image) : false;
        }

        return false;
    }

    /**
     * 根据 EXIF Orientation 归一化 JPEG 方向。
     *
     * @param mixed $source
     * @param int $orientation
     * @return mixed
     */
    private function applyOrientation($source, $orientation)
    {
        if (2 === $orientation) {
            imageflip($source, IMG_FLIP_HORIZONTAL);
        } elseif (3 === $orientation) {
            $source = $this->rotate($source, 180);
        } elseif (4 === $orientation) {
            imageflip($source, IMG_FLIP_VERTICAL);
        } elseif (5 === $orientation) {
            $source = $this->rotate($source, -90);
            imageflip($source, IMG_FLIP_HORIZONTAL);
        } elseif (6 === $orientation) {
            $source = $this->rotate($source, -90);
        } elseif (7 === $orientation) {
            $source = $this->rotate($source, 90);
            imageflip($source, IMG_FLIP_HORIZONTAL);
        } elseif (8 === $orientation) {
            $source = $this->rotate($source, 90);
        }

        return $source;
    }

    /**
     * 旋转图像并释放旧资源。
     *
     * @param mixed $source
     * @param int $angle
     * @return mixed
     */
    private function rotate($source, $angle)
    {
        $rotated = imagerotate($source, $angle, 0);
        if ($this->isImage($rotated)) {
            imagedestroy($source);
            return $rotated;
        }

        return $source;
    }

    /**
     * 生成水印。
     *
     * @param bool $useImage
     * @param int $imagePosition
     * @param bool $useText
     * @param int $textPosition
     * @param int $offsetX
     * @param int $offsetY
     * @param int $alpha
     * @param string|false $save
     */
    public function mark(
        $useImage = false,
        $imagePosition = 0,
        $useText = false,
        $textPosition = 0,
        $offsetX = 0,
        $offsetY = 0,
        $alpha = 0,
        $save = false
    ) {
        if ($useImage && $this->isImage($this->waterImage)) {
            if ($this->checkRange($this->imWaterWidth, $this->imWaterHeight)) {
                list($positionX, $positionY) = $this->getPosition(
                    $imagePosition,
                    $this->imWaterWidth,
                    $this->imWaterHeight
                );
                if ($alpha > 0) {
                    $this->copyWithAlpha(
                        $this->srcImage,
                        $this->waterImage,
                        $positionX,
                        $positionY,
                        $this->imWaterWidth,
                        $this->imWaterHeight,
                        $alpha
                    );
                } else {
                    imagecopy(
                        $this->srcImage,
                        $this->waterImage,
                        $positionX,
                        $positionY,
                        0,
                        0,
                        $this->imWaterWidth,
                        $this->imWaterHeight
                    );
                }
            }
        }

        if ($useText && is_file($this->font) && '' !== $this->fontText) {
            $box = imagettfbbox($this->fontSize, 0, $this->font, $this->fontText);
            if (is_array($box)) {
                $xCoordinates = array($box[0], $box[2], $box[4], $box[6]);
                $yCoordinates = array($box[1], $box[3], $box[5], $box[7]);
                $minX = min($xCoordinates);
                $maxX = max($xCoordinates);
                $minY = min($yCoordinates);
                $maxY = max($yCoordinates);
                $width = $maxX - $minX;
                $height = $maxY - $minY;
                if ($this->checkRange($width, $height)) {
                    list($positionX, $positionY) = $this->getPosition(
                        $textPosition,
                        $width,
                        $height
                    );
                    $color = $this->parseColor($this->fontColor);
                    $gdColor = imagecolorallocate(
                        $this->srcImage,
                        $color['r'],
                        $color['g'],
                        $color['b']
                    );
                    imagettftext(
                        $this->srcImage,
                        $this->fontSize,
                        0,
                        $positionX - $minX + (int) $offsetX,
                        $positionY - $minY + (int) $offsetY,
                        $gdColor,
                        $this->font,
                        $this->fontText
                    );
                }
            }
        }

        if ($save) {
            $this->save($save);
        }
        $this->output();
        $this->clean();
    }

    /**
     * 输出图像。
     */
    public function output()
    {
        if (!$this->isImage($this->srcImage)) {
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $this->mime);
        $this->writeImage();
    }

    /**
     * 保存图像。
     *
     * @param string $file
     * @return bool
     */
    public function save($file)
    {
        if (!$this->isImage($this->srcImage)) {
            return false;
        }

        $directory = dirname($file);
        $temporary = @tempnam($directory, '.watermark-');
        if (false === $temporary) {
            return false;
        }

        $saved = $this->writeImage($temporary);
        $valid = $saved && filesize($temporary) > 0 && @getimagesize($temporary);
        if ($valid && '\\' !== DIRECTORY_SEPARATOR && !@chmod($temporary, 0644)) {
            $valid = false;
        }
        if ($valid && @rename($temporary, $file)) {
            return true;
        }
        if ($valid && is_file($file)) {
            @unlink($temporary);
            return true;
        }

        @unlink($temporary);
        return false;
    }

    /**
     * 清理 GD 资源。
     */
    public function clean()
    {
        if ($this->isImage($this->waterImage)) {
            imagedestroy($this->waterImage);
            $this->waterImage = null;
        }
        if ($this->isImage($this->srcImage)) {
            imagedestroy($this->srcImage);
            $this->srcImage = null;
        }
    }

    /**
     * 写出指定类型的图像。
     *
     * @param string|null $file
     * @return bool
     */
    private function writeImage($file = null)
    {
        switch ($this->type) {
            case IMAGETYPE_GIF:
                return $this->prepareGifForOutput()
                    ? imagegif($this->srcImage, $file)
                    : false;
            case IMAGETYPE_JPEG:
                return imagejpeg($this->srcImage, $file, 90);
            case IMAGETYPE_WEBP:
                return function_exists('imagewebp')
                    ? imagewebp($this->srcImage, $file, 90)
                    : false;
            default:
                return imagepng($this->srcImage, $file);
        }
    }

    /**
     * 获取水印坐标。
     *
     * @param int $position
     * @param int $width
     * @param int $height
     * @return array
     */
    private function getPosition($position, $width, $height)
    {
        $maxX = max(0, $this->imSrcWidth - $width);
        $maxY = max(0, $this->imSrcHeight - $height);

        switch ((int) $position) {
            case 1:
                return array(0, 0);
            case 2:
                return array((int) floor($maxX / 2), 0);
            case 3:
                return array($maxX, 0);
            case 4:
                return array(0, (int) floor($maxY / 2));
            case 5:
                return array((int) floor($maxX / 2), (int) floor($maxY / 2));
            case 6:
                return array($maxX, (int) floor($maxY / 2));
            case 7:
                return array(0, $maxY);
            case 8:
                return array((int) floor($maxX / 2), $maxY);
            case 9:
                return array($maxX, $maxY);
            default:
                return array(rand(0, $maxX), rand(0, $maxY));
        }
    }

    /**
     * 判断原图能否容纳水印。
     *
     * @param int $width
     * @param int $height
     * @return bool
     */
    private function checkRange($width, $height)
    {
        return $this->imSrcWidth >= $width && $this->imSrcHeight >= $height;
    }

    /**
     * 解析文字颜色。
     *
     * @param string $color
     * @return array
     */
    private function parseColor($color)
    {
        $parts = array_map('trim', explode(',', $color));
        if (3 === count($parts)) {
            return array(
                'r' => min(255, max(0, (int) $parts[0])),
                'g' => min(255, max(0, (int) $parts[1])),
                'b' => min(255, max(0, (int) $parts[2]))
            );
        }

        $hex = ltrim($color, '#');
        if (3 === strlen($hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[a-f0-9]{6}$/i', $hex)) {
            $hex = 'ff0000';
        }

        return array(
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        );
    }

    /**
     * 合并带透明度的图片水印。
     *
     * @param mixed $destination
     * @param mixed $source
     * @param int $destinationX
     * @param int $destinationY
     * @param int $width
     * @param int $height
     * @param int $transparency
     */
    private function copyWithAlpha(
        $destination,
        $source,
        $destinationX,
        $destinationY,
        $width,
        $height,
        $transparency
    ) {
        if ($transparency >= 100) {
            return;
        }

        $layer = imagecreatetruecolor($width, $height);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        $clear = imagecolorallocatealpha($layer, 0, 0, 0, 127);
        imagefill($layer, 0, 0, $clear);
        imagecopy($layer, $source, 0, 0, 0, 0, $width, $height);

        $opacity = (100 - $transparency) / 100;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorsforindex($layer, imagecolorat($layer, $x, $y));
                $alpha = 127 - (int) round((127 - $color['alpha']) * $opacity);
                imagesetpixel(
                    $layer,
                    $x,
                    $y,
                    imagecolorallocatealpha(
                        $layer,
                        $color['red'],
                        $color['green'],
                        $color['blue'],
                        $alpha
                    )
                );
            }
        }

        imagealphablending($destination, true);
        imagecopy(
            $destination,
            $layer,
            $destinationX,
            $destinationY,
            0,
            0,
            $width,
            $height
        );
        imagedestroy($layer);
    }

    /**
     * 按最大宽度缩小原图。
     *
     * @param int $maxWidth
     */
    private function resize($maxWidth)
    {
        $width = imagesx($this->srcImage);
        $height = imagesy($this->srcImage);
        if ($width <= $maxWidth) {
            return;
        }

        $ratio = $maxWidth / $width;
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        $preserveAlpha = in_array($this->type, array(IMAGETYPE_PNG, IMAGETYPE_WEBP), true)
            || null !== $this->gifTransparentColor;
        if ($preserveAlpha) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $color = null !== $this->gifTransparentColor
                ? $this->gifTransparentColor
                : array('red' => 0, 'green' => 0, 'blue' => 0);
            $transparent = imagecolorallocatealpha(
                $resized,
                $color['red'],
                $color['green'],
                $color['blue'],
                127
            );
            imagefill($resized, 0, 0, $transparent);
        }

        imagecopyresampled(
            $resized,
            $this->srcImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );
        if ($preserveAlpha) {
            imagealphablending($resized, true);
        }
        imagedestroy($this->srcImage);
        $this->srcImage = $resized;
        $this->imSrcWidth = $newWidth;
        $this->imSrcHeight = $newHeight;
    }

    /**
     * 将 GIF 转为真彩图，避免调色板已满时水印颜色分配失败。
     *
     * @param mixed $source
     * @return mixed
     */
    private function convertGifToTruecolor($source)
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $converted = imagecreatetruecolor($width, $height);
        if (!$this->isImage($converted)) {
            return false;
        }

        $transparentIndex = imagecolortransparent($source);
        if ($transparentIndex >= 0) {
            $this->gifTransparentColor = imagecolorsforindex($source, $transparentIndex);
            imagealphablending($converted, false);
            imagesavealpha($converted, true);
            $clear = imagecolorallocatealpha(
                $converted,
                $this->gifTransparentColor['red'],
                $this->gifTransparentColor['green'],
                $this->gifTransparentColor['blue'],
                127
            );
            imagefill($converted, 0, 0, $clear);
        }

        if (!imagecopy($converted, $source, 0, 0, 0, 0, $width, $height)) {
            imagedestroy($converted);
            return false;
        }
        if (null !== $this->gifTransparentColor) {
            imagealphablending($converted, true);
        }

        return $converted;
    }

    /**
     * 在所有水印绘制完成后量化 GIF，并恢复透明索引。
     *
     * @return bool
     */
    private function prepareGifForOutput()
    {
        if (!imageistruecolor($this->srcImage)) {
            return true;
        }

        $mask = null;
        if (null !== $this->gifTransparentColor) {
            $width = imagesx($this->srcImage);
            $height = imagesy($this->srcImage);
            $mask = imagecreate($width, $height);
            if (!$this->isImage($mask)) {
                return false;
            }

            $opaqueMask = imagecolorallocate($mask, 0, 0, 0);
            $transparentMask = imagecolorallocate($mask, 255, 255, 255);
            imagefill($mask, 0, 0, $opaqueMask);
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $color = imagecolorat($this->srcImage, $x, $y);
                    if ((($color >> 24) & 0x7F) >= 64) {
                        imagesetpixel($mask, $x, $y, $transparentMask);
                    }
                }
            }
        }

        $colors = null !== $mask ? 255 : 256;
        if (!imagetruecolortopalette($this->srcImage, true, $colors)) {
            if (null !== $mask) {
                imagedestroy($mask);
            }
            return false;
        }
        if (null === $mask) {
            return true;
        }

        $transparent = $this->allocateGifTransparentColor();
        if (false === $transparent) {
            imagedestroy($mask);
            return false;
        }

        $width = imagesx($this->srcImage);
        $height = imagesy($this->srcImage);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (imagecolorat($mask, $x, $y) === $transparentMask) {
                    imagesetpixel($this->srcImage, $x, $y, $transparent);
                }
            }
        }
        imagecolortransparent($this->srcImage, $transparent);
        imagedestroy($mask);

        return true;
    }

    /**
     * 为 GIF 透明色分配未被占用的调色板索引。
     *
     * @return int|false
     */
    private function allocateGifTransparentColor()
    {
        $color = $this->gifTransparentColor;
        if (
            imagecolorexact(
                $this->srcImage,
                $color['red'],
                $color['green'],
                $color['blue']
            ) < 0
        ) {
            return imagecolorallocate(
                $this->srcImage,
                $color['red'],
                $color['green'],
                $color['blue']
            );
        }

        for ($value = 0; $value <= 255; $value++) {
            if (imagecolorexact($this->srcImage, $value, $value, $value) < 0) {
                return imagecolorallocate($this->srcImage, $value, $value, $value);
            }
        }

        return false;
    }

    /**
     * PHP 7 资源与 PHP 8 GdImage 兼容判断。
     *
     * @param mixed $image
     * @return bool
     */
    private function isImage($image)
    {
        return is_resource($image) || is_object($image);
    }
}
