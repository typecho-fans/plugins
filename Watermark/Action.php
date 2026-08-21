<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Watermark action.
 *
 * @author NHPT, DEFE
 * @copyright Copyright (c) 2013 DEFE
 * @copyright Modifications Copyright (c) 2026 NHPT
 * @license GNU General Public License 2.0
 * @link https://github.com/NHPT/Watermark
 */
class Watermark_Action extends Typecho_Widget implements Widget_Interface_Do
{
    const TASK_BATCH_SIZE = 20;

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
        $managed = Watermark_Protection::isManaged($source['relative'], $config);
        if (Watermark_Protection::enabled($config)) {
            if (
                !Watermark_Plugin::watermarkTypes($config)
                || !Watermark_Plugin::isImageEligible($source, $config)
            ) {
                $this->outputFile($source['absolute']);
                return;
            }
            if (Watermark_Plugin::isAnimatedGif($source['absolute'])) {
                $this->unsupportedImage();
                return;
            }
            if (!$managed) {
                $result = Watermark_Protection::protectExisting(
                    $source['relative'],
                    $config
                );
                if (empty($result['success'])) {
                    error_log('[Watermark] ' . $result['message']);
                    $this->generationFailed();
                    return;
                }
                $source = Watermark_Plugin::resolveImagePath($relativePath);
                if (
                    false === $source
                    || !Watermark_Protection::isManaged($source['relative'], $config)
                ) {
                    $this->generationFailed();
                    return;
                }
            }
            $this->outputFile($source['absolute']);
            return;
        }
        if ($managed) {
            $this->outputFile($source['absolute']);
            return;
        }

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
     * 自动分批迁移、重新生成、恢复图片或迁移私有目录。
     */
    public function manage()
    {
        $this->widget('Widget_User')->pass('administrator');
        $security = $this->widget('Widget_Security');
        $token = (string) $this->request->get('_');
        if (!hash_equals($security->getToken('watermark-manage'), $token)) {
            $this->response->setStatus(403);
            $this->renderManagementPage(
                _t('安全校验失败'),
                '<p>' . _t('请返回插件设置页重新进入。') . '</p>'
            );
            return;
        }

        $operation = (string) $this->request->get('manage');
        if (!in_array($operation, array('protect', 'restore', 'relocate'), true)) {
            $this->notFound();
            return;
        }

        $config = Watermark_Plugin::pluginOptions();
        $store = Watermark_Protection::status($config);
        if (empty($store['ready'])) {
            $this->response->setStatus(500);
            $this->renderManagementPage(
                _t('私有目录不可用'),
                '<p>' . htmlspecialchars($store['error'], ENT_QUOTES, 'UTF-8') . '</p>'
            );
            return;
        }
        if (
            'protect' === $operation
            && !Watermark_Protection::enabled($config)
        ) {
            $this->response->setStatus(409);
            $this->renderManagementPage(
                _t('尚未启用原图保护模式'),
                '<p>' . _t('请先在插件设置中选择原图保护模式并保存。') . '</p>'
            );
            return;
        }
        if (
            'relocate' === $operation
            && defined('__TYPECHO_WATERMARK_PRIVATE_DIR__')
        ) {
            $this->response->setStatus(409);
            $this->renderManagementPage(
                _t('私有目录已锁定'),
                '<p>' . _t(
                    '当前目录由 __TYPECHO_WATERMARK_PRIVATE_DIR__ 常量配置；'
                    . '请在服务器配置中迁移并修改该常量。'
                ) . '</p>'
            );
            return;
        }

        if ('relocate' === $operation) {
            $this->manageRelocationTask($config, $token);
            return;
        }

        $this->manageImageTask($operation, $config, $token);
    }

