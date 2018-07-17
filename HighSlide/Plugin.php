<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为博客添加HighSlide弹窗效果与相册功能
 * 
 * @package HighSlide
 * @author 羽中
 * @version 1.4.7
 * @dependence 14.5.26-*
 * @link http://www.yzmb.me/archives/net/highslide-for-typecho
 */
class HighSlide_Plugin implements Typecho_Plugin_Interface
{
	/**
	* 相册组ID集合
	* 
	* @access private
	* @var array
	*/
	private static $ids = array();

	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return string
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Helper::addPanel(3,'HighSlide/manage-gallery.php',_t('页面相册'),_t('配置页面相册'),'administrator');
		Helper::addAction('gallery-edit','HighSlide_Action');

		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('HighSlide_Plugin','autohighslide');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('HighSlide_Plugin','autohighslide');
		Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('HighSlide_Plugin','deleteHandle');

		Typecho_Plugin::factory('Widget_Archive')->header = array('HighSlide_Plugin','headlink');
		Typecho_Plugin::factory('Widget_Archive')->footer = array('HighSlide_Plugin','footlink');

		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('HighSlide_Plugin','attachpanel');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('HighSlide_Plugin','attachpanel');

		return self::galleryinstall();
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
		Helper::removePanel(3,'HighSlide/manage-gallery.php');
		Helper::removeAction('gallery-edit');
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
		$mode = new Typecho_Widget_Helper_Form_Element_Radio('mode',
		array('highslide.packed.js'=>_t('基础版 <span style="color:#999;font-size:0.92857em;">(25.2K)仅支持插图弹窗</span>'),'highslide-full.packed.js'=>_t('全功能版 <span style="color:#999;font-size:0.92857em;">(46.8K)支持html弹窗/插图幻灯/页面相册等</span>')),'highslide.packed.js',_t('核心选择'));
		$form->addInput($mode);

		$rpopt = new Typecho_Widget_Helper_Form_Element_Checkbox('rpopt',
		array('link'=>_t('链接至图片'),'img'=>_t('所有图片')),NULL,_t('弹窗模式'),_t('默认将目标为图片的<strong>超链接</strong>(包括文字)转化为弹窗效果,也可以选择将所有图片直接转化为原图弹窗'));
		$rpopt->input->setAttribute('disabled','true');
		$form->addInput($rpopt);

		$rplist = new Typecho_Widget_Helper_Form_Element_Checkbox('rplist',
		array('index'=>_t('首页'),'post'=>_t('文章'),'page'=>_t('独立页面'),'archive'=>_t('归档页面')),array('index','post','page'),_t('应用范围'),_t('取消勾选的页面类型将不会加载插件脚本与替换效果, 归档页包括按时间/标签等索引的列表型页面'));
		$form->addInput($rplist);

		$outline= new Typecho_Widget_Helper_Form_Element_Radio('outline',
		array(''=>_t('无边框'),'rounded-white'=>_t('圆角白'),'rounded-black'=>_t('圆角黑'),'glossy-dark'=>_t('亮泽黑'),'outer-glow'=>_t('外发光'),'beveled'=>_t('半透明')),'',_t('边框风格'));
		$form->addInput($outline);

		$cbutton = new Typecho_Widget_Helper_Form_Element_Radio('cbutton',
		array(1=>_t('显示'),0=>_t('不显示')),0,_t('关闭按钮'));
		$form->addInput($cbutton);

		$ltext = new Typecho_Widget_Helper_Form_Element_Text('ltext',
		NULL,'&copy; '.$_SERVER['HTTP_HOST'].'',_t('角标文字'));
		$ltext->input->setAttribute('class','mini');
		$form->addInput($ltext);

		$lpos = new Typecho_Widget_Helper_Form_Element_Select('lpos',
		array('top left'=>_t('左上'),'top center'=>_t('中上'),'top right'=>_t('右上'),'bottom left'=>_t('左下'),'bottom center'=>_t('中下'),'bottom right'=>_t('右下')),'top left','');
		$lpos->input->setAttribute('style','position:absolute;bottom:16px;left:173px;');
		$lpos->setAttribute('style','position:relative');
		$form->addInput($lpos);

		$capt = new Typecho_Widget_Helper_Form_Element_Radio('capt',
		array(''=>_t('不显示'),'this.a.title'=>_t('显示链接title'),'this.thumb.alt'=>_t('显示图片alt')),'',_t('图片说明'),_t('例:%s图片说明写这%s或者写这显示%s',' &#60;a href="http://xx.jpg" title="','"&#62;&#60;img src="http://xxx.jpg" alt="','"/&#62;&#60;/a&#62;'));
		$form->addInput($capt);

		$lang = new Typecho_Widget_Helper_Form_Element_Radio('lang',
		array('en'=>_t('英文'),'cn'=>_t('中文')),'en',_t('提示语言'),'<p id="advanced" style="color:#467B96;font-weight:bold;">'._t('全功能版设置').' ———————————————————————————————————————</p>');
		$form->addInput($lang);

		$fullalign = new Typecho_Widget_Helper_Form_Element_Radio('fullalign',
		array('default'=>_t('触发位置'),'center'=>_t('页面居中')),'default',_t('弹窗定位'));
		$form->addInput($fullalign);

		$fullopac = new Typecho_Widget_Helper_Form_Element_Text('fullopac',
		NULL,'0.65',_t('背景遮罩'),_t('从透明至纯黑, 可填写0至1间的小数'));
		$fullopac->input->setAttribute('class','mini');
		$form->addInput($fullopac->addRule('isFloat',_t('请填写数字')));

		$fullslide = new Typecho_Widget_Helper_Form_Element_Radio('fullslide',
		array(1=>_t('开启'),0=>_t('关闭')),1,_t('幻灯面板'));
		$form->addInput($fullslide);

		$fullnextimg = new Typecho_Widget_Helper_Form_Element_Radio('fullnextimg',
		array(1=>_t('是'),0=>_t('否')),0,_t('自动翻页'),_t('点击图片不关闭弹窗而是显示下一张'));
		$form->addInput($fullnextimg);

		$fullcpos = new Typecho_Widget_Helper_Form_Element_Radio('fullcpos',
		array(''=>_t('不显示'),'caption'=>_t('底部显示'),'heading'=>_t('顶部显示')),'',_t('图片序数'));
		$form->addInput($fullcpos);

		$fullwrap = new Typecho_Widget_Helper_Form_Element_Checkbox('fullwrap',
		array('draggable-header'=>_t('显示标题栏%s如: %s标题%s',' <span style="color:#999;font-size:0.92857em;">','&#60;hs title="','"&#62;</span>'),'no-footer'=>_t('禁用拉伸')),NULL,_t('html弹窗'));
		$form->addInput($fullwrap);

//输出面板效果
?>
<script type="text/javascript" src="<?php Helper::options()->adminUrl('js/jquery.js'); ?>"></script>
<script type="text/javascript">
$(function(){
	var full = $('#mode-highslide-full-packed-js'),
		adv = $('#advanced'),
		opt = $('ul[id^="typecho-option-item-full"]');
	$('#rpopt-link').prop('checked','true');
	//全功能开关效果
	if (!full.is(':checked')) disable();
	full.click(function(){
		adv.attr('style','color:#467B96;font-weight:bold;');
		opt.removeAttr('style');
		$('input',opt).removeAttr('disabled');
	});
	$('#mode-highslide-packed-js').click(function(){
		disable();
	});
	function disable(){
		$('#fullalign-default,#fullslide-1,#fullnextimg-0').attr('checked','true');
		$('#fullopac-0-11').val('0.65');
		adv.attr('style','color:#999;font-weight:bold;');
		opt.attr('style','color:#999;');
		$('input',opt).attr('disabled','true');
	}
});
</script>
<?php
		//相册设置隐藏域
		$gallery = new Typecho_Widget_Helper_Form_Element_Hidden('gallery',
		array('gallery-horizontal-strip','gallery-thumbstrip-above','gallery-vertical-strip','gallery-in-box','gallery-floating-thumbs','gallery-floating-caption','gallery-controls-in-heading','gallery-in-page'),'gallery-horizontal-strip');
		$form->addInput($gallery);

		$thumbfix = new Typecho_Widget_Helper_Form_Element_Hidden('thumbfix',
		array('fixedwidth','fixedheight','fixedratio'),'fixedwidth');
		$form->addInput($thumbfix);
		$fixedwidth = new Typecho_Widget_Helper_Form_Element_Hidden('fixedwidth',NULL,'200');
		$form->addInput($fixedwidth);
		$fixedheight = new Typecho_Widget_Helper_Form_Element_Hidden('fixedheight',NULL,'100');
		$form->addInput($fixedheight);
		$fixedratio = new Typecho_Widget_Helper_Form_Element_Hidden('fixedratio',NULL,'4:3');
		$form->addInput($fixedratio);

		$thumbapi = new Typecho_Widget_Helper_Form_Element_Hidden('thumbapi',
		array(0,1),0);
		$form->addInput($thumbapi);
		$storage = new Typecho_Widget_Helper_Form_Element_Hidden('storage',
		array('local','qiniu','scs','nos','cos'),'local');
		$form->addInput($storage);
		$path = new Typecho_Widget_Helper_Form_Element_Hidden('path',NULL,'/usr/uploads/HSgallery/');
		$form->addInput($path);
		$cloudtoo = new Typecho_Widget_Helper_Form_Element_Hidden('cloudtoo',
		array(0,1),0);
		$form->addInput($cloudtoo);

