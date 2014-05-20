<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 无缝集成HighSlide双核版实现自动化弹窗与页面相册功能. 
 * @package HighSlide
 * @author 羽中
 * @version 1.4.6-rc
 * @dependence 14.3.14-*
 * @link http://www.jzwalk.com/archives/net/highslide-for-typecho
 */
class HighSlide_Plugin implements Typecho_Plugin_Interface
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
		$info = HighSlide_Plugin::galleryinstall();

		Helper::addPanel(3,'HighSlide/manage-gallery.php',_t('页面相册'),_t('配置页面相册 <span style="color:#999;">(HighSlide全功能版核心支持)</span>'),'administrator');
		Helper::addAction('gallery-edit','HighSlide_Action');

		Typecho_Plugin::factory('Widget_Archive')->header = array('HighSlide_Plugin','headlink');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('HighSlide_Plugin','footlink');

		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('HighSlide_Plugin','autohighslide');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('HighSlide_Plugin','autohighslide');

		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('HighSlide_Plugin','jshelper');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('HighSlide_Plugin','jshelper');
		Typecho_Plugin::factory('admin/write-post.php')->option = array('HighSlide_Plugin','uploadpanel');

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
		Helper::removeAction('gallery-edit');
		Helper::removePanel(3,'HighSlide/manage-gallery.php');
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
?>
<script type="text/javascript" src="<?php Helper::options()->adminUrl('js/jquery.js'); ?>"></script>
<script type="text/javascript">
$(function() {
	var full = $('#mode-highslide-full-packed-js'),
		basic = $('#mode-highslide-packed-js'),
		adv = $('.advanced'),
		option = $('#typecho-option-item-align-9,#typecho-option-item-opac-10,#typecho-option-item-slide-11,#typecho-option-item-nextimg-12,#typecho-option-item-cpos-13,#typecho-option-item-wrap-14'),
		input = option.find('input,select');
	if(!full.is(':checked')) {
		adv.attr('style','color:#999;font-weight:bold');
		option.attr('style','color:#999');
	}
	full.click(function() {
		adv.attr('style','color:#467B96;font-weight:bold');
		option.removeAttr('style');
	});
	basic.click(function() {
		adv.attr('style','color:#999;font-weight:bold');
		option.attr('style','color:#999');
	});
});
</script>
<?php
		$mode = new Typecho_Widget_Helper_Form_Element_Radio('mode',
			array('highslide.packed.js'=>_t('基础版 <span style="color:#999;font-size:0.92857em;">(25.2K)支持插图弹窗</span>'),'highslide-full.packed.js'=>_t('全功能版 <span style="color:#999;font-size:0.92857em;">(46.8K)支持插图幻灯/html弹窗/页面相册等</span>')),'highslide.packed.js',_t('核心选择'));
		$form->addInput($mode);

		$rpmode = new Typecho_Widget_Helper_Form_Element_Radio('rpmode',
			array('ahref'=>_t('链接图片'),'imgsrc'=>_t('所有图片')),'ahref',_t('应用模式'),NULL);
		$form->addInput($rpmode);

		$rplist = new Typecho_Widget_Helper_Form_Element_Checkbox('rplist',
			array('index'=>_t('首页'),'post'=>_t('文章页'),'page'=>_t('独立页'),'archive'=>_t('索引页')),array('index','post','page','archive'),_t('应用范围'),NULL);
		$form->addInput($rplist);

		$lang = new Typecho_Widget_Helper_Form_Element_Radio('lang',
			array('chs'=>_t('中文'),'eng'=>_t('英文')),'chs',_t('提示语言'));
		$form->addInput($lang);

		$outline= new Typecho_Widget_Helper_Form_Element_Radio('outline',
			array(''=>_t('无边框'),'rounded-white'=>_t('圆角白'),'rounded-black'=>_t('圆角黑'),'glossy-dark'=>_t('亮泽黑'),'outer-glow'=>_t('外发光'),'beveled'=>_t('半透明')),'',_t('边框风格'));
		$form->addInput($outline);

		$butn = new Typecho_Widget_Helper_Form_Element_Radio('butn',
			array('false'=>_t('不显示'),'true'=>_t('显示')),'false',_t('关闭按钮'));
		$form->addInput($butn);

		$ltext = new Typecho_Widget_Helper_Form_Element_Text('ltext',
			NULL,'&copy; '.$_SERVER['HTTP_HOST'].'',_t('角标文字'),_t('弹窗logo文字与显示位置，留空则不显示'));
		$ltext->input->setAttribute('class','mini');
		$form->addInput($ltext);

		$lpos = new Typecho_Widget_Helper_Form_Element_Select('lpos',
			array('top left'=>_t('左上'),'top center'=>_t('中上'),'top right'=>_t('右上'),'bottom left'=>_t('左下'),'bottom center'=>_t('中下'),'bottom right'=>_t('右下')),'top left','');
		$lpos->removeAttribute('class','typecho-label');
		$lpos->input->setAttribute('style','position:absolute;bottom:42px;left:165px;');
		$lpos->setAttribute('style','list-style:none;position:relative;');
		$form->addInput($lpos);

		$capt = new Typecho_Widget_Helper_Form_Element_Radio('capt',
			array(''=>_t('不显示'),'this.a.title'=>_t('显示链接title'),'this.thumb.alt'=>_t('显示图片alt')),'',_t('图片说明'),_t('例: &#60;a href="http://xx.jpg" title="图片说明写这"&#62;&#60;img src="http://xxx.jpg" alt="或者写这显示"/&#62;&#60;/a&#62;<p class="advanced" style="color:#467B96;font-weight:bold;">全功能版设置 ———————————————————————————————————————</p>'));
		$form->addInput($capt);

		$align = new Typecho_Widget_Helper_Form_Element_Radio('align',
			array('default'=>_t('默认'),'center'=>_t('居中')),'default',_t('弹窗位置'));
		$form->addInput($align);

		$opac = new Typecho_Widget_Helper_Form_Element_Text('opac',
			NULL,'0.65',_t('背景遮罩'),_t('可填入0~1之间小数, 代表透明至纯黑'));
		$opac->input->setAttribute('class','mini');
		$form->addInput($opac->addRule('isFloat',_t('请填写数字')));

		$slide = new Typecho_Widget_Helper_Form_Element_Radio('slide',
			array('false'=>_t('关闭'),'true'=>_t('开启')),'true',_t('幻灯按钮'));
		$form->addInput($slide);

		$nextimg = new Typecho_Widget_Helper_Form_Element_Radio('nextimg',
			array('false'=>_t('否'),'true'=>_t('是')),'false',_t('自动翻页'),_t('开启后点击图片为显示下一张'));
		$form->addInput($nextimg);

		$cpos = new Typecho_Widget_Helper_Form_Element_Radio('cpos',
			array(''=>_t('不显示'),'caption'=>_t('底部显示'),'heading'=>_t('顶部显示')),'',_t('图片序数'));
		$form->addInput($cpos);

		$wrap = new Typecho_Widget_Helper_Form_Element_Checkbox('wrap',
			array('draggable-header'=>_t('标题栏 <span style="color:#999;font-size:0.92857em;">支持&#60;hs title="标题"&#62;显示</span>'),'no-footer'=>_t('无拉伸')),NULL,_t('html弹窗效果'));
		$form->addInput($wrap);

		//相册设置隐藏域
		$storage = new Typecho_Widget_Helper_Form_Element_Hidden('storage',
			array('local','qiniu','upyun','bcs'),'local');
		$form->addInput($storage);
		$local = new Typecho_Widget_Helper_Form_Element_Hidden('local',NULL,'/usr/uploads/HSgallery/');
		$form->addInput($local);
		$qiniubucket = new Typecho_Widget_Helper_Form_Element_Hidden('qiniubucket',NULL,'');
		$form->addInput($qiniubucket);
		$qiniudomain = new Typecho_Widget_Helper_Form_Element_Hidden('qiniudomain',NULL,'http://');
		$form->addInput($qiniudomain);
		$qiniuaccesskey = new Typecho_Widget_Helper_Form_Element_Hidden('qiniuaccesskey',NULL,'');
		$form->addInput($qiniuaccesskey);
		$qiniusecretkey = new Typecho_Widget_Helper_Form_Element_Hidden('qiniusecretkey',NULL,'');
		$form->addInput($qiniusecretkey);
		$qiniuprefix = new Typecho_Widget_Helper_Form_Element_Hidden('qiniuprefix',NULL,'usr/uploads/HSgallery/');
		$form->addInput($qiniuprefix);
		$upyunbucket = new Typecho_Widget_Helper_Form_Element_Hidden('upyunbucket',NULL,'');
		$form->addInput($upyunbucket);
		$upyundomain = new Typecho_Widget_Helper_Form_Element_Hidden('upyundomain',NULL,'http://');
		$form->addInput($upyundomain);
		$upyunuser = new Typecho_Widget_Helper_Form_Element_Hidden('upyunuser',NULL,'');
		$form->addInput($upyunuser);
		$upyunpwd = new Typecho_Widget_Helper_Form_Element_Hidden('upyunpwd',NULL,'');
		$form->addInput($upyunpwd);
		$upyunkey = new Typecho_Widget_Helper_Form_Element_Hidden('upyunkey',NULL,'');
		$form->addInput($upyunkey);
		$upyunprefix = new Typecho_Widget_Helper_Form_Element_Hidden('upyunprefix',NULL,'/usr/uploads/HSgallery/');
		$form->addInput($upyunprefix);
		$bcsbucket = new Typecho_Widget_Helper_Form_Element_Hidden('bcsbucket',NULL,'');
		$form->addInput($bcsbucket);
		$bcsapikey = new Typecho_Widget_Helper_Form_Element_Hidden('bcsapikey',NULL,'');
		$form->addInput($bcsapikey);
		$bcssecretkey = new Typecho_Widget_Helper_Form_Element_Hidden('bcssecretkey',NULL,'');
		$form->addInput($bcssecretkey);
		$bcsprefix = new Typecho_Widget_Helper_Form_Element_Hidden('bcsprefix',NULL,'/usr/uploads/HSgallery/');
		$form->addInput($bcsprefix);
		$thumbfix = new Typecho_Widget_Helper_Form_Element_Hidden('thumbfix',
			array('fixedwidth','fixedheight','fixedratio'),'fixedwidth');
		$form->addInput($thumbfix);
		$fixedwidth = new Typecho_Widget_Helper_Form_Element_Hidden('fixedwidth',NULL,'100');
		$form->addInput($fixedwidth);
		$fixedheight = new Typecho_Widget_Helper_Form_Element_Hidden('fixedheight',NULL,'200');
		$form->addInput($fixedheight);
		$fixedratio = new Typecho_Widget_Helper_Form_Element_Hidden('fixedratio',NULL,'4:3');
		$form->addInput($fixedratio);
		$gallery = new Typecho_Widget_Helper_Form_Element_Hidden('gallery',
			array('gallery-horizontal-strip','gallery-thumbstrip-above','gallery-vertical-strip','gallery-in-box','gallery-floating-thumbs','gallery-floating-caption','gallery-controls-in-heading','gallery-in-page'),'gallery-horizontal-strip');
		$form->addInput($gallery);
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
	 * 初始化数据表
	 *
	 * @access public
	 * @return string
	 * @throws Typecho_Plugin_Exception
	 */
	public static function galleryinstall()
	{
		$installdb = Typecho_Db::get();
		$type = explode('_',$installdb->getAdapterName());
		$type = array_pop($type);
		$prefix = $installdb->getPrefix();

		$scripts = file_get_contents('usr/plugins/HighSlide/'.$type.'.sql');
		$scripts = str_replace('typecho_',$prefix,$scripts);
		$scripts = str_replace('%charset%','utf8',$scripts);
		$scripts = explode(';',$scripts);

		try {
			foreach ($scripts as $script) {
				$script = trim($script);
				if ($script) {
					$installdb->query($script,Typecho_Db::WRITE);
				}
			}
			return _t('建立页面相册数据表, 插件启用成功');
		} catch (Typecho_Db_Exception $e) {
			$code = $e->getCode();
			if(('Mysql'==$type&&1050==$code)||
					('SQLite'==$type&&('HY000'==$code||1==$code))) {
				try {
					$script = 'SELECT `gid`,`name`,`thumb`,`sort`,`image`,`description`,`order` from `'.$prefix.'gallery`';
					$installdb->query($script,Typecho_Db::READ);
					return _t('检测到页面相册数据表, 插件启用成功');
				} catch (Typecho_Db_Exception $e) {
					$code = $e->getCode();
					throw new Typecho_Plugin_Exception(_t('数据表检测失败, 插件启用失败. 错误号: '.$code));
				}
			} else {
				throw new Typecho_Plugin_Exception(_t('数据表建立失败, 插件启用失败. 错误号: '.$code));
			}
		}
	}

	/**
	 * 调取七牛许可
	 * 
	 * @access public
	 * @return void
	 */
	public static function qiniuset($accesskey,$secretkey)
	{
		require_once("cloud/qiniu/io.php");
		require_once("cloud/qiniu/rs.php");
		require_once("cloud/qiniu/rsf.php");
		Qiniu_setKeys($accesskey,$secretkey);
	}

	/**
	 * 调取又拍云许可
	 * 
	 * @access public
	 * @return void
	 */
	public static function upyunset()
	{
		$settings = Helper::options()->plugin('HighSlide');
		require_once("cloud/upyun/upyun.class.php");
		return new UpYun($settings->upyunbucket,$settings->upyunuser,$settings->upyunpwd);
	}

	/**
	 * 调取百度BCS许可
	 * 
	 * @access public
	 * @return void
	 */
	public static function bcsset()
	{
		$settings = Helper::options()->plugin('HighSlide');
		require_once("cloud/bcs/bcs.class.php");
		return new BaiduBCS($settings->bcsapikey,$settings->bcssecretkey,'bcs.duapp.com');
	}

	/**
	 * 输出文件前缀
	 * 
	 * @access public
	 * @param string $path 附件源路径
	 * @param string $url 附件源地址
	 * @return Typecho_Config
	 */
	public static function filedata($path = NULL,$url = NULL)
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');

		//判断本地附件源
		if (strpos($url,$options->siteUrl)===false) {
			if ($settings->storage=='local') {
				$prefix = ($path)?$path:$settings->local;
				$fileurl = Typecho_Common::url($prefix,$options->siteUrl);
				$filedir = Typecho_Common::url($prefix,__TYPECHO_ROOT_DIR__);
			}
			if ($settings->storage=='qiniu') {
				$prefix = ($path)?$path:$settings->qiniuprefix;
				$fileurl = Typecho_Common::url($prefix,$settings->qiniudomain);
				$filedir = ($path)?substr($path,1):$settings->qiniuprefix;
			}
			if ($settings->storage=='upyun') {
				$prefix = ($path)?$path:$settings->upyunprefix;
				$fileurl = Typecho_Common::url($prefix,$settings->upyundomain);
				$filedir = Typecho_Common::url($prefix,'');
			}
			if ($settings->storage=='bcs') {
				$prefix = ($path)?$path:$settings->bcsprefix;
				$fileurl = Typecho_Common::url($prefix,'http://bcs.duapp.com');
				$filedir = Typecho_Common::url($prefix,'');
			}
		} else {
			$fileurl = Typecho_Common::url($path,$options->siteUrl);
			$filedir = Typecho_Common::url($path,__TYPECHO_ROOT_DIR__);
		}

		return new Typecho_Config(array('url'=>$fileurl,'dir'=>$filedir));
	}

	/**
	 * 输出上传列表
	 *
	 * @access public
	 * @return void
	 */
	public static function filelist()
	{
		$settings = Helper::options()->plugin('HighSlide');
		$db = Typecho_Db::get();
		$filedata = self::filedata();
		$fileurl = $filedata->url;
		$filedir = $filedata->dir;

		//获取对比数据
		$urls = $db->fetchAll($db->select('image','thumb')->from('table.gallery'));
		$images = array();
		$thumbs = array();
		foreach ($urls as $url) {
			$images[] = $url['image'];
			$thumbs[] = $url['thumb'];
		}

		//获取本地列表
		if ($settings->storage=='local') {
			$lists = glob($filedir.'*[0-9].{gif,jpg,jpeg,png,tiff,bmp,GIF,JPG,JPEG,PNG,TIFF,BMP}',GLOB_BRACE|GLOB_NOSORT);
			foreach ($lists as $list) {
				$datas[] = array('key'=>$list,'fsize'=>filesize($list));
			}
		}

		//获取七牛列表
		if ($settings->storage=='qiniu') {
			self::qiniuset($settings->qiniuaccesskey,$settings->qiniusecretkey);
			$client = new Qiniu_MacHttpClient(null);
			list($result,$error) = Qiniu_RSF_ListPrefix($client,$settings->qiniubucket,$filedir);
			if ($error==null) {
				$datas = $result;
			}
		}

		//获取又拍云列表
		if ($settings->storage=='upyun') {
			$upyun = self::upyunset();
			$lists = $upyun->getList($filedir);
			foreach ($lists as $list) {
				$datas[] = array('key'=>$list['name'],'fsize'=>$list['size']);
			}
		}

		//获取百度BCS列表
		if ($settings->storage=='bcs') {
			$bcs = self::bcsset();
			$result = $bcs->list_object($settings->bcsbucket,array('prefix'=>$filedir));
			if ($result->isOK()) {
				$decode = json_decode($result->body,true);
				$lists = $decode['object_list'];
				foreach ($lists as $list) {
					$datas[] = array('key'=>$list['object'],'fsize'=>$list['size']);
				}
			}
		}

		//重构处理排序
		if (!empty($datas)) {
			foreach ($datas as $data) {
				$name = basename($data['key']);
				$keyname = (strpos($data['key'],'thumb_'))?substr($name,6,5)+1:substr($name,0,5);
				$files[''.$keyname.''] = $data;
			}
		}
		if(empty($files)) {
			return false;
		}
		ksort($files);

		$filelist = array();
		$id=0;
		foreach ($files as $file) {
			$filename = basename($file['key']);
			$filesize = number_format(ceil($file['fsize']/1024));
			//过滤输出结果
			if (!in_array($fileurl.$filename,$images)&&!in_array($fileurl.$filename,$thumbs)) {
				$filelist[] = array('id'=>$id++,'name'=>$filename,'size'=>$filesize);
			}
		}

		return $filelist;
	}

	/**
	 * 构建相册表单
	 *
	 * @access public
	 * @param string $action,$render
	 * @return Typecho_Widget_Helper_Form
	 */
	public static function form($action = NULL,$render = '1')
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');

		//图片编辑表单
		$form1 = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/gallery-edit',$options->index),
		Typecho_Widget_Helper_Form::POST_METHOD);

		$image = new Typecho_Widget_Helper_Form_Element_Text('image',
			NULL,"http://",_t('原图地址*'));
		$form1->addInput($image);

		$thumb = new Typecho_Widget_Helper_Form_Element_Text('thumb',
			NULL,"http://",_t('缩略图地址*'));
		$form1->addInput($thumb);

		$name = new Typecho_Widget_Helper_Form_Element_Text('name',
			NULL,NULL,_t('图片名称'));
		$name->input->setAttribute('class','mini');
		$form1->addInput($name);

		$description = new Typecho_Widget_Helper_Form_Element_Textarea('description',
			NULL,NULL,_t('图片描述'),_t('推荐填写, 用于展示相册中图片的文字说明效果'));
		$form1->addInput($description);

		$sort = new Typecho_Widget_Helper_Form_Element_Text('sort',
			NULL,"1",_t('相册组*'),_t('输入数字, 对应写入[GALLERY-数字]在页面调用'));
		$sort->input->setAttribute('class','w-10');
		$form1->addInput($sort);

		$do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
		$form1->addInput($do);

		$gid = new Typecho_Widget_Helper_Form_Element_Hidden('gid');
		$form1->addInput($gid);

		$submit = new Typecho_Widget_Helper_Form_Element_Submit();
		$submit->input->setAttribute('class','btn');
		$form1->addItem($submit);

		//相册设置表单
		$form2 = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/gallery-edit?do=sync',$options->index),
		Typecho_Widget_Helper_Form::POST_METHOD);

		$gallery = new Typecho_Widget_Helper_Form_Element_Select('gallery',
			array('gallery-horizontal-strip'=>_t('连环画册'),'gallery-thumbstrip-above'=>_t('黑色影夹'),'gallery-vertical-strip'=>_t('时光胶带'),'gallery-in-box'=>_t('纯白记忆'),'gallery-floating-thumbs'=>_t('往事片段'),'gallery-floating-caption'=>_t('沉默注脚'),'gallery-controls-in-heading'=>_t('岁月名片'),'gallery-in-page'=>_t('幻影橱窗(单相册)')),$settings->gallery,_t('相册风格'),_t('套装效果, 不受插件通用设置影响'));
		$form2->addInput($gallery);

		$thumboptions = array(
			'fixedwidth'=>_t('固定宽度 %s',' <input type="text" class="w-10 text-s mono" name="fixedwidth" value="'.$settings->fixedwidth.'" />'),
			'fixedheight'=>_t('固定高度 %s',' <input type="text" class="w-10 text-s mono" name="fixedheight" value="'.$settings->fixedheight.'" />'),
			'fixedratio'=>_t('固定比例 %s',' <input type="text" class="w-10 text-s mono" name="fixedratio" value="'.$settings->fixedratio.'" />'),
		);
		$thumbfix = new Typecho_Widget_Helper_Form_Element_Radio('thumbfix',
			$thumboptions,$settings->thumbfix,_t('缩略图规格'),_t('宽高单位px(无需填写), 比例注意使用半角冒号'));
		$form2->addInput($thumbfix->multiMode());

		$storage = new Typecho_Widget_Helper_Form_Element_Radio('storage',
			array('local'=>_t('本地'),'qiniu'=>_t('七牛'),'upyun'=>_t('又拍云'),'bcs'=>_t('百度BCS')),$settings->storage,_t('储存位置'));
		$form2->addInput($storage);

		$local = new Typecho_Widget_Helper_Form_Element_Text('local',
			NULL,$settings->local,_t('本地路径'),_t('确保首层目录可写, 结尾带/号'));
		$form2->addInput($local);

		$qiniubucket = new Typecho_Widget_Helper_Form_Element_Text('qiniubucket',
			NULL,$settings->qiniubucket,_t('空间名称'));
		$form2->addInput($qiniubucket);

		$qiniudomain = new Typecho_Widget_Helper_Form_Element_Text('qiniudomain',
			NULL,$settings->qiniudomain,_t('空间域名'));
		$form2->addInput($qiniudomain);

		$qiniuaccesskey = new Typecho_Widget_Helper_Form_Element_Text('qiniuaccesskey',
			NULL,$settings->qiniuaccesskey,_t('AccessKey'));
		$form2->addInput($qiniuaccesskey);

		$qiniusecretkey = new Typecho_Widget_Helper_Form_Element_Text('qiniusecretkey',
			NULL,$settings->qiniusecretkey,_t('SecretKey'));
		$form2->addInput($qiniusecretkey);

		$qiniuprefix = new Typecho_Widget_Helper_Form_Element_Text('qiniuprefix',
			NULL,$settings->qiniuprefix,_t('路径前缀'),_t('注意开头不要加/号'));
		$form2->addInput($qiniuprefix);

		$upyunbucket = new Typecho_Widget_Helper_Form_Element_Text('upyunbucket',
			NULL,$settings->upyunbucket,_t('空间名称'));
		$form2->addInput($upyunbucket);

		$upyundomain = new Typecho_Widget_Helper_Form_Element_Text('upyundomain',
			NULL,$settings->upyundomain,_t('绑定域名'));
		$form2->addInput($upyundomain);

		$upyunuser = new Typecho_Widget_Helper_Form_Element_Text('upyunuser',
			NULL,$settings->upyunuser,_t('操作员'));
		$form2->addInput($upyunuser);

		$upyunpwd = new Typecho_Widget_Helper_Form_Element_Text('upyunpwd',
			NULL,$settings->upyunpwd,_t('密码'));
		$form2->addInput($upyunpwd);

		$upyunkey = new Typecho_Widget_Helper_Form_Element_Text('upyunkey',
			NULL,$settings->upyunkey,_t('密匙'));
		$form2->addInput($upyunkey);

		$upyunprefix = new Typecho_Widget_Helper_Form_Element_Text('upyunprefix',
			NULL,$settings->upyunprefix,_t('路径前缀'));
		$form2->addInput($upyunprefix);

		$bcsbucket = new Typecho_Widget_Helper_Form_Element_Text('bcsbucket',
			NULL,$settings->bcsbucket,_t('空间名称'));
		$form2->addInput($bcsbucket);

		$bcsapikey = new Typecho_Widget_Helper_Form_Element_Text('bcsapikey',
			NULL,$settings->bcsapikey,_t('APIKey'));
		$form2->addInput($bcsapikey);

		$bcssecretkey = new Typecho_Widget_Helper_Form_Element_Text('bcssecretkey',
			NULL,$settings->bcssecretkey,_t('SecretKey'));
		$form2->addInput($bcssecretkey);

		$bcsprefix = new Typecho_Widget_Helper_Form_Element_Text('bcsprefix',
			NULL,$settings->bcsprefix,_t('路径前缀'));
		$form2->addInput($bcsprefix);

		$form2->addItem($submit);

		//隐藏模式
		switch ($settings->storage) {
			case 'local':
				$qiniubucket->setAttribute('style','display:none;');
				$qiniudomain->setAttribute('style','display:none;');
				$qiniuaccesskey->setAttribute('style','display:none;');
				$qiniusecretkey->setAttribute('style','display:none;');
				$qiniuprefix->setAttribute('style','display:none;');
				$upyunbucket->setAttribute('style','display:none;');
				$upyundomain->setAttribute('style','display:none;');
				$upyunuser->setAttribute('style','display:none;');
				$upyunpwd->setAttribute('style','display:none;');
				$upyunkey->setAttribute('style','display:none;');
				$upyunprefix->setAttribute('style','display:none;');
				$bcsbucket->setAttribute('style','display:none;');
				$bcsapikey->setAttribute('style','display:none;');
				$bcssecretkey->setAttribute('style','display:none;');
				$bcsprefix->setAttribute('style','display:none;');
				break;
			case 'qiniu':
				$local->setAttribute('style','display:none;');
				$upyunbucket->setAttribute('style','display:none;');
				$upyundomain->setAttribute('style','display:none;');
				$upyunuser->setAttribute('style','display:none;');
				$upyunpwd->setAttribute('style','display:none;');
				$upyunkey->setAttribute('style','display:none;');
				$upyunprefix->setAttribute('style','display:none;');
				$bcsbucket->setAttribute('style','display:none;');
				$bcsapikey->setAttribute('style','display:none;');
				$bcssecretkey->setAttribute('style','display:none;');
				$bcsprefix->setAttribute('style','display:none;');
				break;
			case 'upyun':
				$local->setAttribute('style','display:none;');
				$qiniubucket->setAttribute('style','display:none;');
				$qiniudomain->setAttribute('style','display:none;');
				$qiniuaccesskey->setAttribute('style','display:none;');
				$qiniusecretkey->setAttribute('style','display:none;');
				$qiniuprefix->setAttribute('style','display:none;');
				$bcsbucket->setAttribute('style','display:none;');
				$bcsapikey->setAttribute('style','display:none;');
				$bcssecretkey->setAttribute('style','display:none;');
				$bcsprefix->setAttribute('style','display:none;');
				break;
			case 'bcs':
				$local->setAttribute('style','display:none;');
				$qiniubucket->setAttribute('style','display:none;');
				$qiniudomain->setAttribute('style','display:none;');
				$qiniuaccesskey->setAttribute('style','display:none;');
				$qiniusecretkey->setAttribute('style','display:none;');
				$qiniuprefix->setAttribute('style','display:none;');
				$upyunbucket->setAttribute('style','display:none;');
				$upyundomain->setAttribute('style','display:none;');
				$upyunuser->setAttribute('style','display:none;');
				$upyunpwd->setAttribute('style','display:none;');
				$upyunkey->setAttribute('style','display:none;');
				$upyunprefix->setAttribute('style','display:none;');
				break;
		}

		//更新模式
		$request = Typecho_Request::getInstance();

		if (isset($request->gid)&&$action!=='insert') {
			$db = Typecho_Db::get();
			$prefix = $db->getPrefix();

			$gallery = $db->fetchRow($db->select()->from($prefix.'gallery')->where('gid=?',$request->gid));
			if (!$gallery) {
				throw new Typecho_Widget_Exception(_t('图片不存在'),404);
			}

			$thumb->value($gallery['thumb']);
			$image->value($gallery['image']);
			$sort->value($gallery['sort']);
			$name->value($gallery['name']);
			$description->value($gallery['description']);

			$do->value('update');
			$gid->value($gallery['gid']);
			$submit->value(_t('修改图片'));

			$_action = 'update';
		} elseif ($action=='sync'&&$render=='2') {
			$submit->value(_t('保存设置'));
			$_action = 'sync';
		} else {
			$do->value('insert');
			$submit->value(_t('添加图片'));
			$_action = 'insert';
		}
		if (empty($action)) {
			$action = $_action;
		}

		//验证规则
		if ($action=='insert'||$action=='update') {
			$thumb->addRule('required',_t('缩略图地址不能为空'));
			$image->addRule('required',_t('原图地址不能为空'));
			$sort->addRule('required',_t('相册组不能为空'));
			$thumb->addRule('url',_t('请输入合法的图片地址'));
			$image->addRule('url',_t('请输入合法的图片地址'));
			$sort->addRule('isInteger',_t('请输入一个整数数字'));
		}
		if ($action=='update') {
			$gid->addRule('required',_t('图片主键不存在'));
			$gid->addRule(array(new HighSlide_Plugin,'galleryexists'),_t('图片不存在'));
		}

		$form = ($render=='1')?$form1:$form2;
		return $form;
	}

	/**
	 * 判断图片主键
	 * 
	 * @access public
	 * @param string $gid
	 * @return boolean
	 */
	public static function galleryexists($gid)
	{
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$gallery = $db->fetchRow($db->select()->from($prefix.'gallery')->where('gid=?',$gid)->limit(1));
		return $gallery?true:false;
	}

	/**
	 * 判断比例格式
	 * 
	 * @access public
	 * @param string $ratio
	 * @return boolean
	 */
	public static function ratioformat($ratio)
	{
		return preg_match('/^\d*:\d*$/',$ratio);
	}

	/**
	 * 输出标签替换
	 * 
	 * @access public
	 * @param string $content
	 * @return string
	 */
	public static function autohighslide($content,$widget,$lastResult)
	{
		$content = empty($lastResult)?$content:$lastResult;
		$settings = Helper::options()->plugin('HighSlide');
		$istype = self::replacelist();

		//替换范围
		if ($widget->is(''.$istype->index.'')||$widget->is(''.$istype->archive.'')||$widget->is(''.$istype->post.'')||$widget->is(''.$istype->page.'')) {
			$pattern = '/<a(.*?)href\=\"([^\s]+)\.(jpg|gif|png|bmp)\"(.*?)>(.*?)<\/a>/si';
			$replacement = '<a$1href="$2.$3" class="highslide" onclick="return hs.expand(this,{slideshowGroup:\'images\'})"$4>$5</a>';
			$content = preg_replace($pattern,$replacement,$content);

			//全图替换
			if ($settings->rpmode=='imgsrc') {
				$pattern = '/(<img[^>]+src\s*=\s*"?([^>"\s]+)"?[^>]*>)(?!<\/a>)/si';
				$replacement = '<a href="$2" class="highslide" onclick="return hs.expand(this,{slideshowGroup:\'images\'})">$1</a>';
				$content = preg_replace($pattern,$replacement,$content);
			}

			//附件链接替换
			$content = preg_replace_callback('/<a(.*?)href\=\"([^\s]+)\/attachment\/(\d*|n)\/\"(.*?)>/i',array('HighSlide_Plugin','linkparse'),$content);
		}

		//页面相册标签替换
		if ($widget->is('page')&&$settings->mode=='highslide-full.packed.js') {
			$content = preg_replace_callback("/\[GALLERY([\-\d|\,]*?)\]/i",array('HighSlide_Plugin','galleryparse'),$content);
		}

		//html弹窗标签替换
		if ($settings->mode=='highslide-full.packed.js') {
			$content = preg_replace_callback("/<(hs)([^>]*)>(.*?)<\/\\1>/si",array('HighSlide_Plugin','htmlparse'),$content);
		}

		return $content;
	}

	/**
	 * 应用范围参数
	 * 
	 * @access private
	 * @return array
	 */
	private static function replacelist()
	{
		$rplist = Helper::options()->plugin('HighSlide')->rplist;
		$rplists = array();

		if ($rplist) {
			foreach ($rplist as $key=>$val) {
				$key = $val;
				$rplists[$key] = $val;
			}
		}

		return new Typecho_Config($rplists);
	}

	/**
	 * 页面相册解析
	 * 
	 * @access public
	 * @param array $matches
	 * @return string
	 */
	public static function galleryparse($matches)
	{
		$settings = Helper::options()->plugin('HighSlide');
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$tmp = '';
		$cover = '';

		$param = substr(trim($matches[1]),1);
		$sorts = ($param)?explode(',',$param):array(self::defaultsort());

		foreach ($sorts as $sort) {
			$gallerys = $db->fetchAll($db->select()->from($prefix.'gallery')->where('sort=?',''.$sort.'')->order($prefix.'gallery.order',Typecho_Db::SORT_ASC));
			if (!empty($gallerys)) {

				//封面部分
				$coversets = array(array_shift($gallerys));
				foreach ($coversets as $coverset) {
					//幻影橱窗
					if ($settings->gallery=='gallery-in-page') {
						$cover.='<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,inPageOptions)"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a> ';
					}

					//岁月名片
					elseif ($settings->gallery=='gallery-controls-in-heading') {
						$cover.='<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a><div class="highslide-heading">'.$coverset['description'].'</div> ';
					}

					//纯白记忆
					elseif ($settings->gallery=='gallery-in-box') {
						$cover.='<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a><div class="highslide-caption">'.$coverset['description'].'</div> ';
					} else {
						$cover.='<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a> ';
					}
				}

				//列表部分
				foreach ($gallerys as $gallery) {
					//幻影橱窗
					if ($settings->gallery=='gallery-in-page') {
						$tmp.='<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,inPageOptions)"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a>';
					}

					//岁月名片
					elseif ($settings->gallery=='gallery-controls-in-heading') {
						$tmp.='<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a><div class="highslide-heading">'.$gallery['description'].'</div>';
					}

					//纯白记忆
					elseif ($settings->gallery=='gallery-in-box') {
						$tmp.='<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a><div class="highslide-caption">'.$gallery['description'].'</div>';
					} else {
						$tmp.='<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a>';
					}
				}
			}
		}

		//合并输出
		$container = '<div class="hidden-container">'.$tmp.'</div>';
		if ($settings->gallery=='gallery-in-page') {
			$output = '<div id="gallery-area" style="width: 620px; height: 520px; margin: 0 auto; border: 1px solid silver"><div class="hidden-container">'.$cover.$tmp.'</div></div>';
		} else {
			$output = '<div class="highslide-gallery">'.$cover.$container.'</div>';
		}

		return $output;
	}

	/**
	 * 缺省相册分类
	 * 
	 * @access private
	 * @return string
	 */
	private static function defaultsort()
	{
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$default = $db->fetchRow($db->select('sort')->from($prefix.'gallery')->order('sort',Typecho_Db::SORT_ASC));
		return $default?$default['sort']:'';
	}

	/**
	 * html弹窗解析
	 * 
	 * @access public
	 * @param array $matches
	 * @return string
	 */
	public static function htmlparse($matches)
	{
		$settings = Helper::options()->plugin('HighSlide');
		$param = trim($matches[2]);

		$id = 'highslide-html';
		$text = 'text';
		$title = '';
		$ajax = '';
		$addt = '';
		$width = '';
		$height = '';
		$Movetext = 'Move';
		$Movetitle = 'Move';
		$Closetext = 'Close';
		$Closetitle = 'Close (esc)';
		$Resizetitle = 'Resize';

		if ($settings->lang == 'chs') {
			$Movetext = '移动';
			$Movetitle = '移动';
			$Closetext = '关闭';
			$Closetitle = '关闭 (esc)';
			$Resizetitle = '拉伸';
		}

		//标签参数解析
		if (!empty($param)) {
			if (preg_match("/id=[\"']([\w-]*)[\"']/i",$param,$out)) {
				$id = trim($out[1])==''?$id:trim($out[1]);
			}
			if (preg_match("/text=[\"'](.*?)[\"']/si",$param,$out)) {
				$text = trim($out[1])==''?$text:trim($out[1]);
			}
			if (preg_match("/title=[\"'](.*?)[\"']/si",$param,$out)) {
				$title = trim($out[1])==''?$title:trim($out[1]);
			}
			if (preg_match("/ajax=[\"'](.*?)[\"']/i",$param,$out)) {
				$ajax = trim($out[1])==''?$ajax:trim($out[1]);
			}
			if (preg_match("/width=[\"']([\w-]*)[\"']/i",$param,$out)) {
				$width = trim($out[1])==''?$width:',width:'.str_replace('px','',trim($out[1]));
			}
			if (preg_match("/height=[\"']([\w-]*)[\"']/i",$param,$out)) {
				$height = trim($out[1])==''?$height:',height:'.str_replace('px','',trim($out[1]));
			}
		}

		//标题栏支持
		if ($settings->wrap) {
			$addt = (in_array('draggable-header',$settings->wrap)&&$title)?',headingText:\''.$title.'\'':'';
		}

		//ajax模式判断
		$href = ($ajax)?$ajax:'#';
		$shift = ($ajax)?'objectType:\'ajax\'':'contentId:\''.$id.'\'';

		$output = '<a href="'.$href.'" onclick="return hs.htmlExpand(this,{'.$shift.$addt.$width.$height.'})" class="highslide">'.$text.'</a>';
		$output .= '<div class="highslide-html-content" id="'.$id.'">';
		$output .= '<div class="highslide-header"><ul><li class="highslide-move"><a href="#" onclick="return false" title="'.$Movetitle.'"><span>'.$Movetext.'</span></a></li>';
		$output .= '<li class="highslide-close"><a href="#" onclick="return hs.close(this)" title="'.$Closetitle.'"><span>'.$Closetext.'</span></a></li></ul></div>';
		$output .= '<div class="highslide-body">'.trim($matches[3]).'</div>';
		$output .= '<div class="highslide-footer"><div><span class="highslide-resize" title="'.$Resizetitle.'"><span></span></span></div></div>';
		$output .= '</div>
		';

		return $output;
	}

	/**
	 * 附件链接解析
	 * 
	 * @access public
	 * @param array $matches
	 * @return string
	 */
	public static function linkparse($matches)
	{
		$db = Typecho_Db::get();
		$cid = $matches[3];
		$attach = $db->fetchRow($db->select()->from('table.contents')->where('type=\'attachment\' AND cid=?',$cid));
		$attach_data = unserialize($attach['text']);
		$output = '<a'.$matches[1].'href="'.Typecho_Common::url($attach_data['path'],Helper::options()->siteUrl).'" class="highslide" onclick="return hs.expand(this,{slideshowGroup:\'images\'})"'.$matches[4].'>';
		return $output;
	}

	/**
	 * 附件缩略面板
	 *
	 * @access public
	 * @param Widget_Contents_Post_Edit $post
	 * @return void
	 */
	public static function uploadpanel($post)
	{
?>
		<label class="typecho-label"><?php _e('缩略图'); ?></label>
		<link rel="stylesheet" type="text/css" media="all" href="<?php Helper::options()->pluginUrl('HighSlide/css/imgareaselect-animated.css'); ?>" />
		<div id="preview-area" style="border:1px dashed #D9D9D6;background-color:#FFF;color:#999;" class="p">
			<p id="loadattach" style="text-align:center"><a href="###" data-cid="<?php echo $post->cid; ?>"><?php _e('加载附件'); ?></a></p>
			<ul id="attach-list" style="list-style:none;margin:0px 10px;padding:0px;"></ul>
		</div>
<?php
	}

	/**
	 * 附件缩略脚本
	 *
	 * @access public
	 * @return void
	 */
	public static function jshelper()
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$ratio = ($settings->thumbfix=='fixedratio')?$settings->fixedratio:'false';
		$fileurl = self::filedata()->url;
