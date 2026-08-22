<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 原图保护存储。
 *
 * 私有目录按 usr/uploads 后的相对路径镜像保存原图，公开目录只保存派生图。
 *
 * @author NHPT
 * @copyright Copyright (c) 2026 NHPT
 * @license GNU General Public License 2.0
 * @link https://github.com/NHPT/Watermark
 */
class Watermark_Protection
{
    const STORE_FORMAT = 1;
    const TASK_FORMAT = 1;
    const STORE_MARKER = '.watermark-store.json';
    const RELOCATION_MARKER = '.watermark-relocation.json';
    const TASK_PREFIX = '.watermark-task-';
    const METADATA_SUFFIX = '.watermark.json';
    const LOCK_SUFFIX = '.watermark.lock';

    private static $lastError = '';
    private static $managed = array();

    /**
     * 是否启用原图保护模式。
     *
     * @param object $config
     * @return bool
     */
    public static function enabled($config)
    {
        return 'protected' === Watermark_Plugin::configValue(
            $config,
            'vm_mode',
            'dynamic'
        );
    }

    /**
     * 获取最后一次错误。
     *
     * @return string
     */
    public static function lastError()
    {
        return self::$lastError;
    }

    /**
     * 获取默认私有目录。
     *
     * @return string
     */
    public static function defaultDirectory()
    {
        $root = realpath(__TYPECHO_ROOT_DIR__);
        $root = false === $root ? __TYPECHO_ROOT_DIR__ : $root;

        return dirname($root) . DIRECTORY_SEPARATOR . '.typecho-watermark';
    }

    /**
     * 获取配置的私有目录。
     *
     * @param object $config
     * @return string
     */
    public static function configuredDirectory($config)
    {
        if (defined('__TYPECHO_WATERMARK_PRIVATE_DIR__')) {
            return rtrim((string) constant('__TYPECHO_WATERMARK_PRIVATE_DIR__'), '/\\');
        }

        $directory = trim((string) Watermark_Plugin::configValue(
            $config,
            'vm_private_dir',
            self::defaultDirectory()
        ));

        return rtrim($directory, '/\\');
    }

    /**
     * 校验并初始化私有目录。
     *
     * @param object $config
     * @return string|false
     */
    public static function ensureStore($config)
    {
        self::$lastError = '';
        $directory = self::configuredDirectory($config);
        if (!self::isAbsolutePath($directory) || self::containsParentTraversal($directory)) {
            return self::fail('私有原图目录必须是绝对路径，且不能包含 ..');
        }
        if (!self::isAllowedByOpenBaseDir($directory)) {
            return self::fail(
                'PHP open_basedir 禁止访问私有原图目录：' . $directory
                . '。当前允许路径：' . self::openBaseDirSetting()
                . '。请在当前 PHP 运行环境的生效配置中加入私有目录，并使配置重新生效'
            );
        }

        $publicRoots = array(__TYPECHO_ROOT_DIR__);
        if (defined('__TYPECHO_UPLOAD_ROOT_DIR__')) {
            $publicRoots[] = constant('__TYPECHO_UPLOAD_ROOT_DIR__');
        }
        $resolvedPublicRoots = array();
        foreach ($publicRoots as $publicRoot) {
            $publicRoot = @realpath($publicRoot);
            if (false === $publicRoot) {
                continue;
            }
            $resolvedPublicRoots[] = $publicRoot;
            if (self::isWithin($directory, $publicRoot)) {
                return self::fail('私有原图目录必须位于网站公开根目录之外');
            }
        }

        if (
            !@is_dir($directory)
            && !@mkdir($directory, 0700, true)
            && !@is_dir($directory)
        ) {
            return self::fail(
                '无法创建私有原图目录：' . $directory
                . '。请确认父目录权限以及当前 Web/PHP 服务账户的写权限'
            );
        }

        $resolved = @realpath($directory);
        if (false === $resolved || !@is_dir($resolved)) {
            return self::fail('无法解析私有原图目录');
        }
        foreach ($resolvedPublicRoots as $publicRoot) {
            if (self::isWithin($resolved, $publicRoot)) {
                return self::fail('私有原图目录必须位于网站公开根目录之外');
            }
        }

        $marker = $resolved . DIRECTORY_SEPARATOR . self::STORE_MARKER;
        if (!is_file($marker)) {
            $entries = @scandir($resolved);
            if (false === $entries || array_diff($entries, array('.', '..'))) {
                return self::fail('私有目录非空且缺少 Watermark 存储标记');
            }
            if (!self::writeJsonAtomic($marker, array(
                'format' => self::STORE_FORMAT,
                'createdAt' => gmdate('c')
            ), 0600)) {
                return self::fail('无法初始化私有原图目录');
            }
        }

        $metadata = json_decode((string) @file_get_contents($marker), true);
        if (
            !is_array($metadata)
            || !isset($metadata['format'])
            || self::STORE_FORMAT !== (int) $metadata['format']
        ) {
            return self::fail('私有原图目录格式不受支持');
        }
        if (!@is_writable($resolved)) {
            return self::fail('私有原图目录不可写');
        }

        return $resolved;
    }

    /**
     * 获取 PHP open_basedir 原始设置。
     *
     * @return string
     */
    public static function openBaseDirSetting()
    {
        $setting = trim((string) ini_get('open_basedir'));
        return '' === $setting ? '未限制' : $setting;
    }

