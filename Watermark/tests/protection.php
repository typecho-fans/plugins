<?php

/**
 * Watermark protection lifecycle tests.
 *
 * @author NHPT
 * @copyright Copyright (c) 2026 NHPT
 * @license GNU General Public License 2.0
 * @link https://github.com/NHPT/Watermark
 */

if (!extension_loaded('gd')) {
    fwrite(STDERR, "SKIP: GD extension is required\n");
    exit(77);
}

$sandbox = sys_get_temp_dir() . '/watermark-test-' . bin2hex(random_bytes(6));
$siteRoot = $sandbox . '/site';
$privateRoot = $sandbox . '/private';
$uploadDirectory = $siteRoot . '/usr/uploads/2026/08';
if (!mkdir($uploadDirectory, 0755, true)) {
    throw new RuntimeException('Unable to create test upload directory');
}

define('__TYPECHO_ROOT_DIR__', $siteRoot);

interface Typecho_Plugin_Interface
{
}

class Typecho_Plugin_Exception extends RuntimeException
{
}

class Helper
{
    public static $configs = array();

    public static function configPlugin($name, array $settings)
    {
        self::$configs[$name] = $settings;
    }

    public static function removeAction($name)
    {
    }
}

class Typecho_Widget
{
    public static $options;

    public static function widget($name)
    {
        return self::$options;
    }
}

class TestOptions
{
    public $siteUrl = 'https://example.test/';
    public $index = 'https://example.test/index.php';
    public $secret = 'test-secret';
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function plugin($name)
    {
        if (isset(Helper::$configs[$name])) {
            return (object) Helper::$configs[$name];
        }
        return $this->config;
    }
}

function _t($message)
{
    $arguments = func_get_args();
    array_shift($arguments);
    return $arguments ? vsprintf($message, $arguments) : $message;
}

require dirname(__DIR__) . '/Plugin.php';

