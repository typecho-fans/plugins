<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 友情链接插件 【<a href="https://github.com/typecho-fans/plugins" target="_blank">TF</a>社区维护版】
 * 
 * @package Links
 * @author 懵仙兔兔, Hanny
 * @version 1.2.3
 * @dependence 14.10.10-*
 * @link https://github.com/typecho-fans/plugins/tree/master/Links
 *
 * version 1.2.3 at 2020-06-30 by Typecho Fans (合并多人修改)
 * 友链添加noopener外链属性
 * Action使用加盐地址
 * 文本字段入库过滤XSS
 * 修复自动获取邮箱头像Api失效问题
 * 增加图片尺寸参数支持
 * 增加原生设置选项，方便修改源码规则和默认图片尺寸
 * 
 * version 1.2.2 at 2020-03-11 by 懵仙兔兔
 * 修复一个小BUG
 * 
 * version 1.2.1 at 2020-03-03 by 懵仙兔兔
 * 修复邮箱头像解析问题
 * 优化逻辑问题
 * 
 * version 1.2.0 at 2020-02-16 by 懵仙兔兔
 * 增加友链禁用功能
 * 增加友链邮箱功能
 * 增加友链邮箱解析头像链接功能
 * 修正数据表的占用大小问题
 * 
 * version 1.1.3 at 2020-02-08 by 懵仙兔兔
 * 修复已存在表激活失败、表检测失败
 * 
 * version 1.1.2 at 2019-08-26 by 泽泽社长
 * 修复越权漏洞
 * 
 * 历史版本
 * version 1.1.1 at 2014-12-14
 * 修改支持Typecho 1.0
 * 修正Typecho 1.0下不能删除的BUG
 *
 * version 1.1.0 at 2013-12-08
 * 修改支持Typecho 0.9

 * version 1.0.4 at 2010-06-30
 * 修正数据表的前缀问题
 * 在Pattern里加上所有的数据表字段
 
 * version 1.0.3 at 2010-06-20
 * 修改图片链接的支持方式。
 * 增加链接分类功能
 * 增加自定义字段，以便用户自定义扩展
 * 增加多种链接输出方式。
 * 增加较详细的帮助文档
 * 增加在自定义页面引用标签，方便友情链接页面的引用
 *
 * version 1.0.2 at 2010-05-16
 * 增加SQLite支持
 *
 * version 1.0.1 at 2009-12-27
 * 增加显示链接描述
 * 增加首页链接数量限制功能
 * 增加图片链接功能

 * version 1.0.0 at 2009-12-12
 * 实现友情链接的基本功能
 * 包括: 添加 删除 修改 排序
 */
class Links_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return string
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		$info = Links_Plugin::linksInstall();
		Helper::addPanel(3, 'Links/manage-links.php', _t('友情链接'), _t('管理友情链接'), 'administrator');
		Helper::addAction('links-edit', 'Links_Action');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Links_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Links_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('Links_Plugin', 'parse');
		/* 模版调用钩子 */
		Typecho_Plugin::factory('Widget_Archive')->callLinks = array('Links_Plugin', 'output_str');
		return _t($info);
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
		Helper::removeAction('links-edit');
		Helper::removePanel(3, 'Links/manage-links.php');
	}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        echo '
