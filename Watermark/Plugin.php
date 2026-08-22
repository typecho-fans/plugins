<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once __DIR__ . '/Protection.php';

/**
 * 为 Typecho 图片添加图片或文字水印，支持动态水印与原图保护模式；
 * 可将无水印原图存储在 Web 根目录外，并提供历史图片迁移、原图恢复、
 * 平铺、旋转、尺寸过滤及缓存。
 *
 * @package Watermark
 * @author NHPT, DEFE
 * @version 2.0.0
 * @dependence 1.2.0-*
 * @copyright Copyright (c) 2013 DEFE
 * @copyright Modifications Copyright (c) 2026 NHPT
 * @license GNU General Public License 2.0
 * @link https://github.com/NHPT/Watermark
 */
class Watermark_Plugin implements Typecho_Plugin_Interface
{
    const VERSION = '2.0.0';
    const CACHE_RELATIVE_DIR = 'usr/cache/watermark';
    const UPLOAD_RELATIVE_DIR = 'usr/uploads';
    const BACKUP_PLUGIN_NAME = 'WatermarkBackup';
    private static $lastRenderError = '';

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

        $backup = self::backupSettings();
        if (
            'protected' === self::arrayValue($backup, 'vm_mode', 'dynamic')
        ) {
            $store = Watermark_Protection::status((object) $backup);
            if (empty($store['ready'])) {
                $directory = (string) self::arrayValue(
                    $backup,
                    'vm_private_dir',
                    Watermark_Protection::defaultDirectory()
                );
                throw new Typecho_Plugin_Exception(_t(
                    '无法恢复原图保护配置：%s',
                    self::privateDirectoryHelp($directory, $store['error'])
                ));
            }
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

        $upload = Typecho_Plugin::factory('Widget\\Upload');
        $upload->beforeUpload = array('Watermark_Plugin', 'beforeUpload');
        $upload->beforeModify = array('Watermark_Plugin', 'beforeModify');
        $upload->upload = array('Watermark_Plugin', 'afterUpload');
        $upload->modify = array('Watermark_Plugin', 'afterUpload');

        Typecho_Plugin::factory('Widget\\Contents\\Attachment\\Edit')->finishDelete = array(
            'Watermark_Plugin',
            'afterDelete'
        );
        Helper::addAction('Watermark', 'Watermark_Action');

        if (!self::ensureCacheDirectory()) {
            return _t('插件已经激活，但缓存目录不可写；请关闭缓存或检查 usr/cache 目录权限');
        }

        return _t(
            '插件已经激活。原图保护模式需要在设置页保存后，再执行“迁移或重新生成现有图片”'
        );
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        $settings = self::settingsToArray(self::pluginOptions());
        if ($settings) {
            Helper::configPlugin(self::BACKUP_PLUGIN_NAME, $settings);
        }
        Helper::removeAction('Watermark');
    }

