<?php
/**
 * 生成图片水印
 * 
 * @package Watermark
 * @author DEFE
 * @version 1.0.0
 * @link http://defe.me
 */

class Watermark_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('Watermark_Plugin', 'parse');
        Helper::addAction('Watermark', 'Watermark_Action');
        $dir =  __TYPECHO_ROOT_DIR__ . '/usr/img';                
        if((is_dir($dir)|| @mkdir($dir, 0777)) && is_writable($dir)){
            return _t('插件已经激活，请正确设置插件！');
        }else {
            return _t('usr目录权限限制，无法使用缓存功能');
        }
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Helper::removeAction('Watermark');      
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
	$vm_type= new Typecho_Widget_Helper_Form_Element_Checkbox('vm_type',
                array( 'pic' => '图片',
                       'text' => '文字'),
                array('text'), '水印类型');
        $form->addInput($vm_type);
        
        $vm_pos_pic= new Typecho_Widget_Helper_Form_Element_Select('vm_pos_pic',array('随机','顶部左侧',
  '顶部中间','顶部右侧','中部左侧','正中','中部右侧','底部左侧','底部中间','底部右侧'),9,_t('图片位置'));
	$form->addInput($vm_pos_pic);
        
        $vm_pos_text= new Typecho_Widget_Helper_Form_Element_Select('vm_pos_text',array('随机','顶部左侧',
  '顶部中间','顶部右侧','中部左侧','正中','中部右侧','底部左侧','底部中间','底部右侧'),9,_t('文字位置'));
	$form->addInput($vm_pos_text);
        
        $vm_pic = new Typecho_Widget_Helper_Form_Element_Text('vm_pic', NULL, 'WM.png',
                _t('水印图片'),_t('必须是存放于本插件目录中的图片，注意文件名的大小写'));
        $vm_pic->input->setAttribute('class', 'mini');
        $form->addInput($vm_pic);
        
	$vm_text = new Typecho_Widget_Helper_Form_Element_Text('vm_text',null,"Typecho)))",
                _t('水印文字'));
        $form->addInput($vm_text);
        
        $vm_font = new Typecho_Widget_Helper_Form_Element_Text('vm_font', NULL, 'lh.ttf',
                _t('文字字体'),_t('必须是存放于本插件目录中的字体文件'));
        $vm_font->input->setAttribute('class', 'mini');
        $form->addInput($vm_font);
        
        $vm_size = new Typecho_Widget_Helper_Form_Element_Text('vm_size', NULL, '16',
                _t('文字大小'));
        $vm_size->input->setAttribute('class', 'mini');
        $form->addInput($vm_size);
        
        $vm_color = new Typecho_Widget_Helper_Form_Element_Text('vm_color', NULL, '255,0,0',
                _t('文字颜色'),  _t('格式：255,255,255'));
        $vm_color->input->setAttribute('class', 'mini');
        $form->addInput($vm_color);
        
        $vm_m_x = new Typecho_Widget_Helper_Form_Element_Text('vm_m_x', NULL, '0',
                _t('水平微调'), _t('调节文字的水平位置，输入整数'));
        $vm_m_x->input->setAttribute('class', 'mini');
        $form->addInput($vm_m_x->addRule('isInteger', _t('必须是纯数字')));
        
        $vm_m_y = new Typecho_Widget_Helper_Form_Element_Text('vm_m_y', NULL, '0',
                _t('竖直微调'), _t('调节文字的竖直位置，输入整数，可以为负'));
        $vm_m_y->input->setAttribute('class', 'mini');
        $form->addInput($vm_m_y->addRule('isInteger', _t('必须是纯数字')));
         
        $vm_width = new Typecho_Widget_Helper_Form_Element_Text('vm_width', NULL, '0',
                _t('调整图片宽度'), _t('为了使水印效果一致，可以限制图片的宽度，建议和缓存搭配使用。设为 0 则不调整。'));
        $vm_width->input->setAttribute('class', 'mini');
        $form->addInput($vm_width->addRule('isInteger', _t('必须是纯数字')));
        
        $dir =  __TYPECHO_ROOT_DIR__ . '/usr/img';          
        if(is_dir($dir) && is_writable($dir)){
            $url = Typecho_Widget::widget('Widget_Options')->index . "/action/Watermark?clear";
            $vm_cache= new Typecho_Widget_Helper_Form_Element_Radio('vm_cache',
                    array( 'cache' => '使用缓存',
                           'nocache' => '不使用缓存'),
                    'nocache', '使用缓存',_t('清除缓存文件可以 <a href="'.$url.'"> 点击这里 </a> 执行清空缓存功能。'));
            $form->addInput($vm_cache);
        }
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
     * 解析内容
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function parse($value_o)
    {
        if('index.php' === basename($_SERVER['SCRIPT_NAME'])){
            $options = Typecho_Widget::widget('Widget_Options');
            $cache_ab = $options->plugin('Watermark')->vm_cache?$options->plugin('Watermark')->vm_cache:FALSE;

            $value = $value_o['text'];

            if($value_o['isMarkdown']){
                $regex = "/\[\d{1,}\]:\s(http:\/\/[\w\/\.]*.(gif|jpg|png))/";   
            }else{
                $regex = "/<img.*?src=\"(.*?)\".*?[\/]?>/"; 
            }
            preg_match_all($regex, $value, $matches);

            $count = count($matches[0]);
            for($i = 0;$i < $count;$i++) {
                $url = $matches[1][$i];
                $m = parse_url($url);
                $ext = pathinfo($m['path'], PATHINFO_EXTENSION);
                if( ($ext=='GIF' || $ext=='gif') && self::IsAnimatedGif( Watermark_Action::lujin(__TYPECHO_ROOT_DIR__ .$m['path']))){ continue;}//避开动态gif

                $cache_file = 'usr/img/'.md5($m['path']).'.'.$ext; 
                if('cache' == $cache_ab && file_exists('./'.$cache_file)){
                    $mUrl = $options->siteUrl . $cache_file;
                }else{
                    $mUrl = $options->index.'action/Watermark?mark='.base64_encode($m['path']);
                }
                $url = str_replace($url, $mUrl, $matches[0][$i]);           

                $value = str_replace($matches[0][$i], $url, $value);                
            }          
            $value_o['text'] = $value;
        }
        return $value_o;
    }
    
    public static function IsAnimatedGif($filename){
        $fp = fopen($filename, 'rb');
        $filecontent = fread($fp, filesize($filename));
        fclose($fp);
        return strpos($filecontent,chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0') === FALSE?0:1;
    }
}
