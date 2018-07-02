<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Smilies_Action extends Typecho_Widget implements Widget_Interface_Do
{

	/**
	 * 扫描表情文件夹
	 * 
	 * @access public
	 * @return void
	 */
	public function scanfolders()
	{
		$plugindir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/Smilies/';
		$lists = array();
		$rests =array();
		$set = $this->request->set;
		$option = Helper::options();
		$settings = $option->plugin('Smilies');
		$curset = $settings->smiliesset;

		$routes = glob($plugindir.'*',GLOB_ONLYDIR);
		if ($routes) {
			$folder = '';
			$locations = array();

			foreach ($routes as $route) {
				$folder = str_replace($plugindir,'',$route);
				//检索图片后缀
				$locations = glob($plugindir.$folder.'/*.{gif,jpg,jpeg,png,tiff,bmp,svg,GIF,JPG,JPEG,PNG,TIFF,BMP,SVG}',GLOB_BRACE|GLOB_NOSORT);

				array_walk($locations,array(__CLASS__ ,'cname'),'');
				$folder = self::cname($folder);

				//分割标准组
				$lists[$folder] = array_splice($locations,-22);
				$rests[$folder] = $locations;
				if (in_array($folder,array('tieba','weibo','wordpress'))) {
					//预设默认排序
					$lists[$folder] = array('icon_mrgreen.png','icon_neutral.png','icon_twisted.png','icon_arrow.png','icon_eek.png','icon_smile.png','icon_confused.png','icon_cool.png','icon_evil.png','icon_biggrin.png','icon_idea.png','icon_redface.png','icon_razz.png','icon_rolleyes.png','icon_wink.png','icon_cry.png','icon_surprised.png','icon_lol.png','icon_mad.png','icon_sad.png','icon_exclaim.png','icon_question.png');
				}
			}
		}

		//菜单项目显示
		$opts = '<option value="none">'._t('没有表情包').'</option>';
		if ($lists) {
			$opts = '';
			$keys = array_keys($lists);
			foreach ($keys as $key) {
				$opts .= '<option value="'.$key.'"'.($key==$curset ? ' selected="true"' : '').'>'.$key.'</option>';
			}
		}

		$set = $set ? $set : $curset;
		$grids = _t('没有在文件夹%s下找到表情图片','<span style="color:#467B96;">'.$set.'</span>');
		$extras = '';
		//排序表情显示
		if (isset($lists[$set])) {
			$list = $lists[''.$set.''];
			$grids = '<div class="gridly '.$set.'">';
			$names = $set==$curset ? explode('|',$settings->smsort) : $list;
			foreach ($names as $name) {
				$grids .= '<div class="td" id="'.$name.'" title="'._t('拖动对应').'" style="cursor:move;"><img src="'.$option->pluginUrl.'/Smilies/'.$set.'/'.$name.'" alt=""/></div>';
			}
			$grids .= '</div>';
		}

		//更多表情显示
		if (isset($rests[$set])) {
			$rest = $rests[''.$set.''];
			$extras = '<div class="'.$set.'">';
			if ($rest) {
				$extras .= '<div class="caption">------ &#8659; '._t('该表情包下的更多图片').' ------</div>';
			}
			foreach ($rest as $rname) {
				$extras .= '<div class="fix" title="'._t('右击复制图片地址').'"><img src="'.$option->pluginUrl.'/Smilies/'.$set.'/'.$rname.'" alt=""/></div>';
			}
			$extras .= '</div>';
		}

		$parse = Json::encode(array($lists,$opts,$grids,$extras));
		$this->response->throwJson($parse);
	}

	/**
	 * 转码中文名称
	 * 
	 * @access private
	 * @return string
	 */
	private static function cname(&$value) {
		$pagecode = 'utf-8';
		$code = function_exists('mb_detect_encoding') ? strtolower(mb_detect_encoding($value, array('ASCII','GB2312','GBK','UTF-8'))) : $pagecode;

		if ($code=='gb2312' || $code=='euc-cn') {
			if (function_exists('iconv')) {
				$value = iconv($code,$pagecode,$value);
			} else if (function_exists('mb_convert_encoding')) {
				$value = mb_convert_encoding($value,$pagecode,$code);
			}
		}
		$value = preg_replace('/^.+[\\\\\\/]/','',$value);

		return $value;
	}

	/**
	 * 绑定动作
	 * 
	 * @access public
	 * @return void
	 */
	public function action()
	{
		Helper::security()->protect();
		$this->on($this->request->isPost())->scanfolders();
		$this->response->goBack();
	}

}