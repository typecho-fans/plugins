<?php
/**
 * 虾米音乐播放器：虾米音乐搜索+引用
 *
 * @package XiaMiPlayer
 * @author 公子
 * @version 3.0.6
 * @link http://zh.eming.li/#typecho
 */
class XiaMiPlayer_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件版本号
     * @var string
     */
    const _VERSION = '3.0.6';

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

    /**
     * 插件的实现方法
     *
     * @access public
     * @return void
     */
	public static function Insert() 
	{
		$options         = Helper::options();
		$config 		 = $options->plugin('XiaMiPlayer');
        $box			 = Typecho_Common::url('XiaMiPlayer/jplayer/index.html', $options->pluginUrl);

        /* 播放器样式 */
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
	        	$color = ($config->background && $config->border) ? '|'.substr($config->background,1).'|'.substr($config->border,1) : '';
	        break;
	        default:
	        	$color = '';
	        break;
        }

		?>
        <link rel="stylesheet" type="text/css" href="<?php echo Typecho_Common::url('XiaMiPlayer/style.css' , $options->pluginUrl); ?>" />
		<script type="text/javascript">
		$(function() {
			/* 判断是否为默认编辑器插入音乐按钮 */
			if($('#wmd-button-row').length>0) {
				$('#wmd-button-row').prepend('<li class="wmd-button" id="wmd-music-button" style="" title="插入音乐 Ctrl + Shift + M">♫</li>');
			} else {
				$('#text').before('<a href="javascript:void(0)" id="wmd-music-button" title="插入音乐 Ctrl + Shift + M">插入歌曲</a>');
			}
			
			/* 为编辑器按钮增加点击事件 */
			$(document).on('click', '#wmd-music-button', function() {
				$('body').append('<div id="searchPanel">'+
								'<div class="wmd-prompt-background" style="height:'+$(document).height()+'px;width:'+$(document).width()+'px;"></div>'+
								'<div class="wmd-prompt-dialog">'+
									'<div>'+
										'<ul id="tab" class="wmd-button-row">'+
											'<li onclick="xm_search();" title="Ctrl + ←"><b>虾米搜索</b></li>'+
											'<li onclick="xm_link();" title="Ctrl + →"><b>输入链接</b></li>'+
											'<li class="multi" data="0" onclick="multi();">[列表]</li>'+
										'</ul>'+
										'<div class=\"close\" onclick=\"rm();\" title="Esc">×</div>'+
									'</div>'+
									'<form id="xm"></form>'+
								'</div>'+
							'</div>');
				xm_search();
			});

			/* 增加各种快捷键操作 */
			$(document).on('keydown', function(e){	
				
				/* Ctrl+Shift+M 调出音乐搜索窗口 */
				if(e.ctrlKey && e.shiftKey && e.keyCode == '77') 
					$('#wmd-music-button').click();

				/* ESC 退出音乐搜索窗口 */
				if(($('#searchPanel').length != 0) && e.keyCode == '27')
					rm();

				/* Ctrl+Enter 执行搜索操作 */
				if($('#xiami_search').is(':focus') && $('#xiami_search').val() != '' && e.ctrlKey && e.keyCode == '13') {
					$('#xiami_navi').attr('page','1');
					search();
				}

				/* Ctrl+Pagedown 下一页 */
				if(e.ctrlKey && e.keyCode == '40') {
					next();
					e.preventDefault();
				}

				/*Ctrl+Pageup 上一页 */
				if(e.ctrlKey && e.keyCode == '38') {
					pre();
					e.preventDefault();
				}

				/*Ctrl+Left 调出虾米搜索窗口 */
				if(e.ctrlKey && e.keyCode == '37') {
					xm_search();
					e.preventDefault();
				}

				/*Ctrl+Right 调出直链窗口 */
				if(e.ctrlKey && e.keyCode == '39') {
					xm_link();
					e.preventDefault();
				}

				/*Ctrl+[1-8] 使用数字键快捷选择歌曲 */
				for(var i=1;i<9;i++) {
					var result = $('#xiami_result a');
					if(($('#searchPanel').length != 0) && result[i-1] != null && parseInt(e.keyCode) == 48+i) {
						$(result[i-1]).click();
						$('#text').focus();
						e.preventDefault();	
					} 
				}
			})
		});

		function multi() {
			var multi = $('.multi');
			if(multi.hasClass('checked')){
				multi.removeClass('checked');
				multi.attr('data', '0');
				$('.multibtn').remove();
				$('#playlist').remove();
			} else {
				multi.addClass('checked');
				multi.attr('data', '1');
				multi.after('<button class="multibtn" onclick="multishow()">确定</button>');
				$('#tab').after('<ul id="playlist"></ul>');
			}
		}

		function multishow() {
			var list = $('#playlist li');
			if(list.length == 0) return false;
			var ids = [].slice.call(list).map(function(item) {return item.getAttribute('data-id')});
			var c = '<embed src="http://www.xiami.com/widget/1883615_'+ids.join(',')+'_235_346_FF8719_494949_0/multiPlayer.swf" type="application/x-shockwave-flash" width="215" height="316" wmode="opaque"></embed>';
			$('#text').val($('#text').val() + c);
			editor(c);	
			rm();	
		}

		function xm_search() {
			$('#xm').html('<form>'+
							'<input type=\"text\" id=\"xiami_search\" autocomplete=\"off\">'+
							'<input type=\"hidden\" id=\"xiami_page\" value=\"1\">'+
							'<div class="btn-s primary" onclick=\"$(\'#xiami_navi\').attr(\'page\',\'1\');search();\" style="float:right;padding:0 12px;line-height:28px;" title="Ctrl + Enter">搜索</div>'+
						  '</form>'+
						  '<br style="clear:both;" />'+
						  '<div id=\"xiami_list\">'+
						  	'<div id=\"xiami_result\"></div>'+
						  	'<div id=\"xiami_navi\" page="1">'+
						  		'<a href="#" class="pre" onclick="pre();" title="Ctrl+↑" style="display:inline-table;border:none;float:left;"></a>'+
						  		'<a href="#" class="next" onclick="next();" title="Ctrl+↓" style="display:inline-table;border:none;float:right;"></a>'+
						  	'</div>');
			$('#xiami_search').focus();
		}

		function xm_link() {
			$('#xm').html('<p style="margin-bottom:15px;">输入歌曲名称和歌曲直链地址，其中歌曲名称选填。</p>'+
						  '<p><input type=\"text\" id=\"song_name\" placeholder="歌曲名称"></p>'+
						  '<p><input type=\"text\" id=\"song_link\" placeholder="歌曲地址"></p>'+
						  '<button class="btn-s primary" onclick=\"insert_link();\">插入</button>');
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
			if(k) {
				$('#xiami_result').html('正在载入请稍后...');
				$.getJSON('http://www.xiami.com/app/nineteen/search/key/'+k+'/page/'+p+'?callback=?',function(data) {
					$('#xiami_result').html('');
					$.each(data.results,
					function(i, item) {
						var name = decodeURIComponent(item.song_name).replace('+', ' '), artist = decodeURIComponent(item.artist_name).split('+').join(' ');
						$('<a href=\"#\" onclick=\"show(\'' + item.song_id + '\',\'' + name + '-' + artist + '\');\" title=\"Ctrl + '+(i+1)+'\">' + (i+1) + '. ' + name + ' - ' + artist + '</a>').appendTo('#xiami_result');
					});
				});
			} else {
				alert('请输入歌曲名称!')
			}
		}


		function rm() {$('#searchPanel').remove()}
		
		function show(id, name) {
			if($('ul#playlist').size()>0) {
				if($('ul#playlist li[data-id="'+id+'"]').size()>0)
					alert('《'+name+'》已经被添加到列表中了！');
				else
					$('ul#playlist').append('<li data-id="'+id+'">'+name+'</li>');
			} else {
				rm();
				var c = '<iframe src="<?php echo $box; ?>#'+id+'<?php echo $color; ?>" style="width:255px;height:35px;" frameborder="0" scrolling="no"></iframe>';
				$('#text').val($('#text').val() + c);
				editor(c);
			}
		}

		function insert_link() {
			if($('#song_link').val()=='') return alert('必须输入音乐链接');

			var url = '<?php echo $box; ?>#'+$('#song_link').val()+'|'+$('#song_name').val()+'<?php echo $color; ?>';
			var c = '<iframe src="'+url+'" frameborder="0" scrolling="no"></iframe>';
			
			$('#text').val($('#text').val()+c);
			editor(c);
			rm();
		}

		function editor(c) {
			if (window.frames.length > 0) {
				if (fck = window.frames['text___Frame']) 
					var _c = fck.document.getElementsByTagName('iframe')[0].contentDocument.body;

				else if (mce = window.frames['text_ifr']) 
					var _c = mce.document.body;
				
				else if (kin = document.getElementsByClassName('ke-edit-iframe')[0]) 
					var _c = kin.contentDocument.body;
				
				else if (cke = document.getElementsByClassName('cke_wysiwyg_frame')[0]) 
					var _c = cke.contentDocument.body;
				
				_c.innerHTML = _c.innerHTML + c;
			}
		}

		$(document).on('mousedown', '#playlist', function(e) {
			document.onselectstart = function(){return false;}
			$('body').addClass('select');

			var ul = $(this), li = $('#playlist li'),limit = {
				up:parseInt(ul.offset().top),
				down:parseInt(li.last().offset().top),
				left:parseInt(ul.offset().left),
				right:parseInt(ul.offset().left)+ul.width()
			},height = [],parent=parseInt(ul.offsetParent().offset().top);
			for(var i=0,l=li.length;i<l;i++) height.push(li[i].offsetTop+parent);
			var t = $(e.target), s = $(document.createElement('li'));
			s.addClass('sortable');
			$('.sortable').remove(), t.before(s);
			t.addClass('hover');
			$(document).on('mousemove', function(e) {
				var top = e.pageY;
				if(top<limit.up) {
					//top = limit.up;
					ul.prepend(s);
				} else if (top>limit.down) {
					//top = limit.down;
					ul.append(s);
				} else {
					t.css({'top':(top-ul.offsetParent().offset().top)+'px','left':(e.pageX-ul.offsetParent().offset().left)+'px'});
					var f = $(li[getIndex(top)]);
					if(!$(f.siblings()[0]).hasClass('sortable')) f.before(s);
				}
			});
			$(document).on('mouseup', function(e) {
				$(document).off('mouseup');
				$(document).off('mousemove');
				document.onselectstart = function(){return true;}
				$('body').removeClass('select');
				var left = e.pageX;
				left>limit.right || left<limit.left ? t.remove() : s.before(t);
				s.remove();
				t.removeClass('hover');
			});
			function getIndex(y) {
				var i=0;
				height.forEach(function(value, index){
					if(y >= value){i=index+1;return true;}
				});
				return i;
			}

		});
		</script>
		<?php
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
	    $color = array('orange' => _t('默认橙'),'blue' => _t('天空蓝'),'green' => _t('自然绿'),'yellow' => _t('大地黄'),'red' => _t('高原红'),'purple' => _t('葡萄紫'),'user' => _t('自定义'));
		$type = new Typecho_Widget_Helper_Form_Element_Radio('type', $color,'orange',_t('请选择播放器样式'));
    	$form->addInput($type);

    	$background = new Typecho_Widget_Helper_Form_Element_Text('background', NULL, '#FF6503', _t('播放器背景'), NULL);
	    $background->input->setAttribute('class', 'mini');
	    $form->addInput($background);
	    
	    $border = new Typecho_Widget_Helper_Form_Element_Text('border', NULL, '#C4753D', _t('播放器边框'), NULL);
	    $border->input->setAttribute('class', 'mini');
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

