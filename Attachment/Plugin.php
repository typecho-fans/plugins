<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 附件下载插件 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package Attachment
 * @author 羽中, Hanny
 * @version 1.0.2
 * @dependence 9.9.2-*
 * @link https://github.com/typecho-fans/plugins/tree/master/Attachment
 *
 * version 1.0.2 at 2020-07-08
 * 修正输出源码符合W3C标准并本地化文本
 * 增加附件地址域名设置兼容云储存等情况
 *
 * 历史版本
 * version 1.0.1 at 2010-01-02
 * 修改下载地址路由
 * version 1.0.0 at 2009-12-12
 * 实现用<attach>aid</attach>添加附件的功能
 * 与统计插件结合来实现下载次数的统计功能
 *
 */
class Attachment_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Attachment_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Attachment_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('Attachment_Plugin', 'parse');
		Helper::addRoute('download', '/download/[cid:digital]', 'Attachment_Action', 'action');
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
		Helper::removeRoute('download');
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
        $domain = new Typecho_Widget_Helper_Form_Element_Text('domain', NULL, '',
			_t('域名地址'), _t('如果附件使用云储存请填写自定义域名地址，默认留空即本站地址'));
        $form->addInput($domain);
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
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback($matches)
    {
		$options = Typecho_Widget::widget('Widget_Options');
		$offset = $options->timezone - $options->serverTimezone;
		$attach_gif = Typecho_Common::url('Attachment/attach.gif', $options->pluginUrl);
		$db = Typecho_Db::get();
		$cid = $matches[1];
		$attach = $db->fetchRow($db->select()->from('table.contents')->where('type = \'attachment\' AND cid = ?', $cid));
		if (empty($attach)) {
			return '<div><img style="vertical-align:middle;" src="'.$attach_gif.'" alt="'._t('附件').'"/>'._t('附件ID错误').'</div>';
		}
		$attach_date = ', '._t('最后修改: ').date('Y-m-d H:i', $attach['modified'] + $offset);
		$attach_text = unserialize($attach['text']);
		$attach_size = round($attach_text['size'] / 1024, 1)." KB";
		$attach_url = Typecho_Common::url('download/'.$cid, $options->index);
		if (isset($options->plugins['activated']['Stat'])) {
			$attach_views = ', '._t('下载次数: ').$attach['views'];
		} else {
			$attach_views = '';
		}
		$text = '<div class="attachment"><img style="vertical-align:middle;" src="'.$attach_gif.'" alt="'._t('附件').'"/> <a href="'.$attach_url.'" title="'._t('点击下载').'" target="_blank">'.$attach['title'].'</a> <span class="num">('.$attach_size.$attach_views.$attach_date.')</span></div>';

		return $text;
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;
        $text = htmlspecialchars_decode($text); //fix 17.10.30 Markdown
        
        if ($widget instanceof Widget_Archive || $widget instanceof Widget_Abstract_Comments) {
            return preg_replace_callback("/<attach>(\d+)<\/attach>/is", array('Attachment_Plugin', 'parseCallback'), $text);
        } else {
            return $text;
        }
    }
}
