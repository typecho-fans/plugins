(function ($) {
	var fieldSelector,
		currentKey,
		colorField,
		colorPicker,
		colorSwatch,
		player;

	var init = function () {

		// 配色组件控制
		fieldSelector = $("#ap_fieldselector");
		currentKey = fieldSelector.val();
		colorField = $("#ap_colorvalue");
		colorPicker = $("#ap_picker-btn");
		colorSwatch = $("#ap_colorsample");

		fieldSelector.change(function () {
			currentKey = fieldSelector.val();
			colorField.val(colorDatas[currentKey]);
			colorPicker.ColorPickerSetColor(colorDatas[currentKey]);
			colorSwatch.css("background-color", colorDatas[currentKey]);
		});

		colorField.keyup(function () {
			var color = colorField.val();
			if (color.match(/#?[0-9a-f]{6}/i)) {
				colorDatas[currentKey] = color;
				colorSwatch.css("background-color", color);
				colorPicker.ColorPickerSetColor(colorDatas[currentKey]);
				updatePlayer();
			}
		});

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
				colorField.val(color);
				colorDatas[currentKey] = color;
				colorSwatch.css("background-color", color);
				updatePlayer();
				$("#ap_themecolor").css("display", "none");
				evt.stopPropagation();
			});
			$(document).click(function () {
				themeColorPicker.hide();
			});
		}

		colorPicker.ColorPicker({
			onChange: function (hsb, hex, rgb) {
				var color = "#" + hex;
				colorField.val(color);
				colorDatas[currentKey] = color;
				colorSwatch.css("background-color", color);
				updatePlayer();
			},

			onShow: function () {
				themeColorPicker.hide();
			}
		});

		selectColorField();
	}

	var selectColorField = function () {
		currentKey = fieldSelector.val();
		colorField.val(colorDatas[currentKey]);
		colorPicker.ColorPickerSetColor(colorDatas[currentKey]);
		colorSwatch.css("background-color", colorDatas[currentKey]);
	}

	var updatePlayer = function () {
		player = audioplayer_swfobject.getObjectById("ap_demoplayer");

		$.each(colorDatas, function(name,value){
			player.SetVariable(name, value.replace("#", ""));
		});
		player.SetVariable("setcolors", 1);
		// 更新json到隐藏域
		colorInput.val(JSON.stringify(colorDatas));
	}

	$(init);
})(jQuery);