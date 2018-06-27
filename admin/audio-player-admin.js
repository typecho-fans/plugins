(function ($) {
	var fieldSelector,
		colorField,
		colorPicker,
		colorSwatch,
		colorInput,
		colorDatas,
		currentKey,
		color,
		player;

	//初始化配色组件
	var init = function () {
		fieldSelector = $("#ap_fieldselector");
		colorField = $("#ap_colorvalue");
		colorPicker = $("#ap_picker-btn");
		colorSwatch = $("#ap_colorsample");
		colorInput = $("#ap_colors");
		colorDatas = $.parseJSON(colorInput.val());
		currentKey = fieldSelector.val();
		color = colorDatas[currentKey];
		updateVals(currentKey, color);

		//菜单项切换事件
		fieldSelector.change(function () {
			currentKey = fieldSelector.val();
			color = colorDatas[currentKey];
			updateVals(currentKey, color);
		});

		//手动框输入事件
		colorField.keyup(function () {
			var color = colorField.val();
			if (color.match(/#?[0-9a-f]{6}/i)) {
				colorDatas[currentKey] = color;
				updateVals(currentKey, color);
				updatePlayer();
			}
		});

		//选色器提交事件
		colorPicker.ColorPicker({
			color: color,
			onChange: function (hsb, hex, rgb) {
				var color = "#" + hex;
				colorDatas[currentKey] = color;
				updateVals(currentKey, color);
				updatePlayer();
			},

			onShow: function () {
				themeColorPicker.hide();
			}
		});

		//主题色提交事件
		var themeColorPicker = $("#ap_themecolor");
		if (themeColorPicker) {
			themeColorPicker.css("display", "none");
			themeColorPickerBtn = $("#ap_themecolor-btn");
			themeColorPickerBtn.click(function (evt) {
				themeColorPicker.css({
					top : themeColorPickerBtn.offset().top + themeColorPickerBtn.height() + 3,
					left : themeColorPickerBtn.offset().left
				});
				themeColorPicker.show();
				evt.stopPropagation();
			});
			$("li", themeColorPicker).click(function (evt) {
				var color = $(this).attr("title");
				if (color.length == 4) {
					color = color.replace(/#(.)(.)(.)/, "#$1$1$2$2$3$3");
				}
				colorDatas[currentKey] = color;
				updateVals(currentKey, color);
				updatePlayer();
				$("#ap_themecolor").css("display", "none");
				evt.stopPropagation();
			});
			$(document).click(function () {
				themeColorPicker.hide();
			});
		}
	}

	//更新html元素预览
	var updateVals = function (key,color) {
		colorPicker.ColorPickerSetColor(color);
		colorField.val(color);
		colorSwatch.css("background-color", color);
		switch (key) {
			case ("bg") :
			$("span.map_play, span.map_volume").css("border-color", color);
			break;
			case ("leftbg") :
			$(".playerTable span").css("background-color", color);
			break;
			case ("lefticon") :
			$(".playerTable span").not("span.map_title").css("color", color);
			break;
			case ("voltrack") :
			$("span.map_volumeLevel a").css("background-color", color);
			break;
			case ("volslider") :
			$("span.map_volumeLevel a.sel").css("background-color", color);
			break;
			case ("text") :
			$("span.map_title").css("color", color);
			break;
			case ("track") :
			$(".jp-load-bar").css("background-color", color);
			break;
			case ("tracker") :
			$(".jp-play-bar").css("background-color", color);
			break;
		}
	}

	var updatePlayer = function () {
		//更新json到隐藏域
		colorInput.val(JSON.stringify(colorDatas));
		//更新flash预览
		player = audioplayer_swfobject.getObjectById("ap_demoplayer");
		$.each(colorDatas, function(name, value){
			player.SetVariable(name, value.replace("#", ""));
		});
		player.SetVariable("setcolors", 1);
	}

	$(init);
})(jQuery);