    /**
     * 管理图片迁移或恢复任务。
     *
     * @param string $operation
     * @param object $config
     * @param string $token
     */
    private function manageImageTask($operation, $config, $token)
    {
        $fingerprint = $this->taskFingerprint($operation, $config);
        $lock = Watermark_Protection::lockTask($config, $operation);
        if (false === $lock) {
            $this->response->setStatus(409);
            $this->renderManagementPage(
                _t('任务正被占用'),
                '<p>' . htmlspecialchars(
                    Watermark_Protection::lastError(),
                    ENT_QUOTES,
                    'UTF-8'
                ) . '</p>'
            );
            return;
        }

        $notice = '';
        $noticeError = false;
        try {
            $control = $this->request->isPost()
                ? (string) $this->request->get('control')
                : 'view';
            $task = Watermark_Protection::loadTask($config, $operation);
            if (
                !$this->taskIsUsable($task, $operation, $fingerprint)
                || 'rescan' === $control
                || (
                    !$this->request->isPost()
                    && 'complete' === $task['status']
                    && empty($task['failures'])
                )
            ) {
                $task = $this->buildImageTask($operation, $config, $fingerprint);
            }

            if (
                $this->request->isPost()
                && 'rescan' !== $control
                && (string) $this->request->get('task') !== $task['id']
            ) {
                $notice = _t('任务已更新，已显示当前有效进度。');
                $noticeError = true;
                $this->response->setStatus(409);
            } elseif (!$this->request->isPost()) {
                if ('running' === $task['status']) {
                    $task['status'] = 'paused';
                }
            } elseif ('pause' === $control) {
                $task['status'] = 'paused';
                $notice = _t('任务已暂停，可关闭窗口后稍后继续。');
            } elseif ('retry' === $control) {
                $this->retryTaskFailures($task);
                $this->processTaskBatch($task, $config);
            } elseif ('run' === $control || 'batch' === $control) {
                if ('run' === $control) {
                    $task['status'] = 'running';
                }
                if ('running' === $task['status']) {
                    $this->processTaskBatch($task, $config);
                }
            }

            if (!Watermark_Protection::saveTask($config, $operation, $task)) {
                $this->response->setStatus(500);
                $notice = _t('任务进度无法保存，请检查私有目录写权限。');
                $noticeError = true;
                $task['status'] = 'paused';
            }
        } finally {
            Watermark_Protection::unlockTask($lock);
        }

        $this->renderTaskPage($task, $config, $token, $notice, $noticeError);
    }

    /**
     * 管理私有目录迁移任务。
     *
     * @param object $config
     * @param string $token
     */
    private function manageRelocationTask($config, $token)
    {
        $operation = 'relocate';
        $lock = Watermark_Protection::lockTask($config, $operation);
        if (false === $lock) {
            $this->response->setStatus(409);
            $this->renderManagementPage(
                _t('任务正被占用'),
                '<p>' . htmlspecialchars(
                    Watermark_Protection::lastError(),
                    ENT_QUOTES,
                    'UTF-8'
                ) . '</p>'
            );
            return;
        }

        $task = false;
        $notice = '';
        $noticeError = false;
        try {
            $control = $this->request->isPost()
                ? (string) $this->request->get('control')
                : 'view';
            $task = Watermark_Protection::loadTask($config, $operation);
            if (is_array($task) && isset($task['target'])) {
                $fingerprint = $this->taskFingerprint(
                    $operation,
                    $config,
                    $task['target']
                );
                if (!$this->taskIsUsable($task, $operation, $fingerprint)) {
                    $task = false;
                }
            }

            if ('prepare' === $control) {
                $target = trim((string) $this->request->get('target'));
                if ('' === $target) {
                    $this->response->setStatus(400);
                    $notice = _t('必须填写新的私有原图绝对路径。');
                    $noticeError = true;
                } else {
                    $prepared = Watermark_Protection::prepareRelocation(
                        $config,
                        $target
                    );
                    if (empty($prepared['success'])) {
                        $this->response->setStatus(409);
                        $notice = $prepared['message'];
                        $noticeError = true;
                    } else {
                        $fingerprint = $this->taskFingerprint(
                            $operation,
                            $config,
                            $target
                        );
                        $task = $this->buildRelocationTask(
                            $config,
                            $target,
                            $fingerprint
                        );
                        $task['status'] = 'running';
                        $this->processTaskBatch($task, $config);
                    }
                }
            } elseif (is_array($task)) {
                if (
                    $this->request->isPost()
                    && (string) $this->request->get('task') !== $task['id']
                ) {
                    $notice = _t('任务已更新，已显示当前有效进度。');
                    $noticeError = true;
                    $this->response->setStatus(409);
                } elseif (!$this->request->isPost()) {
                    if ('running' === $task['status']) {
                        $task['status'] = 'paused';
                    }
                } elseif ('pause' === $control) {
                    $task['status'] = 'paused';
                    $notice = _t('目录迁移已暂停，当前配置仍指向旧目录。');
                } elseif ('retry' === $control) {
                    $this->retryTaskFailures($task);
                    $this->processTaskBatch($task, $config);
                } elseif ('run' === $control || 'batch' === $control) {
                    if ('run' === $control) {
                        $task['status'] = 'running';
                    }
                    if ('running' === $task['status']) {
                        $this->processTaskBatch($task, $config);
                    }
                }
            }

            if (
                is_array($task)
                && !Watermark_Protection::saveTask($config, $operation, $task)
            ) {
                $this->response->setStatus(500);
                $notice = _t('任务进度无法保存，请检查私有目录写权限。');
                $noticeError = true;
                $task['status'] = 'paused';
            }
        } finally {
            Watermark_Protection::unlockTask($lock);
        }

        if (!is_array($task)) {
            $this->renderRelocationStartPage($config, $token, $notice);
            return;
        }

        $this->renderTaskPage($task, $config, $token, $notice, $noticeError);
    }