    /**
     * 插件配置
     *
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $security = Typecho_Widget::widget('Widget_Security');
        $manageToken = rawurlencode($security->getToken('watermark-manage'));
        $manageBase = $options->index . '/action/Watermark?_=' . $manageToken;
        $protectUrl = $manageBase . '&manage=protect';
        $restoreUrl = $manageBase . '&manage=restore';
        $relocateUrl = $manageBase . '&manage=relocate';
        $savedConfig = self::pluginOptions($options);
        $defaultPrivateDir = defined('__TYPECHO_WATERMARK_PRIVATE_DIR__')
            ? (string) constant('__TYPECHO_WATERMARK_PRIVATE_DIR__')
            : Watermark_Protection::defaultDirectory();
        $currentPrivateDir = Watermark_Protection::configuredDirectory($savedConfig);
        $submittedMode = isset($_POST['vm_mode'])
            ? (string) $_POST['vm_mode']
            : (Watermark_Protection::enabled($savedConfig) ? 'protected' : 'dynamic');
        $submittedPrivateDir = isset($_POST['vm_private_dir'])
            ? trim((string) $_POST['vm_private_dir'])
            : $currentPrivateDir;
        $privateValidationMessage = _t(
            '原图保护目录无法初始化：%s',
            $submittedPrivateDir
        );
        $submittedPrivateError = '';
        $directoryChanged = !Watermark_Protection::sameDirectory(
            $currentPrivateDir,
            $submittedPrivateDir
        );
        $savedStore = Watermark_Protection::enabled($savedConfig)
            ? Watermark_Protection::status($savedConfig)
            : null;
        $changeBlocked = $directoryChanged
            && (
                (is_array($savedStore) && empty($savedStore['ready']))
                || Watermark_Protection::hasOriginals($savedConfig)
            );
        if ($changeBlocked) {
            $submittedPrivateError = _t(
                '当前私有目录已有原图，不能直接修改路径；请使用“安全迁移私有目录”'
            );
            $privateValidationMessage = $submittedPrivateError;
        } elseif ('protected' === $submittedMode) {
            $storeReady = false !== Watermark_Protection::ensureStore((object) array(
                'vm_private_dir' => $submittedPrivateDir
            ));
            if (!$storeReady) {
                $submittedPrivateError = Watermark_Protection::lastError();
                $privateValidationMessage = self::privateDirectoryHelp(
                    $submittedPrivateDir,
                    $submittedPrivateError
                );
            }
        }
        if (Watermark_Protection::enabled($savedConfig)) {
            $store = $savedStore;
            $modeMessage = empty($store['ready'])
                ? _t(
                    '<span style="color:#c62828;font-weight:600">'
                    . '当前状态：原图保护模式，但私有原图目录不可用：%s</span>',
                    htmlspecialchars($store['error'], ENT_QUOTES, 'UTF-8')
                )
                : _t(
                    '<span style="color:#2e7d32;font-weight:600">'
                    . '当前状态：原图保护模式。该模式会在正文图片首次访问时'
                    . '自动迁移原图到私有原图目录，但建议仍执行批量迁移以覆盖'
                    . '正文外图片。</span>'
                );
        } else {
            $modeMessage = _t(
                '<span style="color:#2e7d32;font-weight:600">'
                . '当前状态：动态模式。该模式不会创建私有原图目录，'
                . '也不能阻止直接访问原图。</span>'
            );
        }

        $vmMode = new Typecho_Widget_Helper_Form_Element_Radio(
            'vm_mode',
            array(
                'dynamic' => _t('动态模式'),
                'protected' => _t('原图保护模式')
            ),
            'dynamic',
            _t('工作模式'),
            $modeMessage
        );
        $form->addInput($vmMode);

        $privateMessage = _t(
            '必须使用网站公开根目录之外的绝对路径。目录结构与 usr/uploads 对应，'
            . '更新、停用或删除插件代码不会删除原图。'
        );
        if (
            'protected' === $submittedMode
            && '' !== $submittedPrivateError
        ) {
            $privateMessage .= self::privateDirectoryHelpHtml(
                $submittedPrivateDir,
                $submittedPrivateError
            );
        }
        if (defined('__TYPECHO_WATERMARK_PRIVATE_DIR__')) {
            $privateMessage .= _t(
                '<br>当前目录由 __TYPECHO_WATERMARK_PRIVATE_DIR__ 常量锁定。'
            );
        }
        $vmPrivateDir = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_private_dir',
            NULL,
            $defaultPrivateDir,
            _t('私有原图目录'),
            NULL
        );
        if ('protected' === $submittedMode && !empty($storeReady)) {
            $privateStatus = new Typecho_Widget_Helper_Layout(
                'p',
                array('class' => 'watermark-private-status')
            );
            $privateStatus->html(_t(
                '目录校验成功：%s',
                htmlspecialchars($submittedPrivateDir, ENT_QUOTES, 'UTF-8')
            ));
            $vmPrivateDir->container->removeItem($vmPrivateDir->input);
            $vmPrivateDir->container($privateStatus);
            $vmPrivateDir->container($vmPrivateDir->input);
        }
        $vmPrivateDir->description($privateMessage);
        if (defined('__TYPECHO_WATERMARK_PRIVATE_DIR__')) {
            $vmPrivateDir->input->setAttribute('readonly', 'readonly');
        }
        $form->addInput($vmPrivateDir->addRule(
            array('Watermark_Plugin', 'validatePrivateDirectory'),
            $privateValidationMessage
        ));
        $form->addItem(self::managementTools(
            $protectUrl,
            $restoreUrl,
            $relocateUrl
        ));

        $vmType = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'vm_type',
            array('pic' => _t('图片'), 'text' => _t('文字')),
            array('pic'),
            _t('水印类型')
        );
        $form->addInput($vmType);

        $vmLayout = new Typecho_Widget_Helper_Form_Element_Radio(
            'vm_layout',
            array('single' => _t('单点'), 'tile' => _t('全图平铺')),
            'single',
            _t('水印布局'),
            _t('单点模式保持原有位置设置；全图平铺模式按间距重复绘制水印')
        );
        $form->addInput($vmLayout);

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

        $vmAngle = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_angle',
            NULL,
            '0',
            _t('旋转角度'),
            _t('取 -180 到 180 之间的整数，正数逆时针旋转')
        );
        $vmAngle->input->setAttribute('class', 'mini');
        $form->addInput($vmAngle->addRule('isInteger', _t('必须是整数')));

        $vmGapX = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_gap_x',
            NULL,
            '80',
            _t('平铺水平间距'),
            _t('相邻水印之间的水平空白像素，仅全图平铺模式生效')
        );
        $vmGapX->input->setAttribute('class', 'mini');
        $form->addInput($vmGapX->addRule('isInteger', _t('必须是整数')));

        $vmGapY = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_gap_y',
            NULL,
            '60',
            _t('平铺垂直间距'),
            _t('相邻水印之间的垂直空白像素，仅全图平铺模式生效')
        );
        $vmGapY->input->setAttribute('class', 'mini');
        $form->addInput($vmGapY->addRule('isInteger', _t('必须是整数')));

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

        $vmMinWidth = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_min_width',
            NULL,
            '0',
            _t('原图最小宽度'),
            _t('原图宽度小于该值时不添加水印，0 表示不限制')
        );
        $vmMinWidth->input->setAttribute('class', 'mini');
        $form->addInput($vmMinWidth->addRule('isInteger', _t('必须是整数')));

        $vmMinHeight = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_min_height',
            NULL,
            '0',
            _t('原图最小高度'),
            _t('原图高度小于该值时不添加水印，0 表示不限制')
        );
        $vmMinHeight->input->setAttribute('class', 'mini');
        $form->addInput($vmMinHeight->addRule('isInteger', _t('必须是整数')));

        $vmExclude = new Typecho_Widget_Helper_Form_Element_Textarea(
            'vm_exclude',
            NULL,
            '',
            _t('图片排除列表'),
            _t(
                '每行一条规则，支持完整上传路径、上传目录相对路径、文件名及 *、? 通配符；'
                . '例如 /usr/uploads/avatar/、2026/logo.png、logo-*'
            )
        );
        $form->addInput($vmExclude);

        $vmAlpha = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_alpha',
            NULL,
            '0',
            _t('图片透明度'),
            _t('取 0-100 之间的整数，0 为不透明，100 为全透明')
        );
        $vmAlpha->input->setAttribute('class', 'mini');
        $form->addInput($vmAlpha->addRule('isInteger', _t('必须是整数')));

        $vmTextAlpha = new Typecho_Widget_Helper_Form_Element_Text(
            'vm_text_alpha',
            NULL,
            '0',
            _t('文字透明度'),
            _t('取 0-100 之间的整数，0 为不透明，100 为全透明')
        );
        $vmTextAlpha->input->setAttribute('class', 'mini');
        $form->addInput($vmTextAlpha->addRule('isInteger', _t('必须是整数')));

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
     * 构建设置页内的图片管理工具和模态对话框。
     *
     * @param string $protectUrl
     * @param string $restoreUrl
     * @param string $relocateUrl
     * @return Typecho_Widget_Helper_Layout
     */
    private static function managementTools($protectUrl, $restoreUrl, $relocateUrl)
    {
        $layout = new Typecho_Widget_Helper_Layout(
            'ul',
            array('class' => 'typecho-option', 'id' => 'watermark-management-tools')
        );
        $item = new Typecho_Widget_Helper_Layout('li');
        $item->html(
            '<label class="typecho-label">' . _t('图片管理') . '</label>'
            . '<div class="message error watermark-private-warning">'
            . '<strong>'
            . _t(
                '注意：删除、清空或覆盖私有原图目录会导致无水印原图永久丢失。'
            )
            . '</strong> '
            . _t(
                '该目录保存唯一可恢复的无水印版本；即使公开水印图片仍能显示，'
                . '也无法再恢复原图。'
            )
            . '</div>'
            . '<div class="watermark-tool-actions">'
            . self::managementButton(
                _t('迁移或重新生成现有图片'),
                $protectUrl,
                _t('迁移或重新生成现有图片'),
                'btn',
                _t('正在扫描现有图片'),
                _t('正在检查公开上传目录、私有原图和当前水印配置，请稍候。')
            )
            . self::managementButton(
                _t('恢复公开原图'),
                $restoreUrl,
                _t('恢复公开原图'),
                'btn btn-warn',
                _t('正在检测可恢复原图'),
                _t('正在比对私有原图与公开文件，请稍候。')
            )
            . self::managementButton(
                _t('安全迁移私有目录'),
                $relocateUrl,
                _t('安全迁移私有目录'),
                'btn',
                _t('正在读取目录迁移状态'),
                _t('正在检查私有目录和未完成任务，请稍候。')
            )
            . '</div>'
            . '<p class="description">'
            . _t('管理操作会在当前页面打开；请阅读弹窗说明后再执行。')
            . '</p>'
            . self::managementDialog()
        );
        $layout->addItem($item);

        return $layout;
    }

