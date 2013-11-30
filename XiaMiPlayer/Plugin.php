<?php
/**
 * 虾米音乐播放器：虾米音乐搜索+引用
 *
 * @package XiaMi
 * @author Austin
 * @version 3.0.4
 * @link http://zh.eming.li/#typecho
 */
class XiaMiPlayer_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '3.0.4';

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('XiaMiPlayer_Plugin', 'Insert');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('XiaMiPlayer_Plugin', 'Insert');
    }


	public static function Insert() 
	{
		$options         = Helper::options();
		$config 		 = $options->plugin('XiaMiPlayer');
        $cssUrl          = Typecho_Common::url('XiaMiPlayer/style.css' , $options->pluginUrl);
        $box			 = Typecho_Common::url('XiaMiPlayer/jplayer/index.html', $options->pluginUrl);

        switch($config->type) {
	        case 'blue':
	        	$color = '|37839e|2b5c70';
	        break;
	        case 'green':
	        	$color = '|74db89|55db74';
	        break;
	        case 'yellow':
	        	$color = '|ffd863|de8d24';
	        break;
	        case 'red':
	        	$color = '|d64e3c|b82a25';
	        break;
	        case 'purple':
	        	$color = '|8073c7|5c396e';
	        break;
	        case 'user':
	        	if($config->background && $config->border) {
		        	$color = '|'.substr($config->background,1).'|'.substr($config->border,1);
	        	} else {
	        		$color = '';
        		}
	        break;
	        default:
	        	$color = '';
	        break;
        }

?>
        <link rel="stylesheet" type="text/css" href="<?php echo $cssUrl; ?>" />
		<script type="text/javascript">
		$(function() {
			if($('#wmd-button-row').length>0)	$('#wmd-button-row').append('<li class="wmd-button" id="wmd-music-button" style="font-size:20px;float:left;color:#AAA;width:20px;" title="插入音乐 Ctrl + Shift + M">♫</li>');
			else $('#text').before('<a href="#" id="wmd-music-button" title="插入音乐 Ctrl + Shift + M">插入歌曲</a>');
			$(document).on('click', '#wmd-music-button', function() {
				var bgwidth = (document.body.className == 'fullscreen') ? '200%' : '100%';
				$(this).after('<div id="searchPanel"><div class="wmd-prompt-background" style="position: absolute; top: 0px; z-index: 1000; opacity: 0.5; height: 775px; left: 0px; width: '+bgwidth+';"></div><div class="wmd-prompt-dialog"><div><ul id="tab" class="wmd-button-row"><li onclick="xm_search();" title="Ctrl + ←"><b>虾米搜索</b></li><li onclick="xm_link();" title="Ctrl + →"><b>输入链接</b></li></ul><div class=\"close\" onclick=\"rm();\" title="Esc">×</div></div><form id="xm"></form></div>');
				
				xm_search();
			});
			$(document).on('keydown', function(e){	
				//ctrl+shift+m
				if(e.ctrlKey && e.shiftKey && e.keyCode == '77') $('#wmd-music-button').click();
				//esc
				if(($('#searchPanel').length != 0) && e.keyCode == '27') {
					rm();
				}

				//focus
				if($('#xiami_search').is(':focus') && $('#xiami_search').val() != '' && e.ctrlKey && e.keyCode == '13') {
					$('#xiami_navi').attr('page','1');
					search();
				}
				//ctrl+pagedown
				if(e.ctrlKey && e.keyCode == '40') {
					next();
					e.preventDefault();
				}
				//ctrl+pageup
				if(e.ctrlKey && e.keyCode == '38') {
					pre();
					e.preventDefault();
				}

				//ctrl+left
				if(e.ctrlKey && e.keyCode == '37') {
					xm_search();
					e.preventDefault();
				}
				if(e.ctrlKey && e.keyCode == '39') {
					xm_link();
					e.preventDefault();
				}
				for(var i=1;i<9;i++) {
					var result = $('#xiami_result a');
					if(($('#searchPanel').length != 0) && result[i-1] != null && parseInt(e.keyCode) == 48+i) {
						$(result[i-1]).click();
						$('#text').focus();
						e.preventDefault();	
					} 
				}
			});
		});
		function xm_search() {
			$('#xm').html('<form><input type=\"text\" id=\"xiami_search\" value=\"\" autocomplete=\"off\"><input type=\"hidden\" id=\"xiami_page\" value=\"1\"><div class="btn-s primary" onclick=\"$(\'#xiami_navi\').attr(\'page\',\'1\');search();\" style="float:right;padding:0 12px;line-height:28px;" title="Ctrl + Enter">搜索</div></form><br style="clear:both;" /><div id=\"xiami_list\"><div id=\"xiami_result\"></div><div id=\"xiami_navi\" page="1"><a href="#" class="pre" onclick="pre();" title="Ctrl+↑" style="display:inline-table;border:none;float:left;"></a><a href="#" class="next" onclick="next();" title="Ctrl+↓" style="display:inline-table;border:none;float:right;"></a></div>');
			$('#xiami_search').focus();
		}
		function xm_link() {
			$('#xm').html('<p style="margin-bottom:15px;">输入歌曲名称和歌曲直链地址，其中歌曲名称选填。</p><p><input type=\"text\" id=\"song_name\" value=\"\" placeholder="歌曲名称"></p><p><input type=\"text\" id=\"song_link\" value=\"\" placeholder="歌曲地址"></p><button class="btn-s primary" onclick=\"insert_link();\">插入</button>')
			$('#song_name').focus();
		}
		function pre() {
			var n = $('#xiami_navi'), p = Number(n.attr('page'))-1;
			if(p == 1) $('.pre').html('');
			n.attr('page', p);
			search();
		}
		function next() {
			var n = $('#xiami_navi'), p = Number(n.attr('page'))+1;
			n.attr('page', p);
			search();
		}
		function search() {
			var k = $('#xiami_search').val(), p = Number($('#xiami_navi').attr('page'));
			$('.pre').html('上一页');
			$('.next').html('下一页');
			if (k) {
				$('#xiami_result').html('正在载入请稍后...');
				$.getJSON('http://www.xiami.com/app/nineteen/search/key/'+k+'/page/'+p+'?callback=?',function(data) {
					$('#xiami_result').html('');
					$.each(data.results,
					function(i, item) {
						$('<a href=\"#\" onclick=\"show(\'' + item.song_id + '\');\" title=\"Ctrl + '+(i+1)+'\">' + (i+1) + '. ' + decodeURIComponent(item.song_name).split('+').join(' ') + ' - ' + decodeURIComponent(item.artist_name).split('+').join(' ') + '</a>').appendTo('#xiami_result');
					});
				});
			} else {
				alert('请输入歌曲名称!')
			}
		}
		
		function more() {
			var k = $('#xiami_search').val();
			var p = $('#xiami_page');
			p.val(parseInt(p.val()) + 1);
			$.getJSON('http://www.xiami.com/app/nineteen/search/key/' + k + '/page/' + p.val() + '?callback=?',
			function(data) {
				$.each(data.results,
				function(i, item) {
					$('<a href=\"#\" onclick=\"show(\'' + item.song_id + '\');\">' + decodeURIComponent(item.song_name) + ' - ' + decodeURIComponent(item.artist_name) + '</a>').appendTo('#xiami_result')
				})
			})
		}
		function rm() {
			$('#searchPanel').remove()
		}
		function show(id) {
			$('#searchPanel').remove();
			var t = $('#text');
			var c = '<iframe src="<?php echo $box; ?>#'+id+'<?php echo $color; ?>" style="width:255px;height:35px;" frameborder="0" scrolling="no"></iframe>';
			t.val(t.val() + c);
			editor(c);
		}
		function insert_link() {
			var t=$('#text'), n = $('#song_name').val(), l=$('#song_link').val();
			if(l=='') return alert('必须输入音乐链接');
			var c = '<iframe src="<?php echo $box; ?>#'+l+'|'+n+'" style="width:255px;height:35px;" frameborder="0" scrolling="no"></iframe>';
			t.val(t.val()+c);
			editor(c);
			rm();
		}
		function editor(c) {
			if (window.frames.length > 0) {
				if (fck = window.frames['text___Frame']) var _c = fck.document.getElementsByTagName('iframe')[0].contentDocument.body;
				else if (mce = window.frames['text_ifr']) var _c = mce.document.body;
				else if (kin = document.getElementsByClassName('ke-edit-iframe')[0]) var _c = kin.contentDocument.body;
				else if (cke = document.getElementsByClassName('cke_wysiwyg_frame')[0]) var _c = cke.contentDocument.body;
				_c.innerHTML = _c.innerHTML + c
			}
		}
	</script>
<?php
	}
	
    public static function config(Typecho_Widget_Helper_Form $form)
    {
	    $color = array('orange' => _t('默认橙'),
	    			   'blue' => _t('天空蓝'),
	    			   'green' => _t('自然绿'),
	    			   'yellow' => _t('大地黄'),
	    			   'red' => _t('高原红'),
	    			   'purple' => _t('葡萄紫'),
	    			   'user' => _t('自定义'));
		$type = new Typecho_Widget_Helper_Form_Element_Radio('type', $color,'true',_t('请选择播放器样式'));
    	$form->addInput($type->multiMode());
    	$background = new Typecho_Widget_Helper_Form_Element_Text('background', NULL, '#FF6503', _t('播放器背景'), NULL);
	    $form->addInput($background);
	    $border = new Typecho_Widget_Helper_Form_Element_Text('border', NULL, '#C4753D', _t('播放器边框'), NULL);
	    $form->addInput($border);
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
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

 

}

