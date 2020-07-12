<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 文章内部插入分页符效果 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 *
 * @package SplitArchivePage
 * @author  Noisky & gouki
 * @version 0.1.7
 * @link https://github.com/typecho-fans/plugins/tree/master/SplitArchivePage
 *
 * 0.1.7 修正插入分页符按钮为默认Markdown编辑器或通用自判断型 by Typecho Fans
 *
 * 0.1.6 修复了 Typecho1.1 后无法识别分页标记问题，优化了显示样式 by Noisky
 *
 * 更新日志：
 * 0.1.3 修正了内容页中如果没有插入分页符内容不能显示的 BUG
 * 0.1.4 修正了 Rewrite 规则下，还会自动加上 index.php 的BUG，目前在 Rewrite 规则下去除了 index.php
 * 0.1.5 原有的程序只支持一个 GET 变量，现在已修正，只要是 GET 变量都支持
 */
class SplitArchivePage_Plugin implements Typecho_Plugin_Interface
{
    protected static $splitWord = '<page>';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('SplitArchivePage_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('SplitArchivePage_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('SplitArchivePage_Plugin', 'parse');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @static
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        //设置分页标记
        $name = new Typecho_Widget_Helper_Form_Element_Text('word', NULL, '<page>', _t('分页标记'));
        $form->addInput($name);
        $name = new Typecho_Widget_Helper_Form_Element_Text('prev', NULL, '上一页', _t('上一页显示'));
        $form->addInput($name);
        $name = new Typecho_Widget_Helper_Form_Element_Text('next', NULL, '下一页', _t('下一页显示'));
        $form->addInput($name);

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
     * 默认编辑器插入分页符功能。
     * @access public
     * @return void
     */
    public static function render(){
        $splitword = Typecho_Widget::widget('Widget_Options')->plugin('SplitArchivePage')->word;
        if(!$splitword){
            $splitword = self::$splitWord;
        }
?>
<script>
$(function(){
        var wmd = $('#wmd-image-button');
        if (wmd.length>0) {
            wmd.after(
        '<li class="wmd-button" id="wmd-sp-button" style="padding-top:5px;" title="<?php _e("插入分页符"); ?>"><img src="data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' viewBox=\'0 0 24 24\'%3e%3cpath fill=\'%23999\' d=\'M11 6h-4l5-6 5 6h-4v3h-2v-3zm2 9h-2v3h-4l5 6 5-6h-4v-3zm6-4h-14v2h14v-2z\'/%3e%3c/svg%3e"/></li>');
        } else {
            $('.url-slug').after('<button type="button" id="wmd-sp-button" class="btn btn-xs" style="margin-right:5px;"><?php _e("插入分页符"); ?></button>');
        }
        $('#wmd-sp-button').click(function(){
            var textarea = $('#text'),
            spinput = '<?php echo $splitword; ?>',
            sel = textarea.getSelection(),
            offset = (sel ? sel.start : 0)+spinput.length;
            textarea.replaceSelection(spinput);
            textarea.setSelection(offset,offset);
        });
});
</script>
<?php
    }

    /**
     * 插件实现方法（写的很挫，半夜写的，先实现以后再改。）
     *
     * @access public
     * @param string $text string
     * @param object $widget
     * @param string $lastResult
     * @return void
     */
    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;
        $content = $pagebar = '';
        if ($widget instanceof Widget_Archive ) {
            $splitword = Typecho_Widget::widget('Widget_Options')->plugin('SplitArchivePage')->word;
            if(!$splitword){
                $splitword = self::$splitWord;
            }
            if(Typecho_Router::$current == 'post'){
                $content = $text;
                if( strpos( $text , $splitword) !== false){
                    $contents = explode($splitword , $text );
                    $page = isset($_GET['page'])?intval($_GET['page']):1;
                    $content = $contents[$page-1];
                    $request = Typecho_Request::getInstance();
                    $_GET['page'] = '{page}';
                    $pagebar = self::setPageBar(count($contents),$page,$request->getPathinfo()."?".  http_build_query($_GET));
                }
            }else{
                $content = str_replace($splitword, '', $text);
                $pagebar = '';
            }
        }
        $text = $content.$pagebar;
        return $text;
    }

    private static function setPageBar($pageTotals,$page,$pageTemplate)
    {
		$selfOptions = Typecho_Widget::widget('Widget_Options')->plugin('SplitArchivePage');
        $isRewrite = Typecho_Widget::widget('Widget_Options')->rewrite;
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $pageTemplate = ($isRewrite ? rtrim($siteUrl, '/') : $siteUrl."index.php") . $pageTemplate;
        $prevWord = isSet( $selfOptions->prev ) ? $selfOptions->prev : 'PREV';
        $nextWord = isSet( $selfOptions->next ) ? $selfOptions->next : 'NEXT';
        $splitPage = 3;
        $pageHolder = array('{page}', '%7Bpage%7D');
        if ($pageTotals < 1) {
            return;
        }
        $pageBar = "<link rel='stylesheet' media='screen' type='text/css' href='".Helper::options()->pluginUrl . "/SplitArchivePage/pagebar.css"."' />";
        $pageBar .= '<div class="archives_page_bar">';
        //输出上一页
        if ($page > 1) {
            $pageBar .= '<a class="prev" href="' . str_replace($pageHolder, $page - 1, $pageTemplate) . '">'
            . $prevWord . '</a>';
        }
        for ($i = 1; $i <= $pageTotals; $i ++) {
           $pageBar .= '<a href="' .
                str_replace($pageHolder, $i, $pageTemplate) . '" ' . ($i != $page ? '' : ' class="sel"') . '>'
                . $i . '</a>';
        }
        if ($page < $pageTotals) {
            $pageBar .= '<a class="next" href="' . str_replace($pageHolder, $page + 1, $pageTemplate)
             . '">' . $nextWord . '</a>';
        }
        $pageBar .='</div>';
        return $pageBar;
    }

}
?>