    /**
     * 生成设置页管理按钮。
     *
     * @param string $label
     * @param string $url
     * @param string $title
     * @param string $class
     * @param string $loading
     * @param string $loadingDetail
     * @return string
     */
    private static function managementButton(
        $label,
        $url,
        $title,
        $class,
        $loading,
        $loadingDetail
    ) {
        return '<button type="button" class="'
            . htmlspecialchars($class, ENT_QUOTES, 'UTF-8')
            . ' watermark-open-dialog" data-url="'
            . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            . '" data-title="'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            . '" data-loading="'
            . htmlspecialchars($loading, ENT_QUOTES, 'UTF-8')
            . '" data-loading-detail="'
            . htmlspecialchars($loadingDetail, ENT_QUOTES, 'UTF-8')
            . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button>';
    }

    /**
     * 生成当前页面模态对话框。
     *
     * @return string
     */
    private static function managementDialog()
    {
        return <<<'HTML'
<div id="watermark-dialog" class="watermark-dialog" hidden aria-hidden="true">
    <div class="watermark-dialog-backdrop" data-watermark-close></div>
    <section class="watermark-dialog-panel" role="dialog" aria-modal="true"
        aria-labelledby="watermark-dialog-title">
        <header class="watermark-dialog-header">
            <strong id="watermark-dialog-title"></strong>
            <button type="button" class="watermark-dialog-close" data-watermark-close
                aria-label="关闭">&times;</button>
        </header>
        <div id="watermark-dialog-loading" class="watermark-dialog-loading"
            role="status" aria-live="polite">
            <span class="watermark-loading-spinner" aria-hidden="true"></span>
            <strong id="watermark-loading-title">正在读取任务状态</strong>
            <span id="watermark-loading-detail">请稍候。</span>
        </div>
        <iframe id="watermark-dialog-frame" title="Watermark 图片管理"></iframe>
    </section>
</div>
<style>
.watermark-private-status {
    margin: 0 0 .5em; color: #2e7d32; font-weight: 600;
}
.watermark-private-warning { margin: 0 0 12px; }
.watermark-tool-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.watermark-tool-actions .btn { margin: 0; }
.watermark-dialog[hidden] { display: none; }
.watermark-dialog { position: fixed; inset: 0; z-index: 1000; }
.watermark-dialog-backdrop { position: absolute; inset: 0; background: rgba(0, 0, 0, .42); }
.watermark-dialog-panel {
    position: absolute; top: 50%; left: 50%; box-sizing: border-box;
    width: calc(100% - 32px); max-width: 760px; height: 360px; max-height: calc(100% - 48px);
    transform: translate(-50%, -50%); transition: height .16s ease;
    background: #fff; border-radius: 2px; box-shadow: 0 12px 40px rgba(0, 0, 0, .28);
    overflow: hidden;
}
.watermark-dialog-header {
    box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;
    height: 48px; padding: 0 12px 0 16px; border-bottom: 1px solid #d9d9d6;
    background: #f6f6f3;
}
.watermark-dialog-close {
    border: 0; background: transparent; color: #666; cursor: pointer;
    width: 32px; height: 32px; padding: 0; font-size: 24px; line-height: 30px;
}
.watermark-dialog-close:hover { color: #b94a48; }
#watermark-dialog-frame { display: block; border: 0; width: 100%; height: calc(100% - 48px); }
.watermark-dialog-loading {
    position: absolute; z-index: 2; inset: 48px 0 0; display: none;
    box-sizing: border-box; padding: 24px; background: #fff;
    align-items: center; justify-content: center; flex-direction: column;
    text-align: center; color: #555;
}
.watermark-dialog-loading strong { margin-top: 14px; color: #333; font-size: 16px; }
.watermark-dialog-loading span:last-child { margin-top: 5px; color: #888; }
.watermark-dialog-panel.is-loading .watermark-dialog-loading { display: flex; }
.watermark-dialog-panel.is-loading #watermark-dialog-frame { visibility: hidden; }
.watermark-loading-spinner {
    box-sizing: border-box; width: 34px; height: 34px; border: 3px solid #d9d9d6;
    border-top-color: #467b96; border-radius: 50%;
    animation: watermark-loading-spin .75s linear infinite;
}
@keyframes watermark-loading-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) {
    .watermark-loading-spinner { animation-duration: 1.5s; }
}
@media (max-width: 575px) {
    .watermark-tool-actions { display: grid; }
    .watermark-tool-actions .btn { width: 100%; }
    .watermark-dialog-panel {
        width: calc(100% - 16px); height: calc(100% - 16px); max-height: none;
    }
}
</style>
<script>
(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }
    ready(function () {
        var dialog = document.getElementById('watermark-dialog');
        var frame = document.getElementById('watermark-dialog-frame');
        var title = document.getElementById('watermark-dialog-title');
        var panel = dialog ? dialog.querySelector('.watermark-dialog-panel') : null;
        var loadingTitle = document.getElementById('watermark-loading-title');
        var loadingDetail = document.getElementById('watermark-loading-detail');
        var previousOverflow = '';
        var activeTrigger = null;
        if (!dialog || !frame || !title || !panel || !loadingTitle || !loadingDetail) {
            return;
        }
        function showLoading(message, detail) {
            loadingTitle.textContent = message || '正在处理任务';
            loadingDetail.textContent = detail || '正在保存进度并加载最新统计，请稍候。';
            panel.classList.add('is-loading');
        }
        function hideLoading() {
            panel.classList.remove('is-loading');
        }
        frame.addEventListener('load', function () {
            try {
                var frameWindow = frame.contentWindow;
                var frameDocument = frame.contentDocument;
                if (!frameWindow || frameWindow.location.href === 'about:blank') {
                    if (dialog.hidden) {
                        hideLoading();
                    }
                    return;
                }
                hideLoading();
                frameWindow.addEventListener('beforeunload', function () {
                    if (!panel.classList.contains('is-loading')) {
                        showLoading(
                            '正在处理任务',
                            '正在保存本批结果并加载最新统计，请稍候。'
                        );
                    }
                });
                frameDocument.addEventListener('submit', function (event) {
                    var control = event.target.querySelector(
                        'input[name="control"]'
                    );
                    var messages = {
                        rescan: ['正在重新扫描', '正在重新检查全部图片状态，请稍候。'],
                        prepare: ['正在验证目标目录', '正在检查目录并建立迁移任务，请稍候。'],
                        retry: ['正在重试失败项', '正在重新处理失败项目，请稍候。'],
                        pause: ['正在暂停任务', '正在保存当前进度，请稍候。']
                    };
                    var message = control ? messages[control.value] : null;
                    showLoading(
                        message ? message[0] : '正在处理任务',
                        message
                            ? message[1]
                            : '正在保存本批结果并加载最新统计，请稍候。'
                    );
                });
                if (window.innerWidth <= 575) {
                    panel.style.height = 'calc(100% - 16px)';
                    return;
                }
                var body = frameDocument.body;
                var root = frameDocument.documentElement;
                var contentHeight = Math.max(
                    body ? body.scrollHeight : 0,
                    root ? root.scrollHeight : 0
                );
                panel.style.height = Math.min(680, Math.max(300, contentHeight + 48)) + 'px';
            } catch (error) {
                panel.style.height = '680px';
            }
        });
        function closeDialog() {
            dialog.hidden = true;
            dialog.setAttribute('aria-hidden', 'true');
            frame.src = 'about:blank';
            hideLoading();
            document.body.style.overflow = previousOverflow;
            if (activeTrigger) {
                activeTrigger.focus();
                activeTrigger = null;
            }
        }
        Array.prototype.forEach.call(
            document.querySelectorAll('.watermark-open-dialog'),
            function (button) {
                button.addEventListener('click', function () {
                    activeTrigger = button;
                    previousOverflow = document.body.style.overflow;
                    title.textContent = button.getAttribute('data-title') || '';
                    showLoading(
                        button.getAttribute('data-loading'),
                        button.getAttribute('data-loading-detail')
                    );
                    dialog.hidden = false;
                    dialog.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                    dialog.querySelector('.watermark-dialog-close').focus();
                    frame.src = button.getAttribute('data-url');
                });
            }
        );
        Array.prototype.forEach.call(
            dialog.querySelectorAll('[data-watermark-close]'),
            function (button) {
                button.addEventListener('click', closeDialog);
            }
        );
        document.addEventListener('keydown', function (event) {
            if (!dialog.hidden && (event.key === 'Escape' || event.keyCode === 27)) {
                closeDialog();
            }
        });
    });
}());
</script>
HTML;
    }