    /**
     * 创建迁移或恢复任务。
     *
     * @param string $operation
     * @param object $config
     * @param string $fingerprint
     * @return array
     */
    private function buildImageTask($operation, $config, $fingerprint)
    {
        $paths = 'protect' === $operation
            ? array_values(array_unique(array_merge(
                Watermark_Protection::publicImages(),
                Watermark_Protection::privateImages($config)
            )))
            : Watermark_Protection::privateImages($config);
        sort($paths, SORT_STRING);

        $primary = array();
        $secondary = array();
        $counts = $this->emptyTaskCounts();
        $counts['candidates'] = count($paths);
        foreach ($paths as $path) {
            $state = 'protect' === $operation
                ? Watermark_Protection::protectionState($path, $config)
                : Watermark_Protection::restorationState($path, $config);
            if ('current' === $state) {
                $counts['current']++;
                continue;
            }
            if ('migrate' === $state) {
                $counts['migrate']++;
                $primary[] = array('path' => $path, 'kind' => 'migrate');
            } elseif ('regenerate' === $state) {
                $counts['regenerate']++;
                $secondary[] = array('path' => $path, 'kind' => 'regenerate');
            } elseif ('restore' === $state) {
                $counts['restore']++;
                $primary[] = array('path' => $path, 'kind' => 'restore');
            } else {
                $counts['unknown']++;
                $secondary[] = array('path' => $path, 'kind' => 'inspect');
            }
        }

        return $this->newTask(
            $operation,
            $fingerprint,
            array_merge($primary, $secondary),
            $counts
        );
    }

    /**
     * 创建私有目录迁移任务。
     *
     * @param object $config
     * @param string $target
     * @param string $fingerprint
     * @return array
     */
    private function buildRelocationTask($config, $target, $fingerprint)
    {
        $paths = Watermark_Protection::privateImages($config);
        sort($paths, SORT_STRING);
        $queue = array();
        $counts = $this->emptyTaskCounts();
        $counts['candidates'] = count($paths);
        foreach ($paths as $path) {
            $state = Watermark_Protection::relocationState(
                $path,
                $config,
                $target
            );
            if ('current' === $state) {
                $counts['current']++;
            } else {
                if ('copy' === $state) {
                    $counts['copy']++;
                } else {
                    $counts['unknown']++;
                }
                $queue[] = array(
                    'path' => $path,
                    'kind' => 'copy'
                );
            }
        }

        $task = $this->newTask('relocate', $fingerprint, $queue, $counts);
        $task['source'] = Watermark_Protection::configuredDirectory($config);
        $task['target'] = $target;

        return $task;
    }

    /**
     * 创建任务基础数据。
     *
     * @param string $operation
     * @param string $fingerprint
     * @param array $queue
     * @param array $counts
     * @return array
     */
    private function newTask($operation, $fingerprint, array $queue, array $counts)
    {
        $total = count($queue);
        return array(
            'id' => $this->taskId(),
            'operation' => $operation,
            'fingerprint' => $fingerprint,
            'status' => $total ? 'ready' : 'complete',
            'createdAt' => gmdate('c'),
            'completedAt' => $total ? '' : gmdate('c'),
            'total' => $total,
            'counts' => $counts,
            'stats' => array(
                'migrated' => 0,
                'regenerated' => 0,
                'restored' => 0,
                'copied' => 0,
                'retained' => 0,
                'skipped' => 0
            ),
            'queue' => array_values($queue),
            'failures' => array(),
            'recent' => array(),
            'finalError' => ''
        );
    }