    /**
     * 生成包含私有目录的 open_basedir 建议值。
     *
     * @param string $directory
     * @return string
     */
    public static function recommendedOpenBaseDir($directory)
    {
        $setting = trim((string) ini_get('open_basedir'));
        if ('' === $setting) {
            return $directory;
        }
        if (self::isAllowedByOpenBaseDir($directory)) {
            return $setting;
        }

        $paths = array_values(array_filter(
            explode(PATH_SEPARATOR, $setting),
            function ($path) {
                return '' !== trim($path);
            }
        ));
        array_splice($paths, max(0, count($paths) - 1), 0, array(
            rtrim($directory, '/\\') . DIRECTORY_SEPARATOR
        ));

        return implode(PATH_SEPARATOR, $paths);
    }

    /**
     * 判断图片是否已有可信的私有原图。
     *
     * @param string $relativePath
     * @param object $config
     * @return bool
     */
    public static function isManaged($relativePath, $config)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return false;
        }

        $cacheKey = self::configuredDirectory($config) . '|' . $relativePath;
        if (array_key_exists($cacheKey, self::$managed)) {
            return self::$managed[$cacheKey];
        }

        $private = self::privatePath($relativePath, $config, false);
        if (false === $private || !is_file($private)) {
            return self::$managed[$cacheKey] = false;
        }

        $metadata = self::readMetadata($private);
        $managed = is_array($metadata)
            && isset($metadata['relative'], $metadata['originalSha256'], $metadata['state'])
            && $relativePath === $metadata['relative']
            && preg_match('/^[a-f0-9]{64}$/', $metadata['originalSha256'])
            && 'protected' === $metadata['state'];

        return self::$managed[$cacheKey] = $managed;
    }

    /**
     * 获取公开水印成品的缓存版本。
     *
     * @param string $relativePath
     * @param object $config
     * @return string
     */
    public static function publicVersion($relativePath, $config)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return '';
        }

        $private = self::privatePath($relativePath, $config, false);
        $metadata = false !== $private && is_file($private)
            ? self::readMetadata($private)
            : false;
        if (
            !is_array($metadata)
            || !isset($metadata['relative'], $metadata['state'], $metadata['publicSha256'])
            || $relativePath !== $metadata['relative']
            || 'protected' !== $metadata['state']
            || !preg_match('/^[a-f0-9]{64}$/', $metadata['publicSha256'])
        ) {
            return '';
        }

        return substr($metadata['publicSha256'], 0, 16);
    }

    /**
     * 捕获刚上传的原图并生成公开派生图。
     *
     * @param string $relativePath
     * @param object $config
     * @param bool $reuseExisting
     * @return array
     */
    public static function captureAndProtect($relativePath, $config, $reuseExisting = false)
    {
        self::$lastError = '';
        $source = Watermark_Plugin::resolveImagePath($relativePath);
        if (false === $source) {
            return self::result(false, 'error', '上传文件不是受支持的本地图片');
        }
        if (Watermark_Plugin::isAnimatedGif($source['absolute'])) {
            return self::result(false, 'error', '原图保护模式不支持动态 GIF');
        }

        $store = self::ensureStore($config);
        if (false === $store) {
            return self::result(false, 'error', self::$lastError);
        }
        $private = self::privatePath($source['relative'], $config, true);
        if (false === $private) {
            return self::result(false, 'error', self::$lastError);
        }

        $lock = self::lock($private);
        if (false === $lock) {
            return self::result(false, 'error', self::$lastError);
        }

        if ($reuseExisting && is_file($private)) {
            $result = self::isCurrent($source['relative'], $private, $config)
                ? self::result(true, 'skipped', '水印成品与当前配置一致')
                : self::renderPrivateToPublic($source['relative'], $private, $config);
            self::invalidateManagedCache($source['relative'], $config);
            self::unlock($lock);
            return $result;
        }

        $hasOldPrivate = is_file($private);
        $oldPrivate = $hasOldPrivate
            ? self::temporaryPath(dirname($private), '.previous-')
            : false;
        if ($hasOldPrivate && false === $oldPrivate) {
            self::unlock($lock);
            return self::result(false, 'error', '无法创建私有原图备份');
        }
        $staged = self::temporaryPath(dirname($private), '.original-');
        if (false === $staged || !self::copyVerified($source['absolute'], $staged, 0600)) {
            if (false !== $staged) {
                @unlink($staged);
            }
            if (false !== $oldPrivate) {
                @unlink($oldPrivate);
            }
            self::unlock($lock);
            return self::result(false, 'error', '无法保存私有原图');
        }

        if (false !== $oldPrivate) {
            @unlink($oldPrivate);
            if (!@rename($private, $oldPrivate)) {
                @unlink($staged);
                self::unlock($lock);
                return self::result(false, 'error', '无法备份已有私有原图');
            }
        }

        if (!@rename($staged, $private)) {
            if (false !== $oldPrivate) {
                @rename($oldPrivate, $private);
            }
            @unlink($staged);
            self::unlock($lock);
            return self::result(false, 'error', '无法提交私有原图');
        }

        if (!@unlink($source['absolute'])) {
            @unlink($private);
            if (false !== $oldPrivate) {
                @rename($oldPrivate, $private);
            }
            self::unlock($lock);
            return self::result(false, 'error', '无法移除公开目录中的原图');
        }

        $result = self::renderPrivateToPublic($source['relative'], $private, $config);
        if (empty($result['success'])) {
            if (false !== $oldPrivate) {
                @unlink($private);
                @rename($oldPrivate, $private);
                self::renderPrivateToPublic($source['relative'], $private, $config);
            } else {
                self::copyVerified($private, $source['absolute'], 0644);
                @unlink($private);
                @unlink(self::metadataPath($private));
            }
            self::invalidateManagedCache($source['relative'], $config);
            self::unlock($lock);
            return $result;
        }

        if (false !== $oldPrivate) {
            @unlink($oldPrivate);
        }
        self::invalidateManagedCache($source['relative'], $config);
        self::unlock($lock);

        return $result;
    }

    /**
     * 迁移或重新生成一张公开图片。
     *
     * @param string $relativePath
     * @param object $config
     * @return array
     */
    public static function protectExisting($relativePath, $config)
    {
        self::$lastError = '';
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return self::result(false, 'error', '上传路径无效');
        }

        $private = self::privatePath($relativePath, $config, true);
        if (false === $private) {
            return self::result(false, 'error', self::$lastError);
        }
        if (!is_file($private)) {
            return self::captureAndProtect($relativePath, $config, true);
        }

        $lock = self::lock($private);
        if (false === $lock) {
            return self::result(false, 'error', self::$lastError);
        }
        if (self::isCurrent($relativePath, $private, $config)) {
            self::unlock($lock);
            return self::result(true, 'skipped', '水印成品与当前配置一致');
        }
        $result = self::renderPrivateToPublic($relativePath, $private, $config);
        self::invalidateManagedCache($relativePath, $config);
        self::unlock($lock);

        return $result;
    }

    /**
     * 将一张私有原图恢复到公开路径，保留私有副本。
     *
     * @param string $relativePath
     * @param object $config
     * @return array
     */
    public static function restore($relativePath, $config)
    {
        self::$lastError = '';
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return self::result(false, 'error', '上传路径无效');
        }

        $private = self::privatePath($relativePath, $config, false);
        $public = self::publicPath($relativePath, true);
        if (false === $private || !is_file($private)) {
            return self::result(true, 'skipped', '没有对应的私有原图');
        }
        if (false === $public) {
            return self::result(false, 'error', self::$lastError);
        }

        $lock = self::lock($private);
        if (false === $lock) {
            return self::result(false, 'error', self::$lastError);
        }
        if (!self::copyVerified($private, $public, 0644)) {
            self::unlock($lock);
            return self::result(false, 'error', '恢复公开原图失败');
        }

        $metadata = self::buildMetadata($relativePath, $private, $public, $config, 'restored');
        if (!self::writeMetadata($private, $metadata)) {
            self::unlock($lock);
            return self::result(false, 'error', '原图已恢复，但状态元数据写入失败');
        }

        self::invalidateManagedCache($relativePath, $config);
        self::unlock($lock);

        return self::result(true, 'restored', '原图已恢复');
    }

    /**
     * 删除附件对应的私有原图与状态文件。
     *
     * @param string $relativePath
     * @param object $config
     * @return void
     */
    public static function remove($relativePath, $config)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return;
        }

        $private = self::privatePath($relativePath, $config, false);
        if (false === $private) {
            return;
        }
        @unlink($private);
        @unlink(self::metadataPath($private));
        self::invalidateManagedCache($relativePath, $config);
    }

    /**
     * 获取公开上传目录中的图片路径。
     *
     * @return array
     */
    public static function publicImages()
    {
        $root = realpath(__TYPECHO_ROOT_DIR__ . '/' . Watermark_Plugin::UPLOAD_RELATIVE_DIR);
        if (false === $root) {
            return array();
        }

        return self::listImages($root, '/' . Watermark_Plugin::UPLOAD_RELATIVE_DIR);
    }

    /**
     * 获取私有目录中的原图路径。
     *
     * @param object $config
     * @return array
     */
    public static function privateImages($config)
    {
        $root = self::ensureStore($config);
        if (false === $root) {
            return array();
        }

        return self::listImages($root, '/' . Watermark_Plugin::UPLOAD_RELATIVE_DIR);
    }

    /**
     * 返回图片在保护任务中的状态。
     *
     * @param string $relativePath
     * @param object $config
     * @return string
     */
    public static function protectionState($relativePath, $config)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return 'error';
        }

        $private = self::privatePath($relativePath, $config, true);
        if (false === $private) {
            return 'error';
        }
        if (!is_file($private)) {
            return 'migrate';
        }

        return self::isCurrent($relativePath, $private, $config)
            ? 'current'
            : 'regenerate';
    }

    /**
     * 返回图片在公开原图恢复任务中的状态。
     *
     * @param string $relativePath
     * @param object $config
     * @return string
     */
    public static function restorationState($relativePath, $config)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return 'error';
        }

        $private = self::privatePath($relativePath, $config, false);
        if (false === $private || !is_file($private)) {
            return 'error';
        }
        $public = self::publicPath($relativePath);
        if (false === $public || !is_file($public)) {
            return 'restore';
        }

        $privateHash = @hash_file('sha256', $private);
        $publicHash = @hash_file('sha256', $public);

        return false !== $privateHash
            && false !== $publicHash
            && hash_equals($privateHash, $publicHash)
            ? 'current'
            : 'restore';
    }

    /**
     * 返回私有原图在目录迁移任务中的状态。
     *
     * @param string $relativePath
     * @param object $config
     * @param string $targetDirectory
     * @return string
     */
    public static function relocationState($relativePath, $config, $targetDirectory)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return 'error';
        }

        $source = self::privatePath($relativePath, $config, false);
        $targetConfig = self::configWithDirectory($config, $targetDirectory);
        $target = self::privatePath($relativePath, $targetConfig, true);
        if (false === $source || !is_file($source) || false === $target) {
            return 'error';
        }
        if (!is_file($target) || !is_file(self::metadataPath($target))) {
            return 'copy';
        }

        $sourceHash = @hash_file('sha256', $source);
        $targetHash = @hash_file('sha256', $target);
        $sourceMetadataHash = @hash_file('sha256', self::metadataPath($source));
        $targetMetadataHash = @hash_file('sha256', self::metadataPath($target));

        return false !== $sourceHash
            && false !== $targetHash
            && false !== $sourceMetadataHash
            && false !== $targetMetadataHash
            && hash_equals($sourceHash, $targetHash)
            && hash_equals($sourceMetadataHash, $targetMetadataHash)
            ? 'current'
            : 'copy';
    }

    /**
     * 读取持久化管理任务。
     *
     * @param object $config
     * @param string $operation
     * @return array|false
     */
    public static function loadTask($config, $operation)
    {
        $path = self::taskPath($config, $operation);
        if (false === $path || !is_file($path)) {
            return false;
        }

        $task = json_decode((string) @file_get_contents($path), true);
        return is_array($task)
            && isset($task['format'], $task['operation'])
            && self::TASK_FORMAT === (int) $task['format']
            && $operation === $task['operation']
            ? $task
            : false;
    }

    /**
     * 原子保存管理任务。
     *
     * @param object $config
     * @param string $operation
     * @param array $task
     * @return bool
     */
    public static function saveTask($config, $operation, array $task)
    {
        $path = self::taskPath($config, $operation);
        if (false === $path) {
            return false;
        }

        $task['format'] = self::TASK_FORMAT;
        $task['operation'] = $operation;
        $task['updatedAt'] = gmdate('c');

        return self::writeJsonAtomic($path, $task, 0600);
    }

    /**
     * 删除指定管理任务。
     *
     * @param object $config
     * @param string $operation
     * @return void
     */
    public static function deleteTask($config, $operation)
    {
        $path = self::taskPath($config, $operation);
        if (false !== $path) {
            @unlink($path);
        }
    }

    /**
     * 获取管理任务独占锁。
     *
     * @param object $config
     * @param string $operation
     * @return resource|false
     */
    public static function lockTask($config, $operation)
    {
        $path = self::taskPath($config, $operation);
        if (false === $path) {
            return false;
        }

        $handle = @fopen($path . self::LOCK_SUFFIX, 'c');
        if (!$handle || !@flock($handle, LOCK_EX | LOCK_NB)) {
            if ($handle) {
                @fclose($handle);
            }
            return self::fail('无法锁定批处理任务');
        }

        return $handle;
    }

    /**
     * 释放管理任务锁。
     *
     * @param resource $handle
     * @return void
     */
    public static function unlockTask($handle)
    {
        self::unlock($handle);
    }

    /**
     * 返回私有存储状态。
     *
     * @param object $config
     * @return array
     */
    public static function status($config)
    {
        $directory = self::ensureStore($config);

        return array(
            'ready' => false !== $directory,
            'directory' => self::configuredDirectory($config),
            'error' => false === $directory ? self::$lastError : ''
        );
    }

    /**
     * 判断私有存储中是否已有原图。
     *
     * @param object $config
     * @return bool
     */
    public static function hasOriginals($config)
    {
        return (bool) self::privateImages($config);
    }

    /**
     * 判断两个目录配置是否指向同一路径。
     *
     * @param string $first
     * @param string $second
     * @return bool
     */
    public static function sameDirectory($first, $second)
    {
        $first = self::normalizeAbsolutePath($first);
        $second = self::normalizeAbsolutePath($second);

        return false !== $first && false !== $second && $first === $second;
    }

    /**
     * 初始化可恢复的私有目录迁移。
     *
     * @param object $config
     * @param string $targetDirectory
     * @return array
     */
    public static function prepareRelocation($config, $targetDirectory)
    {
        self::$lastError = '';
        $source = self::ensureStore($config);
        if (false === $source) {
            return self::result(false, 'error', self::$lastError);
        }
        if (self::sameDirectory($source, $targetDirectory)) {
            return self::result(false, 'error', '新目录与当前私有目录相同');
        }

        $targetConfig = self::configWithDirectory($config, $targetDirectory);
        $target = self::ensureStore($targetConfig);
        if (false === $target) {
            return self::result(false, 'error', self::$lastError);
        }

        $relocationFile = $target . DIRECTORY_SEPARATOR . self::RELOCATION_MARKER;
        $relocation = is_file($relocationFile)
            ? json_decode((string) @file_get_contents($relocationFile), true)
            : false;
        if (is_array($relocation)) {
            if (
                empty($relocation['source'])
                || !self::sameDirectory($relocation['source'], $source)
            ) {
                return self::result(false, 'error', '目标目录属于另一项未完成的迁移');
            }
        } else {
            $sourcePaths = self::privateImages($config);
            $targetPaths = self::privateImages($targetConfig);
            if (array_diff($targetPaths, $sourcePaths)) {
                return self::result(false, 'error', '目标目录包含当前源目录中不存在的原图');
            }
            foreach ($targetPaths as $relativePath) {
                $sourceFile = self::privatePath($relativePath, $config, false);
                $targetFile = self::privatePath($relativePath, $targetConfig, false);
                $sourceHash = false !== $sourceFile
                    ? @hash_file('sha256', $sourceFile)
                    : false;
                $targetHash = false !== $targetFile
                    ? @hash_file('sha256', $targetFile)
                    : false;
                if (
                    false === $sourceHash
                    || false === $targetHash
                    || !hash_equals($sourceHash, $targetHash)
                ) {
                    return self::result(false, 'error', '目标目录存在内容冲突：' . $relativePath);
                }
            }
            if (!self::writeJsonAtomic($relocationFile, array(
                'format' => self::STORE_FORMAT,
                'source' => $source,
                'createdAt' => gmdate('c')
            ), 0600)) {
                return self::result(false, 'error', '无法初始化目录迁移状态');
            }
        }

        return self::result(true, 'ready', '目标私有目录已就绪');
    }

    /**
     * 将一张私有原图和元数据复制到迁移目标。
     *
     * @param string $relativePath
     * @param object $config
     * @param string $targetDirectory
     * @return array
     */
    public static function relocateOriginal($relativePath, $config, $targetDirectory)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return self::result(false, 'error', '上传路径无效');
        }

        $source = self::privatePath($relativePath, $config, false);
        $targetConfig = self::configWithDirectory($config, $targetDirectory);
        $target = self::privatePath($relativePath, $targetConfig, true);
        if (false === $source || !is_file($source) || false === $target) {
            return self::result(false, 'error', self::$lastError ?: '私有原图不存在');
        }

        $lock = self::lock($source);
        if (false === $lock) {
            return self::result(false, 'error', self::$lastError);
        }
        try {
            $metadata = self::readMetadata($source);
            if (
                !is_array($metadata)
                || empty($metadata['originalSha256'])
                || $relativePath !== $metadata['relative']
            ) {
                return self::result(false, 'error', '源原图元数据缺失或无效');
            }

            $sourceHash = @hash_file('sha256', $source);
            if (
                false === $sourceHash
                || !hash_equals($metadata['originalSha256'], $sourceHash)
            ) {
                return self::result(false, 'error', '源原图 SHA-256 校验失败');
            }

            if (is_file($target)) {
                $targetHash = @hash_file('sha256', $target);
                if (false === $targetHash || !hash_equals($sourceHash, $targetHash)) {
                    return self::result(false, 'error', '目标目录存在同名但内容不同的原图');
                }
            } elseif (!self::copyVerified($source, $target, 0600)) {
                return self::result(false, 'error', '复制私有原图失败');
            }

            if (
                !self::copyVerified(
                    self::metadataPath($source),
                    self::metadataPath($target),
                    0600
                )
            ) {
                @unlink($target);
                return self::result(false, 'error', '复制原图元数据失败');
            }

            $targetHash = @hash_file('sha256', $target);
            if (false === $targetHash || !hash_equals($sourceHash, $targetHash)) {
                @unlink($target);
                @unlink(self::metadataPath($target));
                return self::result(false, 'error', '迁移后 SHA-256 校验失败');
            }

            return self::result(true, 'copied', '原图和元数据已复制并校验');
        } finally {
            self::unlock($lock);
        }
    }

    /**
     * 验证迁移目标完整性并结束迁移状态。
     *
     * @param object $config
     * @param string $targetDirectory
     * @return array
     */
    public static function finishRelocation($config, $targetDirectory)
    {
        $prepared = self::prepareRelocation($config, $targetDirectory);
        if (empty($prepared['success'])) {
            return $prepared;
        }

        $sourcePaths = self::privateImages($config);
        $targetConfig = self::configWithDirectory($config, $targetDirectory);
        $targetPaths = self::privateImages($targetConfig);
        sort($sourcePaths, SORT_STRING);
        sort($targetPaths, SORT_STRING);
        if ($sourcePaths !== $targetPaths) {
            return self::result(false, 'error', '目标目录的原图清单与源目录不一致');
        }

        foreach ($sourcePaths as $relativePath) {
            $source = self::privatePath($relativePath, $config, false);
            $target = self::privatePath($relativePath, $targetConfig, false);
            $sourceHash = false !== $source ? @hash_file('sha256', $source) : false;
            $targetHash = false !== $target ? @hash_file('sha256', $target) : false;
            if (
                false === $sourceHash
                || false === $targetHash
                || !hash_equals($sourceHash, $targetHash)
                || !is_file(self::metadataPath($target))
            ) {
                return self::result(false, 'error', '目标目录完整性校验失败：' . $relativePath);
            }
        }

        if (false === self::ensureStore($targetConfig)) {
            return self::result(false, 'error', self::$lastError);
        }

        return self::result(true, 'complete', '全部私有原图和元数据已校验');
    }

    /**
     * 配置切换成功后清理目标目录的迁移状态。
     *
     * @param string $targetDirectory
     * @return void
     */
    public static function completeRelocation($targetDirectory)
    {
        $targetDirectory = @realpath($targetDirectory);
        if (false !== $targetDirectory) {
            @unlink($targetDirectory . DIRECTORY_SEPARATOR . self::RELOCATION_MARKER);
        }
    }

    /**
     * 从私有原图生成公开文件。
     *
     * @param string $relativePath
     * @param string $private
     * @param object $config
     * @return array
     */
    private static function renderPrivateToPublic($relativePath, $private, $config)
    {
        $source = self::sourceInfo($relativePath, $private);
        if (false === $source) {
            return self::result(false, 'error', '私有原图不是受支持的图片');
        }
        if (Watermark_Plugin::isAnimatedGif($private)) {
            return self::result(false, 'error', '原图保护模式不支持动态 GIF');
        }

        $public = self::publicPath($relativePath, true);
        if (false === $public) {
            return self::result(false, 'error', self::$lastError);
        }

        if (!Watermark_Plugin::isImageEligible($source, $config)) {
            if (!self::copyVerified($private, $public, 0644)) {
                return self::result(false, 'error', '写入排除图片失败');
            }
            $metadata = self::buildMetadata(
                $relativePath,
                $private,
                $public,
                $config,
                'excluded'
            );
            if (!self::writeMetadata($private, $metadata)) {
                @unlink($public);
                return self::result(false, 'error', '写入排除图片状态失败');
            }

            return self::result(true, 'excluded', '图片按排除或尺寸规则保留原样');
        }

        if (!Watermark_Plugin::renderWatermarkFile($private, $public, $config)) {
            @unlink($public);
            return self::result(false, 'error', Watermark_Plugin::lastRenderError());
        }

        $metadata = self::buildMetadata(
            $relativePath,
            $private,
            $public,
            $config,
            'protected'
        );
        if (!self::writeMetadata($private, $metadata)) {
            @unlink($public);
            return self::result(false, 'error', '水印已生成，但状态元数据写入失败');
        }

        return self::result(true, 'protected', '水印图已生成');
    }

    /**
     * 复制配置并替换私有目录。
     *
     * @param object $config
     * @param string $directory
     * @return object
     */
    private static function configWithDirectory($config, $directory)
    {
        $copy = new stdClass();
        if ($config instanceof Traversable) {
            foreach ($config as $name => $value) {
                $copy->{$name} = $value;
            }
        } elseif (is_object($config)) {
            foreach (get_object_vars($config) as $name => $value) {
                $copy->{$name} = $value;
            }
        }
        $copy->vm_private_dir = $directory;

        return $copy;
    }

    /**
     * 构造状态元数据。
     *
     * @param string $relativePath
     * @param string $private
     * @param string $public
     * @param object $config
     * @param string $state
     * @return array
     */
    private static function buildMetadata($relativePath, $private, $public, $config, $state)
    {
        return array(
            'format' => self::STORE_FORMAT,
            'relative' => $relativePath,
            'state' => $state,
            'originalSha256' => (string) @hash_file('sha256', $private),
            'publicSha256' => (string) @hash_file('sha256', $public),
            'configSha256' => Watermark_Plugin::renderConfigFingerprint($config),
            'processorVersion' => Watermark_Plugin::VERSION,
            'originalSize' => (int) @filesize($private),
            'publicSize' => (int) @filesize($public),
            'updatedAt' => gmdate('c')
        );
    }

    /**
     * 读取状态元数据。
     *
     * @param string $private
     * @return array|false
     */
    private static function readMetadata($private)
    {
        $content = @file_get_contents(self::metadataPath($private));
        if (false === $content) {
            return false;
        }

        $metadata = json_decode($content, true);
        return is_array($metadata)
            && isset($metadata['format'])
            && self::STORE_FORMAT === (int) $metadata['format']
            ? $metadata
            : false;
    }

    /**
     * 判断公开成品、私有原图和当前配置是否完全一致。
     *
     * @param string $relativePath
     * @param string $private
     * @param object $config
     * @return bool
     */
    private static function isCurrent($relativePath, $private, $config)
    {
        $metadata = self::readMetadata($private);
        $public = self::publicPath($relativePath);
        if (
            !is_array($metadata)
            || false === $public
            || !is_file($public)
            || !isset(
                $metadata['relative'],
                $metadata['state'],
                $metadata['originalSha256'],
                $metadata['publicSha256'],
                $metadata['configSha256'],
                $metadata['processorVersion']
            )
            || $relativePath !== $metadata['relative']
            || !in_array($metadata['state'], array('protected', 'excluded'), true)
            || Watermark_Plugin::VERSION !== $metadata['processorVersion']
            || Watermark_Plugin::renderConfigFingerprint($config)
                !== $metadata['configSha256']
        ) {
            return false;
        }

        $originalHash = @hash_file('sha256', $private);
        $publicHash = @hash_file('sha256', $public);

        return false !== $originalHash
            && false !== $publicHash
            && hash_equals($metadata['originalSha256'], $originalHash)
            && hash_equals($metadata['publicSha256'], $publicHash);
    }

    /**
     * 写入状态元数据。
     *
     * @param string $private
     * @param array $metadata
     * @return bool
     */
    private static function writeMetadata($private, array $metadata)
    {
        if (
            empty($metadata['originalSha256'])
            || empty($metadata['publicSha256'])
        ) {
            return false;
        }

        return self::writeJsonAtomic(self::metadataPath($private), $metadata, 0600);
    }

    /**
     * 获取私有原图路径。
     *
     * @param string $relativePath
     * @param object $config
     * @param bool $createDirectory
     * @return string|false
     */
    private static function privatePath($relativePath, $config, $createDirectory)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return self::fail('上传路径无效');
        }

        $configured = self::configuredDirectory($config);
        if (!self::isAllowedByOpenBaseDir($configured)) {
            return self::fail(
                'PHP open_basedir 禁止访问私有原图目录：' . $configured
                . '。当前允许路径：' . self::openBaseDirSetting()
            );
        }

        $store = $createDirectory
            ? self::ensureStore($config)
            : @realpath($configured);
        if (false === $store) {
            return self::fail('私有原图目录不可用');
        }

        $uploadPrefix = '/' . Watermark_Plugin::UPLOAD_RELATIVE_DIR . '/';
        $suffix = substr($relativePath, strlen($uploadPrefix));
        $path = $store . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $suffix);
        $directory = dirname($path);
        if (
            $createDirectory
            && !is_dir($directory)
            && !@mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            return self::fail('无法创建私有原图子目录');
        }

        $resolvedDirectory = realpath($directory);
        if (false === $resolvedDirectory || !self::isWithin($resolvedDirectory, $store)) {
            return self::fail('私有原图路径越界');
        }
        if (is_link($path)) {
            return self::fail('私有原图不能是符号链接');
        }

        return $resolvedDirectory . DIRECTORY_SEPARATOR . basename($path);
    }

    /**
     * 获取公开图片路径，允许目标文件尚不存在。
     *
     * @param string $relativePath
     * @param bool $createDirectory
     * @return string|false
     */
    private static function publicPath($relativePath, $createDirectory = false)
    {
        $relativePath = self::normalizeRelativePath($relativePath);
        if (false === $relativePath) {
            return self::fail('上传路径无效');
        }

        $root = realpath(__TYPECHO_ROOT_DIR__ . '/' . Watermark_Plugin::UPLOAD_RELATIVE_DIR);
        if (false === $root) {
            return self::fail('公开上传目录不可用');
        }

        $prefix = '/' . Watermark_Plugin::UPLOAD_RELATIVE_DIR . '/';
        $suffix = substr($relativePath, strlen($prefix));
        $directory = $root;
        $subdirectory = dirname($suffix);
        if ('.' !== $subdirectory) {
            foreach (explode('/', $subdirectory) as $segment) {
                $candidateDirectory = $directory . DIRECTORY_SEPARATOR . $segment;
                if (is_link($candidateDirectory)) {
                    return self::fail('公开图片目录不能经过符号链接');
                }
                if (
                    !is_dir($candidateDirectory)
                    && (
                        !$createDirectory
                        || (!@mkdir($candidateDirectory, 0755) && !is_dir($candidateDirectory))
                    )
                ) {
                    return self::fail('无法创建公开图片目录');
                }
                $directory = realpath($candidateDirectory);
                if (false === $directory || !self::isWithin($directory, $root)) {
                    return self::fail('公开图片路径越界');
                }
            }
        }

        $candidate = $directory . DIRECTORY_SEPARATOR . basename($suffix);
        if (is_link($candidate)) {
            return self::fail('公开图片路径越界或不可用');
        }

        return $candidate;
    }

    /**
     * 构造图片信息。
     *
     * @param string $relativePath
     * @param string $absolutePath
     * @return array|false
     */
    private static function sourceInfo($relativePath, $absolutePath)
    {
        $imageInfo = @getimagesize($absolutePath);
        $allowed = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
        if (defined('IMAGETYPE_WEBP')) {
            $allowed[] = IMAGETYPE_WEBP;
        }
        if (!$imageInfo || !in_array($imageInfo[2], $allowed, true)) {
            return false;
        }

        return array(
            'relative' => $relativePath,
            'absolute' => $absolutePath,
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
            'type' => (int) $imageInfo[2]
        );
    }

    /**
     * 规范化默认上传目录中的相对路径。
     *
     * @param string $relativePath
     * @return string|false
     */
    private static function normalizeRelativePath($relativePath)
    {
        if (!is_string($relativePath) || '' === $relativePath || false !== strpos($relativePath, "\0")) {
            return false;
        }

        $relativePath = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        $prefix = '/' . Watermark_Plugin::UPLOAD_RELATIVE_DIR . '/';
        if (0 !== strpos($relativePath, $prefix)) {
            return false;
        }

        $segments = explode('/', substr($relativePath, strlen($prefix)));
        if (!$segments) {
            return false;
        }
        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment) {
                return false;
            }
        }

        return $prefix . implode('/', $segments);
    }

    /**
     * 递归列出图片并转换为上传相对路径。
     *
     * @param string $root
     * @param string $prefix
     * @return array
     */
    private static function listImages($root, $prefix)
    {
        $result = array();
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    FilesystemIterator::SKIP_DOTS
                )
            );
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->isLink()) {
                    continue;
                }
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, array('gif', 'jpg', 'jpeg', 'png', 'webp'), true)) {
                    continue;
                }
                $relative = substr($file->getPathname(), strlen($root) + 1);
                $result[] = rtrim($prefix, '/') . '/'
                    . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            }
        } catch (Throwable $exception) {
            self::$lastError = $exception->getMessage();
        }

        sort($result, SORT_STRING);
        return $result;
    }

    /**
     * 原子复制并校验文件内容。
     *
     * @param string $source
     * @param string $destination
     * @param int $mode
     * @return bool
     */
    private static function copyVerified($source, $destination, $mode)
    {
        $directory = dirname($destination);
        if (
            !is_dir($directory)
            && !@mkdir($directory, 0755, true)
            && !is_dir($directory)
        ) {
            return false;
        }

        $temporary = self::temporaryPath($directory, '.copy-');
        if (false === $temporary || !@copy($source, $temporary)) {
            if (false !== $temporary) {
                @unlink($temporary);
            }
            return false;
        }

        $sourceHash = @hash_file('sha256', $source);
        $copyHash = @hash_file('sha256', $temporary);
        $valid = false !== $sourceHash
            && hash_equals($sourceHash, (string) $copyHash)
            && @filesize($source) === @filesize($temporary);
        if ($valid && '\\' !== DIRECTORY_SEPARATOR && !@chmod($temporary, $mode)) {
            $valid = false;
        }
        if (!$valid || !self::replace($temporary, $destination)) {
            @unlink($temporary);
            return false;
        }

        return true;
    }

    /**
     * 原子写入 JSON。
     *
     * @param string $path
     * @param array $data
     * @param int $mode
     * @return bool
     */
    private static function writeJsonAtomic($path, array $data, $mode)
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            return false;
        }

        $temporary = self::temporaryPath(dirname($path), '.metadata-');
        if (false === $temporary) {
            return false;
        }
        $written = @file_put_contents($temporary, $json, LOCK_EX);
        $valid = false !== $written && strlen($json) === $written;
        if ($valid && '\\' !== DIRECTORY_SEPARATOR && !@chmod($temporary, $mode)) {
            $valid = false;
        }
        if (!$valid || !self::replace($temporary, $path)) {
            @unlink($temporary);
            return false;
        }

        return true;
    }

    /**
     * 跨平台替换文件。
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    private static function replace($source, $destination)
    {
        if (@rename($source, $destination)) {
            return true;
        }
        if (is_file($destination)) {
            $backup = self::temporaryPath(dirname($destination), '.replace-backup-');
            if (false !== $backup) {
                @unlink($backup);
                if (@rename($destination, $backup)) {
                    if (@rename($source, $destination)) {
                        @unlink($backup);
                        return true;
                    }
                    @rename($backup, $destination);
                }
            }
        }

        return false;
    }

    /**
     * 创建临时文件路径。
     *
     * @param string $directory
     * @param string $prefix
     * @return string|false
     */
    private static function temporaryPath($directory, $prefix)
    {
        if (!is_dir($directory)) {
            return false;
        }

        return @tempnam($directory, $prefix);
    }

    /**
     * 获取并加锁。
     *
     * @param string $private
     * @return resource|false
     */
    private static function lock($private)
    {
        $handle = @fopen($private . self::LOCK_SUFFIX, 'c');
        if (!$handle || !@flock($handle, LOCK_EX)) {
            if ($handle) {
                fclose($handle);
            }
            return self::fail('无法锁定图片处理任务');
        }

        return $handle;
    }

    /**
     * 释放锁。
     *
     * @param resource $handle
     * @return void
     */
    private static function unlock($handle)
    {
        @flock($handle, LOCK_UN);
        @fclose($handle);
    }

    /**
     * 获取元数据路径。
     *
     * @param string $private
     * @return string
     */
    private static function metadataPath($private)
    {
        return $private . self::METADATA_SUFFIX;
    }

    /**
     * 获取管理任务文件路径。
     *
     * @param object $config
     * @param string $operation
     * @return string|false
     */
    private static function taskPath($config, $operation)
    {
        if (!in_array($operation, array('protect', 'restore', 'relocate'), true)) {
            return self::fail('批处理任务类型无效');
        }

        $store = self::ensureStore($config);
        if (false === $store) {
            return false;
        }

        return $store . DIRECTORY_SEPARATOR . self::TASK_PREFIX . $operation . '.json';
    }

    /**
     * 判断路径是否位于根目录内。
     *
     * @param string $path
     * @param string $root
     * @return bool
     */
    private static function isWithin($path, $root)
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if ('\\' === DIRECTORY_SEPARATOR) {
            $path = strtolower($path);
            $root = strtolower($root);
        }

        return $path === $root || 0 === strpos($path, $root . '/');
    }

    /**
     * 在进行文件系统调用前判断路径是否符合 open_basedir。
     *
     * @param string $path
     * @return bool
     */
    private static function isAllowedByOpenBaseDir($path)
    {
        $setting = trim((string) ini_get('open_basedir'));
        if ('' === $setting) {
            return true;
        }

        $path = self::normalizeAbsolutePath($path);
        if (false === $path) {
            return false;
        }

        foreach (explode(PATH_SEPARATOR, $setting) as $allowed) {
            $allowed = trim($allowed);
            if ('' === $allowed) {
                continue;
            }
            if (!self::isAbsolutePath($allowed)) {
                $allowed = getcwd() . DIRECTORY_SEPARATOR . $allowed;
            }
            $allowed = self::normalizeAbsolutePath($allowed);
            if (false !== $allowed && self::isWithin($path, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 不访问文件系统地规范化绝对路径。
     *
     * @param string $path
     * @return string|false
     */
    private static function normalizeAbsolutePath($path)
    {
        if (!self::isAbsolutePath($path) || self::containsParentTraversal($path)) {
            return false;
        }

        $path = str_replace('\\', '/', $path);
        $prefix = '';
        if (preg_match('/^[A-Za-z]:/', $path, $matches)) {
            $prefix = strtoupper($matches[0]);
            $path = substr($path, 2);
        }

        $segments = array();
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }
            $segments[] = $segment;
        }

        return $prefix . '/' . implode('/', $segments);
    }

    /**
     * 判断是否为绝对路径。
     *
     * @param string $path
     * @return bool
     */
    private static function isAbsolutePath($path)
    {
        return is_string($path)
            && (
                '/' === substr($path, 0, 1)
                || '\\' === substr($path, 0, 1)
                || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path)
            );
    }

    /**
     * 判断路径是否包含父目录跳转。
     *
     * @param string $path
     * @return bool
     */
    private static function containsParentTraversal($path)
    {
        return in_array('..', preg_split('~[\\\\/]+~', $path), true);
    }

    /**
     * 清除受管状态缓存。
     *
     * @param string $relativePath
     * @param object $config
     * @return void
     */
    private static function invalidateManagedCache($relativePath, $config)
    {
        unset(self::$managed[self::configuredDirectory($config) . '|' . $relativePath]);
    }

    /**
     * 构造操作结果。
     *
     * @param bool $success
     * @param string $status
     * @param string $message
     * @return array
     */
    private static function result($success, $status, $message)
    {
        if (!$success) {
            self::$lastError = $message;
        }

        return array(
            'success' => (bool) $success,
            'status' => $status,
            'message' => $message
        );
    }

    /**
     * 记录错误并返回 false。
     *
     * @param string $message
     * @return false
     */
    private static function fail($message)
    {
        self::$lastError = $message;
        return false;
    }
}
