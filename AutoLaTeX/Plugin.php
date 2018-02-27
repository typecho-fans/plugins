<?php
/**
 * 自动渲染 LaTeX 公式
 * 
 * @package AutoLaTeX 
 * @author bLue
 * @version 0.1.0
 * @link https://dreamer.blue
 */
class AutoLaTeX_Plugin implements Typecho_Plugin_Interface {
     /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'footer');
        Typecho_Plugin::factory('admin/write-post.php')->content = array(__CLASS__, 'header');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array(__CLASS__, 'footer');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {}

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        $renderingList = array(
            'KaTeX' => 'KaTeX',
            'MathJax' => 'MathJax',
        );
        $name = new Typecho_Widget_Helper_Form_Element_Select('rendering', $renderingList, 'KaTeX', _t('选择 LaTeX 渲染方式'));
        $form->addInput($name->addRule('enum', _t('请选择 LaTeX 渲染方式'), $renderingList));
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render() {}

    /**
     * 添加额外输出到 Header
     * 
     * @access public
     * @return void
     */
    public static function header() {
        $pluginDir = Helper::options()->pluginUrl . '/AutoLaTeX';
        $rendering = Helper::options()->plugin('AutoLaTeX')->rendering;
        switch($rendering) {
            case 'MathJax':
                break;
            case 'KaTeX':
                echo <<<HTML
                    <link href="{$pluginDir}/KaTeX/katex.min.css" rel="stylesheet">
HTML;
                break;
        }
    }

    /**
     * 添加额外输出到 Footer
     * 
     * @access public
     * @return void
     */
    public static function footer() {
        $pluginDir = Helper::options()->pluginUrl . '/AutoLaTeX';
        $rendering = Helper::options()->plugin('AutoLaTeX')->rendering;
        switch($rendering) {
            case 'MathJax':
                echo <<<HTML
                    <script src="{$pluginDir}/MathJax/MathJax.js?config=TeX-AMS-MML_HTMLorMML,local/local"></script>
                    <script>
                        function triggerRenderingLaTeX(element) {
                            MathJax.Hub.Queue(["Typeset", MathJax.Hub, element]);
                        }
                    </script>
HTML;
                break;
            case 'KaTeX':
                echo <<<HTML
                    <script src="{$pluginDir}/KaTeX/katex.min.js"></script>
                    <script src="{$pluginDir}/KaTeX/auto-render.min.js"></script>
                    <script>
                        function triggerRenderingLaTeX(element) {
                            renderMathInElement(
                                element,
                                {
                                    delimiters: [
                                        {left: "$$", right: "$$", display: true},
                                        {left: "\\\\[", right: "\\\\]", display: true},
                                        {left: "$", right: "$", display: false},
                                        {left: "\\\\(", right: "\\\\)", display: false}
                                    ]
                                }
                            );
                        }

                        document.addEventListener("DOMContentLoaded", function() {
                            triggerRenderingLaTeX(document.body);
                        });
                    </script>
HTML;
                break;
        }
        echo <<<HTML
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var wmdPreviewLink = document.querySelector("a[href='#wmd-preview']");
                    var wmdPreviewContainer = document.querySelector("#wmd-preview");
                    if(wmdPreviewLink && wmdPreviewContainer) {
                        wmdPreviewLink.onclick = function() {
                            triggerRenderingLaTeX(wmdPreviewContainer);
                        };
                    }
                });
            </script>
HTML;
    }
}
