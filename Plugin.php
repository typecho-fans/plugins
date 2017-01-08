<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 音乐播放器
 *
 * @package Remix
 * @author ShingChi
 * @version 2.1.0
 * @link https://github.com/shingchi
 * @dependence 14.5.26-*
 */
class Remix_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // 编辑按钮
        Typecho_Plugin::factory('admin/editor-js.php')->markdownEditor_30 = array('Remix_Plugin', 'addButton');

        // 前端输出
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx_30 = array('Remix_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx_30 = array('Remix_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header_30 = array('Remix_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer_30 = array('Remix_Plugin', 'footer');

        Helper::addAction('remix', 'Remix_Action');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeAction('remix');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 缓存模式 */
        $cacheMode = new Typecho_Widget_Helper_Form_Element_Radio(
            'cacheMode',
            array(
                'memcache' => _t('Memcache'),
                'file' => _t('文件式'),
                'redis' => _t('Redis')
            ),
            'memcache',
            _t('缓存模式'),
            _t('Redis 缓存暂时无法使用，请选择其余两个，默认为 Memcache。下面配置留空为默认，文件式缓存不需要配置。')
        );
        $form->addInput($cacheMode);

        /** 缓存服务地址 */
        $cacheHost = new Typecho_Widget_Helper_Form_Element_Text(
            'cacheHost', NULL, '127.0.0.1',
            _t('缓存服务地址'),
            _t('默认为 127.0.0.1')
        );
        $form->addInput($cacheHost);

        /** 缓存服务地址 */
        $cachePort = new Typecho_Widget_Helper_Form_Element_Text(
            'cachePort', NULL, '11211',
            _t('缓存服务端口'),
            _t('Memcache 默认为 11211，Redis 默认为 6379')
        );
        $form->addInput($cachePort);

        /** 请求哈希值 */
        $hash = new Typecho_Widget_Helper_Form_Element_Text(
            'hash', NULL, 'Remix_v2.0.0',
            _t('前端请求哈希值'),
            _t('设置有利于防止别人盗用自己站点的api')
        );
        $form->addInput($hash);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 短代码解析
     *
     * @access public
     * @return void
     */
    public static function parse($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;

        if ($widget instanceof Widget_Archive) {
            $pattern = '/<p>\[Remix serve=(.*)(\s)auto=(.*)(\s)loop=(.*)(\s)type=(.*)(\s)songs=(.*)\]<\/p>/i';
            $replace = '<div class="remix" data-serve="' . '\1' . '" data-auto="' . '\3' . '" data-loop="' . '\5' . '" data-type="' . '\7' . '" data-songs="' . '\9' . '">
    <div class="remix-controls">
        <div class="remix-detail">歌曲 - 艺术家</div>
        <div class="remix-progress">
            <div class="remix-progress-loaded"></div>
            <div class="remix-progress-played"></div>
        </div>
        <div class="remix-duration">00:00</div>
        <i class="remix-button-play"></i>
        <i class="remix-button-volume"></i>
        <i class="remix-button-menu"></i>
    </div>
    <ul class="remix-playlist"></ul>
</div>';

            $content = preg_replace($pattern, $replace, $content);
        }

        return $content;
    }

    /**
     * 顶部输出
     *
     * @access public
     * @return void
     */
    public static function header()
    {
        $css = Helper::options()->pluginUrl . '/Remix/dist/css/remix.min.css';
        echo '<link rel="stylesheet" href="' . $css . '">' . "\n";
    }

    /**
     * 底部输出
     *
     * @access public
     * @return void
     */
    public static function footer()
    {
        Typecho_Widget::widget('Widget_Options')->to($options);

        $js = $options->pluginUrl . '/Remix/dist/js/remix.concat.min.js';
        $swf = $options->pluginUrl . '/Remix/dist/swf';
?>
<script>
  // Remix Config
  var remix = {
    url: '<?php $options->index('/action/remix'); ?>',
    swf: '<?php echo $swf; ?>/',
    hash: '<?php echo Typecho_Common::hash($options->plugin('Remix')->hash); ?>'
  };
</script>
<?php
        echo '<script src="' . $js . '"></script>' . "\n";
    }

    /**
     * 创建编辑器按钮
     *
     * @access public
     * @return void
     */
    public static function addButton()
    {
        ?>// 播放器按钮
    editor.hooks.chain('makeButton', function(buttons, makeButton, bindCommand, ui) {
        buttons.remix = makeButton('wmd-remix-button', '音乐 [Remix] Ctrl+S', '0', function(chunk, postProcessing) {
            var background = ui.createBackground();

            ui.prompt("<p><b>插入音乐</b></p><p>1. 服务: 网易云音乐(<b>nets</b>) 和虾米(<b>xiami</b>)</p><p>2. 自动(auto) 和循环(loop): <b>1 或 0</b></p><p>3. 类型: 单曲(song),列表(list),专辑(album),精选集(collect)</p><p>4. 输入框可以输入虾米单曲、专辑、精选集或列表的ID如:<br><b>单曲: 1773431302; 列表: 1769023557,2091668</b></p>", '', function(music) {

                background.parentNode.removeChild(background);

                if (music !== null) {
                    music = music.replace("http://", "");
                    chunk.startTag = "[Remix serve=服务 auto=自动 loop=循环 type=类型 songs=" + music + "]";
                    chunk.endTag = "";
                }
                postProcessing();

            }, '确定', '取消');
        });

        // 按钮样式
        var button = buttons.remix.getElementsByTagName("span")[0];
        button.style.backgroundImage = "none";

        buttons.remix.style.backgroundImage = "url(<?php echo Typecho_Common::url('dist/image/music.png', Helper::options()->pluginUrl('Remix')); ?>)";
        buttons.remix.style.backgroundRepeat = "no-repeat";
        buttons.remix.style.backgroundPosition = "3px 3px";

        // 快捷键
        document.getElementById("text").addEventListener("keydown", function(e) {
            if ((e.ctrlKey || e.metaKey) && !e.altKey && !e.shiftKey) {
                var keyCode = e.charCode || e.keyCode;
                var keyCodeStr = String.fromCharCode(keyCode).toLowerCase();

                if (keyCodeStr == "s") {
                    e.preventDefault();
                    buttons.remix.click();
                }
            }
        }, false);
    });
        <?php
    }
}