    /**
     * 在保存设置时创建并校验私有目录。
     *
     * @param string $directory
     * @return bool
     */
    public static function validatePrivateDirectory($directory)
    {
        $mode = isset($_POST['vm_mode']) ? (string) $_POST['vm_mode'] : 'dynamic';
        $savedConfig = self::pluginOptions();
        $currentDirectory = Watermark_Protection::configuredDirectory($savedConfig);
        if (
            !Watermark_Protection::sameDirectory($currentDirectory, $directory)
            && (
                (
                    Watermark_Protection::enabled($savedConfig)
                    && empty(Watermark_Protection::status($savedConfig)['ready'])
                )
                || Watermark_Protection::hasOriginals($savedConfig)
            )
        ) {
            return false;
        }

        return 'protected' !== $mode
            || false !== Watermark_Protection::ensureStore((object) array(
                'vm_private_dir' => $directory
            ));
    }

    /**
     * 生成纯文本目录修复提示。
     *
     * @param string $directory
     * @param string $error
     * @return string
     */
    private static function privateDirectoryHelp($directory, $error)
    {
        $permission = self::permissionInstruction($directory);
        return _t(
            '%s。目标目录：%s。建议 open_basedir：%s。'
            . '请在当前 PHP 运行环境的生效配置中修改 open_basedir；配置位置可能是 '
            . 'php.ini、PHP-FPM 池、Web 服务器虚拟主机、.user.ini 或托管控制面板。'
            . '%s。%s',
            $error,
            $directory,
            Watermark_Protection::recommendedOpenBaseDir($directory),
            $permission['text'],
            self::reloadInstruction()
        );
    }