		$qiniubucket = new Typecho_Widget_Helper_Form_Element_Hidden('qiniubucket',NULL,'');
		$form->addInput($qiniubucket);
		$qiniudomain = new Typecho_Widget_Helper_Form_Element_Hidden('qiniudomain',NULL,'http://');
		$form->addInput($qiniudomain);
		$qiniuak = new Typecho_Widget_Helper_Form_Element_Hidden('qiniuak',NULL,'');
		$form->addInput($qiniuak);
		$qiniusk = new Typecho_Widget_Helper_Form_Element_Hidden('qiniusk',NULL,'');
		$form->addInput($qiniusk);

		$scsbucket = new Typecho_Widget_Helper_Form_Element_Hidden('scsbucket',NULL,'');
		$form->addInput($scsbucket);
		$scsdomain = new Typecho_Widget_Helper_Form_Element_Hidden('scsdomain',NULL,'http://');
		$form->addInput($scsdomain);
		$scsimgx = new Typecho_Widget_Helper_Form_Element_Hidden('scsimgx',NULL,'http://*.applinzi.com|*');
		$form->addInput($scsimgx);
		$scsak = new Typecho_Widget_Helper_Form_Element_Hidden('scsak',NULL,'');
		$form->addInput($scsak);
		$scssk = new Typecho_Widget_Helper_Form_Element_Hidden('scssk',NULL,'');
		$form->addInput($scssk);

		$nosbucket = new Typecho_Widget_Helper_Form_Element_Hidden('nosbucket',NULL,'');
		$form->addInput($nosbucket);
		$nosdomain = new Typecho_Widget_Helper_Form_Element_Hidden('nosdomain',NULL,'http://');
		$form->addInput($nosdomain);
		$nosak = new Typecho_Widget_Helper_Form_Element_Hidden('nosak',NULL,'');
		$form->addInput($nosak);
		$nosas = new Typecho_Widget_Helper_Form_Element_Hidden('nosas',NULL,'');
		$form->addInput($nosas);
		$nosep = new Typecho_Widget_Helper_Form_Element_Hidden('nosep',
		array('nos-eastchina1.126.net'),'nos-eastchina1.126.net');
		$form->addInput($nosep);

