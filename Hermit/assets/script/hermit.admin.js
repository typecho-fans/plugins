jQuery(document).ready(function($){

	var _body = $('.body').first();
	
	$('#wmd-button-bar').before('<div id="gohermit-container"><button id="gohermit" title="添加虾米音乐"><img src="'+hermit_img_url+'" width="16" height="16" />添加虾米音乐</button></div>');




	var type = ['songlist', 'album'],

		send_to_editor = function(val){
			$('#text').val( $('#text').val() + val )
		},
	
		do_songlist = function(){
			var val = $('#hermit-song').val(),
				song = [],
				preg = /http:\/\/www.xiami.com\/song\/(\d+).*?/;


			val = val.split(/\r?\n/g);

			val = $.grep(val, function(e){
				
				if( preg.test(e) ){

					return true
				}else{
					return false;
				}
			});

			if( val.length > 0 ){
			
				$.each(val, function(index, value){
					song.push( value.match(preg)[1] );
					
				});

				val = '[hermit]songlist#:' + song.join(',') + '[/hermit]';

				send_to_editor( val );

				remove_hermit();
			}else{
				alert('错误的虾米歌曲地址');
			}
		},

		do_album = function(){
			var val = $('#hermit-album').val(),
				//song = [],
				preg = /http:\/\/www.xiami.com\/album\/(\d+).*?/;


			if( preg.test(val) ){

				val = val.match(preg)[1];

				val = '[hermit]album#:' + val + '[/hermit]';

				send_to_editor( val );

				remove_hermit();
			}else{
				alert('错误的虾米专辑地址');
			}
		},

		remove_hermit = function(){
			$('#gohermit').removeClass('selected');
			$('#hermit-box').remove();

		}


	_body.on('click', '#gohermit', function(e){
		e.preventDefault();
		
		$('#hermit-box').length > 0 ? ($(this).removeClass('selected'), $('#hermit-box').remove()) : ( $(this).addClass('selected'), $('#gohermit-container').after('<div id="hermit-box" class="postbox"><div id="hermit-content"><div id="hermit-tab"><ul id="hermit-tabul"><li class="hermit-tabli current">单曲</li><li class="hermit-tabli">专辑</li></ul></div><div id="hermit-body"><ul id="hermit-bodyul"><li class="hermit-bodyli current"><textarea id="hermit-song" placeholder="输入虾米歌曲地址，多个地址请回车换行"></textarea></li><li class="hermit-bodyli"><input type="text" id="hermit-album" placeholder="输入虾米专辑地址" /></li></ul></div></div><div id="hermit-action" class="clear"><a id="hermit-delete" class="submitdelete deletion" href="javascript:;">取消</a><button id="hermit-publish" class="primary">添加到文章中</button></div></div>') );
		
		return false;
	});


	_body.on('click', '#hermit-delete', function(e){
		e.preventDefault();
		remove_hermit()	});


	_body.on('click', '.hermit-tabli', function(e){
		e.preventDefault();
		if( !$(this).hasClass('current') ){
			var index = $('.hermit-tabli').index($(this));

			$('.hermit-tabli, .hermit-bodyli').removeClass('current');

			$('.hermit-tabli:eq('+index+'), .hermit-bodyli:eq('+index+')').addClass('current')
		}
		return false;
	});


	_body.on('click', '#hermit-publish', function(e){
	
		e.preventDefault();
	
		var index = $('.hermit-tabli').index( $('.hermit-tabli.current') );

		switch( type[index] ){
			case 'songlist':
				do_songlist();
			break;

			case 'album':
				do_album();
			break;
		}
	});	


});