    /**
     * 生成设置页目录修复提示。
     *
     * @param string $directory
     * @param string $error
     * @return string
     */
    private static function privateDirectoryHelpHtml($directory, $error)
    {
        $openBaseDir = Watermark_Protection::recommendedOpenBaseDir($directory);
        $permission = self::permissionInstruction($directory);

        return '<br><strong>目录校验失败：</strong>'
            . htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
            . '<br><strong>建议 open_basedir：</strong><code>'
            . htmlspecialchars($openBaseDir, ENT_QUOTES, 'UTF-8')
            . '</code><br>请在当前环境实际生效的 php.ini、PHP-FPM 池、'
            . 'Web 服务器虚拟主机、.user.ini 或托管控制面板中修改。'
            . '<br><strong>目录权限：</strong>'
            . htmlspecialchars($permission['text'], ENT_QUOTES, 'UTF-8')
            . ('' !== $permission['command']
                ? '<br><code>'
                    . htmlspecialchars($permission['command'], ENT_QUOTES, 'UTF-8')
                    . '</code>'
                : '')
            . '<br>' . htmlspecialchars(self::reloadInstruction(), ENT_QUOTES, 'UTF-8');
    }

    /**
     * 生成适配当前操作系统和进程账户的权限提示。
     *
     * @param string $directory
     * @return array
     */
    private static function permissionInstruction($directory)
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return array(
                'text' => _t(
                    '请创建该目录，并通过 NTFS ACL 授予当前 Web 应用程序池或 PHP 服务账户读写权限'
                ),
                'command' => ''
            );
        }

        $account = self::runtimeAccount();
        $placeholder = '<PHP运行用户>:<PHP运行组>';
        $owner = '' !== $account ? $account : $placeholder;
        $command = 'mkdir -p ' . escapeshellarg($directory)
            . ' && chown ' . escapeshellarg($owner) . ' ' . escapeshellarg($directory)
            . ' && chmod 700 ' . escapeshellarg($directory);
        $text = '' !== $account
            ? _t('检测到当前 PHP 进程账户为 %s；无法自动创建时可由管理员执行：', $account)
            : _t(
                '无法检测当前 PHP 进程账户；请将命令中的 %s 替换为实际 Web/PHP 服务账户：',
                $placeholder
            );

        return array('text' => $text, 'command' => $command);
    }

    /**
     * 获取当前 PHP 进程的有效用户和组。
     *
     * @return string
     */
    private static function runtimeAccount()
    {
        if (
            !function_exists('posix_geteuid')
            || !function_exists('posix_getegid')
            || !function_exists('posix_getpwuid')
            || !function_exists('posix_getgrgid')
        ) {
            return '';
        }

        $user = @posix_getpwuid(posix_geteuid());
        $group = @posix_getgrgid(posix_getegid());
        if (
            !is_array($user)
            || !is_array($group)
            || empty($user['name'])
            || empty($group['name'])
        ) {
            return '';
        }

        return $user['name'] . ':' . $group['name'];
    }

    /**
     * 根据 PHP SAPI 生成配置生效提示。
     *
     * @return string
     */
    private static function reloadInstruction()
    {
        $sapi = strtolower(PHP_SAPI);
        if (false !== strpos($sapi, 'apache')) {
            return _t('修改后请重新加载 Apache，使 PHP 配置生效。');
        }
        if (false !== strpos($sapi, 'litespeed')) {
            return _t('修改后请重新加载 LiteSpeed，使 PHP 配置生效。');
        }
        if (false !== strpos($sapi, 'fpm') || false !== strpos($sapi, 'fastcgi')) {
            return _t('修改后请重新加载对应的 PHP-FPM/FastCGI 服务，使配置生效。');
        }

        return _t(
            '修改后请重新加载当前 Web/PHP 服务，或按托管平台要求等待配置生效。'
        );
    }

    /**
     * 接管配置保存，确保无效保护配置不会写入数据库。
     *
     * @param array $settings
     * @param bool $isInit
     */
    public static function configHandle($settings, $isInit)
    {
        $settings = is_array($settings) ? $settings : array();
        if ($isInit) {
            $backup = self::backupSettings();
            if ($backup) {
                $settings = array_merge($settings, $backup);
            }
        }

        $savedConfig = self::pluginOptions();
        $currentDirectory = Watermark_Protection::configuredDirectory($savedConfig);
        $newDirectory = (string) self::arrayValue(
            $settings,
            'vm_private_dir',
            Watermark_Protection::defaultDirectory()
        );
        $savedStore = Watermark_Protection::enabled($savedConfig)
            ? Watermark_Protection::status($savedConfig)
            : null;
        if (
            !Watermark_Protection::sameDirectory($currentDirectory, $newDirectory)
            && (
                (is_array($savedStore) && empty($savedStore['ready']))
                || Watermark_Protection::hasOriginals($savedConfig)
            )
        ) {
            throw new Typecho_Plugin_Exception(_t(
                '私有原图目录未修改：当前目录已有原图，请使用“安全迁移私有目录”'
            ));
        }

        if ('protected' === self::arrayValue($settings, 'vm_mode', 'dynamic')) {
            $store = Watermark_Protection::status((object) $settings);
            if (empty($store['ready'])) {
                $directory = (string) self::arrayValue(
                    $settings,
                    'vm_private_dir',
                    Watermark_Protection::defaultDirectory()
                );
                throw new Typecho_Plugin_Exception(_t(
                    '原图保护设置未保存：%s',
                    self::privateDirectoryHelp($directory, $store['error'])
                ));
            }
        }

        Helper::configPlugin('Watermark', $settings);
        Helper::configPlugin(self::BACKUP_PLUGIN_NAME, $settings);
    }

    /**
     * 在完整校验后切换私有目录配置。
     *
     * @param string $directory
     */
    public static function switchPrivateDirectory($directory)
    {
        if (defined('__TYPECHO_WATERMARK_PRIVATE_DIR__')) {
            throw new Typecho_Plugin_Exception(_t(
                '私有目录由 __TYPECHO_WATERMARK_PRIVATE_DIR__ 常量锁定，无法在后台切换'
            ));
        }

        $settings = self::settingsToArray(self::pluginOptions());
        $settings['vm_private_dir'] = $directory;
        Helper::configPlugin('Watermark', $settings);
        Helper::configPlugin(self::BACKUP_PLUGIN_NAME, $settings);
    }

    /**
     * 在附件记录写入前保护新上传文件。
     *
     * @param array $content
     */
    public static function beforeUpload($content)
    {
        self::protectIncomingAttachment($content, false);
    }

    /**
     * 在附件记录更新前保护替换文件。
     *
     * @param array $content
     */
    public static function beforeModify($content)
    {
        self::protectIncomingAttachment($content, true);
    }

    /**
     * 同步水印成品的附件大小和 MIME。
     *
     * @param object $widget
     */
    public static function afterUpload($widget)
    {
        if (!is_object($widget) || !isset($widget->attachment->path, $widget->cid)) {
            return;
        }

        $relativePath = (string) $widget->attachment->path;
        $source = self::resolveImagePath($relativePath);
        if (false === $source) {
            return;
        }

        $size = @filesize($source['absolute']);
        $imageInfo = @getimagesize($source['absolute']);
        if (false === $size || !$imageInfo || empty($imageInfo['mime'])) {
            return;
        }

        try {
            $db = Typecho_Db::get();
            $row = $db->fetchRow(
                $db->select('text')
                    ->from('table.contents')
                    ->where('cid = ?', (int) $widget->cid)
                    ->limit(1)
            );
            if (!$row || !isset($row['text'])) {
                return;
            }

            $text = (string) $row['text'];
            $attachment = json_decode($text, true);
            $useJson = is_array($attachment);
            if (!$useJson) {
                $attachment = @unserialize($text, array('allowed_classes' => false));
            }
            if (!is_array($attachment)) {
                return;
            }

            $attachment['size'] = (int) $size;
            $attachment['mime'] = (string) $imageInfo['mime'];
            $serialized = $useJson
                ? json_encode($attachment)
                : serialize($attachment);
            if (false === $serialized) {
                return;
            }

            $db->query(
                $db->update('table.contents')
                    ->rows(array('text' => $serialized))
                    ->where('cid = ?', (int) $widget->cid)
            );
            $widget->attachment->size = (int) $size;
            $widget->attachment->mime = (string) $imageInfo['mime'];
        } catch (Throwable $exception) {
            // 文件已安全落盘，元数据同步失败不应暴露私有原图。
        }
    }

    /**
     * 附件删除后清理对应私有原图。
     *
     * @param int $cid
     * @param object $widget
     */
    public static function afterDelete($cid, $widget)
    {
        if (!is_object($widget) || !isset($widget->attachment->path)) {
            return;
        }

        $config = self::pluginOptions();
        Watermark_Protection::remove((string) $widget->attachment->path, $config);
    }

    /**
     * 处理新上传或替换的附件。
     *
     * @param array $content
     * @param bool $replacement
     */
    private static function protectIncomingAttachment($content, $replacement)
    {
        if (!is_array($content) || empty($content['path'])) {
            return;
        }

        $relativePath = (string) $content['path'];
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        if (!in_array($extension, array('gif', 'jpg', 'jpeg', 'png', 'webp'), true)) {
            return;
        }

        $config = self::pluginOptions();
        if (!Watermark_Protection::enabled($config)) {
            if ($replacement) {
                Watermark_Protection::remove($relativePath, $config);
            }
            return;
        }

        $source = self::resolveImagePath($relativePath);
        if (false === $source) {
            self::removePublicUpload($relativePath);
            throw new Typecho_Plugin_Exception(_t(
                '原图保护失败：上传文件不是受支持的本地图片'
            ));
        }

        $result = Watermark_Protection::captureAndProtect($relativePath, $config);
        if (empty($result['success'])) {
            @unlink($source['absolute']);
            throw new Typecho_Plugin_Exception(_t(
                '原图保护失败：%s',
                isset($result['message']) ? $result['message'] : '未知错误'
            ));
        }
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
        if (class_exists('Widget\\Base\\Contents')) {
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
        if (false === $source) {
            return $url;
        }

        $config = self::pluginOptions($options);
        if (Watermark_Protection::isManaged($source['relative'], $config)) {
            $version = Watermark_Protection::publicVersion($source['relative'], $config);
            return '' === $version ? $url : self::appendUrlVersion($url, $version);
        }
        if (
            !self::watermarkTypes($config)
            || !self::isImageEligible($source, $config)
        ) {
            return $url;
        }
        if (Watermark_Protection::enabled($config)) {
            return self::buildActionUrl($source['relative'], $options);
        }
        if (self::isAnimatedGif($source['absolute'])) {
            return $url;
        }
        if ('cache' === self::configValue($config, 'vm_cache', 'nocache')) {
            $cacheFile = self::cacheFile($source['relative'], $source['absolute'], $config);
            if (false !== $cacheFile && is_file($cacheFile)) {
                return rtrim($options->siteUrl, '/') . '/'
                    . self::CACHE_RELATIVE_DIR . '/' . basename($cacheFile);
            }
        }

        return self::buildActionUrl($source['relative'], $options);
    }

    /**
     * 生成签名水印 Action 地址。
     *
     * @param string $relativePath
     * @param object $options
     * @return string
     */
    private static function buildActionUrl($relativePath, $options)
    {
        $encoded = self::encodePath($relativePath);
        $signature = self::signPath($relativePath, $options);

        return $options->index . '/action/Watermark?mark='
            . rawurlencode($encoded) . '&signature=' . rawurlencode($signature);
    }

    /**
     * 为公开水印成品增加内容版本，避免浏览器和 CDN 继续使用旧原图。
     *
     * @param string $url
     * @param string $version
     * @return string
     */
    private static function appendUrlVersion($url, $version)
    {
        $fragment = '';
        $fragmentPosition = strpos($url, '#');
        if (false !== $fragmentPosition) {
            $fragment = substr($url, $fragmentPosition);
            $url = substr($url, 0, $fragmentPosition);
        }

        $query = array();
        $queryPosition = strpos($url, '?');
        if (false !== $queryPosition) {
            parse_str(substr($url, $queryPosition + 1), $query);
            $url = substr($url, 0, $queryPosition);
        }
        $query['watermark'] = $version;

        return $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) . $fragment;
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

        return array(
            'relative' => $relative,
            'absolute' => $absolutePath,
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
            'type' => (int) $imageInfo[2]
        );
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
     * 读取停用前保存的配置副本。
     *
     * @return array
     */
    private static function backupSettings()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        try {
            return self::settingsToArray($options->plugin(self::BACKUP_PLUGIN_NAME));
        } catch (Throwable $exception) {
            return array();
        }
    }

    /**
     * 将 Typecho Config 或普通对象转换为数组。
     *
     * @param mixed $settings
     * @return array
     */
    private static function settingsToArray($settings)
    {
        if (is_array($settings)) {
            return $settings;
        }

        $result = array();
        if ($settings instanceof Traversable) {
            foreach ($settings as $name => $value) {
                $result[$name] = $value;
            }
        } elseif (is_object($settings)) {
            $result = get_object_vars($settings);
        }

        return $result;
    }

    /**
     * 安全读取配置数组。
     *
     * @param array $settings
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    private static function arrayValue(array $settings, $name, $default = null)
    {
        return array_key_exists($name, $settings) ? $settings[$name] : $default;
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
     * 将私有原图渲染为水印文件，不向 HTTP 响应输出内容。
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @param object $config
     * @return bool
     */
    public static function renderWatermarkFile($sourcePath, $destinationPath, $config)
    {
        self::$lastRenderError = '';
        $types = self::watermarkTypes($config);
        $useImage = in_array('pic', $types, true);
        $useText = in_array('text', $types, true);

        $watermarkFile = false;
        if ($useImage) {
            $watermarkFile = self::resolvePluginAsset(
                self::configValue($config, 'vm_pic', 'WM.png'),
                array('gif', 'jpg', 'jpeg', 'png', 'webp')
            );
            $useImage = false !== $watermarkFile;
        }

        $fontFile = false;
        if ($useText) {
            $fontFile = self::resolvePluginAsset(
                self::configValue($config, 'vm_font', 'lh.ttf'),
                array('ttf', 'ttc')
            );
            $useText = false !== $fontFile
                && function_exists('imagettfbbox')
                && function_exists('imagettftext');
        }

        if (!$useImage && !$useText) {
            self::$lastRenderError = '没有可用的水印图片或文字字体';
            return false;
        }

        require_once __DIR__ . '/class.php';
        try {
            $watermark = new WaterMark();
            $width = max(0, (int) self::configValue($config, 'vm_width', 0));
            if (!$watermark->setImSrc($sourcePath, $width)) {
                self::$lastRenderError = '无法读取私有原图';
                return false;
            }

            if ($useImage && !$watermark->setImWater($watermarkFile)) {
                $useImage = false;
            }
            if ($useText) {
                $watermark->setFont(
                    $fontFile,
                    (string) self::configValue($config, 'vm_text', 'Typecho)))'),
                    max(1, (int) self::configValue($config, 'vm_size', 16)),
                    (string) self::configValue($config, 'vm_color', '255,0,0')
                );
            }
            if (!$useImage && !$useText) {
                self::$lastRenderError = '水印资源加载失败';
                $watermark->clean();
                return false;
            }

            $saved = $watermark->mark(
                $useImage,
                self::normalizePosition(self::configValue($config, 'vm_pos_pic', 9)),
                $useText,
                self::normalizePosition(self::configValue($config, 'vm_pos_text', 9)),
                (int) self::configValue($config, 'vm_m_x', 0),
                (int) self::configValue($config, 'vm_m_y', 0),
                min(100, max(0, (int) self::configValue($config, 'vm_alpha', 0))),
                $destinationPath,
                'tile' === self::configValue($config, 'vm_layout', 'single')
                    ? 'tile'
                    : 'single',
                min(180, max(-180, (int) self::configValue($config, 'vm_angle', 0))),
                max(0, (int) self::configValue($config, 'vm_gap_x', 80)),
                max(0, (int) self::configValue($config, 'vm_gap_y', 60)),
                min(100, max(0, (int) self::configValue(
                    $config,
                    'vm_text_alpha',
                    0
                ))),
                false,
                true
            );
            if (!$saved) {
                self::$lastRenderError = '水印未实际绘制或成品写入失败';
            }

            return $saved;
        } catch (Throwable $exception) {
            if (isset($watermark)) {
                $watermark->clean();
            }
            self::$lastRenderError = '水印处理异常：' . $exception->getMessage();
            return false;
        }
    }

    /**
     * 获取水印渲染错误。
     *
     * @return string
     */
    public static function lastRenderError()
    {
        return self::$lastRenderError ?: '水印处理失败';
    }

    /**
     * 生成只与渲染结果有关的配置指纹。
     *
     * @param object $config
     * @return string
     */
    public static function renderConfigFingerprint($config)
    {
        $settings = array(
            self::VERSION,
            self::configValue($config, 'vm_type', array()),
            self::configValue($config, 'vm_layout', 'single'),
            self::configValue($config, 'vm_pos_pic', 9),
            self::configValue($config, 'vm_pos_text', 9),
            self::configValue($config, 'vm_angle', 0),
            self::configValue($config, 'vm_gap_x', 80),
            self::configValue($config, 'vm_gap_y', 60),
            self::configValue($config, 'vm_pic', 'WM.png'),
            self::configValue($config, 'vm_text', 'Typecho)))'),
            self::configValue($config, 'vm_font', 'lh.ttf'),
            self::configValue($config, 'vm_size', 16),
            self::configValue($config, 'vm_color', '255,0,0'),
            self::configValue($config, 'vm_m_x', 0),
            self::configValue($config, 'vm_m_y', 0),
            self::configValue($config, 'vm_width', 0),
            self::configValue($config, 'vm_min_width', 0),
            self::configValue($config, 'vm_min_height', 0),
            self::configValue($config, 'vm_exclude', ''),
            self::configValue($config, 'vm_alpha', 0),
            self::configValue($config, 'vm_text_alpha', 0)
        );

        $watermark = self::resolvePluginAsset(
            self::configValue($config, 'vm_pic', 'WM.png'),
            array('gif', 'jpg', 'jpeg', 'png', 'webp')
        );
        $font = self::resolvePluginAsset(
            self::configValue($config, 'vm_font', 'lh.ttf'),
            array('ttf', 'ttc')
        );
        $settings[] = $watermark ? @hash_file('sha256', $watermark) : '';
        $settings[] = $font ? @hash_file('sha256', $font) : '';

        return hash('sha256', serialize($settings));
    }

    /**
     * 约束水印位置。
     *
     * @param mixed $value
     * @return int
     */
    private static function normalizePosition($value)
    {
        $value = (int) $value;
        return $value >= 0 && $value <= 9 ? $value : 9;
    }

    /**
     * 安全删除默认上传目录中的文件。
     *
     * @param string $relativePath
     * @return void
     */
    private static function removePublicUpload($relativePath)
    {
        if (!is_string($relativePath) || false !== strpos($relativePath, "\0")) {
            return;
        }

        $relativePath = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        $prefix = '/' . self::UPLOAD_RELATIVE_DIR . '/';
        if (0 !== strpos($relativePath, $prefix)) {
            return;
        }

        $root = realpath(__TYPECHO_ROOT_DIR__ . '/' . self::UPLOAD_RELATIVE_DIR);
        $path = realpath(__TYPECHO_ROOT_DIR__ . $relativePath);
        if (false === $root || false === $path || !is_file($path)) {
            return;
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (0 === strpos($path, $root)) {
            @unlink($path);
        }
    }

    /**
     * 判断图片是否满足排除列表与最小尺寸限制。
     *
     * @param array $source
     * @param object $config
     * @return bool
     */
    public static function isImageEligible(array $source, $config)
    {
        if (empty($source['relative']) || empty($source['absolute'])) {
            return false;
        }
        if (self::isExcludedPath($source['relative'], $config)) {
            return false;
        }

        $width = isset($source['width']) ? (int) $source['width'] : 0;
        $height = isset($source['height']) ? (int) $source['height'] : 0;
        if ($width <= 0 || $height <= 0) {
            $imageInfo = @getimagesize($source['absolute']);
            if (!$imageInfo) {
                return false;
            }
            $width = (int) $imageInfo[0];
            $height = (int) $imageInfo[1];
        }

        $minimumWidth = max(0, (int) self::configValue($config, 'vm_min_width', 0));
        $minimumHeight = max(0, (int) self::configValue($config, 'vm_min_height', 0));

        return $width >= $minimumWidth && $height >= $minimumHeight;
    }

    /**
     * 判断上传图片路径是否命中排除规则。
     *
     * @param string $relativePath
     * @param object $config
     * @return bool
     */
    public static function isExcludedPath($relativePath, $config)
    {
        $rules = self::configValue($config, 'vm_exclude', '');
        if (is_array($rules)) {
            $rules = implode("\n", $rules);
        }
        if (!is_string($rules) || '' === trim($rules)) {
            return false;
        }

        $path = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        $basename = basename($path);
        $lines = preg_split('/\r\n|\r|\n/', $rules);
        $checked = 0;
        foreach ($lines as $line) {
            $rule = trim($line);
            if ('' === $rule || '#' === $rule[0]) {
                continue;
            }
            if (++$checked > 200) {
                break;
            }

            $rule = substr(str_replace('\\', '/', $rule), 0, 512);
            $containsSlash = false !== strpos($rule, '/');
            if (!$containsSlash) {
                $target = $basename;
                $pattern = $rule;
            } else {
                $target = $path;
                $pattern = 0 === strpos($rule, '/')
                    ? $rule
                    : '/' . self::UPLOAD_RELATIVE_DIR . '/' . ltrim($rule, '/');
            }

            if ('/' === substr($pattern, -1)) {
                if (0 === strpos($target, $pattern)) {
                    return true;
                }
                continue;
            }
            if (self::wildcardMatch($pattern, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 匹配只包含 * 与 ? 的简单路径通配规则。
     *
     * @param string $pattern
     * @param string $value
     * @return bool
     */
    private static function wildcardMatch($pattern, $value)
    {
        $patternLength = strlen($pattern);
        $valueLength = strlen($value);
        $patternIndex = 0;
        $valueIndex = 0;
        $starIndex = -1;
        $starValueIndex = 0;

        while ($valueIndex < $valueLength) {
            if (
                $patternIndex < $patternLength
                && (
                    '?' === $pattern[$patternIndex]
                    || $pattern[$patternIndex] === $value[$valueIndex]
                )
            ) {
                $patternIndex++;
                $valueIndex++;
                continue;
            }
            if ($patternIndex < $patternLength && '*' === $pattern[$patternIndex]) {
                $starIndex = $patternIndex++;
                $starValueIndex = $valueIndex;
                continue;
            }
            if ($starIndex >= 0) {
                $patternIndex = $starIndex + 1;
                $valueIndex = ++$starValueIndex;
                continue;
            }

            return false;
        }

        while ($patternIndex < $patternLength && '*' === $pattern[$patternIndex]) {
            $patternIndex++;
        }

        return $patternIndex === $patternLength;
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
            self::configValue($config, 'vm_layout', 'single'),
            self::configValue($config, 'vm_pos_pic', 9),
            self::configValue($config, 'vm_pos_text', 9),
            self::configValue($config, 'vm_angle', 0),
            self::configValue($config, 'vm_gap_x', 80),
            self::configValue($config, 'vm_gap_y', 60),
            self::configValue($config, 'vm_pic', 'WM.png'),
            self::configValue($config, 'vm_text', 'Typecho)))'),
            self::configValue($config, 'vm_font', 'lh.ttf'),
            self::configValue($config, 'vm_size', 16),
            self::configValue($config, 'vm_color', '255,0,0'),
            self::configValue($config, 'vm_m_x', 0),
            self::configValue($config, 'vm_m_y', 0),
            self::configValue($config, 'vm_width', 0),
            self::configValue($config, 'vm_min_width', 0),
            self::configValue($config, 'vm_min_height', 0),
            self::configValue($config, 'vm_exclude', ''),
            self::configValue($config, 'vm_alpha', 0),
            self::configValue($config, 'vm_text_alpha', 0)
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