?>
<script src="<?php $options->pluginUrl('HighSlide/js/imgareaselect.js'); ?>"></script>
<script type="text/javascript">
$(function() {
	$('#loadattach').find('a').click(function() {
		var list = $('#attach-list'),
			cid = $(this).data('cid');
		list.empty();
		$.post('<?php $options->index('/action/gallery-edit'); ?>',
			{'do':'preview','cid':cid},
			function(data) {
				var val = eval(data);
				for(var i=0;i<val.length;i++) {
					var thumb = $('<li data-name="thumb_'+val[i].name+'">').attr('style','padding:8px 0px;border-top:1px dashed #D9D9D6;')
							.data('path',val[i].path).data('thumb',val[i].thumb)
							.html('<img class="preview" src="'+val[i].thumb+'" alt="thumb_'+val[i].name+'" style="max-width:148px;"/><div class="info">'+val[i].tsize
							+' <a class="addto" href="###" title="<?php _e('插入图链'); ?>"><i class="i-exlink"></i></a>'
							+'<a class="delete" href="###" title="<?php _e('删除缩略图'); ?>"><i class="i-delete"></i></a></div>'),
						li = $('<li id="list-'+i+'">').attr('style','padding:8px 0px;border-top:1px dashed #D9D9D6;')
							.data('name',val[i].name).data('title',val[i].title).data('thumb',val[i].thumb).data('path',val[i].path).data('url',val[i].url)
							.html('<input type="hidden" name="imgname" value="'+val[i].name+'" />'
							+'<img id="uploadimg-list-'+i+'" class="preview" src="'+val[i].url+'" alt="'+val[i].title+'" style="max-width:248px;"/><div class="info">'+val[i].size
							+' <a class="crop" href="###" title="<?php _e('截取缩略图'); ?>"><i class="mime-application"></i></a></div>'
							+'<input type="hidden" name="x1" value="" id="x1" />'
							+'<input type="hidden" name="y1" value="" id="y1" />'
							+'<input type="hidden" name="x2" value="" id="x2" />'
							+'<input type="hidden" name="y2" value="" id="y2" />'
							+'<input type="hidden" name="w" value="" id="w" />'
							+'<input type="hidden" name="h" value="" id="h" />')
							.prependTo(list);
					if (val[i].tstat) thumb.appendTo(li);
					iasEffectEvent(li);
					thumbCropEvent(li);
					imageAddtoEvent(thumb);
					imageDeleteEvent(thumb);
				}
			});
		return false;
	});

	function iasEffectEvent(el) {
		var id = $(el).attr('id');
		$('img#uploadimg-'+id+'',el).imgAreaSelect({
			handles:true,
			instance:true,
			classPrefix:'ias-'+id+' ias',
			aspectRatio:'<?php echo $ratio; ?>',
			onSelectEnd:function(img,selection) {
				$('#x1',el).val(selection.x1);
				$('#y1',el).val(selection.y1);
				$('#x2',el).val(selection.x2);
				$('#y2',el).val(selection.y2);
				$('#w',el).val(selection.width);
				$('#h',el).val(selection.height);
			}
		});
	}

	function thumbCropEvent(el) {
		$('.crop',el).click(function() {
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				thumb = pli.data('thumb'),
				path = pli.data('path'),
				url = pli.data('url'),
				li = $('li[data-name="thumb_'+name+'"]'),
				x1 = $('#x1',el).val(),
				y1 = $('#y1',el).val(),
				x2 = $('#x2',el).val(),
				y2 = $('#y2',el).val(),
				w = $('#w',el).val(),
				h = $('#h',el).val();
			if(x1==""||y1==""||x2==""||y2==""||w==""||h=="") {
				alert("请先拖选图片区域");
				return false;
			}
			if (li.length==0) {
				$('<li data-name="thumb_'+name+'" class="loading"></li>').appendTo(pli);
			} else {
				li.empty().addClass('loading');
			}
			$('img[id^="uploadimg-"]').imgAreaSelect({hide:true});
			$.post('<?php $options->index('/action/gallery-edit'); ?>',
				{'do':'crop','imgname':name,'x1':x1,'y1':y1,'w':w,'h':h,'path':path,'url':url},
				function(data) {
					var li = $('li[data-name="thumb_'+name+'"]').removeClass('loading')
							.data('path',path).data('thumb',thumb)
							.html('<img class="preview" style="max-width:250px;overflow:hidden;" src="'+thumb+'?u='+Math.floor(Math.random()*100)+'" alt="thumb_'+name+'" /><div class="info">'+data.bytes
							+' <a class="addto" href="###" title="<?php _e('插入图链'); ?>"><i class="i-exlink"></i></a>'
							+'<a class="delete" href="###" title="<?php _e('删除缩略图'); ?>"><i class="i-delete"></i></a></div>')
					.effect('highlight',1000);
				imageAddtoEvent(li);
				imageDeleteEvent(li);
				});
			return false;
		});
	}

	function imageDeleteEvent(el) {
		$('.delete',el).click(function() {
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				path = pli.data('path'),
				url = pli.data('thumb');
			if (confirm('<?php _e('确认删除缩略图 %s 吗?'); ?>'.replace('%s',name))) {
				$.post('<?php $options->index('/action/gallery-edit'); ?>',
				{'do':'remove','imgname':name,'path':path,'url':url},
				function() {
					$(el).fadeOut(function() {
						$(this).remove();
					});
				});
			}
			return false;
		});
	}

	function imageAddtoEvent(el) {
		$('.addto',el).click(function() {
			var pli = $(this).parents('li'),
				thumb = pli.data('thumb'),
				url = pli.parents('li').data('url'),
				title = pli.parents('li').data('title'),
				textarea = $('#text'),sel = textarea.getSelection(),
<?php
		//兼容markdown
		if ($options->markdown=='1') {
?>
				html = '[!['+title+']('+thumb+')]('+url+' "'+title+'")',
<?php
		} else {
?>
				html = '<a href="'+url+'" title="'+title+'"><img src="'+thumb+'" alt="'+title+'" /></a>',
<?php
		}
?>
				offset = (sel?sel.start:0)+html.length;
			textarea.replaceSelection(html);
			textarea.setSelection(offset,offset);
			return false;
		});
	}
});
</script>
<?php
	}

	/**
	 * 输出头部样式
	 *
	 * @access public
	 * @return void
	 */
	public static function headlink()
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$widget = Typecho_Widget::widget('Widget_Archive');
		$hsurl = $options->pluginUrl.'/HighSlide/';

		$istype = self::replacelist();
		$cssurl = '';

		//输出范围
		if ($widget->is(''.$istype->index.'')||$widget->is(''.$istype->archive.'')||$widget->is(''.$istype->post.'')||$widget->is(''.$istype->page.'')) {
			$cssurl = '
<link rel="stylesheet" type="text/css" href="'.$hsurl.'css/highslide.css" />
<!--[if lt IE 7]>
<link rel="stylesheet" type="text/css" href="'.$hsurl.'css/highslide-ie6.css" />
<![endif]-->
';
			if ($settings->mode=='highslide-full.packed.js'&&$widget->is('page')) {
				//幻影橱窗
				if ($settings->gallery == 'gallery-in-page') {
					$cssurl.= '<style type="text/css">
.highslide-image {
border: 1px solid black;
}
.highslide-controls {
width: 90px !important;
}
.highslide-controls .highslide-close {
display: none;
}
.highslide-caption {
padding: .5em 0;
}
</style>
';
				}

				//时光胶带
				if ($settings->gallery == 'gallery-vertical-strip') {
					$cssurl.= '<style type="text/css">
.highslide-caption {
width: 100%;
text-align: center;
}
.highslide-close {
display: none !important;
}
.highslide-number {
display: inline;
padding-right: 1em;
color: white;
}
</style>
';
				}
			}
		}

		echo $cssurl;
	}

	/**
	 * 输出底部脚本
	 *
	 * @access public
	 * @return void
	 */
	public static function footlink()
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$widget = Typecho_Widget::widget('Widget_Archive');
		$hsurl = $options->pluginUrl.'/HighSlide/';

		$closetitle = ($settings->lang=='chs')?'关闭':'Close';
		$istype = self::replacelist();

		$links = '';

		//输出范围
		if ($widget->is(''.$istype->index.'')||$widget->is(''.$istype->archive.'')||$widget->is(''.$istype->post.'')||$widget->is(''.$istype->page.'')) {
			$links = '
<script type="text/javascript" src="'.$hsurl.'js/'.$settings->mode.'"></script>';
			$links.='
<script type="text/javascript">
//<![CDATA[
hs.graphicsDir = "'.$hsurl.'css/graphics/";
hs.fadeInOut = true;
hs.transitions = ["expand","crossfade"];';

			//角标设置
			if ($settings->ltext=='') {
				$links.='
hs.showCredits = false;';
			}
			if ($settings->ltext!=='') {
				$links.='
hs.lang.creditsText = "'.$settings->ltext.'";
hs.lang.creditsTitle = "'.$settings->ltext.'";
hs.creditsHref = "'.$options->index.'";
hs.creditsPosition = "'.$settings->lpos.'";';
			}

			//中文支持
			if ($settings->lang=='chs') {
				$links.='
hs.lang={
loadingText : "载入中...",
loadingTitle : "取消",
closeText : "关闭",
closeTitle : "关闭 (Esc)",
previousText : "上一张",
previousTitle : "上一张 (←键)",
nextText : "下一张",
nextTitle : "下一张 (→键)",
moveTitle : "移动",
moveText : "移动",
playText : "播放",
playTitle : "幻灯播放 (空格键)",
pauseText : "暂停",
pauseTitle : "幻灯暂停 (空格键)",
number : "第%1张 共%2张",
restoreTitle :	"点击关闭或拖动. 左右方向键切换图片. ",
fullExpandTitle : "完整尺寸",
fullExpandText :  "原大"
};';
			}

			//插图弹窗定制
			$type = ($settings->mode=='highslide-full.packed.js')?'post':'single';
			if ($widget->is(''.$istype->index.'')||$widget->is(''.$type.'')||$widget->is(''.$istype->archive.'')) {
				//边框样式搭配
				if ($settings->outline!=='') {
					$links.='
hs.outlineType = "'.$settings->outline.'";';
				}
				if ($settings->outline=='glossy-dark'&&$settings->wrap!==NULL||
					$settings->outline=='rounded-black'&&$settings->wrap!==NULL) {
					$links.='
hs.wrapperClassName = "dark '.implode(" ",$settings->wrap).'";';
				}
				if ($settings->outline=='outer-glow'&&$settings->wrap!== NULL) {
					$links.='
hs.wrapperClassName = "outer-glow '.implode(" ",$settings->wrap).'";';
				}
				if ($settings->outline=='beveled'&&$settings->wrap!==NULL) {
					$links.='
hs.wrapperClassName = "borderless '.implode(" ",$settings->wrap).'";';
				}
				if ($settings->wrap!==NULL&&
					$settings->outline!=='glossy-dark'&&
					$settings->outline!=='rounded-black'&&
					$settings->outline!=='outer-glow'&&
					$settings->outline!=='beveled') {
					$links.='
hs.wrapperClassName = "'.implode(" ",$settings->wrap).'";';
				}
				if ($settings->outline=='glossy-dark'&&$settings->wrap==NULL||
					$settings->outline=='rounded-black'&&$settings->wrap==NULL) {
					$links.='
hs.wrapperClassName = "dark";';
				}
				if ($settings->outline=='outer-glow'&&$settings->wrap==NULL) {
					$links.='
hs.wrapperClassName = "outer-glow";';
				}
				if ($settings->outline=='beveled'&&$settings->wrap==NULL) {
					$links.='
hs.wrapperClassName = "borderless";';
				}

				//关闭按钮
				if ($settings->butn=='true') {
					$links.='
hs.registerOverlay({
html: \'<div class="closebutton" onclick="return hs.close(this)" title="'.$closetitle.'"></div>\',
position: "top right",
fade: 2
});';
				}

				//图片说明
				if ($settings->capt!=='') {
					$links.='
hs.captionEval = "'.$settings->capt.'";';
				}

				if ($settings->mode=='highslide-full.packed.js') {
					//图片序数
					if ($settings->cpos!=='') {
						$links.='
hs.numberPosition = "'.$settings->cpos.'";';
					}

					//背景遮罩
					if ($settings->opac!=='') {
						$links.='
hs.dimmingOpacity = '.$settings->opac.';';
					}

					//弹窗位置
					if ($settings->align=='center') {
						$links.='
hs.align = "center";';
					}

					//幻灯按钮
					if ($settings->slide=='true') {
						$links.='
if (hs.addSlideshow) hs.addSlideshow({
slideshowGroup: "images",
interval: 5000,
repeat: true,
useControls: true,
fixedControls: "fit",
overlayOptions: {
opacity: .65,
position: "bottom center",
hideOnMouseOut: true
}
});';
					}

					//自动翻页
					if ($settings->nextimg=='true') {
						$links.='
hs.Expander.prototype.onImageClick = function() {
return hs.next();
}';
					}
				}
			}

			//页面相册套装
			elseif ($settings->mode=='highslide-full.packed.js'&&$widget->is(''.$istype->page.'')) {

				//获取相册组参数
				preg_match_all("/\[GALLERY([\-\d|\,]*?)\]/i",$widget->text,$matches);
				$params = array();
				foreach ($matches[1] as $param) {
					$params[] = substr(trim($param),1);
				}

				//兼容性处理
				$groups = (array_filter($params))?explode(',',implode(',',$params)):array(self::defaultsort());
				function groupsalter(&$item,$key,$prefix) {
					$item = '"'.$prefix.''.$item.'"';
				}

				//格式化输出
				array_walk($groups,'groupsalter','group');
				$group = implode(',',array_unique($groups));

				$links.='
if (hs.addSlideshow) hs.addSlideshow({
interval: 5000,
repeat: true,
useControls: true,';

				//连环画册
				if ($settings->gallery=='gallery-horizontal-strip') {
					$links.='
slideshowGroup: ['.$group.'],
overlayOptions: {
className: "text-controls",
position: "bottom center",
relativeTo: "viewport",
offsetY: -60
},
thumbstrip: {
position: "bottom center",
mode: "horizontal",
relativeTo: "viewport"
}
});
hs.align = "center";
hs.dimmingOpacity = 0.8;
hs.outlineType = "rounded-white";
hs.captionEval = "this.thumb.alt";
hs.marginBottom = 105;
hs.numberPosition = "caption";';
				}

				//黑色影夹
				if ($settings->gallery=='gallery-thumbstrip-above') {
					$links.='
slideshowGroup: ['.$group.'],
fixedControls: "fit",
overlayOptions: {
position: "bottom center",
opacity: .75,
hideOnMouseOut: true
},
thumbstrip: {
position: "above",
mode: "horizontal",
relativeTo: "expander"
}
});
hs.align = "center";
hs.outlineType = "glossy-dark";
hs.wrapperClassName = "dark";
hs.captionEval = "this.a.title";
hs.numberPosition = "caption";
hs.useBox = true;
hs.width = 600;
hs.height = 400;';
				}

				//幻影橱窗
				if ($settings->gallery=='gallery-in-page') {
					$restoreTitle = ($settings->lang == 'chs')?'点击查看下一张':'Click for next image';
					$links.='
overlayOptions: {
position: "bottom right",
offsetY: 50
},
thumbstrip: {
position: "above",
mode: "horizontal",
relativeTo: "expander"
}
});
hs.restoreCursor = null;
hs.lang.restoreTitle = "'.$restoreTitle.'";
var inPageOptions = {
outlineType: null,
wrapperClassName: "in-page controls-in-heading",
thumbnailId: "gallery-area",
useBox: true,
width: 600,
height: 400,
targetX: "gallery-area 10px",
targetY: "gallery-area 10px",
captionEval: "this.a.title",
numberPosition: "caption"
}
hs.addEventListener(window,"load",function() {
document.getElementById("thumb'.current(explode(',',$params[0])).'").onclick();
});
hs.Expander.prototype.onImageClick = function() {
if (/in-page/.test(this.wrapper.className))	return hs.next();
}
hs.Expander.prototype.onBeforeClose = function() {
if (/in-page/.test(this.wrapper.className))	return false;
}
hs.Expander.prototype.onDrag = function() {
if (/in-page/.test(this.wrapper.className))	return false;
}
hs.addEventListener(window,"resize",function() {
var i,exp;
hs.getPageSize();
for (i = 0; i < hs.expanders.length; i++) {
exp = hs.expanders[i];
if (exp) {
	var x = exp.x,
y = exp.y;
	exp.tpos = hs.getPosition(exp.el);
	x.calcThumb();
	y.calcThumb();
	x.pos = x.tpos - x.cb + x.tb;
	x.scroll = hs.page.scrollLeft;
	x.clientSize = hs.page.width;
	y.pos = y.tpos - y.cb + y.tb;
	y.scroll = hs.page.scrollTop;
	y.clientSize = hs.page.height;
	exp.justify(x,true);
	exp.justify(y,true);
	exp.moveTo(x.pos,y.pos);
}
}
});';
				}

				//时光胶带
				if ($settings->gallery=='gallery-vertical-strip') {
					$links.='
slideshowGroup: ['.$group.'],
overlayOptions: {
	className: "text-controls",
	position: "bottom center",
	relativeTo: "viewport",
	offsetX: 50,
	offsetY: -5
},
thumbstrip: {
	position: "middle left",
	mode: "vertical",
	relativeTo: "viewport"
}
});
hs.registerOverlay({
html: \'<div class="closebutton" onclick="return hs.close(this)" title="Close"></div>\',
position: "top right",
fade: 2
});
hs.align = "center";
hs.dimmingOpacity = 0.8;
hs.wrapperClassName = "borderless floating-caption";
hs.captionEval = "this.thumb.alt";
hs.marginLeft = 100;
hs.marginBottom = 80;
hs.numberPosition = "caption";
hs.lang.number = "%1/%2";';
				}

				//纯白记忆
				if ($settings->gallery=='gallery-in-box') {
					$links.='
slideshowGroup: ['.$group.'],
fixedControls: "fit",
overlayOptions: {
	opacity: 1,
	position: "bottom center",
	hideOnMouseOut: true
}
});
hs.align = "center";
hs.outlineType = "rounded-white";
hs.dimmingOpacity = 0.75;
hs.useBox = true;
hs.width = 640;
hs.height = 480;';
				}

				//往事片段
				if ($settings->gallery=='gallery-floating-thumbs') {
					$links.='
slideshowGroup: ['.$group.'],
fixedControls: "fit",
overlayOptions: {
	position: "top right",
	offsetX: 200,
	offsetY: -65
},
thumbstrip: {
	position: "rightpanel",
	mode: "float",
	relativeTo: "expander",
	width: "210px"
}
});
hs.align = "center";
hs.outlineType = "rounded-white";
hs.headingEval = "this.a.title";
hs.numberPosition = "heading";
hs.useBox = true;
hs.width = 600;
hs.height = 400;';
				}

				//沉默注脚
				if ($settings->gallery=='gallery-floating-caption') {
					$links.='
slideshowGroup: ['.$group.'],
fixedControls: "fit",
overlayOptions: {
	opacity: .6,
	position: "bottom center",
	hideOnMouseOut: true
}
});
hs.align = "center";
hs.wrapperClassName = "dark borderless floating-caption";
hs.dimmingOpacity = .75;
hs.captionEval = "this.a.title";';
				}

				//岁月名片
				if ($settings->gallery=='gallery-controls-in-heading') {
					$links.='
slideshowGroup: ['.$group.'],
fixedControls: false,
overlayOptions: {
	opacity: 1,
	position: "top right",
	hideOnMouseOut: false
}
});
hs.align = "center";
hs.outlineType = "rounded-white";
hs.wrapperClassName = "controls-in-heading";';
				}
			}

			$links.='