    /**
     * 处理任务中的下一批项目。
     *
     * @param array $task
     * @param object $config
     */
    private function processTaskBatch(array &$task, $config)
    {
        $task['status'] = 'running';
        $batch = array_splice($task['queue'], 0, self::TASK_BATCH_SIZE);
        $task['recent'] = array();
        foreach ($batch as $item) {
            $path = $item['path'];
            if ('protect' === $task['operation']) {
                $result = Watermark_Protection::protectExisting($path, $config);
            } elseif ('restore' === $task['operation']) {
                $result = Watermark_Protection::restore($path, $config);
            } else {
                $result = Watermark_Protection::relocateOriginal(
                    $path,
                    $config,
                    $task['target']
                );
            }

            $detail = array(
                'path' => $path,
                'status' => empty($result['success']) ? 'error' : $result['status'],
                'message' => $result['message']
            );
            $task['recent'][] = $detail;
            if (empty($result['success'])) {
                $item['message'] = $result['message'];
                $item['attempts'] = isset($item['attempts'])
                    ? (int) $item['attempts'] + 1
                    : 1;
                $task['failures'][] = $item;
                continue;
            }

            if ('skipped' === $result['status']) {
                $task['stats']['skipped']++;
            } elseif ('excluded' === $result['status']) {
                $task['stats']['retained']++;
            } elseif ('protect' === $task['operation']) {
                if ('migrate' === $item['kind']) {
                    $task['stats']['migrated']++;
                } else {
                    $task['stats']['regenerated']++;
                }
            } elseif ('restore' === $task['operation']) {
                $task['stats']['restored']++;
            } else {
                $task['stats']['copied']++;
            }
        }

        if ($task['queue']) {
            return;
        }
        if ($task['failures']) {
            $task['status'] = 'complete';
            $task['completedAt'] = gmdate('c');
            return;
        }
        if ('relocate' === $task['operation']) {
            $this->finishRelocationTask($task, $config);
            return;
        }

        $task['status'] = 'complete';
        $task['completedAt'] = gmdate('c');
    }

    /**
     * 完成目录迁移并切换配置。
     *
     * @param array $task
     * @param object $config
     */
    private function finishRelocationTask(array &$task, $config)
    {
        $finished = Watermark_Protection::finishRelocation(
            $config,
            $task['target']
        );
        if (empty($finished['success'])) {
            $task['status'] = 'complete';
            $task['finalError'] = $finished['message'];
            $task['completedAt'] = gmdate('c');
            return;
        }

        try {
            Watermark_Plugin::switchPrivateDirectory($task['target']);
            Watermark_Protection::completeRelocation($task['target']);
            $task['status'] = 'complete';
            $task['finalError'] = '';
            $task['completedAt'] = gmdate('c');
        } catch (Throwable $exception) {
            $task['status'] = 'complete';
            $task['finalError'] = $exception->getMessage();
            $task['completedAt'] = gmdate('c');
        }
    }

    /**
     * 将失败项重新放回队列。
     *
     * @param array $task
     */
    private function retryTaskFailures(array &$task)
    {
        foreach ($task['failures'] as $item) {
            unset($item['message']);
            $task['queue'][] = $item;
        }
        $task['failures'] = array();
        $task['finalError'] = '';
        $task['status'] = 'running';
    }

