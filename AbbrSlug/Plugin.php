<?php
/**
 * 自动生成缩略名
 *
 * @package AbbrSlug
 * @author 羽叶
 * @version 1.0.0
 * @link https://chenyeah.com
 */
class AbbrSlug_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('AbbrSlug_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('AbbrSlug_Plugin', 'render');

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
    */
    public static function config(Typecho_Widget_Helper_Form $form){
        /** 生成模式 */
        $alg = new Typecho_Widget_Helper_Form_Element_Radio(
            'alg',
            array(
                'crc32' => _t('crc32'),
                'crc16' => _t('crc16')
            ),
            'crc32',
            _t('算法'),
            _t('目前支持crc16和crc32，其中crc32是默认的')
        );
        $form->addInput($alg);

        $rep = new Typecho_Widget_Helper_Form_Element_Radio(
            'rep',
            array(
                'hex' => _t('16进制'),
                'dec' => _t('10进制')
            ),
            'hex',
            _t('进制显示'),
            _t('其中16进制是默认的')
        );
        

        $form->addInput($rep);
    }
    
    /**
    * 个人用户的配置面板
    */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 插件实现方法
     *
     * @access public
     * @param array $contents 文章输入信息
     * @return void
     */
    public static function render($contents)
    {
        if (empty($contents['slug'])) {
            $settings = Helper::options()->plugin('AbbrSlug');
            $title = $contents['title'];

            if($settings->alg=='crc16'){
                /* 
                * crc16 算法实现
                */
                function crc16($string,$crc=0) 
                {
                    for ( $x=0; $x<strlen( $string ); $x++ ) { 
                        $crc = $crc ^ ord( $string[$x] ); 
                        for ($y = 0; $y < 8; $y++) { 
                            if ( ($crc & 0x0001) == 0x0001 ) $crc = ( ($crc >> 1 ) ^ 0xA001 ); 
                            else                             $crc =    $crc >> 1; 
                        } 
                    } 
                    return $crc; 
                }
                
                if($settings->rep=='dec'){
                    $result = crc16($title);
                }else{
                    $result = dechex(crc16($title));
                }
            }else{
                if($settings->rep=='dec'){
                    $result = crc32($title);
                }else{
                    $result = dechex(crc32($title));
                }
            }
            $contents['slug'] = $result;
        }
        return $contents;
    }
}