		$cosbucket = new Typecho_Widget_Helper_Form_Element_Hidden('cosbucket',NULL,'');
		$form->addInput($cosbucket);
		$cosdomain = new Typecho_Widget_Helper_Form_Element_Hidden('cosdomain',NULL,'http://*.image.myqcloud.com');
		$form->addInput($cosdomain);
		$cosai = new Typecho_Widget_Helper_Form_Element_Hidden('cosai',NULL,'');
		$form->addInput($cosai);
		$cossi = new Typecho_Widget_Helper_Form_Element_Hidden('cossi',NULL,'');
		$form->addInput($cossi);
		$cossk = new Typecho_Widget_Helper_Form_Element_Hidden('cossk',NULL,'');
		$form->addInput($cossk);
		$cosrg = new Typecho_Widget_Helper_Form_Element_Hidden('cosrg',array('sh','gz','cd','tj','bj','sgp','hk','ca','ger'),'sh');
		$form->addInput($cosrg);
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
	 * 初始化数据表
	 * 
	 * @access public
	 * @return string
	 * @throws Typecho_Plugin_Exception
	 */
	public static function galleryinstall()
	{
		$installdb = Typecho_Db::get();
		$type = array_pop(explode('_',$installdb->getAdapterName()));
		$prefix = $installdb->getPrefix();

		$scripts = file_get_contents('usr/plugins/HighSlide/'.$type.'.sql');
		$scripts = explode(';',str_replace('%charset%','utf8',str_replace('typecho_',$prefix,$scripts)));

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
			if (('Mysql'==$type && ('42S01'==$code || 1050==$code)) || 
					('SQLite'==$type && ('HY000'==$code || 1==$code))) {
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
	 * @param string $key,$secret
	 * @return Qiniu\Auth
	 */
	public static function qiniuset($key,$secret)
	{
		require_once('cloud/qiniu/autoload.php');
		return new Qiniu\Auth($key,$secret);
	}

	/**
	 * 调取新浪云SCS许可
	 * 
	 * @access public
	 * @param string $key,$secret
	 * @return SCS
	 */
	public static function scsset($key,$secret)
	{
		require_once('cloud/scs/SCS.php');
		return new SCS($key,$secret);
	}

	/**
	 * 调取网易云NOS许可
	 * 
	 * @access public
	 * @param string $key,$secret,$endpoint
	 * @return NOS\NosClient
	 */
	public static function nosset($key,$secret,$endpoint)
	{
		require_once('cloud/nos/autoload.php');
		return new NOS\NosClient($key,$secret,$endpoint);
	}

	/**
	 * 调取腾讯云COS许可
	 * 
	 * @access public
	 * @param string $region
	 * @return qcloudcos\Cosapi
	 */
	public static function cosset($region)
	{
		require_once('cloud/cos/include.php');
		qcloudcos\Cosapi::setRegion($region);
		return new qcloudcos\Cosapi();
	}

	/**
	 * 输出路由参数
	 * 
	 * @access public
	 * @param string $url 原图地址
	 * @param boolean $isatt 是否来自附件
	 * @return Typecho_Config
	 */
	public static function route($url=NULL,$isatt=false)
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$localsite = $options->siteUrl;

		$qiniusite = $settings->qiniudomain;
		$qiniusite = $qiniusite=='http://' ? '' : $qiniusite;
		$scssite = $settings->scsdomain;
		$scssite = $scssite && $scssite!=='http://' ? $scssite : 'http://'.$settings->scsbucket.'.cdn.sinacloud.net';
		$nossite = $settings->nosdomain;
		$nossite = $nossite=='http://' ? '' : $nossite;
		$cossite = $settings->cosdomain;
		$cossite = $cossite!=='http://*.image.myqcloud.com' && $cossite!=='http://' ? $cossite : '';

		//获取路径前缀
		$dname = '';
		$durl = '';
		if ($url) {
			$source = parse_url($url);
			$dname = dirname($url);
			$durl = 0===strpos($dname,$localsite) ? $localsite : $source['scheme'].'://'.$source['host'];
		}

		//按储存来源获取地址
		switch (true) {
			case !$url && $settings->storage=='local' || 0===strpos($url,$localsite) :
			$site = $localsite;
			$from = 'local';
			break;
			case !$url && $settings->storage=='qiniu' || $qiniusite && 0===strpos($url,$qiniusite) :
			$site = $qiniusite;
			$from = 'qiniu';
			break;
			case !$url && $settings->storage=='scs' || 0===strpos($url,$scssite) :
			if ($url) {
				$dname = dirname(str_replace($scssite,'',$url)); //fix 子目录
			}
			$site = $scssite;
			$from = 'scs';
			break;
			case !$url && $settings->storage=='nos' || $nossite && 0===strpos($url,$nossite) :
			$site = $nossite;
			$from = 'nos';
			break;
			case !$url && $settings->storage=='cos' || $cossite && 0===strpos($url,$cossite) :
			$site = $cossite;
			$from = 'cos';
			break;
			default :
			$site = '';
			$from = '';
		}
		$filedir = $isatt ? str_replace($durl,'',$dname.'/') : $settings->path;
		$filedir = substr(Typecho_Common::url($filedir,''),1); //处理"/"号
		$filedir = $filedir ? $filedir : '';

		return new Typecho_Config(array(
			'dir'=>$filedir,
			'url'=>$url ? $url : Typecho_Common::url($filedir,$site),
			'site'=>$site,
			'from'=>$from //判断url来源
		));
	}

	/**
	 * 构建相册表单
	 * 
	 * @access public
	 * @param string $action,$render
	 * @return Typecho_Widget_Helper_Form
	 */
	public static function form($action=NULL,$render='g')
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$security = Helper::security();

		//图片编辑表单
		$gform = new Typecho_Widget_Helper_Form($security->getIndex('/action/gallery-edit'),
		Typecho_Widget_Helper_Form::POST_METHOD);

		$image = new Typecho_Widget_Helper_Form_Element_Text('image',
		NULL,NULL,_t('原图地址*'));
		$gform->addInput($image);
		$thumb = new Typecho_Widget_Helper_Form_Element_Text('thumb',
		NULL,NULL,_t('缩略图地址*'));
		$gform->addInput($thumb);
		$name = new Typecho_Widget_Helper_Form_Element_Text('name',
		NULL,NULL,_t('图片名称'));
		$name->input->setAttribute('class','mini');
		$gform->addInput($name);
		$description = new Typecho_Widget_Helper_Form_Element_Textarea('description',
		NULL,NULL,_t('图片描述'),_t('推荐填写, 用于展示相册中图片的文字说明效果'));
		$gform->addInput($description);
		$sort = new Typecho_Widget_Helper_Form_Element_Text('sort',
		NULL,'1',_t('相册组*'),_t('输入数字, 对应标签[GALLERY-数字]在页面调用'));
		$sort->input->setAttribute('class','w-10');
		$gform->addInput($sort);

		$do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
		$gform->addInput($do);
		$gid = new Typecho_Widget_Helper_Form_Element_Hidden('gid');
		$gform->addInput($gid);
		$submit = new Typecho_Widget_Helper_Form_Element_Submit();
		$submit->input->setAttribute('class','btn');
		$gform->addItem($submit);

		//相册设置表单
		$sform = new Typecho_Widget_Helper_Form($security->getIndex('/action/gallery-edit?do=sync'),
		Typecho_Widget_Helper_Form::POST_METHOD);

		$gallery = new Typecho_Widget_Helper_Form_Element_Select('gallery',
		array('gallery-horizontal-strip'=>_t('连环画册'),'gallery-thumbstrip-above'=>_t('黑色影夹'),'gallery-vertical-strip'=>_t('时光胶带'),'gallery-in-box'=>_t('纯白记忆'),'gallery-floating-thumbs'=>_t('往事片段'),'gallery-floating-caption'=>_t('沉默注脚'),'gallery-controls-in-heading'=>_t('岁月名片'),'gallery-in-page'=>_t('幻影橱窗(单相册)')),$settings->gallery,_t('相册风格'));
		$sform->addInput($gallery);

		$thumboptions = array(
		'fixedwidth'=>_t('固定宽度 %s',' <input type="text" class="w-10 text-s mono" name="fixedwidth" value="'.$settings->fixedwidth.'"/>'),
		'fixedheight'=>_t('固定高度 %s',' <input type="text" class="w-10 text-s mono" name="fixedheight" value="'.$settings->fixedheight.'"/>'),
		'fixedratio'=>_t('固定比例 %s',' <input type="text" class="w-10 text-s mono" name="fixedratio" value="'.$settings->fixedratio.'"/>'),
		);
		$thumbfix = new Typecho_Widget_Helper_Form_Element_Radio('thumbfix',
		$thumboptions,$settings->thumbfix,_t('缩略图规格'),_t('宽高单位px不用填写, 比例带“:”号, 同步影响正文附件缩略图'));
		$sform->addInput($thumbfix->multiMode());

		$thumbapi = new Typecho_Widget_Helper_Form_Element_Radio('thumbapi',
		array(0=>_t('本地GD库渲染'),1=>_t('云端API演算')),$settings->thumbapi,_t('缩略图生成方式'),_t('API方式将按照原图url生成访问缓存, 不会占用额外储存空间'));
		$sform->addInput($thumbapi);

		$storage = new Typecho_Widget_Helper_Form_Element_Radio('storage',
		array('local'=>_t('本地'),'qiniu'=>_t('<a href="https://portal.qiniu.com/signup?code=3lgwdq6pao2tu" target="_blank">七牛</a>'),'scs'=>_t('<a href="http://www.sinacloud.com/public/login/inviter/gaimrn-mddmzeKWrhKWnroB4fWt9rnlsf6K6dg.html" target="_blank">新浪云SCS</a>'),'nos'=>_t('<a href="https://www.163yun.com/nos/free" target="_blank">网易云NOS</a>'),'cos'=>_t('<a href="https://cloud.tencent.com/product/cos" target="_blank">腾讯云COS</a>')),$settings->storage,_t('储存位置%s','<div id="tooltip" style="display:none;"></div>'));
		$storage->setAttribute('style','position:relative;font-size:98%'); //fix IE换行
		$sform->addInput($storage);

		$path = new Typecho_Widget_Helper_Form_Element_Text('path',
		NULL,$settings->path,_t('路径前缀'),_t('“/”号结尾, 本地路径请确保可写, 云端前缀将忽略开头的“/”号'));
		$sform->addInput($path);

		$qiniubucket = new Typecho_Widget_Helper_Form_Element_Text('qiniubucket',
		NULL,$settings->qiniubucket,_t('空间名称'));
		$sform->addInput($qiniubucket);
		$qiniudomain = new Typecho_Widget_Helper_Form_Element_Text('qiniudomain',
		NULL,$settings->qiniudomain,_t('访问域名'));
		$sform->addInput($qiniudomain);
		$qiniuak = new Typecho_Widget_Helper_Form_Element_Text('qiniuak',
		NULL,$settings->qiniuak,_t('AccessKey'));
		$sform->addInput($qiniuak);
		$qiniusk = new Typecho_Widget_Helper_Form_Element_Text('qiniusk',
		NULL,$settings->qiniusk,_t('SecretKey'));
		$sform->addInput($qiniusk);
		$scsbucket = new Typecho_Widget_Helper_Form_Element_Text('scsbucket',
		NULL,$settings->scsbucket,_t('Bucket名称'));
		$sform->addInput($scsbucket);

		$scsdomain = new Typecho_Widget_Helper_Form_Element_Text('scsdomain',
		NULL,$settings->scsdomain,_t('绑定域名'),_t('未申请<a href="http://open.sinastorage.com/?c=console&a=parked_domain" target="_blank">域名绑定</a>留空即可'));
		$sform->addInput($scsdomain);
		$scsimgx = new Typecho_Widget_Helper_Form_Element_Text('scsimgx',
		NULL,$settings->scsimgx,_t('图片处理服务 (新Imgxs)'),_t('填写<a href="https://imgxs.sinacloud.com" target="_blank">服务实例</a>域名和<a href="https://imgxs.sinacloud.com/#/detail/origin" target="_blank">源站标识</a>用|号隔开, 留空则使用原<a href="http://scs.sinacloud.com/doc/scs/imgx" target="_blank">imgx</a>免费子域名API'));
		$sform->addInput($scsimgx);
		$scsak = new Typecho_Widget_Helper_Form_Element_Text('scsak',
		NULL,$settings->scsak,_t('Access Key'));
		$sform->addInput($scsak);
		$scssk = new Typecho_Widget_Helper_Form_Element_Text('scssk',
		NULL,$settings->scssk,_t('Secret Key'));
		$sform->addInput($scssk);

		$nosbucket = new Typecho_Widget_Helper_Form_Element_Text('nosbucket',
		NULL,$settings->nosbucket,_t('桶名称'));
		$sform->addInput($nosbucket);
		$nosdomain = new Typecho_Widget_Helper_Form_Element_Text('nosdomain',
		NULL,$settings->nosdomain,_t('域名'));
		$sform->addInput($nosdomain);
		$nosak = new Typecho_Widget_Helper_Form_Element_Text('nosak',
		NULL,$settings->nosak,_t('Access Key'));
		$sform->addInput($nosak);
		$nosas = new Typecho_Widget_Helper_Form_Element_Text('nosas',
		NULL,$settings->nosas,_t('Access Secret'));
		$sform->addInput($nosas);
		$nosep = new Typecho_Widget_Helper_Form_Element_Radio('nosep',
		array('nos-eastchina1.126.net'=>_t('华东1')),$settings->nosep, _t('数据中心'));
		$sform->addInput($nosep);

		$cosbucket = new Typecho_Widget_Helper_Form_Element_Text('cosbucket',
		NULL,$settings->cosbucket,_t('Bucket名称'));
		$sform->addInput($cosbucket);
		$cosdomain = new Typecho_Widget_Helper_Form_Element_Text('cosdomain',
		NULL,$settings->cosdomain,_t('访问域名'),_t('使用API方式生成缩略图须填写%s万象优图%s的图片处理/加速域名','<a href="https://console.qcloud.com/ci/bucket" target="_blank">','</a>'));
		$sform->addInput($cosdomain);
		$cosai = new Typecho_Widget_Helper_Form_Element_Text('cosai',
		NULL,$settings->cosai,_t('APPID'),_t('可通过%s腾讯云控制台%s【账号信息】查看','<a href="https://console.cloud.tencent.com/developer" target="_blank">','</a>'));
		$sform->addInput($cosai);
		$cossi = new Typecho_Widget_Helper_Form_Element_Text('cossi',
		NULL,$settings->cossi,_t('SecretId'));
		$sform->addInput($cossi);
		$cossk = new Typecho_Widget_Helper_Form_Element_Text('cossk',
			NULL,$settings->cossk,_t('SecretKey'));
		$sform->addInput($cossk);
		$cosrg = new Typecho_Widget_Helper_Form_Element_Select('cosrg',
		array('sh'=>_t('华东(上海)'),'gz'=>_t('华南(广州)'),'cd'=>_t('西南(成都)'),'tj'=>_t('华北(北京一区)'),'bj'=>_t('北京'),'sgp'=>_t('新加坡'),'hk'=>_t('香港'),'ca'=>_t('多伦多'),'ger'=>_t('法兰克福')),$settings->cosrg, _t('所属地区'));
		$sform->addInput($cosrg);

		$cloudtoo = new Typecho_Widget_Helper_Form_Element_Select('cloudtoo',
		array(0=>_t('否'),1=>_t('是')),$settings->cloudtoo,_t('正文附件缩略图也使用该云储存'));
		$cloudtoo->label->setAttribute('style','color:#999;font-weight:normal;font-size:.92857em;');
		$cloudtoo->input->setAttribute('style','position:absolute;font-size:.92857em;height:24px;bottom:-2px;left:184px;');
		$cloudtoo->setAttribute('style','position:relative');
		$sform->addInput($cloudtoo);

		$sform->addItem($submit);

		$request = Typecho_Request::getInstance();
		//修改图片模式
		if (isset($request->gid) && $action!=='insert') {
			$db = Typecho_Db::get();
			$gallery = $db->fetchRow($db->select()->from('table.gallery')->where('gid = ?',$request->filter('int')->gid));
			if (!$gallery) {
				throw new Typecho_Widget_Exception(_t('修改图片不存在'));
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
		//保存设置模式
		} elseif ($action=='sync' && $render=='s') {
			$submit->value(_t('保存设置'));
			$_action = 'sync';
		//添加图片模式
		} else {
			$do->value('insert');
			$submit->value(_t('添加图片'));
			$_action = 'insert';
		}
		if (!$action) {
			$action = $_action;
		}

		//验证表单规则
		if ($action=='insert' || $action=='update') {
			$thumb->addRule('required',_t('图片地址不能为空'));
			$image->addRule('required',_t('图片地址不能为空'));
			$sort->addRule('required',_t('相册组不能为空'));
			$thumb->addRule('url',_t('请填写合法的图片地址'));
			$image->addRule('url',_t('请填写合法的图片地址'));
			$sort->addRule('isInteger',_t('请填写整数数字'));
		}

		return $render=='g' ? $gform : $sform;
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
		$content = empty($lastResult) ? $content : $lastResult;

		if ($widget instanceof Widget_Archive) {
			$options = Helper::options();
			$settings = $options->plugin('HighSlide');
			$type = self::replacelist();

			//判断替换范围
			if ($widget->is(''.$type['index'].'') || $widget->is(''.$type['archive'].'') || $widget->is(''.$type['post'].'') || $widget->is(''.$type['page'].'')) {

				$content = preg_replace('/<a(.*?)href=\"([^\s]+)\.(jpg|gif|png|bmp)\"(.*?)>(.*?)<\/a>/si'
					,'<a$1href="$2.$3" class="highslide" onclick="return hs.expand(this,{slideshowGroup:\'images\'})"$4>$5</a>',$content);

				//所有图片弹窗
				if ($settings->rpopt) {
					$content = preg_replace('/(<img[^>]+src\s*=\s*"?([^>"\s]+)"?[^>]*>)(?!<\/a>)/si'
						,'<a href="$2" class="highslide" onclick="return hs.expand(this,{slideshowGroup:\'images\'})">$1</a>',$content);
				}
				//兼容旧版附件
				if (strpos($content,'/attachment/')) {
					$content = preg_replace_callback('/<a(.*?)href=\"([^\s]+)\/attachment\/(\d*)\/\"(.*?)>/i',array('HighSlide_Plugin','linkparse'),$content);
				}
			}

			//相册标签替换
			if ($settings->mode=='highslide-full.packed.js' && $widget->is('page')) {
				$content = preg_replace_callback('/\[GALLERY([\-\d|,]*?)\]/i',array('HighSlide_Plugin','galleryparse'),$content);
			}

			$version = explode('/',$options->version);
			$sign = '</hs>';
			$pattern = '/<(hs)(.*?)>(.*?)<\/\\1>/si';
			//markdown fix
			if ($version['1']=='17.10.30' && $widget->isMarkdown && !stripos($content,'</hs>')) {
				$sign = '&lt;/hs&gt;';
				$pattern = '/&lt;(hs)(.*?)&gt;(.*?)&lt;\/\\1&gt;/si';
			}
			//html标签替换
			if ($settings->mode=='highslide-full.packed.js' && false!==stripos($content,$sign)) {
				$content = preg_replace_callback($pattern,array('HighSlide_Plugin','htmlparse'),$content);
			}
		}

		return $content;
	}

	/**
	 * 输出范围参数
	 * 
	 * @access private
	 * @return array
	 */
	private static function replacelist()
	{
		$rplist = Helper::options()->plugin('HighSlide')->rplist;
		$rplists = array('index'=>'','archive'=>'','post'=>'','page'=>'');

		if ($rplist) {
			foreach ($rplist as $key=>$val) {
				$key = $val;
				$rplists[$key] = $val;
			}
		}

		return $rplists;
	}

	/**
	 * 页面相册解析
	 * 
	 * @access public
	 * @param array $match
	 * @return string
	 */
	public static function galleryparse($match)
	{
		$output = '<span style="font-weight:bold;color:#467B96;">'._t('相册数据为空').'</span>';
		$sort = self::defaultsort();

		if ($sort) {
			$settings = Helper::options()->plugin('HighSlide');
			$db = Typecho_Db::get();
			$gallerys = array();
			$coversets = array();
			$cover = '';
			$body = '';

			//记录相册组ID
			$param = substr(trim($match['1']),1);
			$param = $param ? $param : $sort;
			static $count = 0;
			++$count;
			self::$ids[] = $param;

			$sorts = array_filter(explode(',',$param));
			foreach ($sorts as $sort) {
				$gallerys = $db->fetchAll($db->select()->from('table.gallery')->where('sort = ?',''.$sort.'')->order('table.gallery.order',Typecho_Db::SORT_ASC));

				if ($gallerys) {
					//输出封面部分
					$coversets = array(array_shift($gallerys));
					foreach ($coversets as $coverset) {
						switch ($settings->gallery) {
							case 'gallery-in-page' :
							$cover .= '<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,inPageOptions)"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a> ';
							break;
							case 'gallery-controls-in-heading' :
							$cover .= '<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a><div class="highslide-heading">'.$coverset['description'].'</div> ';
							break;
							case 'gallery-in-box' :
							$cover .= '<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a><div class="highslide-caption">'.$coverset['description'].'</div> ';
							break;
							default :
							$cover .= '<a id="thumb'.$sort.'" class="highslide" href="'.$coverset['image'].'" title="'.$coverset['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$coverset['thumb'].'" alt="'.$coverset['description'].'"/></a> ';
						}
					}

					//输出列表部分
					foreach ($gallerys as $gallery) {
						switch ($settings->gallery) {
							case 'gallery-in-page' :
							$body .= '<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,inPageOptions)"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a>';
							break;
							case 'gallery-controls-in-heading' :
							$body .= '<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a><div class="highslide-heading">'.$gallery['description'].'</div>';
							break;
							case 'gallery-in-box' :
							$body .= '<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a><div class="highslide-caption">'.$gallery['description'].'</div>';
							break;
							default :
							$body .= '<a class="highslide" href="'.$gallery['image'].'" title="'.$gallery['description'].'" onclick="return hs.expand(this,{slideshowGroup:\'group'.$sort.'\'})"><img src="'.$gallery['thumb'].'" alt="'.$gallery['description'].'"/></a>';
						}
					}
				}
			}

			$output = $settings->gallery=='gallery-in-page'
				 ? '<div id="gallery-area" style="width:620px;height:520px;margin:0 auto;border:1px solid silver;"><div class="hidden-container">'.$cover.$body.'</div></div>'
				: '<div class="highslide-gallery">'.$cover.'<div class="hidden-container">'.$body.'</div></div>';
		}

		return $output;
	}

	/**
	 * 缺省相册分类
	 * 
	 * @access public
	 * @return string
	 */
	public static function defaultsort()
	{
		$db = Typecho_Db::get();
		$default = $db->fetchRow($db->select('sort')->from('table.gallery')->order('sort',Typecho_Db::SORT_ASC));
		return $default ? $default['sort'] : '';
	}

	/**
	 * html弹窗解析
	 * 
	 * @access public
	 * @param array $match
	 * @return string
	 */
	public static function htmlparse($match)
	{
		$settings = Helper::options()->plugin('HighSlide');
		$fullwrap = $settings->fullwrap;
		//准备默认设置
		$id = 'highslide-html';
		$text = 'text';
		$title = '';
		$href = '#';
		$width = '';
		$height = '';
		$Movetext = 'Move';
		$Movetitle = 'Move';
		$Closetext = 'Close';
		$Closetitle = 'Close (esc)';
		$Resizetitle = 'Resize';
		if ($settings->lang=='cn') {
			$Movetext = '移动';
			$Movetitle = '移动';
			$Closetext = '关闭';
			$Closetitle = '关闭 (esc)';
			$Resizetitle = '拉伸';
		}

		$param = htmlspecialchars_decode(trim($match['2'])); //markdown fix
		//解析标签参数
		if ($param) {
			if (preg_match('/id=["\']([\w-]*)["\']/i',$param,$out)) {
				$id = trim($out[1]) ? trim($out[1]) : $id;
			}
			if (preg_match('/text=["\'](.*?)["\']/si',$param,$out)) {
				$text = trim($out[1]) ? trim($out[1]) : $text;
			}
			if (preg_match('/title=["\'](.*?)["\']/si',$param,$out)) {
				$title = trim($out[1]) ? trim($out[1]) : $title;
			}
			if (preg_match('/ajax=["\'](.*?)["\']/i',$param,$out)) {
				$href = trim($out[1]) ? trim($out[1]) : $href;
			}
			if (preg_match('/width=["\']([\w-]*)["\']/i',$param,$out)) {
				$width = trim($out[1]) ? ',width:'.strtr(trim($out[1]),'px','') : $width;
			}
			if (preg_match('/height=["\']([\w-]*)["\']/i',$param,$out)) {
				$height = trim($out[1]) ? ',height:'.strtr(trim($out[1]),'px','') : $height;
			}
		}

		$output = '<a href="'.$href.'" onclick="return hs.htmlExpand(this,{';
		$output .= $href=='#' ? 'contentId:\''.$id.'\'' : 'objectType:\'ajax\''; //ajax判断
		$output .= in_array('draggable-header',($fullwrap ? $fullwrap : array())) && $title ? ',headingText:\''.$title.'\'' : ''; //标题栏显示
		$output .= $width.$height.'})" class="highslide">'.$text.'</a>';
		$output .= '<div class="highslide-html-content" id="'.$id.'"><div class="highslide-header"><ul><li class="highslide-move"><a href="#" onclick="return false" title="'.$Movetitle.'"><span>'.$Movetext.'</span></a></li>';
		$output .= '<li class="highslide-close"><a href="#" onclick="return hs.close(this)" title="'.$Closetitle.'"><span>'.$Closetext.'</span></a></li></ul></div>';
		$output .= '<div class="highslide-body">'.trim($match['3']).'</div><div class="highslide-footer"><div><span class="highslide-resize" title="'.$Resizetitle.'"><span></span></span></div></div></div>
		';

		return $output;
	}

	/**
	 * 附件链接解析
	 * 
	 * @access public
	 * @param array $match
	 * @return string
	 */
	public static function linkparse($match)
	{
		$db = Typecho_Db::get();
		$cid = $match['3'];
		$attach = $db->fetchRow($db->select()->from('table.contents')->where('type = ?','attachment')->where('cid = ?',$cid));
		if ($attach) {
			$text = unserialize($attach['text']);
			$output = '<a'.$match['1'].'href="'.Typecho_Common::url($text['path'],Helper::options()->siteUrl).'" class="highslide" onclick="return hs.expand(this,{slideshowGroup:\'images\'})"'.$match['4'].'>';
			return $output;
		}
	}

	/**
	 * 内文附件脚本
	 * 
	 * @access public
	 * @return void
	 */
	public static function attachpanel()
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$security = Helper::security();
		$request = Typecho_Request::getInstance();
		$cid = !empty($request->cid) ? '?cid='.$request->filter('int')->cid : '';
?>
<link rel="stylesheet" type="text/css" media="all" href="<?php $options->pluginUrl('HighSlide/css/imgareaselect-animated.css'); ?>"/>
<script src="<?php $options->pluginUrl('HighSlide/js/imgareaselect.js'); ?>"></script>
<script type="text/javascript">
$(function(){
<?php
		//输出编辑器按钮
		if ($settings->mode=='highslide-full.packed.js') {
?>
	var wmd = $('#wmd-image-button');
	if (wmd.length>0) {
		wmd.after(
	'<li class="wmd-button" id="wmd-hs-button" style="padding-top:5px;" title="<?php _e("插入Html弹窗"); ?>"><img src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%3E%3Cpath%20fill%3D%22%23999%22%20d%3D%22M17%2016h-4v8h-2v-8h-4l5-6%205%206zm7%208h-9v-2h7v-16h-20v16h7v2h-9v-24h24v24z%22%2F%3E%3C%2Fsvg%3E"/></li>');
	} else {
		$('.url-slug').after('<button type="button" id="wmd-hs-button" class="btn btn-xs" style="margin-right:5px;"><?php _e("插入Html弹窗"); ?></button>');
	}
	$('#wmd-hs-button').click(function(){
		$('body').append('<div id="hsanel">' +
		'<div class="wmd-prompt-background" style="position:absolute;z-index:1000;opacity:0.5;top:0px;left:0px;width:100%;height:954px;"></div>' +
		'<div class="wmd-prompt-dialog"><div><p><b><?php _e("插入Html弹窗"); ?></b></p>' +
			'<p><?php _e("请在下方的输入框内输入要实现弹窗效果的链接与内容"); ?></p></div>' +
			'<form><input type="text"></input><button type="button" class="btn btn-s primary" id="ok"><?php _e("确定"); ?></button>' +
			'<button type="button" class="btn btn-s" id="cancel"><?php _e("取消"); ?></button></form>' +
		'</div></div>');
		var hslog = $('.wmd-prompt-dialog input'),
			textarea = $('#text');
		hslog.val('<hs text="链接文字">弹窗内容</hs>').select();
		$('#cancel').click(function(){
			$('#hsanel').remove();
			textarea.focus();
		});
		$('#ok').click(function(){
			var hsinput = hslog.val(),
				sel = textarea.getSelection(),
				offset = (sel ? sel.start : 0)+hsinput.length;
			textarea.replaceSelection(hsinput);
			textarea.setSelection(offset,offset);
			$('#hsanel').remove();
		});
	});
<?php
		}
?>
	var list = $('#file-list');
	list.before('<button type="button" id="loadattach" class="btn btn-xs"><?php _e('缩略图模式'); ?> <i class="i-caret-right"></i></button>');
	list.after('<ul id="image-list" style="list-style:none;margin:0px 10px;padding:0px;max-height:800px;overflow:auto;display:none;"></ul>');

	//点击切换模式
	$('#loadattach').click(function(){
		var it = $('i',$(this)),
			button = it.parent(),
			pre = $('#image-list');
		if (it.attr('class')=='i-caret-left') {
			button.html('<?php _e('缩略图模式'); ?> <i class="i-caret-right">');
		} else {
			button.html('<?php _e('普通模式'); ?> <i class="i-caret-left">');
		}
		$('img[id^="image-"]').imgAreaSelect({remove:true});

		//上传切回普通
		$('.upload-file').click(function(){
			button.html('<?php _e('缩略图模式'); ?> <i class="i-caret-right">');
			$('img[id^="image-"]').imgAreaSelect({remove:true});
			pre.hide();
			list.show();
		});

		pre.html('<li class="loading"><?php _e('图片预览加载中...'); ?></li>');
		$.post('<?php $security->index('/action/gallery-edit'.$cid); ?>',
			{'do':'preview'},
			function(data){
				list.empty();
				pre.empty();
				var val = $.parseJSON(data);
				for (var i=0;i<val.length;i++) {
					//其他附件显示
					var cid = val[i].cid,
						isimg = val[i].isimg,
						url = val[i].url,
						title = val[i].title,
						size = val[i].size,
						fli = $('<li data-cid="'+cid+'">').attr('data-url',val[i].url).attr('data-image',isimg)
						.html('<input type="hidden" name="attachment[]" value="'+cid+'"/>'
							+'<a class="insert" title="<?php _e('点击插入文件'); ?>" href="###">'+title+'</a><div class="info">'+size
							+'<a class="file" target="_blank" href="<?php $options->adminUrl('media.php?cid='); ?>'+cid+'" title="<?php _e('编辑'); ?>"><i class="i-edit"></i></a>'
							+'<a href="###" class="delete" title="<?php _e('删除'); ?>"><i class="i-delete"></i></a></div>')
						.appendTo(list);
						attachInsertEvent(fli);
						attachDeleteEvent(fli);
					//图片附件显示
					if (isimg) {
						var name = val[i].name,
							t = title.indexOf('thumb_'), //判断缩略图
							aurl = val[i].aurl,
							hash = '?r='+Math.floor(Math.random()*200),
							turl = val[i].turl,
							li = $('<li data-url="'+(aurl ? turl : url)+'">').attr('style','padding:8px 0px;border-top:1px dashed #D9D9D6;')
							.attr('data-turl',turl).data('cid',cid).data('name',name).data('title',title).data('url',url).data('aurl',aurl)
							.html((t==0 ? '<img src="'+(aurl ? aurl : url+hash)+'" alt="'+title+'" style="max-width:50%;"/>'
								: '<input type="hidden" name="imgname" value="'+name+'"/>'
								+'<img id="image-'+cid+'" src="'+url+hash+'" alt="'+name+'" style="max-width:100%;"/>'
								+'<input type="hidden" name="x1" value="" id="x1"/>'
								+'<input type="hidden" name="y1" value="" id="y1"/>'
								+'<input type="hidden" name="x2" value="" id="x2"/>'
								+'<input type="hidden" name="y2" value="" id="y2"/>'
								+'<input type="hidden" name="w" value="" id="w"/>'
								+'<input type="hidden" name="h" value="" id="h"/>')
								+'<div class="info">'+size
								+ (t==0 ? ' <a class="addto" href="###" title="<?php _e('插入图链'); ?>"><i class="mime-image"></i></a>'
								 : ' <a class="crop" href="###" title="<?php _e('截取缩略图'); ?>"><i class="mime-application"></i></a>')
								+'</div>')
							.appendTo(pre),
							ti = li.siblings('li[data-turl="'+turl+'"]');
						if (t==0) li.insertAfter(ti); //关联位置
						iasEffectEvent(li);
						thumbCropEvent(li);
						imageAddtoEvent(li);
					}
				}
			});
		pre.toggle();
		list.toggle();
		return false;
	});

	//裁切蒙板事件
	function iasEffectEvent(el){
		var cid = $(el).data('cid');
		$('img#image-'+cid,el).imgAreaSelect({
			handles:true,
			instance:true,
			classPrefix:'ias-'+cid+' ias',
			aspectRatio:'<?php echo $settings->thumbfix=='fixedratio' ? $settings->fixedratio : 'false'; ?>', //宽高比设置
			onSelectEnd:function(img,selection){
				$('#x1',el).val(selection.x1);
				$('#y1',el).val(selection.y1);
				$('#x2',el).val(selection.x2);
				$('#y2',el).val(selection.y2);
				$('#w',el).val(selection.width);
				$('#h',el).val(selection.height);
			}
		});
	}

	//生成缩略图事件
	function thumbCropEvent(el){
		var pre = $('#image-list');
		$('.crop',el).click(function(){
			var pli = $(this).parents('li'),
				name = pli.data('name'),
				turl = pli.data('turl'),
				nli = pli.next(), //检测关联
				li = $('li[data-url="'+turl+'"]',pre),
				x1 = $('#x1',el).val(),
				y1 = $('#y1',el).val(),
				x2 = $('#x2',el).val(),
				y2 = $('#y2',el).val(),
				w = $('#w',el).val(),
				h = $('#h',el).val();
			if ('<?php echo $settings->cloudtoo && $settings->thumbapi; ?>'==1 && pli.data('url').indexOf('<?php echo self::route()->site; ?>')!==0) {
				alert('<?php _e('原图不在当前云储存, 无法使用API'); ?>');
				return false;
			}
			if (x1=='' || y1=='' || x2=='' || y2=='' || w=='' || h=='') {
				alert('<?php _e('请先拖选图片区域'); ?>');
				return false;
			}
			if (li.length==0) {
				pli.after('<li data-url="'+turl+'" class="loading"></li>');
			} else {
				li.empty().addClass('loading');
			}
			$('img[id^="image-"]').imgAreaSelect({hide:true});
			$.post('<?php $security->index('/action/gallery-edit'.$cid); ?>',
				{'do':'crop','imgname':name,'x1':x1,'y1':y1,'w':w,'h':h,'url':pli.data('url'),'tid':(nli.data('turl')==turl ? nli.data('cid') : '')},
				function(data){
					var li = $('li[data-url="'+turl+'"]',pre).removeClass('loading')
						.data('cid',data.cid).data('turl',turl).data('aurl',data.aurl) //添加数据
						.html('<img src="'+(data.aurl ? data.aurl : turl+'?r='+Math.floor(Math.random()*200))+'" alt="thumb_'+name+'" style="max-width:50%;"/><div class="info">'+data.bytes
						+' <a class="addto" href="###" title="<?php _e('插入图链'); ?>"><i class="mime-image"></i></a></div>')
					.effect('highlight',1000);
					imageAddtoEvent(li);
				}).error(function(XMLHttpRequest,textStatus,errorThrown){ //报错信息
					var mes = '<span style="background-color:#FBE3E4;color:#8A1F11;"><?php _e('缩略图截取出错: '); ?>'+textStatus+'</span>';
					if (li.length==0) {
						pli.after(mes);
					} else {
						li.removeClass('loading').html(mes);
					}
				});
			return false;
		});
	}

	//插入图链事件
	function imageAddtoEvent(el){
		$('.addto',el).click(function(){
			var li = $(this).parents('li'),
				turl = li.data('turl'),
				pli = li.prev('li');
				if (turl==pli.data('turl')) { //判断关联
					var url = pli.data('url'),
						title = pli.data('title'),
						aurl = li.data('aurl'),
						turl = aurl ? aurl : turl,
						textarea = $('#text'),
						sel = textarea.getSelection(),
<?php
		if ($options->markdown=='1') { //markdown
?>
						html = '[!['+title+']('+turl+')]('+url+' "'+title+'")',
<?php
		} else {
?>
						html = '<a href="'+url+'" title="'+title+'"><img src="'+turl+'" alt="'+title+'"/></a>',
<?php
		}
?>
						offset = (sel ? sel.start : 0)+html.length;
					textarea.replaceSelection(html);
					textarea.setSelection(offset,offset);
				} else {
					alert('<?php _e('找不到原图! 请删除后重新截取'); ?>');
				}
			return false;
		});
	}

	//typecho插入附件
	function attachInsertEvent(el){
		$('.insert',el).click(function(){
			var t = $(this), p = t.parents('li');
			Typecho.insertFileToEditor(t.text(),p.data('url'),p.data('image'));
			return false;
		});
	}

	//typecho删除附件
	function attachDeleteEvent(el){
		var file = $('a.insert',el).text();
		$('.delete',el).click(function(){
			if (confirm('<?php _e('确认要删除文件 %s 吗?'); ?>'.replace('%s', file))) {
				var cid = $(this).parents('li').data('cid');
				$.post('<?php $security->index('/action/contents-attachment-edit'); ?>',
				{'do':'delete','cid':cid},
				function(){
					$(el).fadeOut(function(){
						$(this).remove();
					});
				});
			}
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
		$widget = Typecho_Widget::widget('Widget_Archive');
		$type = self::replacelist();

		//判断输出范围
		if ($widget->is(''.$type['index'].'') || $widget->is(''.$type['archive'].'') || $widget->is(''.$type['post'].'') || $widget->is(''.$type['page'].'')) {
			$options = Helper::options();
			$settings = $options->plugin('HighSlide');
			$hsurl = $options->pluginUrl.'/HighSlide/';

			$cssurl = '
<link rel="stylesheet" type="text/css" href="'.$hsurl.'css/highslide.css"/>
<!--[if lt IE 7]>
<link rel="stylesheet" type="text/css" href="'.$hsurl.'css/highslide-ie6.css"/>
<![endif]-->
';
			//页面相册样式
			if ($settings->mode=='highslide-full.packed.js' && $widget->is('page')) {
				if ($settings->gallery=='gallery-in-page') {
					$cssurl .= '<style type="text/css">
.highslide-image {
border:1px solid black;
}
.highslide-controls {
width:90px!important;
}
.highslide-controls .highslide-close {
display:none;
}
.highslide-caption {
padding:.5em 0;
}
</style>
';
				}
				if ($settings->gallery=='gallery-vertical-strip') {
					$cssurl .= '<style type="text/css">
.highslide-caption {
width:100%;
text-align:center;
}
.highslide-close {
display:none !important;
}
.highslide-number {
display:inline;
padding-right:1em;
color:white;
}
</style>
';
				}
			}

			echo $cssurl;
		}
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
		$widget = Typecho_Widget::widget('Widget_Archive');
		$type = self::replacelist();

		//判断输出范围
		if ($widget->is(''.$type['index'].'') || $widget->is(''.$type['archive'].'') || $widget->is(''.$type['post'].'') || $widget->is(''.$type['page'].'')) {
			$hsurl = $options->pluginUrl.'/HighSlide/';
			$settings = $options->plugin('HighSlide');

			$links = '
<script type="text/javascript" src="'.$hsurl.'js/'.$settings->mode.'"></script>';
			$links .= '
<script type="text/javascript">
hs.graphicsDir = "'.$hsurl.'css/graphics/";
hs.fadeInOut = true;
hs.transitions = ["expand","crossfade"];';

			$ltext = $settings->ltext;
			//输出角标设置
			$links .= $ltext ? '
hs.lang.creditsText = "'.$ltext.'";
hs.lang.creditsTitle = "'.$ltext.'";
hs.creditsHref = "'.$options->index.'";
hs.creditsPosition = "'.$settings->lpos.'";' : '
hs.showCredits = false;';

			//输出中文支持
			if ($settings->lang=='cn') {
				$links .= '
hs.lang={
loadingText:"载入中...",
loadingTitle:"取消",
closeText:"关闭",
closeTitle:"关闭 (Esc)",
previousText:"上一张",
previousTitle:"上一张 (←键)",
nextText:"下一张",
nextTitle:"下一张 (→键)",
moveTitle:"移动",
moveText:"移动",
playText:"播放",
playTitle:"幻灯播放 (空格键)",
pauseText:"暂停",
pauseTitle:"幻灯暂停 (空格键)",
number:"第%1张 共%2张",
restoreTitle:"点击关闭或拖动. 左右方向键切换图片. ",
fullExpandTitle:"完整尺寸",
fullExpandText:"原大"
};';
			}

			//页面相册效果
			$ids= self::$ids;
			if ($ids && $settings->mode=='highslide-full.packed.js' && $widget->is('page')) {

				//整理相册组ID
				$group = array_unique(explode(',',implode(',',$ids)));
				$groups = $group;
				array_walk($groups,function(&$item,$key,$prefix){
					$item = '"'.$prefix.''.$item.'"';},'group');
				$groups = '
slideshowGroup:['.implode(',',$groups).'],';

				$links .= '
if (hs.addSlideshow) hs.addSlideshow({
interval:5000,
repeat:true,
useControls:true,';

				switch ($settings->gallery) {
					//连环画册
					case 'gallery-horizontal-strip' :
					$links .= $groups.'
overlayOptions:{
className:"text-controls",
position:"bottom center",
relativeTo:"viewport",
offsetY:-60
},
thumbstrip:{
position:"bottom center",
mode:"horizontal",
relativeTo:"viewport"
}
});
hs.align = "center";
hs.dimmingOpacity = 0.8;
hs.outlineType = "rounded-white";
hs.captionEval = "this.thumb.alt";
hs.marginBottom = 105;
hs.numberPosition = "caption";';
					break;

					//黑色影夹
					case 'gallery-thumbstrip-above' :
					$links .= $groups.'
fixedControls:"fit",
overlayOptions:{
position:"bottom center",
opacity:.75,
hideOnMouseOut:true
},
thumbstrip:{
position:"above",
mode:"horizontal",
relativeTo:"expander"
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
					break;

					//幻影橱窗
					case 'gallery-in-page' :
					$links .= '
overlayOptions:{
position:"bottom right",
offsetY:50
},
thumbstrip:{
position:"above",
mode:"horizontal",
relativeTo:"expander"
}
});
hs.restoreCursor = null;
hs.lang.restoreTitle = "'.($settings->lang=='cn' ? '点击查看下一张' : 'Click for next image').'";
var inPageOptions = {
outlineType:null,
wrapperClassName:"in-page controls-in-heading",
thumbnailId:"gallery-area",
useBox:true,
width:600,
height:400,
targetX:"gallery-area 10px",
targetY:"gallery-area 10px",
captionEval:"this.a.title",
numberPosition:"caption"
}
hs.addEventListener(window,"load",function(){
document.getElementById("thumb'.$group['0'].'").onclick();
});
hs.Expander.prototype.onImageClick = function(){
if (/in-page/.test(this.wrapper.className))	return hs.next();
}
hs.Expander.prototype.onBeforeClose = function(){
if (/in-page/.test(this.wrapper.className))	return false;
}
hs.Expander.prototype.onDrag = function(){
if (/in-page/.test(this.wrapper.className))	return false;
}
hs.addEventListener(window,"resize",function(){
var i,exp;
hs.getPageSize();
for (i=0;i<hs.expanders.length;i++) {
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
					break;

					//时光胶带
					case 'gallery-vertical-strip' :
					$links .= $groups.'
overlayOptions:{
	className:"text-controls",
	position:"bottom center",
	relativeTo:"viewport",
	offsetX:50,
	offsetY:-5
},
thumbstrip:{
	position:"middle left",
	mode:"vertical",
	relativeTo:"viewport"
}
});
hs.registerOverlay({
html:\'<div class="closebutton" onclick="return hs.close(this)" title="Close"></div>\',
position:"top right",
fade:2
});
hs.align = "center";
hs.dimmingOpacity = 0.8;
hs.wrapperClassName = "borderless floating-caption";
hs.captionEval = "this.thumb.alt";
hs.marginLeft = 100;
hs.marginBottom = 80;
hs.numberPosition = "caption";
hs.lang.number = "%1/%2";';
					break;

					//纯白记忆
					case 'gallery-in-box' :
					$links .= $groups.'
fixedControls:"fit",
overlayOptions:{
	opacity:1,
	position:"bottom center",
	hideOnMouseOut:true
}
});
hs.align = "center";
hs.outlineType = "rounded-white";
hs.dimmingOpacity = 0.75;
hs.useBox = true;
hs.width = 640;
hs.height = 480;';
					break;

					//往事片段
					case 'gallery-floating-thumbs' :
					$links .= $groups.'
fixedControls:"fit",
overlayOptions:{
	position:"top right",
	offsetX:200,
	offsetY:-65
},
thumbstrip:{
	position:"rightpanel",
	mode:"float",
	relativeTo:"expander",
	width:"210px"
}
});
hs.align = "center";
hs.outlineType = "rounded-white";
hs.headingEval = "this.a.title";
hs.numberPosition = "heading";
hs.useBox = true;
hs.width = 600;
hs.height = 400;';
					break;

					//沉默注脚
					case 'gallery-floating-caption' :
					$links .= $groups.'
fixedControls:"fit",
overlayOptions:{
	opacity:.6,
	position:"bottom center",
	hideOnMouseOut:true
}
});
hs.align = "center";
hs.wrapperClassName = "dark borderless floating-caption";
hs.dimmingOpacity = .75;
hs.captionEval = "this.a.title";';
					break;

					//岁月名片
					case 'gallery-controls-in-heading' :
					$links .= $groups.'
fixedControls:false,
overlayOptions:{
	opacity:1,
	position:"top right",
	hideOnMouseOut:false
}
});
hs.align = "center";
hs.outlineType = "rounded-white";
hs.wrapperClassName = "controls-in-heading";';
				}

			//插图弹窗效果
			} else {

				//边框样式
				$outline = $settings->outline;
				$wrap = $settings->fullwrap;
				$wrap = $wrap ? ' '.implode(' ',$wrap) : '';
				switch (true) {
					case 'glossy-dark'==$outline || 'rounded-black'==$outline :
					$class = '
hs.wrapperClassName = "dark'.$wrap.'";';
					break;
					case 'outer-glow'==$outline :
					$class = '
hs.wrapperClassName = "outer-glow'.$wrap.'";';
					break;
					case !$outline || 'beveled'==$outline :
					$class = '
hs.wrapperClassName = "borderless'.$wrap.'";';
					break;
					default :
					$class = $wrap ? '
hs.wrapperClassName = "'.$wrap.'";' : '';
				}
				$outline = $outline ? '
hs.outlineType = "'.$outline.'";' : '';
				$links .= $outline.$class;

				//关闭按钮
				$links .= $settings->cbutton ? '
hs.registerOverlay({
html:\'<div class="closebutton" onclick="return hs.close(this)" title="'.($settings->lang=='cn' ? '关闭' : 'Close').'"></div>\',
position:"top right",
fade:2
});' : '';
				$links .= $settings->capt ? '
hs.captionEval = "'.$settings->capt.'";' : '';

				//全功能版效果
				if ($settings->mode=='highslide-full.packed.js') {

					//图片序数
					$cpos = $settings->fullcpos;
					$links .= $cpos ? '
hs.numberPosition = "'.$cpos.'";' : '';
					$opac = $settings->fullopac;
					//背景遮罩
					$links .= $opac ? '
hs.dimmingOpacity = '.$opac.';' : '';
					//弹窗位置
					$links .= $settings->fullalign=='center' ? '
hs.align = "center";' : '';
					//幻灯按钮
					$links .= $settings->fullslide ? '
if (hs.addSlideshow) hs.addSlideshow({
slideshowGroup:"images",
interval:5000,
repeat:true,
useControls:true,
fixedControls:"fit",
overlayOptions:{
opacity:.65,
position:"bottom center",
hideOnMouseOut:true
}
});' : '';
					//自动翻页
					$links .= $settings->fullnextimg ? '
hs.Expander.prototype.onImageClick = function(){
return hs.next();
}' : '';
				}
			}