<style type="text/css">
table {
	background: #FFF;
	border: 2px solid #e3e3e3;
	color: #666;
	font-size: .92857em;
	width: 452px;
}
th {
	border: 2px solid #e3e3e3;
	padding: 5px;
}
table td {
	border-top: 1px solid #e3e3e3;
	padding: 3px;
	text-align: center;
	border-right: 2px solid #e3e3e3;
}
.field {
	color: #467B96;
	font-weight: bold;
}
</style>
<table>
<colgroup>
<col width="30%"/>
<col width="70%"/>
</colgroup>
<thead>
<tr>
<th>'._t('字段').'</th>
<th>'._t('对应数据').'</th>
</tr>
</thead>
<tbody>
<tr>
<td class="field">{url}</td>
<td>'._t('友链地址').'</td>
</tr>
<tr>
<td class="field">{title}<br/>{description}</td>
<td>'._t('友链描述').'</td>
</tr>
<tr>
<td class="field">{name}</td>
<td>'._t('友链名称').'</td>
</tr>
<tr>
<td class="field">{image}</td>
<td>'._t('友链图片').'</td>
</tr>
<tr>
<td class="field">{size}</td>
<td>'._t('图片尺寸').'</td>
</tr>
<tr>
<td class="field">{sort}</td>
<td>'._t('友链分类').'</td>
</tr>
<tr>
<td class="field">{user}</td>
<td>'._t('自定义数据').'</td>
</tr>
<tr>
<td class="field">{lid}</td>
<td>'._t('链接的数据表ID').'</td>
</tr>
</tbody>
</table>';
        $pattern_text = new Typecho_Widget_Helper_Form_Element_Textarea('pattern_text',
		null, '<li><a href="{url}" title="{title}" target="_blank" rel="noopener">{name}</a></li>', _t('SHOW_TEXT模式源码规则'),
		_t('使用SHOW_TEXT(仅文字)模式输出时的源码，可按上表规则替换其中字段'));
        $form->addInput($pattern_text);
        $pattern_img = new Typecho_Widget_Helper_Form_Element_Textarea('pattern_img',
		null, '<li><a href="{url}" title="{title}" target="_blank" rel="noopener"><img src="{image}" alt="{name}" width="{size}" height="{size}" /></a></li>', _t('SHOW_IMG模式源码规则'),
		_t('使用SHOW_IMG(仅图片)模式输出时的源码，可按上表规则替换其中字段'));
        $form->addInput($pattern_img);
        $pattern_mix = new Typecho_Widget_Helper_Form_Element_Textarea('pattern_mix',
		null, '<li><a href="{url}" title="{title}" target="_blank" rel="noopener"><img src="{image}" alt="{name}" width="{size}" height="{size}" /> <span>{name}</span></a></li>', _t('SHOW_MIX模式源码规则'),
		_t('使用SHOW_MIX(图文混合)模式输出时的源码，可按上表规则替换其中字段'));
        $form->addInput($pattern_mix);
        $dsize = new Typecho_Widget_Helper_Form_Element_Text('dsize',
		NULL,'32',_t('默认输出图片尺寸'),_t('调用时如果未指定尺寸参数默认输出的图片大小(单位px不用填写)'));
        $dsize->input->setAttribute('class','w-10');
        $form->addInput($dsize->addRule('isInteger',_t('请填写整数数字')));
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

	public static function linksInstall()
	{
		$installDb = Typecho_Db::get();
		$type = explode('_', $installDb->getAdapterName());
		$type = array_pop($type);
		$prefix = $installDb->getPrefix();
		$scripts = file_get_contents('usr/plugins/Links/'.$type.'.sql');
		$scripts = str_replace('typecho_', $prefix, $scripts);
		$scripts = str_replace('%charset%', 'utf8', $scripts);
		$scripts = explode(';', $scripts);
		try {
			foreach ($scripts as $script) {
				$script = trim($script);
				if ($script) {
					$installDb->query($script, Typecho_Db::WRITE);
				}
			}
			return _t('建立友情链接数据表，插件启用成功');
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if (('Mysql' == $type && (1050 == $code || '42S01' == $code)) ||
					('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
				try {
					$script = 'SELECT `lid`, `name`, `url`, `sort`, `email`, `image`, `description`, `user`, `state`, `order` from `'.$prefix.'links`';
					$installDb->query($script, Typecho_Db::READ);
					return _t('检测到友情链接数据表，友情链接插件启用成功');					
				} catch (Typecho_Db_Exception $e) {
					$code = $e->getCode();
					if (('Mysql' == $type && (1054 == $code || '42S22' == $code)) ||
							('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
						return Links_Plugin::linksUpdate($installDb, $type, $prefix);
					}
					throw new Typecho_Plugin_Exception(_t('数据表检测失败，友情链接插件启用失败。错误号：').$code);
				}
			} else {
				throw new Typecho_Plugin_Exception(_t('数据表建立失败，友情链接插件启用失败。错误号：').$code);
			}
		}
	}
	
	public static function linksUpdate($installDb, $type, $prefix)
	{
		$scripts = file_get_contents('usr/plugins/Links/Update_'.$type.'.sql');
		$scripts = str_replace('typecho_', $prefix, $scripts);
		$scripts = str_replace('%charset%', 'utf8', $scripts);
		$scripts = explode(';', $scripts);
		try {
			foreach ($scripts as $script) {
				$script = trim($script);
				if ($script) {
					$installDb->query($script, Typecho_Db::WRITE);
				}
			}
			return _t('检测到旧版本友情链接数据表，升级成功');
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if (('Mysql' == $type && (1060 == $code || '42S21' == $code))) {
				return _t('友情链接数据表已经存在，插件启用成功');
			}
			throw new Typecho_Plugin_Exception(_t('友情链接插件启用失败。错误号：').$code);
		}
	}

	public static function form($action = null)
	{
		/** 构建表格 */
		$options = Typecho_Widget::widget('Widget_Options');
		$form = new Typecho_Widget_Helper_Form(Helper::security()->getIndex('/action/links-edit'),
		Typecho_Widget_Helper_Form::POST_METHOD);
		
		/** 友链名称 */
		$name = new Typecho_Widget_Helper_Form_Element_Text('name', null, null, _t('友链名称*'));
		$form->addInput($name);
		
		/** 友链地址 */
		$url = new Typecho_Widget_Helper_Form_Element_Text('url', null, 'http://', _t('友链地址*'));
		$form->addInput($url);
		
		/** 友链分类 */
		$sort = new Typecho_Widget_Helper_Form_Element_Text('sort', null, null, _t('友链分类'), _t('建议以英文字母开头，只包含字母与数字'));
		$form->addInput($sort);
		
		/** 友链邮箱 */
		$email = new Typecho_Widget_Helper_Form_Element_Text('email', null, null, _t('友链邮箱'), _t('填写友链邮箱'));
		$form->addInput($email);
		
		/** 友链图片 */
		$image = new Typecho_Widget_Helper_Form_Element_Text('image', null, null, _t('友链图片'),  _t('需要以http://或https://开头，留空表示没有友链图片'));
		$form->addInput($image);
		
		/** 友链描述 */
		$description =  new Typecho_Widget_Helper_Form_Element_Textarea('description', null, null, _t('友链描述'));
		$form->addInput($description);
		
		/** 自定义数据 */
		$user = new Typecho_Widget_Helper_Form_Element_Text('user', null, null, _t('自定义数据'), _t('该项用于用户自定义数据扩展'));
		$form->addInput($user);
		
		/** 友链状态 */
		$list = array('0' => '禁用', '1' => '启用');
		$state = new Typecho_Widget_Helper_Form_Element_Radio('state', $list, '1', '友链状态');
		$form->addInput($state);
		
		/** 友链动作 */
		$do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
		$form->addInput($do);
		
		/** 友链主键 */
		$lid = new Typecho_Widget_Helper_Form_Element_Hidden('lid');
		$form->addInput($lid);
		
		/** 提交按钮 */
		$submit = new Typecho_Widget_Helper_Form_Element_Submit();
		$submit->input->setAttribute('class', 'btn primary');
		$form->addItem($submit);
		$request = Typecho_Request::getInstance();

        if (isset($request->lid) && 'insert' != $action) {
            /** 更新模式 */
			$db = Typecho_Db::get();
			$prefix = $db->getPrefix();
            $link = $db->fetchRow($db->select()->from($prefix.'links')->where('lid = ?', $request->lid));
            if (!$link) {
                throw new Typecho_Widget_Exception(_t('友链不存在'), 404);
            }
            
            $name->value($link['name']);
            $url->value($link['url']);
            $sort->value($link['sort']);
            $email->value($link['email']);
            $image->value($link['image']);
            $description->value($link['description']);
            $user->value($link['user']);
            $state->value($link['state']);
            $do->value('update');
            $lid->value($link['lid']);
            $submit->value(_t('编辑友链'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('增加友链'));
            $_action = 'insert';
        }
        
        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
			$name->addRule('required', _t('必须填写友链名称'));
			$url->addRule('required', _t('必须填写友链地址'));
			$url->addRule('url', _t('不是一个合法的链接地址'));
			$email->addRule('email', _t('不是一个合法的邮箱地址'));
			$image->addRule('url', _t('不是一个合法的图片地址'));
			$name->addRule('maxLength', _t('友链名称最多包含50个字符'), 50);
			$url->addRule('maxLength', _t('友链地址最多包含200个字符'), 200);
			$sort->addRule('maxLength', _t('友链分类最多包含50个字符'), 50);
			$email->addRule('maxLength', _t('友链邮箱最多包含50个字符'), 50);
			$image->addRule('maxLength', _t('友链图片最多包含200个字符'), 200);
			$description->addRule('maxLength', _t('友链描述最多包含200个字符'), 200);
			$user->addRule('maxLength', _t('自定义数据最多包含200个字符'), 200);
        }
        if ('update' == $action) {
            $lid->addRule('required', _t('友链主键不存在'));
            $lid->addRule(array(new Links_Plugin, 'LinkExists'), _t('友链不存在'));
        }
        return $form;
	}

	public static function LinkExists($lid)
	{
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$link = $db->fetchRow($db->select()->from($prefix.'links')->where('lid = ?', $lid)->limit(1));
		return $link ? true : false;
	}

    /**
     * 控制输出格式
     */
	public static function output_str($widget, array $params)
	{
		$options = Typecho_Widget::widget('Widget_Options');
		$settings = $options->plugin('Links');
		if (!isset($options->plugins['activated']['Links'])) {
			return _t('友情链接插件未激活');
		}
		//验证默认参数
		$pattern = !empty($params[0]) && is_string($params[0]) ? $params[0] : 'SHOW_TEXT';
		$links_num = !empty($params[1]) && is_numeric($params[1]) ? $params[1] : 0;
		$sort = !empty($params[2]) && is_string($params[2]) ? $params[2] : 'ALL';
		$size = !empty($params[3]) && is_numeric($params[3]) ? $params[3] : $settings->dsize;
		$mode= isset($params[4]) ? $params[4] : 'FUNC';
		if ($pattern == 'SHOW_TEXT') {
			$pattern = $settings->pattern_text."\n";
		} elseif ($pattern == 'SHOW_IMG') {
			$pattern = $settings->pattern_img."\n";
		} elseif ($pattern == 'SHOW_MIX') {
			$pattern = $settings->pattern_mix."\n";
		}
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$sql = $db->select()->from($prefix.'links');
		if ($sort !== 'ALL') {
			$sql = $sql->where('sort=?', $sort);
		}
		$sql = $sql->order($prefix.'links.order', Typecho_Db::SORT_ASC);
		$links_num = intval($links_num);
		if ($links_num > 0) {
			$sql = $sql->limit($links_num);
		}
		$links = $db->fetchAll($sql);
		$str = '';
		foreach ($links as $link) {
			if ($link['image'] == null) {
				$link['image'] = $options->siteUrl.'/usr/plugins/Links/nopic.png';
		if($link['email'] != null){
			$link['image'] ='https://gravatar.helingqi.com/avatar/'.md5($link['email']).'?s='.$size.'&d=mm';
		}
			}
			if ($link['state'] == 1) {
			$str .= str_replace(
				array('{lid}', '{name}', '{url}', '{sort}', '{title}', '{description}', '{image}', '{user}', '{size}'),
				array($link['lid'], $link['name'], $link['url'], $link['sort'], $link['description'], $link['description'], $link['image'], $link['user'], $size),
				$pattern
			);
			}
		}
		if ($mode == 'HTML') {
			return $str;
		} else{
			echo $str;
		}
	}

	//输出
	public static function output($pattern='SHOW_TEXT', $links_num=0, $sort='ALL', $size=32)
	{
		return Links_Plugin::output_str('', array($pattern, $links_num, $sort, $size));
	}
	
    /**
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback($matches)
    {
		return Links_Plugin::output_str('', array($matches[4], $matches[1], $matches[2], $matches[3], 'HTML'));
    }

    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;
        
        if ($widget instanceof Widget_Archive || $widget instanceof Widget_Abstract_Comments) {
            return preg_replace_callback('/<links\s*(\d*)\s*(\w*)\s*(\d*)>\s*(.*?)\s*<\/links>/is', array('Links_Plugin', 'parseCallback'), $text);
        } else {
            return $text;
        }
    }
}
