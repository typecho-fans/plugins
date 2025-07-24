<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit(0);
/**
 * 文章目录树
 * 
 * @package MenuTree
 * @author hongweipeng
 * @version 0.8.1
 * @link https://www.hongweipeng.com
 */
class MenuTree_Plugin implements Typecho_Plugin_Interface {

    public static $v = '0.8.1';
    
    /**
     * 索引ID
     */
    public static $id = 1;
    
    /**
     * 目录树
     */
    public static $tree = array();

     /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array(__CLASS__, 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'footer');
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
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){

        $jq_import = new Typecho_Widget_Helper_Form_Element_Radio('jq_import', array(
            0   =>  _t('不引入'),
            1   =>  _t('引入')
        ), 1, _t('是否引入jQuery'), _t('此插件需要jQuery，如已有选择不引入避免引入多余jQuery'));
        $form->addInput($jq_import->addRule('enum', _t('必须选择一个模式'), array(0, 1)));

        $hidden_set = new Typecho_Widget_Helper_Form_Element_Radio('hidden_no_title', array(
            0   =>  _t('否'),
            1   =>  _t('是')
        ), 0, _t('文章无标题时隐藏'), _t('文章中无h1、h2、h3...时隐藏'));
        $form->addInput($hidden_set->addRule('enum', _t('必须选择一个模式'), array(0, 1)));

        $anchor_offset = new Typecho_Widget_Helper_Form_Element_Text('anchor_offset', NULL, '0', _t('标题链接偏移量(单位 em)'), _t('填入数字，支持负数'));
        $form->addInput($anchor_offset);

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
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render() {
        
    }

    /**
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback( $match ) {
        $parent = &self::$tree;

        $html = $match[0];
        $n = $match[1];
        $menu = array(
            'num' => $n,
            'title' => trim( strip_tags( $html ) ),
            'id' => 'menu_index_' . self::$id,
            'sub' => array()
        );
        $current = array();
        if( $parent ) {
            $current = &$parent[ count( $parent ) - 1 ];
        }
        // 根
        if( ! $parent || ( isset( $current['num'] ) && $n <= $current['num'] ) ) {
            $parent[] = $menu;
        } else {
            while( is_array( $current[ 'sub' ] ) ) {
                // 父子关系
                if( $current['num'] == $n - 1 ) {
                    $current[ 'sub' ][] = $menu;
                    break;
                }
                // 后代关系，并存在子菜单
                elseif( $current['num'] < $n && $current[ 'sub' ] ) {
                    $current = &$current['sub'][ count( $current['sub'] ) - 1 ];
                }
                // 后代关系，不存在子菜单
                else {
                    for( $i = 0; $i < $n - $current['num']; $i++ ) {
                        $current['sub'][] = array(
                            'num' => $current['num'] + 1,
                            'sub' => array()
                        );
                        $current = &$current['sub'][0];
                    }
                    $current['sub'][] = $menu;
                    break;
                }
            }
        }
        self::$id++;
        return "<span id=\"{$menu['id']}\" class=\"anchor\" name=\"{$menu['id']}\"></span>" . $html;
    }
    
    /**
     * 构建目录树，生成索引
     * 
     * @access public
     * @return string
     */
    public static function buildMenuHtml( $tree, $include = true ) {
        $menuHtml = '';
        foreach( $tree as $menu ) {
            if( ! isset( $menu['id'] ) && $menu['sub'] ) {
                $menuHtml .= self::buildMenuHtml( $menu['sub'], false );
            } else {
                $title = htmlspecialchars($menu['title'], ENT_QUOTES);
                $li = "<li><a data-scroll href=\"#{$menu['id']}\" title=\"{$title}\">{$title}</a>";
                if ($menu['sub']) {
                    $li .= self::buildMenuHtml( $menu['sub'] );
                }
                $li .= "</li>";
                $menuHtml .= $li;
            }
        }
        if( $include ) {
            $menuHtml = '<ul>' . $menuHtml . '</ul>';
        }
        return $menuHtml;
    }

    /**
     * 判断是否是内容页，避免主页加载插件
     */
    public static function is_content($widget = null) {
        static $is_content = null;
        if (!$widget) {
            $widget = Typecho_Widget::widget('Widget_Archive');
        }
        if($is_content === null) {
            $is_content = !($widget->is('index') || $widget->is('search') || $widget->is('date') || $widget->is('category') || $widget->is('author'));
        }
        return $is_content;
    }
    /**
     * 插件实现方法
     * 
     * @access public
     * @return string
     */
    public static function parse( $html, $widget, $lastResult ) {
        $html = empty( $lastResult ) ? $html : $lastResult;
        if (!self::is_content($widget)) {
            return $html;
        }
        $html = preg_replace_callback( '/<h([1-6])[^>]*>.*?<\/h\1>/s', array( 'MenuTree_Plugin', 'parseCallback' ), $html );
        return $html;
    }

    /**
     *为header添加css文件
     *@return void
     */
    public static function header() {
        if (!self::is_content()) {
            return;
        }
        $cssUrl = Helper::options()->pluginUrl . '/MenuTree/menutree.css?v=' . self::$v;
        $anchor_offset = Helper::options()->plugin('MenuTree')->anchor_offset;

        echo <<<EOF
<link rel="stylesheet" type="text/css" href="{$cssUrl}" />
<style>
span.anchor {
    display: block;
    position: relative;
    top: {$anchor_offset}em;
    visibility: hidden;
}
</style>
EOF;
    }

    /**
     *为footer添加js文件
     *@return void
     */
    public static function footer() {
        if (!self::is_content()) {
            return;
        }
        if (Helper::options()->plugin('MenuTree')->jq_import) {
            echo '<script src="//cdn.bootcss.com/jquery/2.1.4/jquery.min.js"></script>';
        }

        if (empty(self::$tree) && Helper::options()->plugin('MenuTree')->hidden_no_title) {
            return;
        }

        $html = '<div class="in-page-preview-buttons in-page-preview-buttons-full-reader"><svg data-toggle="dropdown" aria-expanded="false" class="dropdown-toggle icon-list" version="1.1" id="tree_nav" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="104.5 -245.5 51 51" style="enable-background:new 104.5 -245.5 50 50;" xml:space="preserve"><path d="M130.895-218.312c-0.922,0-1.842-0.317-2.59-0.951l-11.01-9.352c-1.684-1.43-1.889-3.955-0.459-5.638c1.43-1.684,3.953-1.89,5.639-0.459l8.42,7.152l8.42-7.152c1.686-1.43,4.211-1.225,5.639,0.459c1.43,1.684,1.225,4.208-0.459,5.638l-11.01,9.352C132.738-218.628,131.816-218.312,130.895-218.312z M133.486-206.289l11.008-9.352c1.684-1.43,1.889-3.955,0.459-5.638c-1.43-1.682-3.955-1.89-5.639-0.458l-8.418,7.152l-8.422-7.152c-1.686-1.431-4.209-1.225-5.639,0.459c-1.43,1.684-1.225,4.208,0.461,5.638l11.012,9.352c0.746,0.634,1.668,0.951,2.588,0.951C131.818-205.337,132.74-205.654,133.486-206.289z"/></svg><div class="dropdown-menu theme pull-right theme-white keep-open" id="toc-list"><h3>内容目录</h3><hr><div class="table-of-contents"><div class="toc"><ul><li>'. self::buildMenuHtml( self::$tree ) .'</div></div></div></li></ul></div>';
        $js = Helper::options()->pluginUrl . '/MenuTree/dropdown.js?v=' . self::$v;
        echo <<<HTML
        <script src="{$js}"></script>
        <script type="text/javascript">
        (function($) {
            $('body').append('$html');
            $('.in-page-preview-buttons .dropdown-menu.keep-open').on('click', function (e) {
              e.stopPropagation();
            });
        })(jQuery);
        </script>
HTML;
        self::$id = 1;
        self::$tree = array();

    }
}