			$links .= '
</script>
';
			echo $links;
		}
	}

	/**
	 * 图片上传处理
	 * 
	 * @access public
	 * @param array $file 上传的文件
	 * @param boolean $thumb 是否为缩略图
	 * @return array
	 */
	public static function uploadhandle($file,$thumb=false)
	{
		$settings = Helper::options()->plugin('HighSlide');

		//准备路径参数
		if ($thumb) {
			$filepath = $file['file'];
			$imgpath = $file['path'];
		} else {
			if (!$file['name']) {
				return false;
			}

			$imgname = preg_split('(\/|\\|:)',$file['name']);
			$file['name'] = array_pop($imgname);
			//处理扩展名
			$ext = self::getsafename($file['name']);
			if (!in_array($ext,array('gif','jpg','jpeg','png','tiff','bmp')) || Typecho_Common::isAppEngine()) {
				return false;
			}

			$path = self::route()->dir;
			$imgdir = __TYPECHO_ROOT_DIR__.'/'.$path;
			//生成文件目录
			if ($settings->storage=='local' && !is_dir($imgdir)) {
				if (!self::makedir($imgdir)) {
					return false;
				}
			}

			//处理文件名
			$imgname = sprintf('%u',crc32(uniqid())).'.'.$ext;
			$imgfile = $imgdir.$imgname;
			$imgpath = $path.$imgname;

			if (!isset($file['tmp_name'])) {
				return false;
			}
			$filepath = $file['tmp_name'];
		}

		//按储存上传
		switch ($settings->storage) {

			case 'local' :
			if (!$thumb) {
				if (!@move_uploaded_file($filepath,$imgfile)) {
					if (isset($file['bytes'])) {
						if (!file_put_contents($imgfile, $file['bytes'])) { //直接写入
							return false;
						}
					} else {
						return false;
					}
				}
			}
			break;

			case 'qiniu' :
			$qiniu = self::qiniuset($settings->qiniuak,$settings->qiniusk);
			$bucket = $settings->qiniubucket;
			if ($thumb) {
				$bmgr = new Qiniu\Storage\BucketManager($qiniu);
				$bmgr->delete($bucket,$imgpath);
			}
			$token = $qiniu->uploadToken($bucket);
			$umgr = new Qiniu\Storage\UploadManager();
			$umgr->putFile($token,$imgpath,$filepath);
			break;

			case 'scs' :
			$scs = self::scsset($settings->scsak,$settings->scssk);
			$bucket = $settings->scsbucket;
			if ($thumb) {
				$scs->deleteObject($bucket,$imgpath);
			}
			$scs->putObjectFile($filepath,$bucket,$imgpath,SCS::ACL_PUBLIC_READ,array(),array('Content-Type'=>Typecho_Common::mimeContentType($filepath)));
			break;

			case 'nos' :
			$nos = self::nosset($settings->nosak,$settings->nosas,$settings->nosep);
			$bucket = $settings->nosbucket;
			if ($thumb && $nos->doesObjectExist($bucket,$imgpath)) {
				$nos->deleteObject($bucket,$imgpath);
			}
			$nos->uploadFile($bucket,$imgpath,$filepath);
			break;

			case 'cos' :
			$cos = self::cosset($settings->cosrg);
			$bucket = $settings->cosbucket;
			$stat = $cos->stat($bucket,$imgpath);
			if ($thumb && $stat['message']=='SUCCESS') {
				$cos->delFile($bucket,$imgpath);
			}
			$cos->upload($bucket,$filepath,$imgpath);
			$cos->update($bucket,$imgpath,'','eWPrivateRPublic',array('Content-Type' =>Typecho_Common::mimeContentType($filepath),'Content-Disposition'=>'inline'));
		}

		//返回文件数据
		if (!$thumb) {
			return array(
				'name'=>$imgname,
				'title'=>$file['name'],
				'size'=>$file['size']
			);
		}
	}

	/**
	 * 图片删除处理
	 * 
	 * @access public
	 * @param string $imgname 图片名称
	 * @param boolean $isatt 是否来自附件
	 * @param string $url 图片地址
	 * @param string $from 来源字段
	 * @return void
	 */
	public static function removehandle($imgname,$isatt=false,$url=NULL,$from=NULL)
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$route = self::route($url);
		$dir = $route->dir;
		$from = $from ? $from : $route->from;

		$imgdir = $from=='local' ? __TYPECHO_ROOT_DIR__.'/'.$dir : $dir;
		$imgpath = $imgdir.$imgname;
		$thumbpath = $imgdir.'thumb_'.$imgname;

		//附件模式删除
		if ($isatt) {
			$localpath = __TYPECHO_ROOT_DIR__.'/'.$imgname;
			$imgpath = $from=='local' ? $localpath : $imgname;
			if (file_exists($localpath)) {
				@unlink($localpath);
			}
		}

		//按储存来源删除
		switch ($from) {
			case 'local' :
			if (file_exists($imgpath)) {
				@unlink($imgpath);
			}
			if (file_exists($thumbpath)) {
				@unlink($thumbpath);
			}
			break;

			case 'qiniu' :
			$qiniu = self::qiniuset($settings->qiniuak,$settings->qiniusk);
			$bucket = $settings->qiniubucket;
			$bmgr = new Qiniu\Storage\BucketManager($qiniu);
			$bmgr->delete($bucket,$imgpath);
			$bmgr->delete($bucket,$thumbpath);
			break;

			case 'scs' :
			$scs = self::scsset($settings->scsak,$settings->scssk);
			$bucket = $settings->scsbucket;
			$scs->deleteObject($bucket,$imgpath);
			$scs->deleteObject($bucket,$thumbpath);
			break;

			case 'nos' :
			$nos = self::nosset($settings->nosak,$settings->nosas,$settings->nosep);
			$bucket = $settings->nosbucket;
			$nos->deleteObject($bucket,$imgpath);
			if ($nos->doesObjectExist($bucket,$thumbpath)) {
				$nos->deleteObject($bucket,$thumbpath);
			}
			break;

			case 'cos' :
			$cos = self::cosset($settings->cosrg);
			$bucket = $settings->cosbucket;
			$cos->delFile($bucket,$imgpath);
			$cos->delFile($bucket,$thumbpath);
		}
	}

	/**
	 * 图片缩略处理
	 * 
	 * @access public
	 * @param $imagename 原图名称
	 * @param string $width,$height,$xset,$yset 裁切参数
	 * @param string $url 原图地址
	 * @param boolean $isatt 是否来自附件
	 * @return array
	 */
	public static function crophandle($imgname,$width,$height,$xset,$yset,$url=NULL,$isatt=false)
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');
		$route = self::route($url,$isatt);
		$dir = $route->dir;
		$imgdir = __TYPECHO_ROOT_DIR__.'/'.$dir;
		$thumb = 'thumb_'.$imgname;
		$thumbpath = $dir.$thumb;
		$aurl = '';

		//删除本地缩略图
		$localpath = $imgdir.$thumb;
		if (file_exists($localpath)) {
			@unlink($localpath);
		}

		//获取原图信息
		$imgfile = $url && false===strpos($url,$options->siteUrl) ? $url
			 : ($url || !$url && $settings->storage=='local' ? $imgdir : $route->url).$imgname;
		$info = getimagesize($imgfile);
		$sets = self::newsets($info['0'],$width,$height,$xset,$yset,$isatt); //调整参数
		$imgtype = image_type_to_mime_type($info['2']);
		$thumime = $imgtype;

		//按云端API演算
		if (!$isatt && $settings->thumbapi || $isatt && $settings->cloudtoo && $settings->thumbapi) {
			$imgpath = $dir.$imgname;
			$prefix = '';
			$site = $route->site;
			//删除上传缩略图
			self::removehandle($thumbpath,true);

			switch ($route->from) {
				case 'qiniu' :
				$postfix = '?imageMogr2/crop/!'.$sets['2'].'x'.$sets['3'].'a'.$sets['4'].'a'.$sets['5'].'/thumbnail/'.$sets['0'].'x'.$sets['1'];
				break;

				case 'scs' :
				$scsimgx = $settings->scsimgx;
				$scsimgx = $scsimgx ? $scsimgx : 'http://*.applinzi.com|*';
				$scsdset = strpos($scsimgx,'|') && $scsimgx!=='http://*.applinzi.com|*' ? explode('|',$scsimgx) : array();
				//开启imgx权限
				self::scsset($settings->scsak,$settings->scssk)->setAccessControlPolicy($scsbucket,$imgpath,array('GRPS000000ANONYMOUSE'=>array('read'),'SINA000000000000IMGX'=>array('read','write')));
				$prefix = 'c_crop,w_'.$sets['2'].',h_'.$sets['3'].',x_'.$sets['4'].',y_'.$sets['5'].'--c_thumb,w_'.$sets['0'].',h_'.$sets['1'].'/';
				$postfix = '';
				$site = 'http://'.$scsbucket.'.imgx.sinacloud.net';
				//新Imgxs版API
				if ($scsdset) {
					$prefix = 'c_'.$sets['4'].'-'.$sets['5'].',w_'.$sets['2'].',h_'.$sets['3'].'|w_'.$sets['0'].',h_'.$sets['1'].'/'.$scsdset['1'].'/'.$scsbucket.'/';
					$postfix = '';
					$site = $scsdset['0'];
				}
				break;

				case 'nos' :
				$postfix = '?imageView&crop='.$sets['4'].'_'.$sets['5'].'_'.$sets['2'].'_'.$sets['3'].'&thumbnail='.$sets['0'].'x'.$sets['1'];
				break;

				case 'cos' :
				$postfix = '?imageMogr2/cut/'.$sets['2'].'x'.$sets['3'].'x'.$sets['4'].'x'.$sets['5'].'/thumbnail/'.$sets['0'].'x'.$sets['1'];
			}

			$thumbpath = $prefix.$imgpath.$postfix;
			$aurl = Typecho_Common::url($thumbpath,$site);
			$thumsize = strlen(file_get_contents($aurl));

		//用本地GD库渲染
		} else {

			//生成临时目录
			$thumbdir = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/HighSlide/thumb/';
			if (!is_dir($thumbdir)) {
				if (!self::makedir($thumbdir)) {
					return false;
				}
			}

			//GD库开始采样
			switch ($imgtype) {
				case 'image/gif' :
				$source = imagecreatefromgif($imgfile);
				break;
				case 'image/pjpeg' :
				case 'image/jpeg' :
				case 'image/jpg' :
				$source = imagecreatefromjpeg($imgfile);
				break;
				case 'image/png' :
				case 'image/x-png' :
				$source = imagecreatefrompng($imgfile);
			}
			$newimg = imagecreatetruecolor($sets['0'],$sets['1']);

			//GD库开始渲染
			imagecopyresampled($newimg,$source,0,0,$sets['4'],$sets['5'],$sets['0'],$sets['1'],$sets['2'],$sets['3']);
			imagefilter($newimg,IMG_FILTER_SMOOTH,-20);
			imageantialias($newimg,true); //优化效果
			$thumbfile = $thumbdir.$thumb;
			switch ($imgtype) {
				case 'image/gif' :
				imagegif($newimg,$thumbfile);
				break;
				case 'image/pjpeg' :
				case 'image/jpeg' :
				case 'image/jpg' :
				imagejpeg($newimg,$thumbfile,80); //控制体积
				break;
				case 'image/png' :
				case 'image/x-png' :
				imagepng($newimg,$thumbfile);
			}
			imagedestroy($newimg); //释放内存
			@chmod($thumbfile,0777);
			$thumsize = filesize($thumbfile);

			//云储存上传后删除
			if (!$isatt && $settings->storage!=='local' || $isatt && $settings->cloudtoo) {
				self::uploadhandle(array('file'=>$thumbfile,'path'=>$thumbpath),true);
				@unlink($thumbfile);

			//本地移动缩略图
			} else {
				if (!is_dir($imgdir)) { //检查目录
					if (!self::makedir($imgdir)) {
						return false;
					}
				}
				rename($thumbfile,$imgdir.$thumb);
			}
		}

		//返回文件数据
		return $isatt ? array('name'=>$thumb,'path'=>$thumbpath,'size' =>$thumsize,'type' =>self::getSafeName($imgname),'mime' =>$thumime,'from'=>$route->from,'aurl'=>$aurl)
		 : array('size'=>$thumsize,'aurl'=>$aurl);
	}

	/**
	 * 修正缩略参数
	 * 
	 * @access private
	 * @param string $imgwidth 原图宽度
	 * @param string $width,$height,$xset,$yset 裁切参数
	 * @param string $isatt 是否来自缩略图
	 * @return array
	 */
	private static function newsets($imgwidth,$width,$height,$xset,$yset,$isatt=false)
	{
		$options = Helper::options();
		$settings = $options->plugin('HighSlide');

		//适应预览比例
		$scale = $imgwidth>433.4 ? $imgwidth/433.4 : 1;
		if ($isatt) {
			$scale = $imgwidth>248.4 ? $imgwidth/248.4 : 1; //附件面板
		}
		$width *= $scale;
		$height *= $scale;
		$xset *= $scale;
		$yset *= $scale;

		//应用缩放规格
		switch ($settings->thumbfix) {
			case 'fixedwidth':
			$scale = $settings->fixedwidth/$width;
			break;
			case 'fixedheight':
			$scale = $settings->fixedheight/$height;
			break;
			case 'fixedratio':
			$fix = explode(':',$settings->fixedratio);
			$scale = trim($fix['0'])/trim($fix['1']);
		}
		$newwidth = round($width*$scale);
		$newheight = round($height*$scale);

		return array($newwidth,$newheight,round($width),round($height),round($xset),round($yset));
	}

	/**
	 * 本地目录创建
	 * 
	 * @access private
	 * @param string $path 路径
	 * @return boolean
	 */
	private static function makedir($path)
	{
		$path = preg_replace("/\\\+/", '/', $path);
		$current = rtrim($path, '/');
		$last = $current;

		while (!is_dir($current) && false !== strpos($path, '/')) {
			$last = $current;
			$current = dirname($current);
		}
		if ($last == $current) {
			return true;
		}
		if (!@mkdir($last)) {
			return false;
		}

		$stat = @stat($last);
		$perms = $stat['mode'] & 0007777;
		@chmod($last, $perms);

		return self::makedir($path);
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
		$name = str_replace('\\','/',$name);
		$name = false===strpos($name,'/') ? 'a'.$name : str_replace('/','/a',$name);
		$info = pathinfo($name);
		$name = substr($info['basename'],1);

		return isset($info['extension']) ? $info['extension'] : '';
	}

	/**
	 * 附件删除处理
	 * 
	 * @access public
	 * @param array $content 文件相关信息
	 * @return boolean
	 */
	public static function deleteHandle(array $content,$lastResult)
	{
		$attach = $content['attachment'];
		$path = $attach->path;
		$from = $attach->from;
		//按缩略字段删除
		if ($from) {
			self::removehandle($path,true,NULL,$from);
		}
		//按储存设置删除
		self::removehandle($path,true,NULL,Helper::options()->plugin('HighSlide')->storage);

		//按传统继承删除
		return empty($lastResult) ?
			!Typecho_Common::isAppEngine() && @unlink(__TYPECHO_ROOT_DIR__ .'/'.$path) : $lastResult;
	}

	/**
	 * 检测图片是否存在
	 * 
	 * @access public
	 * @param string $img
	 * @return string
	 */
	public static function ifexist($img)
	{
		$opts = array(
			'http'=>array(
			'method'=>'HEAD',
			'timeout'=>1
		));
		@file_get_contents($img,false,stream_context_create($opts));
		$code = $http_response_header['0'];
		return isset($code) && (strpos($code,'200') || strpos($code,'304')) ? $img : '';
	}

}