    /**
     * 渲染自动批处理任务页。
     *
     * @param array $task
     * @param object $config
     * @param string $token
     * @param string $notice
     * @param bool $noticeError
     */
    private function renderTaskPage(
        array $task,
        $config,
        $token,
        $notice = '',
        $noticeError = false
    )
    {
        $operation = $task['operation'];
        $title = 'protect' === $operation
            ? _t('迁移或重新生成图片')
            : ('restore' === $operation ? _t('恢复公开原图') : _t('安全迁移私有目录'));
        $description = 'protect' === $operation
            ? _t(
                '任务优先迁移尚无私有原图的图片，再重新生成配置变化或成品失效的图片。'
                . '已与当前配置一致的图片不会进入处理队列。'
            )
            : ('restore' === $operation
                ? _t('任务只恢复公开文件尚未与私有原图一致的图片，私有副本始终保留。')
                : _t(
                    '任务只复制目标目录中尚未完整存在的原图和元数据；'
                    . '全部 SHA-256 校验通过后才切换配置。'
                ));

        $content = '<p>' . $description . '</p>';
        if ('' !== $notice) {
            $content .= '<p class="message '
                . ($noticeError ? 'error' : 'notice')
                . '">' . htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        if ('relocate' === $operation) {
            $content .= '<p><strong>' . _t('源目录') . '：</strong><code>'
                . htmlspecialchars($task['source'], ENT_QUOTES, 'UTF-8')
                . '</code><br><strong>' . _t('目标目录') . '：</strong><code>'
                . htmlspecialchars($task['target'], ENT_QUOTES, 'UTF-8')
                . '</code></p>';
        }

        $content .= $this->taskSummary($task);
        $remaining = count($task['queue']);
        $failures = count($task['failures']);
        if ('running' === $task['status'] && $remaining > 0) {
            $content .= '<p class="task-running"><strong>'
                . _t('正在自动处理，每批最多 %d 张。', self::TASK_BATCH_SIZE)
                . '</strong></p>'
                . $this->taskControlForm(
                    $token,
                    $operation,
                    $task,
                    'batch',
                    '',
                    '',
                    'watermark-auto-form'
                )
                . $this->taskControlForm(
                    $token,
                    $operation,
                    $task,
                    'pause',
                    _t('暂停'),
                    'btn',
                    'watermark-pause-form'
                )
                . '<script>(function(){'
                . 'var auto=document.getElementById("watermark-auto-form");'
                . 'var pause=document.getElementById("watermark-pause-form");'
                . 'var timer=setTimeout(function(){auto.submit();},1500);'
                . 'if(pause){pause.addEventListener("submit",function(){clearTimeout(timer);});}'
                . '}());</script>';
        } elseif ($remaining > 0) {
            $label = 'ready' === $task['status']
                ? _t('一键处理全部（%d 项）', $remaining)
                : _t('继续处理剩余 %d 项', $remaining);
            $content .= $this->taskControlForm(
                $token,
                $operation,
                $task,
                'run',
                $label,
                'btn primary'
            );
        } elseif ($failures > 0 || '' !== $task['finalError']) {
            $content .= '<p class="message error"><strong>'
                . _t('任务已完成，但有失败项。')
                . '</strong> ' . _t('修复对应问题后可只重试失败项。') . '</p>'
                . $this->taskControlForm(
                    $token,
                    $operation,
                    $task,
                    'retry',
                    _t('重试失败项（%d）', $failures + ('' !== $task['finalError'] ? 1 : 0)),
                    'btn btn-warn'
                );
        } else {
            $content .= '<p class="message success"><strong>'
                . _t('全部处理完成。')
                . '</strong></p>';
            if ('relocate' === $operation) {
                $content .= '<p>' . _t(
                    '配置已切换到新目录。旧目录仍保留在 %s，'
                    . '确认新目录运行和备份正常后再由管理员处理。',
                    '<code>' . htmlspecialchars($task['source'], ENT_QUOTES, 'UTF-8') . '</code>'
                ) . '</p>';
            }
        }

        if ('relocate' !== $operation && 'running' !== $task['status']) {
            $content .= $this->taskControlForm(
                $token,
                $operation,
                $task,
                'rescan',
                _t('重新扫描'),
                'btn'
            );
        }

        $this->renderManagementPage($title, $content);
    }

    /**
     * 构建任务统计与进度。
     *
     * @param array $task
     * @return string
     */
    private function taskSummary(array $task)
    {
        $stats = $task['stats'];
        $completed = array_sum($stats);
        $failed = count($task['failures']);
        $pending = count($task['queue']);
        $attempted = min((int) $task['total'], $completed + $failed);
        $total = (int) $task['total'];
        $percent = 0 === $total ? 100 : (int) floor($attempted * 100 / $total);
        $counts = $task['counts'];
        $pendingCounts = $this->taskQueueCounts($task['queue']);
        $completedCandidates = min(
            (int) $counts['candidates'],
            (int) $counts['current'] + $completed
        );

        $html = '<div class="task-overview">'
            . $this->taskMetric(_t('候选图片'), $counts['candidates'])
            . $this->taskMetric(_t('已完成'), $completedCandidates, 'complete')
            . $this->taskMetric(_t('待处理'), $pending, 'pending')
            . $this->taskMetric(_t('失败'), $failed, $failed > 0 ? 'failed' : '')
            . '</div><div class="task-progress">'
            . '<div><strong>' . _t('总体进度') . '</strong><span>'
            . $attempted . ' / ' . $total . '（' . $percent . '%）</span></div>'
            . '<progress max="' . max(1, $total) . '" value="'
            . (0 === $total ? 1 : $attempted) . '"></progress></div>';

        if ('protect' === $task['operation']) {
            $html .= '<p class="task-breakdown">'
                . _t(
                    '当前待处理：未迁移 %d，需重新生成 %d，待检查 %d。'
                    . '扫描时已有 %d 张是最新状态。',
                    $pendingCounts['migrate'],
                    $pendingCounts['regenerate'],
                    $pendingCounts['inspect'],
                    $counts['current']
                ) . '</p><p class="task-breakdown">'
                . _t(
                    '处理结果：已迁移 %d，已重新生成 %d，按规则保留 %d，'
                    . '并发跳过 %d，失败 %d。',
                    $stats['migrated'],
                    $stats['regenerated'],
                    $stats['retained'],
                    $stats['skipped'],
                    $failed
                ) . '</p>';
        } elseif ('restore' === $task['operation']) {
            $html .= '<p class="task-breakdown">'
                . _t(
                    '当前待处理：待恢复 %d，待检查 %d。'
                    . '扫描时已有 %d 张完成恢复。处理结果：'
                    . '已恢复 %d，并发跳过 %d，失败 %d。',
                    $pendingCounts['restore'],
                    $pendingCounts['inspect'],
                    $counts['current'],
                    $stats['restored'],
                    $stats['skipped'],
                    $failed
                ) . '</p>';
        } else {
            $html .= '<p class="task-breakdown">'
                . _t(
                    '当前待处理：待复制 %d，待检查 %d。'
                    . '扫描时已有 %d 张复制完成。处理结果：'
                    . '已复制并校验 %d，失败 %d。',
                    $pendingCounts['copy'],
                    $pendingCounts['inspect'],
                    $counts['current'],
                    $stats['copied'],
                    $failed
                ) . '</p>';
        }

        if ($task['recent']) {
            $html .= '<details open><summary>' . _t('最近一批结果') . '</summary><ul>';
            foreach ($task['recent'] as $detail) {
                $html .= '<li class="task-result-'
                    . ('error' === $detail['status'] ? 'error' : 'success')
                    . '"><code>' . htmlspecialchars($detail['path'], ENT_QUOTES, 'UTF-8')
                    . '</code>：' . htmlspecialchars(
                        $detail['message'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) . '</li>';
            }
            $html .= '</ul></details>';
        }
        if ($task['failures']) {
            $html .= '<details open><summary>' . _t('失败项') . '</summary><ul>';
            foreach (array_slice($task['failures'], 0, 50) as $failure) {
                $html .= '<li class="task-result-error"><code>'
                    . htmlspecialchars($failure['path'], ENT_QUOTES, 'UTF-8')
                    . '</code>：' . htmlspecialchars(
                        isset($failure['message']) ? $failure['message'] : _t('处理失败'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) . '</li>';
            }
            $html .= '</ul></details>';
        }
        if ('' !== $task['finalError']) {
            $html .= '<p class="message error">'
                . htmlspecialchars($task['finalError'], ENT_QUOTES, 'UTF-8')
                . '</p>';
        }

        return $html;
    }

    /**
     * 渲染任务指标。
     *
     * @param string $label
     * @param int $value
     * @param string $class
     * @return string
     */
    private function taskMetric($label, $value, $class = '')
    {
        return '<div'
            . ('' !== $class
                ? ' class="task-metric-' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"'
                : '')
            . '><span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</span><strong>' . (int) $value . '</strong></div>';
    }

    /**
     * 按任务类型统计当前队列。
     *
     * @param array $queue
     * @return array
     */
    private function taskQueueCounts(array $queue)
    {
        $counts = array(
            'migrate' => 0,
            'regenerate' => 0,
            'restore' => 0,
            'copy' => 0,
            'inspect' => 0
        );
        foreach ($queue as $item) {
            $kind = isset($item['kind']) ? (string) $item['kind'] : 'inspect';
            if (isset($counts[$kind])) {
                $counts[$kind]++;
            } else {
                $counts['inspect']++;
            }
        }

        return $counts;
    }

    /**
     * 生成任务控制表单。
     *
     * @param string $token
     * @param string $operation
     * @param array $task
     * @param string $control
     * @param string $label
     * @param string $class
     * @param string $id
     * @return string
     */
    private function taskControlForm(
        $token,
        $operation,
        array $task,
        $control,
        $label,
        $class,
        $id = ''
    ) {
        $action = htmlspecialchars(
            $this->widget('Widget_Options')->index . '/action/Watermark',
            ENT_QUOTES,
            'UTF-8'
        );
        $html = '<form method="post" action="' . $action . '"'
            . ('' !== $id ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '')
            . ' class="task-control">'
            . '<input type="hidden" name="manage" value="'
            . htmlspecialchars($operation, ENT_QUOTES, 'UTF-8') . '">'
            . '<input type="hidden" name="control" value="'
            . htmlspecialchars($control, ENT_QUOTES, 'UTF-8') . '">'
            . '<input type="hidden" name="task" value="'
            . htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') . '">'
            . '<input type="hidden" name="_" value="'
            . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
        if ('' !== $label) {
            $html .= '<button type="submit" class="'
                . htmlspecialchars($class, ENT_QUOTES, 'UTF-8')
                . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button>';
        }

        return $html . '</form>';
    }

    /**
     * 渲染目录迁移起始页。
     *
     * @param object $config
     * @param string $token
     * @param string $notice
     */
    private function renderRelocationStartPage($config, $token, $notice)
    {
        $source = Watermark_Protection::configuredDirectory($config);
        $content = '<p>' . _t(
            '当前目录：%s。插件会扫描全部私有原图，跳过目标目录中已完整复制的项目，'
            . '其余项目将自动分批复制并逐文件校验 SHA-256。全部通过后才切换配置。',
            '<code>' . htmlspecialchars($source, ENT_QUOTES, 'UTF-8') . '</code>'
        ) . '</p>';
        if ('' !== $notice) {
            $content .= '<p class="message error">'
                . htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $action = htmlspecialchars(
            $this->widget('Widget_Options')->index . '/action/Watermark',
            ENT_QUOTES,
            'UTF-8'
        );
        $content .= '<form method="post" action="' . $action . '">'
            . '<input type="hidden" name="manage" value="relocate">'
            . '<input type="hidden" name="control" value="prepare">'
            . '<input type="hidden" name="_" value="'
            . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">'
            . '<p><label for="target">' . _t('新私有目录') . '</label><br>'
            . '<input id="target" name="target" type="text" required></p>'
            . '<button type="submit" class="btn primary">'
            . _t('验证并一键迁移全部') . '</button></form>';

        $this->renderManagementPage(_t('安全迁移私有目录'), $content);
    }

    /**
     * 判断任务是否与当前操作和配置一致。
     *
     * @param mixed $task
     * @param string $operation
     * @param string $fingerprint
     * @return bool
     */
    private function taskIsUsable($task, $operation, $fingerprint)
    {
        return is_array($task)
            && isset(
                $task['id'],
                $task['operation'],
                $task['fingerprint'],
                $task['status'],
                $task['total'],
                $task['counts'],
                $task['stats'],
                $task['queue'],
                $task['failures'],
                $task['recent'],
                $task['finalError']
            )
            && $operation === $task['operation']
            && hash_equals($fingerprint, (string) $task['fingerprint'])
            && is_array($task['counts'])
            && isset(
                $task['counts']['candidates'],
                $task['counts']['current'],
                $task['counts']['migrate'],
                $task['counts']['regenerate'],
                $task['counts']['restore'],
                $task['counts']['copy'],
                $task['counts']['unknown']
            )
            && is_array($task['stats'])
            && isset(
                $task['stats']['migrated'],
                $task['stats']['regenerated'],
                $task['stats']['restored'],
                $task['stats']['copied'],
                $task['stats']['retained'],
                $task['stats']['skipped']
            )
            && is_array($task['queue'])
            && is_array($task['failures'])
            && is_array($task['recent']);
    }

    /**
     * 生成任务配置指纹。
     *
     * @param string $operation
     * @param object $config
     * @param string $target
     * @return string
     */
    private function taskFingerprint($operation, $config, $target = '')
    {
        return hash(
            'sha256',
            $operation . "\n"
            . Watermark_Protection::configuredDirectory($config) . "\n"
            . Watermark_Plugin::renderConfigFingerprint($config) . "\n"
            . trim($target)
        );
    }

    /**
     * 生成任务标识。
     *
     * @return string
     */
    private function taskId()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable $exception) {
            return hash('sha256', uniqid('', true));
        }
    }

    /**
     * 初始化扫描计数。
     *
     * @return array
     */
    private function emptyTaskCounts()
    {
        return array(
            'candidates' => 0,
            'current' => 0,
            'migrate' => 0,
            'regenerate' => 0,
            'restore' => 0,
            'copy' => 0,
            'unknown' => 0
        );
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
        if ($this->request->is('manage')) {
            $this->manage();
            return;
        }

        $this->notFound();
    }

    /**
     * 输出独立的管理页面。
     *
     * @param string $title
     * @param string $content
     */
    private function renderManagementPage($title, $content)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="zh-CN"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
            . '<style>body{max-width:880px;margin:0 auto;padding:20px;'
            . 'font:14px/1.7 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;'
            . 'color:#444;background:#fff}h1{font-size:20px;margin:0 0 16px}'
            . 'code{word-break:break-all}li{margin:6px 0}details{margin:14px 0}'
            . 'summary{cursor:pointer;font-weight:600}'
            . '.btn{border:0;background:#e9e9e6;cursor:pointer;border-radius:2px;'
            . 'display:inline-block;padding:0 12px;height:32px;color:#666}'
            . '.primary{background:#467b96;color:#fff}.btn-warn{background:#b94a48;color:#fff}'
            . '.message{padding:8px 10px;border-radius:2px}'
            . '.error{background:#fbe3e4;color:#8a1f11}'
            . '.notice{background:#eaf4f8;color:#35677d}'
            . '.success{background:#e7f5e8;color:#2e7d32}'
            . '.task-overview{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));'
            . 'border:1px solid #d9d9d6;border-radius:2px}'
            . '.task-overview div{padding:10px;border-right:1px solid #d9d9d6}'
            . '.task-overview div:last-child{border-right:0}'
            . '.task-overview span,.task-overview strong{display:block}'
            . '.task-overview span{color:#777;font-size:12px}'
            . '.task-overview strong{font-size:20px;color:#333}'
            . '.task-overview .task-metric-complete strong{color:#2e7d32}'
            . '.task-overview .task-metric-pending strong{color:#467b96}'
            . '.task-overview .task-metric-failed strong{color:#b94a48}'
            . '.task-progress{margin:14px 0}.task-progress div{display:flex;'
            . 'justify-content:space-between;gap:12px}.task-progress progress{width:100%;height:14px}'
            . '.task-breakdown{margin:6px 0;color:#666}'
            . '.task-control{display:inline-block;margin:8px 8px 0 0}'
            . '.task-running{color:#2e7d32}.task-result-success{color:#2e7d32}'
            . '.task-result-error{color:#b94a48}'
            . 'input[type=text]{box-sizing:border-box;width:100%;padding:7px;border:1px solid #d9d9d6}'
            . '@media(max-width:575px){body{padding:16px}.task-overview{grid-template-columns:1fr 1fr}'
            . '.task-overview div:nth-child(2){border-right:0}'
            . '.task-overview div:nth-child(-n+2){border-bottom:1px solid #d9d9d6}'
            . '.task-control,.task-control .btn{display:block;width:100%;margin-right:0}}'
            . '</style></head><body>'
            . '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
            . $content . '</body></html>';
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

    /**
     * 返回无法生成水印的状态。
     */
    private function generationFailed()
    {
        $this->response->setStatus(503);
        echo 'Watermark generation failed';
    }

    /**
     * 返回不支持的图片状态。
     */
    private function unsupportedImage()
    {
        $this->response->setStatus(415);
        echo 'Unsupported image';
    }
}