//]]>
</script>
		';
		}

		echo $links;
	}

	/**
	 * 图片上传处理
	 *
	 * @access public
	 * @param array $file 上传的文件
	 * @return mixed
	 */
	public static function uploadhandle($file)
	{
		if (empty($file['name'])) {
			return false;
		}

		$imgname = preg_split("(\/|\\|:)",$file['name']);
		$file['name'] = array_pop($imgname);

		//扩展名
		$ext = self::getsafename($file['name']);
		if (!self::checkimgtype($ext)||Typecho_Common::isAppEngine()) {
			return false;
		}

		//上传路径
		$settings = Helper::options()->plugin('HighSlide');
		$imgdir = self::filedata()->dir;
		if ($settings->storage=='local'&&!is_dir($imgdir)) {
			if (!self::makehsdir($imgdir)) {
				return false;
			}
		}

		//文件名
		$imgname = sprintf('%u',crc32(uniqid())).'.'.$ext;
		$imgpath = $imgdir.$imgname;
		$filename = $file['tmp_name'];
		if (!isset($filename)) {
			return false;
		}

		//本地上传
		if ($settings->storage=='local') {
			if (!@move_uploaded_file($file['tmp_name'],$imgpath)) {
				return false;
			}
		}

		//七牛上传
		if ($settings->storage=='qiniu') {
			self::qiniuset($settings->qiniuaccesskey,$settings->qiniusecretkey);
			$policy = new Qiniu_RS_PutPolicy($settings->qiniubucket);
			$token = $policy->Token(null);
			$extra = new Qiniu_PutExtra();
			$extra->Crc32 = 1;
			list($result,$error) = Qiniu_PutFile($token,$imgpath,$filename,$extra);
			if ($error!==null) {
				return false;
			}
		}

		//又拍云上传
		if ($settings->storage=='upyun') {
			$upyun = self::upyunset();
			$upyun->writeFile('/'.$imgpath,file_get_contents($filename),TRUE);
		}

		//百度BCS上传
		if ($settings->storage=='bcs') {
			$bcs = self::bcsset();
			$result = $bcs->create_object($settings->bcsbucket,$imgpath,$filename,array("acl"=>"public-read"));
			if (!$result->isOK()) {
				return false;
			}
		}

		return array(
			'name'=>$imgname,
			'title'=>$file['name'],
			'size'=>$file['size']
		);
	}

	/**
	 * 图片删除处理
	 *
	 * @access public
	 * @param string $imgname 图片名称
	 * @param string $path 附件源路径
	 * @param string $url 附件源地址
	 * @return string
	 */
	public static function removehandle($imgname,$path = NULL,$url = NULL)
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$imgdir = self::filedata($path,$url)->dir;

		$imgpath = $imgdir.$imgname;
		$thumbpath = $imgdir.'thumb_'.$imgname;

		if (strpos($url,$options->siteUrl)===false) {
			//本地删除
			if ($settings->storage=='local') {
				if (!file_exists($imgpath)) {
					return false;
				}
				if (file_exists($thumbpath)) {
					unlink($thumbpath);
				}
				return !Typecho_Common::isAppEngine()
				&& @unlink($imgpath);
			}

			//七牛删除
			if ($settings->storage=='qiniu') {
				self::qiniuset($settings->qiniuaccesskey,$settings->qiniusecretkey);
				$client = new Qiniu_MacHttpClient(null);
				Qiniu_RS_Delete($client,$settings->qiniubucket,$thumbpath);
				Qiniu_RS_Delete($client,$settings->qiniubucket,$imgpath);
				return true;
			}

			//又拍云删除
			if ($settings->storage=='upyun') {
				$upyun = self::upyunset();
				$upyun->delete($thumbpath);
				return $upyun->delete($imgpath);
			}

			//百度BCS删除
			if ($settings->storage=='bcs') {
				$bcs = self::bcsset();
				$bcs->delete_object($settings->bcsbucket,$thumbpath);
				$bcs->delete_object($settings->bcsbucket,$imgpath);
				return true;
			}

		//本地附件源删除
		} else {
			if (!file_exists($imgpath)) {
				return false;
			}
			return @unlink($imgpath);
		}
	}

	/**
	 * 图片裁切处理
	 *
	 * @access public
	 * @param string $imagename,$width,$height,$xset,$yset 基本参数
	 * @param string $path 附件源路径
	 * @param string $url 附件源地址
	 * @return string
	 */
	public static function crophandle($imgname,$width,$height,$xset,$yset,$path = NULL,$url = NULL)
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$filedata = self::filedata($path,$url);

		$imgdir = ($settings->storage=='local')?$filedata->dir:$filedata->url;
		$imgfile = ($url)?$url:$imgdir.$imgname;
		$thumbdir = ($settings->storage=='local')?$imgdir:__TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/HighSlide/tmp/';
		if (!is_dir($thumbdir)) {
			if (!self::makehsdir($thumbdir)) {
				return false;
			}
		}

		$thumbfile = $thumbdir.'thumb_'.$imgname;
		$thumbpath = $filedata->dir.'thumb_'.$imgname;

		//预览尺寸适应
		list($imgwidth,$imgheight,$imgtype) = getimagesize($imgfile);
		$imgtype = image_type_to_mime_type($imgtype);
		$adjust = ($imgwidth>442)?$imgwidth/442:1;
		if ($url) {
			$adjust = ($imgwidth>248)?$imgwidth/248:1;
		}
		$xset *= $adjust;
		$yset *= $adjust;
		$width *= $adjust;
		$height *= $adjust;

		//缩放规格
		switch ($settings->thumbfix) {
			case 'fixedwidth':
				$scale = $settings->fixedwidth/$width;
				break;
			case 'fixedheight':
				$scale = $settings->fixedheight/$height;
				break;
			case 'fixedratio':
				$fix = explode(':',$settings->fixedratio);
				$scale = $fix[0]/$fix[1];
				break;
		}
		$newwidth = $width*$scale;
		$newheight = $height*$scale;

		//采样
		$newimg = imagecreatetruecolor($newwidth,$newheight);
		switch ($imgtype) {
			case "image/gif":
				$source = imagecreatefromgif($imgfile);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source = imagecreatefromjpeg($imgfile);
				break;
			case "image/png":
			case "image/x-png":
				$source = imagecreatefrompng($imgfile);
				break;
		}

		//渲染
		imagecopyresampled($newimg,$source,0,0,$xset,$yset,$newwidth,$newheight,$width,$height);
		switch ($imgtype) {
			case "image/gif":
				imagegif($newimg,$thumbfile);
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newimg,$thumbfile,100);
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newimg,$thumbfile);
				break;
		}

		chmod($thumbfile,0777);
		$thumbsize = filesize($thumbfile);

		if (strpos($url,$options->siteUrl)===false) {
			//七牛上传
			if ($settings->storage=='qiniu') {
				self::qiniuset($settings->qiniuaccesskey,$settings->qiniusecretkey);
				$client = new Qiniu_MacHttpClient(null);
				Qiniu_RS_Delete($client,$settings->qiniubucket,$thumbpath);
				$policy = new Qiniu_RS_PutPolicy($settings->qiniubucket);
				$token = $policy->Token(null);
				$extra = new Qiniu_PutExtra();
				$extra->Crc32 = 1;
				list($result,$error) = Qiniu_PutFile($token,$thumbpath,$thumbfile,$extra);
				if ($error!==null) {
					return false;
				}
				unlink($thumbfile);
			}

			//又拍云上传
			if ($settings->storage=='upyun') {
				$upyun = self::upyunset();
				$upyun->delete($thumbpath);
				$upyun->writeFile($thumbpath,file_get_contents($thumbfile),TRUE);
				unlink($thumbfile);
			}

			//百度BCS上传
			if ($settings->storage=='bcs') {
				$bcs = self::bcsset();
				$bcs->delete_object($settings->bcsbucket,$thumbpath);
				$result = $bcs->create_object($settings->bcsbucket,$thumbpath,$thumbfile,array("acl"=>"public-read"));
				if (!$result->isOK()) {
					return false;
				}
				unlink($thumbfile);
			}

		//本地附件源移动
		} else {
			rename($thumbfile,$thumbpath);
		}

		return $thumbsize;
	}

	/**
	 * 本地目录创建
	 *
	 * @access private
	 * @param string $path 路径
	 * @return boolean
	 */
	private static function makehsdir($path)
	{
		if (!@mkdir($path,0777,true)) {
			return false;
		}
		$stat = @stat($path);
		$perms = $stat['mode']&0007777;
		@chmod($path,$perms);
		return true;
	}

	/**
	 * 获取安全的文件名
	 * 
	 * @access private
	 * @param string $name
	 * @return string
	 */
	private static function getsafename(&$name)
	{
		$name = str_replace(array('"','<','>'),'',$name);
		$name = str_replace('\\','/', $name);
		$name = false === strpos($name,'/')?('a'.$name):str_replace('/','/a',$name);
		$info = pathinfo($name);
		$name = substr($info['basename'],1);
		return isset($info['extension'])?$info['extension']:'';
	}

	/**
	 * 图片扩展名检查
	 *
	 * @access private
	 * @param string $ext 扩展名
	 * @return boolean
	 */
	private static function checkimgtype($ext)
	{
		return in_array($ext,array('gif','jpg','jpeg','png','tiff','bmp'));
	}

}