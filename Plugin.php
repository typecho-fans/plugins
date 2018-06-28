<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * WeChat Share for Typecho
 *
 * @package WeChatShare
 * @author Fuzqing
 * @version 1.0.5
 * @link https://huangweitong.com
 *
 *
 * version 1.0.0 at 2018-6-14
 * 优化了代码
 * 更新了支持在线更新
 *
 * version 0.0.4 at 2018-4-30
 * 修复了某些bug,优化了代码逻辑
 * 把AccessToken、JsapiTicket写进配置里面,方便更新调用,之前版本是写到文件里面的
 * 
 * version 0.0.3 at 2018-4-29
 * 更新支持pjax
 * 如果你启用了pjax,当切换页面时候，js不会重写绑定事件到新生成的节点上。
 * 你可以在pjax加载页面完成后重新加载js，以便将事件正确绑定ajax生成的DOM节点上。
 * 例如:$.getScript("/usr/plugins/WeChatShare/wx_share.js?ver="+Math.random());
 *
 * version 0.0.2 at 2018-4-27
 * 修复jssdk因为ssl判别证书的问题出现的notice
 *
 * version 0.0.1 at 2018-4-26
 * 实现分享博客文章到微信、朋友圈、QQ、QQ空间等
 * 包括:  自定义摘要 图标 标题
 * 本插件自带自定义摘要,如果填了摘要的话,也可以在前台调用
 * 调用字段<?php $description = $this->fields->description;?>
 * 具体其他使用方法,请查看官方文档：http://docs.typecho.org/help/custom-fields
 */
