<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * WeChat Share for Typecho
 *
 * @package WeChatShare
 * @author Fuzqing
 * @version 1.0.6
 * @link https://huangweitong.com
 *
 *
 * version 1.0.6 at 2018-12-19
 * 删除在线更新
 * 删除自定义字段
 * 
 */
class WeChatShare_Plugin  implements Typecho_Plugin_Interface
{

    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '1.0.6';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     */
    public static Function activate()
    {
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

		$data = '<style>:-moz-placeholder {color: #E0E0E0; opacity:1;}::-moz-placeholder {color: #E0E0E0;opacity:1;}input:-ms-input-placeholder{color: #E0E0E0;opacity:1;}input::-webkit-input-placeholder{color: #E0E0E0;opacity:1;}</style><form id="wx_share" ><fieldset><legend>微信分享</legend><ol style="list-style-type:none;><li style="padding-bottom: 5px;"><label for="wx_title">标题：</label><input style="width: 80%" type="text" class="wx_title" value="'.$wx_title.'" name="wx_title" ></li><li style="padding-bottom: 5px;"><label for="wx_url">链接：</label><input type="text" style="width: 80%" value="'.$wx_url.'" name="wx_url"></li><li style="padding-bottom: 5px;display:block;"><span style="float:left" for="wx_describe">摘要：</span><textarea rows="4" class="wx_describe" name="wx_description" style="width: 80%"  >'.$wx_description.'</textarea></li><li style="padding-bottom: 5px;"><label for="wx_image">图标：</label><input type="text" class="wx_image"  value="'.$wx_image.'" style="width: 80%"  name="wx_image"></li></ol></fieldset><input type="hidden" name="cid" value="'.$match['1'].'"></form>';

        ?>

        <script>
            $(document).ready(function(){

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