function assertTrue($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function removeTree($directory)
{
    if (!is_dir($directory)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($directory);
}

function createFixture($path, $red, $green, $blue)
{
    $image = imagecreatetruecolor(320, 180);
    $background = imagecolorallocate($image, $red, $green, $blue);
    imagefilledrectangle($image, 0, 0, 319, 179, $background);
    imagepng($image, $path);
    imagedestroy($image);
}

function configFor($privateRoot)
{
    return (object) array(
        'vm_mode' => 'protected',
        'vm_private_dir' => $privateRoot,
        'vm_type' => array('pic'),
        'vm_layout' => 'single',
        'vm_pos_pic' => 9,
        'vm_pos_text' => 9,
        'vm_angle' => 0,
        'vm_gap_x' => 80,
        'vm_gap_y' => 60,
        'vm_pic' => 'WM.png',
        'vm_text' => 'Watermark',
        'vm_font' => 'lh.ttf',
        'vm_size' => 16,
        'vm_color' => '255,0,0',
        'vm_m_x' => 0,
        'vm_m_y' => 0,
        'vm_width' => 0,
        'vm_min_width' => 0,
        'vm_min_height' => 0,
        'vm_exclude' => '',
        'vm_alpha' => 0,
        'vm_text_alpha' => 0,
        'vm_cache' => 'nocache'
    );
}

try {
    $config = configFor($privateRoot);
    Typecho_Widget::$options = new TestOptions($config);
    assertTrue(
        '.typecho-watermark' === basename(Watermark_Protection::defaultDirectory()),
        'Default private directory still contains an unnecessary site hash'
    );

    $relative = '/usr/uploads/2026/08/photo.png';
    $public = $siteRoot . $relative;
    $private = $privateRoot . '/2026/08/photo.png';
    createFixture($public, 20, 80, 160);
    $originalHash = hash_file('sha256', $public);
    assertTrue(
        'migrate' === Watermark_Protection::protectionState($relative, $config),
        'An unprotected public image was not classified for migration'
    );

    $pendingUrl = Watermark_Plugin::buildWatermarkUrl($relative);
    assertTrue(
        false !== strpos($pendingUrl, '/action/Watermark?mark='),
        'Protected mode exposed an unmanaged original instead of using the signed action'
    );

    $result = Watermark_Protection::protectExisting($relative, $config);
    assertTrue($result['success'], 'Initial protection failed: ' . $result['message']);
    assertTrue('protected' === $result['status'], 'Initial image was not protected');
    assertTrue(is_file($private), 'Private original was not created');
    assertTrue($originalHash === hash_file('sha256', $private), 'Private original was modified');
    assertTrue($originalHash !== hash_file('sha256', $public), 'Public image was not watermarked');
    assertTrue(Watermark_Protection::isManaged($relative, $config), 'Managed state was not persisted');
    assertTrue(
        'current' === Watermark_Protection::protectionState($relative, $config),
        'A current protected image remained in the migration queue'
    );

    $task = array(
        'id' => 'test-task',
        'fingerprint' => 'test-fingerprint',
        'status' => 'paused',
        'counts' => array(),
        'stats' => array(),
        'queue' => array(array('path' => $relative, 'kind' => 'migrate')),
        'failures' => array(),
        'recent' => array(),
        'finalError' => ''
    );
    assertTrue(
        Watermark_Protection::saveTask($config, 'protect', $task),
        'Persistent task could not be saved'
    );
    $savedTask = Watermark_Protection::loadTask($config, 'protect');
    assertTrue(
        is_array($savedTask)
            && 'test-task' === $savedTask['id']
            && $relative === $savedTask['queue'][0]['path'],
        'Persistent task could not be resumed'
    );
    assertTrue(
        array($relative) === Watermark_Protection::privateImages($config),
        'Task metadata was mistaken for a private original'
    );
    $taskLock = Watermark_Protection::lockTask($config, 'protect');
    assertTrue(false !== $taskLock, 'Persistent task lock could not be acquired');
    assertTrue(
        false === Watermark_Protection::lockTask($config, 'protect'),
        'A concurrent task acquired the same exclusive lock'
    );
    Watermark_Protection::unlockTask($taskLock);
    Watermark_Protection::deleteTask($config, 'protect');
    assertTrue(
        false === Watermark_Protection::loadTask($config, 'protect'),
        'Persistent task was not deleted'
    );

    $config->vm_angle = 1;
    assertTrue(
        'regenerate' === Watermark_Protection::protectionState($relative, $config),
        'A rendering configuration change did not request regeneration'
    );
    $config->vm_angle = 0;

    $reflection = new ReflectionClass('Watermark_Protection');
    $managedCache = $reflection->getProperty('managed');
    $managedCache->setAccessible(true);
    $managedCache->setValue(null, array());
    assertTrue(
        Watermark_Protection::isManaged($relative, $config),
        'Managed state could not be recovered after a simulated reinstall'
    );

    $firstPublicHash = hash_file('sha256', $public);
    $result = Watermark_Protection::captureAndProtect($relative, $config, true);
    assertTrue(
        $result['success'] && 'skipped' === $result['status'],
        'Concurrent on-demand protection did not reuse the private original'
    );
    assertTrue(
        $originalHash === hash_file('sha256', $private),
        'Concurrent protection replaced the private original with a derivative'
    );

    $result = Watermark_Protection::protectExisting($relative, $config);
    assertTrue($result['success'], 'Regeneration failed: ' . $result['message']);
    assertTrue('skipped' === $result['status'], 'Unchanged image should not be regenerated');
    assertTrue($originalHash === hash_file('sha256', $private), 'Regeneration changed the original');
    assertTrue(
        $firstPublicHash === hash_file('sha256', $public),
        'Regeneration added a second watermark or changed deterministic output'
    );

    $config->vm_mode = 'dynamic';
    Typecho_Widget::$options = new TestOptions($config);
    $versionedUrl = Watermark_Plugin::buildWatermarkUrl($relative);
    assertTrue(
        0 === strpos($versionedUrl, $relative . '?watermark='),
        'Protected image did not receive a cache-busting content version'
    );
    assertTrue(
        false === strpos($versionedUrl, '/action/Watermark'),
        'Dynamic mode attempted to add a second watermark'
    );
    assertTrue(
        $versionedUrl === Watermark_Plugin::buildWatermarkUrl($versionedUrl),
        'Content filters appended the watermark version more than once'
    );

    $result = Watermark_Protection::restore($relative, $config);
    assertTrue($result['success'], 'Restore failed: ' . $result['message']);
    assertTrue($originalHash === hash_file('sha256', $public), 'Public original was not restored');
    assertTrue(
        'current' === Watermark_Protection::restorationState($relative, $config),
        'An already restored original remained in the restore queue'
    );
    assertTrue(
        !Watermark_Protection::isManaged($relative, $config),
        'Restored image must not be treated as a watermarked derivative'
    );

    $config->vm_mode = 'protected';
    $result = Watermark_Protection::protectExisting($relative, $config);
    assertTrue($result['success'], 'Protection after restore failed: ' . $result['message']);
    assertTrue(
        $firstPublicHash === hash_file('sha256', $public),
        'Protection after restore did not use the preserved private original'
    );

    $excludedRelative = '/usr/uploads/2026/08/excluded.png';
    $excludedPublic = $siteRoot . $excludedRelative;
    createFixture($excludedPublic, 60, 120, 30);
    $excludedHash = hash_file('sha256', $excludedPublic);
    $config->vm_exclude = 'excluded.png';
    $result = Watermark_Protection::captureAndProtect($excludedRelative, $config);
    assertTrue($result['success'] && 'excluded' === $result['status'], 'Exclusion failed');
    assertTrue(
        $excludedHash === hash_file('sha256', $excludedPublic),
        'Excluded public image should remain unchanged'
    );
    assertTrue(
        !Watermark_Protection::isManaged($excludedRelative, $config),
        'An excluded original must not be mistaken for a watermarked derivative'
    );
    $config->vm_mode = 'dynamic';
    $config->vm_exclude = '';
    Typecho_Widget::$options = new TestOptions($config);
    assertTrue(
        $excludedRelative !== Watermark_Plugin::buildWatermarkUrl($excludedRelative),
        'Dynamic mode did not watermark a formerly excluded image'
    );

    $invalidRelative = '/usr/uploads/2026/08/invalid.png';
    $invalidPublic = $siteRoot . $invalidRelative;
    createFixture($invalidPublic, 180, 20, 20);
    $config->vm_mode = 'protected';
    $config->vm_type = array();
    $result = Watermark_Protection::captureAndProtect($invalidRelative, $config);
    assertTrue(!$result['success'], 'Missing watermark resources should fail');
    assertTrue(
        is_file($invalidPublic),
        'Failed standalone protection did not restore the public original for retry'
    );
    assertTrue(
        !is_file($privateRoot . '/2026/08/invalid.png'),
        'Failed protection left an uncommitted private original'
    );

    $insideConfig = configFor($siteRoot . '/private');
    $status = Watermark_Protection::status($insideConfig);
    assertTrue(!$status['ready'], 'A private directory inside the web root was accepted');

    $config->vm_type = array('pic');
    Watermark_Plugin::configHandle((array) $config, false);
    assertTrue(
        isset(Helper::$configs['Watermark'], Helper::$configs['WatermarkBackup']),
        'Validated settings and deactivation backup were not persisted'
    );

    $relocatedRoot = $sandbox . '/private-relocated';
    $changedSettings = (array) $config;
    $changedSettings['vm_private_dir'] = $relocatedRoot;
    try {
        Watermark_Plugin::configHandle($changedSettings, false);
        throw new RuntimeException('Direct private directory change was accepted');
    } catch (Typecho_Plugin_Exception $exception) {
        assertTrue(
            false !== strpos($exception->getMessage(), '安全迁移私有目录'),
            'Direct directory change did not explain the safe relocation requirement'
        );
    }

    $result = Watermark_Protection::prepareRelocation($config, $relocatedRoot);
    assertTrue($result['success'], 'Relocation preparation failed: ' . $result['message']);
    foreach (Watermark_Protection::privateImages($config) as $path) {
        assertTrue(
            'copy' === Watermark_Protection::relocationState(
                $path,
                $config,
                $relocatedRoot
            ),
            'An uncopied original was not added to the relocation queue'
        );
        $result = Watermark_Protection::relocateOriginal($path, $config, $relocatedRoot);
        assertTrue($result['success'], 'Original relocation failed: ' . $result['message']);
        assertTrue(
            'current' === Watermark_Protection::relocationState(
                $path,
                $config,
                $relocatedRoot
            ),
            'A copied original remained in the relocation queue'
        );
    }
    $result = Watermark_Protection::finishRelocation($config, $relocatedRoot);
    assertTrue($result['success'], 'Relocation verification failed: ' . $result['message']);
    Watermark_Plugin::switchPrivateDirectory($relocatedRoot);
    Watermark_Protection::completeRelocation($relocatedRoot);
    assertTrue(
        $relocatedRoot === Helper::$configs['Watermark']['vm_private_dir'],
        'Validated relocation did not switch the active configuration'
    );
    assertTrue(
        is_file($private) && is_file($relocatedRoot . '/2026/08/photo.png'),
        'Relocation did not preserve the old directory as a rollback copy'
    );
    assertTrue(
        hash_file('sha256', $private)
            === hash_file('sha256', $relocatedRoot . '/2026/08/photo.png'),
        'Relocated original failed SHA-256 verification'
    );

    $relocatedConfig = (object) Helper::$configs['Watermark'];
    $result = Watermark_Protection::prepareRelocation($relocatedConfig, $privateRoot);
    assertTrue($result['success'], 'Rollback preparation rejected the preserved old store');
    foreach (Watermark_Protection::privateImages($relocatedConfig) as $path) {
        $result = Watermark_Protection::relocateOriginal(
            $path,
            $relocatedConfig,
            $privateRoot
        );
        assertTrue($result['success'], 'Rollback copy verification failed');
    }
    $result = Watermark_Protection::finishRelocation($relocatedConfig, $privateRoot);
    assertTrue($result['success'], 'Rollback store verification failed');
    Watermark_Plugin::switchPrivateDirectory($privateRoot);
    Watermark_Protection::completeRelocation($privateRoot);

    Watermark_Plugin::deactivate();
    assertTrue(
        'protected' === Helper::$configs['WatermarkBackup']['vm_mode'],
        'Deactivation did not preserve the protected mode'
    );

    Watermark_Protection::remove($relative, $config);
    assertTrue(!is_file($private), 'Private original was not removed with attachment state');

    $warning = null;
    set_error_handler(function ($severity, $message) use (&$warning) {
        $warning = $message;
        throw new ErrorException($message, 0, $severity);
    });
    $openBaseDirChanged = false !== ini_set(
        'open_basedir',
        $siteRoot . PATH_SEPARATOR . sys_get_temp_dir()
    );
    if ($openBaseDirChanged) {
        $blockedConfig = configFor('/www/watermark-originals');
        $status = Watermark_Protection::status($blockedConfig);
        assertTrue(!$status['ready'], 'An open_basedir-blocked directory was accepted');
        assertTrue(
            false !== strpos($status['error'], 'open_basedir'),
            'The open_basedir error was not reported clearly'
        );
        assertTrue(
            false !== strpos(
                Watermark_Protection::recommendedOpenBaseDir('/www/watermark-originals'),
                '/www/watermark-originals'
            ),
            'The suggested open_basedir value omitted the private directory'
        );
        assertTrue(null === $warning, 'Filesystem warning leaked before open_basedir validation');

        $_POST['vm_mode'] = 'protected';
        assertTrue(
            !Watermark_Plugin::validatePrivateDirectory('/www/watermark-originals'),
            'Configuration validation accepted an open_basedir-blocked directory'
        );
        unset($_POST['vm_mode']);

        Helper::$configs['Watermark'] = array(
            'vm_mode' => 'dynamic',
            'vm_private_dir' => '/www/watermark-originals'
        );
        try {
            Watermark_Plugin::configHandle((array) $blockedConfig, false);
            throw new RuntimeException('Invalid protected settings were persisted');
        } catch (Typecho_Plugin_Exception $exception) {
            assertTrue(
                false !== strpos($exception->getMessage(), 'open_basedir'),
                'Configuration exception omitted the open_basedir cause'
            );
        }
    }
    restore_error_handler();

    echo "OK: protection lifecycle\n";
} finally {
    removeTree($sandbox);
}