class WeChatShare_Plugin  implements Typecho_Plugin_Interface
{

    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '1.0.5';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     */
    public static Function activate()
    {
	// 检查curl
        if ( !function_exists('curl_init') ) {
            throw new Typecho_Plugin_Exception(_t('你好，使用本插件必须开启curl扩展'));
        }    
        $info = WeChatShare_Plugin::wechatShareInstall();
        Helper::addAction('wx-share', 'WeChatShare_Action');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('WeChatShare_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('WeChatShare_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('WeChatShare_Plugin','updateWxShare');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('WeChatShare_Plugin','updateWxShare');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('WeChatShare_Plugin','addWxShareScript');

        return $info;
    }


    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate(){

        Helper::removeAction('wx-share');
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
	$options = Typecho_Widget::widget('Widget_Options');
	$up_action_url = Typecho_Common::url('/index.php/action/wx-share?do=update-plugin', $options->siteUrl);
?>
	<style>
		#update_txt{font-size:1.2em;font-weight:700}#update_notice{display:block;text-align:center;margin:5px}#update_body{font-weight:700;color:#1abc9c}.message{padding:10px;background-color:#fff;box-shadow:2px 2px 5px #888;font-size:1pc;line-height:1.875rem}.bttn-default{color:#fff}.bttn,.bttn-md,.bttn-primary{color:#1d89ff}.bttn,.bttn-md{margin:0;padding:0;border-width:0;border-color:transparent;background:transparent;font-weight:400;cursor:pointer;position:relative}.bttn-md{padding:5px 9pt}.bttn-md,.bttn-slant{font-size:20px;font-family:inherit}.bttn-slant{margin:0;padding:0;border-width:0;border-color:transparent;font-weight:400;cursor:pointer;position:relative;padding:5px 9pt;z-index:0;border:none;border-radius:0;background:transparent;color:#1d89ff;-webkit-transition:color .3s cubic-bezier(0.02,0.01,0.47,1),-webkit-transform .3s cubic-bezier(0.02,0.01,0.47,1);transition:color .3s cubic-bezier(0.02,0.01,0.47,1),-webkit-transform .3s cubic-bezier(0.02,0.01,0.47,1);transition:color .3s cubic-bezier(0.02,0.01,0.47,1),transform .3s cubic-bezier(0.02,0.01,0.47,1);transition:color .3s cubic-bezier(0.02,0.01,0.47,1),transform .3s cubic-bezier(0.02,0.01,0.47,1),-webkit-transform .3s cubic-bezier(0.02,0.01,0.47,1)}.bttn-slant:before{width:100%;background:#fafafa;-webkit-transition:box-shadow .2s cubic-bezier(0.02,0.01,0.47,1);transition:box-shadow .2s cubic-bezier(0.02,0.01,0.47,1)}.bttn-slant:after,.bttn-slant:before{position:absolute;top:0;left:0;z-index:-1;height:100%;content:'';-webkit-transform:skewX(20deg);transform:skewX(20deg)}.bttn-slant:after{width:0;background:hsla(0,0%,98%,.3);opacity:0;-webkit-transition:opacity .2s cubic-bezier(0.02,0.01,0.47,1),width .15s cubic-bezier(0.02,0.01,0.47,1);transition:opacity .2s cubic-bezier(0.02,0.01,0.47,1),width .15s cubic-bezier(0.02,0.01,0.47,1)}.bttn-slant:focus,.bttn-slant:hover{-webkit-transform:translateX(5px);transform:translateX(5px)}.bttn-slant:focus:after,.bttn-slant:hover:after{width:5px;opacity:1}.bttn-slant:focus:before,.bttn-slant:hover:before{box-shadow:inset 0 -1px 0 #a7c3ff,inset 0 1px 0 #a7c3ff,inset -1px 0 0 #a7c3ff}.bttn-slant.bttn-md{font-size:20px;font-family:inherit;padding:5px 9pt}.bttn-slant.bttn-primary{color:#fff}.bttn-slant.bttn-primary:focus:before,.bttn-slant.bttn-primary:hover:before{box-shadow:inset 0 -1px 0 #006de3,inset 0 1px 0 #006de3,inset -1px 0 0 #006de3}.bttn-slant.bttn-primary:before{background:#1d89ff}.bttn-slant.bttn-primary:after{background:#006de3}
	</style>
        <div class="message">
            <div id="update_txt">当前版本： <?php _e(self::_VERSION); ?>，正在检测版本更新...</div>
            <div id="update_notice"></div>
            <div id="update_body"></div>
        </div>
        <script src="//cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
        <script src="//cdn.bootcss.com/marked/0.3.12/marked.min.js"></script>
        <script>
            $(function () {
                $.getJSON(
                    'https://api.github.com/repos/fuzqing/WeChatShare/releases/latest',
                    function (data) {
                        if (checkUpdater('<?php _e(self::_VERSION);?>', data.tag_name)) {
			    $('#update_txt').html('当前版本：<?php _e(self::_VERSION);?>，检测到有 v'+data.tag_name+' 最新版本，请更新！');
			    $('#update_notice').html('<button class="bttn-slant bttn-md bttn-primary" zipball_url="' + data.zipball_url + '" onClick="updatePlugin();" id="update-plugin">立即更新</button><hr>');
                            $('#update_body').html('<span style="font-size:1.3em">版本说明：</span>' + marked(data.body));
                        } else {
                            $('#update_txt').html('当前版本：<?php _e(self::_VERSION);?>，当前没有新版本');
                        }
                    }
                );
            });
			
            // 版本比较
            function checkUpdater(currVer, remoteVer) {
                currVer = currVer || '0.0.0';
                remoteVer = remoteVer || '0.0.0';
                if (currVer == remoteVer) return false;
                var currVerAry = currVer.split('.');
                var remoteVerAry = remoteVer.split('.');
                var len = Math.max(currVerAry.length, remoteVerAry.length);
                for (var i = 0; i < len; i++) {
                    if (~~remoteVerAry[i] > ~~currVerAry[i]) return true;
                }

                return false;
            }
			
             function updatePlugin() {
                var zipball_url =  $("#update-plugin").attr('zipball_url');
                $.post("<?php echo $up_action_url;?>", {zipball_url:zipball_url} ,success,"");
                function  success(data){
		    $('#update_txt').html('');
                    $('#update_notice').html('<span style="font-size:1.4em;color:#1d89f;font-weight:700;">'+data+'</span>');

                } 
		return false;
            }
        </script>
<?php
        /** 公众号配置 */
        $wx_AppID = new Typecho_Widget_Helper_Form_Element_Text('wx_AppID', NULL, NULL, _t('APPID'),'请登录微信公众号获取');
		
        $form->addInput($wx_AppID);
		
        $wx_AppSecret = new Typecho_Widget_Helper_Form_Element_Text('wx_AppSecret', NULL, NULL, _t('密钥'),'请登录微信公众号获取');
		
        $form->addInput($wx_AppSecret);
		
        $wx_image = new Typecho_Widget_Helper_Form_Element_Text('wx_image', NULL, NULL, _t('默认图标URL'),'请注意图标大小不要超过32KB');
		
        $form->addInput($wx_image);
		
        $access_token_expire_time = new Typecho_Widget_Helper_Form_Element_Hidden('access_token_expire_time', NULL, NULL, _t('AccessToken 过期时间'),'隐藏');
		
        $form->addInput($access_token_expire_time);
		
        $access_token = new Typecho_Widget_Helper_Form_Element_Hidden('access_token', NULL, NULL, _t('AccessToken'),'隐藏');
		
        $form->addInput($access_token);
		
        $jsapi_ticket_expire_time = new Typecho_Widget_Helper_Form_Element_Hidden('jsapi_ticket_expire_time', NULL, NULL, _t('JsapiTicket 过期时间'),'隐藏');
	
        $form->addInput($jsapi_ticket_expire_time);
		
        $jsapi_ticket = new Typecho_Widget_Helper_Form_Element_Text('jsapi_ticket', NULL, NULL, _t('JsapiTicket'),'方便出错调试，不用填写自动更新');
		
        $form->addInput($jsapi_ticket);
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
     * 添加微信分享信息输入框到编辑文章/页面的页面,添加自定义摘要自定义字段
     */
    public static function render()
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $wx_action_url = Typecho_Common::url('/index.php/action/wx-share?do=insert', $options->siteUrl);
        $cid_res = preg_match('/cid=(\d+)/',$_SERVER['REQUEST_URI'],$match);
		
		$description = '';$wx_title = '';$wx_description = '';$wx_url = '';$wx_image = '';
		
        if($cid_res) {
			/** 取出数据 */
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
			$desc_res= $db->fetchAll($db->select()->from($prefix.'fields')->where('cid = ?', $match['1'])->where('name = ?', 'description'));
            $wx_share= $db->fetchAll($db->select()->from($prefix.'wx_share')->where('cid = ?', $match['1']));
			if($desc_res) {
				$description = $desc_res[0]['str_value'];
			}
			if($wx_share) {
				$wx_title = $wx_share[0]['wx_title'];
				$wx_description = $wx_share[0]['wx_description'];
				$wx_url = $wx_share[0]['wx_url'];
				$wx_image = $wx_share[0]['wx_image'];
			}
        }
		$str = '<tr><td><input type="hidden" name="fieldNames[]" my-description="my-description" value="description" id="fieldname"><label class="typecho-label" for="description">文章自定义摘要</label><input type="hidden" name="fieldTypes[]" value="str" id="fieldtype" class="text-s w-100"></td><td colspan="3"><textarea name="fieldValues[]" onfocus="get_describe(this);" id="fieldvalue" class="text-s w-100" rows="4">'.$description.'</textarea></td></tr>';
		
		$data = '<style>:-moz-placeholder {color: #E0E0E0; opacity:1;}::-moz-placeholder {color: #E0E0E0;opacity:1;}input:-ms-input-placeholder{color: #E0E0E0;opacity:1;}input::-webkit-input-placeholder{color: #E0E0E0;opacity:1;}</style><form id="wx_share" ><fieldset><legend>微信分享</legend><ol style="list-style-type:none;><li style="padding-bottom: 5px;"><label for="wx_title">标题：</label><input style="width: 80%" type="text" class="wx_title" value="'.$wx_title.'" name="wx_title" ></li><li style="padding-bottom: 5px;"><label for="wx_url">链接：</label><input type="text" style="width: 80%" value="'.$wx_url.'" name="wx_url"></li><li style="padding-bottom: 5px;display:block;"><span style="float:left" for="wx_describe">摘要：</span><textarea rows="4" class="wx_describe" name="wx_description" style="width: 80%"  >'.$wx_description.'</textarea></li><li style="padding-bottom: 5px;"><label for="wx_image">图标：</label><input type="text" class="wx_image"  value="'.$wx_image.'" style="width: 80%"  name="wx_image"></li></ol></fieldset><input type="hidden" name="cid" value="'.$match['1'].'"></form>';

        ?>

        <script>
            //监控摘要自定义字段的输入,同步更新微信摘要
            function get_describe(self){
                $(self).on('input propertychange', function(event) {
                    var _val = $(self).val();
                    var wx_describe = $(".wx_describe").val();
                    if (wx_describe !== null || wx_describe !== undefined || wx_describe !== '') {

                        $(".wx_describe").val(_val);
                    }


                });
                $(self).blur(function(){
                    $(self).off('input propertychange');
                });
            }
            $(document).ready(function(){
				 //添加description自定义字段
                $("#custom-field table tbody").prepend('<?php echo $str ?>');
                //自动打开自定义字段
                $(".i-caret-right").click();
                //多出来的一个自定义字段
                $("#custom-field table input").each(function () {
                    if(!$(this).val()) {
                        $("#custom-field table tr .btn-xs").parents('tr').fadeOut(function () {
                            $(this).remove();
                        });
                        $("#custom-field table tr .btn-xs").parents('form').trigger('field');
                    }
 					if($(this).val() == 'description' && $(this).attr('my-description') == undefined) {
						 $(this).parents('tr').fadeOut(function () {
                             $(this).remove();
                        });
                        $(this).parents('form').trigger('field');
					 }
                });


                //添加微信分享
                $("#custom-field").after('<?php echo $data ?>');

                //监控文章的输入,同步更新微信标题
                $("#title").bind(' input propertychange ',function () {
                    var wx_title = $(".wx_title").val();
                    if($("#title").val()) {
                        if (wx_title !== null || wx_title !== undefined || wx_title !== '') {

                            $(".wx_title").val($("#title").val());
                        }
                    }
                });
                $("#text").blur(function(){
                    var text = $("#text").val();
                    var images = text.match(/http.*?(png|jpg|jpeg|gif)$/gi);
                    if(images){
                        var wx_image = $(".wx_image").val();
                        if (wx_image !== null || wx_image !== undefined || wx_image !== '') {

                            $(".wx_image").val(images[0]);
                        }
                    }
                });

            });
            $("#btn-submit").click(function () {
                var wx_data =  $("#wx_share").serialize();
                $.post("<?php echo $wx_action_url;?>", wx_data,success,"");
                function  success(data){
                    //console.log(data); 
                } 
            });
        </script>

        <?php

    }


    /**
     * 更新wx_share的wx_url字段
     */
    public static function updateWxShare($contents,$class)
    {
        $db = Typecho_Db::get();

        $prefix = $db->getPrefix();

        $wx_share = $db->fetchAll($db->select('wx_id,wx_url')->from('table.wx_share')->order('wx_id',Typecho_Db::SORT_DESC)->limit(1));

        $wx_id = $wx_share[0]['wx_id'];

        !empty($wx_share[0]['wx_url']) || $data['wx_url'] = $class->permalink;

        $data['cid'] = $class->cid;

        //更新数据，执行后，返回收影响的行数。
        $db->query($db->update($prefix.'wx_share')->rows($data)->where('wx_id = ?',$wx_id));

    }
	
    /**
     * 将微信分享的需要的信息写入wx_share.js文件中
     */
    public static function addWxShareScript($archive)
    {

        echo '<script type="text/javascript" src="//qzonestyle.gtimg.cn/qzone/qzact/common/share/share.js"></script>';

        $options = Typecho_Widget::widget('Widget_Options');

        $ajax_wx_share_url = Typecho_Common::url('/index.php/action/wx-share?do=ajax-get', $options->siteUrl);

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';  
        
        $signature_url = $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	// pjax
        $signature_url = preg_replace('/\?_pjax=.*/','',$signature_url);
        
        $version = self::_VERSION;

        $wx_script = <<<SCRIPT

            WX_Custom_Share = function(){

                var xhr = null;
                var url = '{$ajax_wx_share_url}';
                var formData = {
                    title: '{$archive->title}',
                    parameter_type: '{$archive->parameter->type}',
				    cid: '{$archive->cid}',
                    signature_url: '{$signature_url}'
                };

                this.init = function(){
                    if( window.XMLHttpRequest ){
                        xhr = new XMLHttpRequest();
                    }
                    else if( window.ActiveXObject ){
                        xhr = new ActiveXObject('Microsoft.XMLHTTP');
                    };

                    get_share_info();
                };
                function formatPostData( obj ){

                    var arr = new Array();
                    for (var attr in obj ){
                        arr.push( encodeURIComponent( attr ) + '=' + encodeURIComponent( obj[attr] ) );
                    };

                    return arr.join( '&' );
                };

                function get_share_info(){

                    if( xhr == null ) return;

                    xhr.onreadystatechange = function(){
                        if( xhr.readyState == 4 && xhr.status == 200 ){

                            var data = eval('(' + xhr.responseText + ')');
                            if( data == null ){
                                return;
                            }
							//console.log(data);
                            var info = {
                                title: data.wx_title,
                                summary: data.wx_description,
                                pic: data.wx_image,
                                url: data.wx_url
                            };


                            //info.url = data.wx_url;


                            if( data.error ){
                                console.error( data.error );
                            } else if( data.appId ){
                                info.WXconfig = {
                                    swapTitleInWX: true,
                                    appId: data.appId,
                                    timestamp: data.timestamp,
                                    nonceStr: data.nonceStr,
                                    signature: data.signature
                                };
                            };

                            setShareInfo( info );
                        }
                    };

                    xhr.open( 'POST', url, true);
                    xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
                    xhr.send( formatPostData( formData ) );
                };

            };

            new WX_Custom_Share().init();
			console.log("%c", "padding:100px 200px;line-height:220px;background:url('https://hiphotos.baidu.com/feed/pic/item/b999a9014c086e06606a9d0009087bf40bd1cbbf.jpg') no-repeat;");
			console.log("%c WeChatShare v{$version}  %c By Fuzqing https://huangweitong.com ","color:#444;background:#eee;padding:5px 0;","color:#eee;background:#444;padding:5px 0;");
SCRIPT;

        file_put_contents('usr/plugins/WeChatShare/wx_share.js',$wx_script);
		
        $wx_script_js = Typecho_Common::url('/usr/plugins/WeChatShare/wx_share.js', $options->siteUrl);
		
        echo '<script type="text/javascript">'.$wx_script.'</script>';
        
    }
	
    /*
     * WeChatShare插件安装方法
    */
    public static function wechatShareInstall()
    {
        $installDb = Typecho_Db::get();
        $type = explode('_', $installDb->getAdapterName());
        $type = array_pop($type);
        $prefix = $installDb->getPrefix();
        $scripts = file_get_contents('usr/plugins/WeChatShare/'.$type.'.sql');
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
            return '建立微信分享数据表,插件启用成功,请去配置页面填写微信公众号APPID、密钥和默认图标url';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if(('Mysql' == $type && 1050 == $code) || ('Mysql' == $type && '42S01' == $code) ||
                ('SQLite' == $type && ('HY000' == $code || 1 == $code))) {
                try {
                    $script = 'SELECT `wx_id`, `wx_title`, `wx_url`, `wx_image`, `wx_description` ,`cid` from `' . $prefix . 'wx_share`';
                    $installDb->query($script, Typecho_Db::READ);
                    return '检测到微信分享数据表,微信分享插件启用成功,请去配置页面填写微信公众号APPID、密钥和默认图标url';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    throw new Typecho_Plugin_Exception('数据表检测失败,微信分享插件启用失败。错误号：'.$code);
                }
            } else {
                throw new Typecho_Plugin_Exception('数据表建立失败,微信分享插件启用失败。错误号：'.$code);
            }
        }
    }

}
