(function ($) {
	var fieldSelector,
		colorField,
		colorPicker,
		colorSwatch,
		colorInput,
		colorDatas,
		currentKey,
		color,
		cdatas;

	//初始化配色组件
	var init = function () {
		fieldSelector = $("#selector");
		colorField = $("#field");
		colorPicker = $("#swatch");
		colorSwatch = $("#swatch div");
		colorInput = $("#jwcolors");
		colorDatas = $.parseJSON(colorInput.val());
		currentKey = fieldSelector.val();
		color = colorDatas[currentKey];
		cdatas = CDatas(currentKey,color);
		color = cdatas[0];
		updateVals(currentKey,color,cdatas[1]);

		//菜单项切换事件
		fieldSelector.change(function () {
			currentKey = fieldSelector.val();
			color = colorDatas[currentKey];
			cdatas = CDatas(currentKey,color);
			updateVals(currentKey,cdatas[0],cdatas[1]);
		});

		//手动框输入事件
		colorField.keyup(function () {
			var color = colorField.val();
			cdatas = CDatas(currentKey,color);
			if (cdatas) {
				colorDatas[currentKey] = cdatas[0];
				updateVals(currentKey,cdatas[0],cdatas[1]);
			}
			updateInput();
		});

		//选色器提交事件
		colorPicker.ColorPicker({
			color: color,
			onSubmit: function (hsb,hex,rgb) {
				var val = colorField.val(),
					color = "#"+hex,
					cdatas = CDatas(currentKey,val),
					alpha = cdatas[1];
				colorDatas[currentKey] = alpha ? "rgba("+rgb.r+","+rgb.g+","+rgb.b+","+alpha+")" : color;
				updateVals(currentKey,color,alpha);
				updateInput();
			}
		});
	}

	//更新html元素预览
	var updateVals = function (key,color,alpha=null) {
		colorPicker.ColorPickerSetColor(color);
		var rgb = HexToRGB(color),
			val = (key=='controlbar.icons'||key=='controlbar.background'||key=='timeslider.rail'||key=='menus.text')
			 ? "rgba("+rgb.r+","+rgb.g+","+rgb.b+","+alpha+")" : color,
			bc = $("#demo .jw-button-color"),tc = $("#demo .jw-nextup-tooltip, #demo .jw-nextup-close");
		colorField.val(val);
		colorSwatch.css("background-color",val);
		switch (key) {
			case ("controlbar.text") :
			$("#demo .jw-controlbar .jw-icon-inline.jw-text, #demo .jw-title-primary, #demo .jw-title-secondary").css("color",val);
			break;
			case ("controlbar.icons") :
			bc.css("color",val);
			bc.hover(function(){$(this).css("color",colorDatas['controlbar.iconsActive']);}, function(){$(this).css("color",val);});
			break;
			case ("controlbar.iconsActive") :
			bc.hover(function(){$(this).css("color",val);}, function(){$(this).css("color",colorDatas['controlbar.icons']);});
			break;
			case ("controlbar.background") :
			$("#demo .jw-controlbar").css("background-color",val);
			break;
			case ("timeslider.progress") :
			$("#demo .jw-progress, #demo .jw-knob").css("background-color",val);
			break;
			case ("timeslider.rail") :
			$("#demo .jw-rail").css("background-color",val);
			break;
			case ("menus.text") :
			tc.css("color",val);
			tc.hover(function(){$(this).css("color",colorDatas['menus.textActive']);}, function(){$(this).css("color",val);});
			break;
			case ("menus.textActive") :
			tc.hover(function(){$(this).css("color",val);}, function(){$(this).css("color",colorDatas['menus.text']);});
			break;
			case ("menus.background") :
			$("#demo .jw-nextup").css("background-color",val);
			break;
			case ("tooltips.text") :
			$("#demo .jw-tooltip .jw-text").css("color",val);
			break;
			case ("tooltips.background") :
			$("#demo .jw-tooltip").css("color",val);
			$("#demo .jw-tooltip .jw-text").css("background-color",val);
			break;
		}
	}

	//颜色数据转换方法
	var CDatas = function (key,color) {
		var alpha = null;
		if (key=='controlbar.icons'||key=='controlbar.background'||key=='timeslider.rail'||key=='menus.text') {
			var rgba = color.match(/^rgba\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3}),\s*(\d*(?:\.\d+)?)\)$/i),
			deAlpha = {'controlbar.icons':'0.8',
				'controlbar.background':'0',
				'timeslider.rail':'0.3',
				'menus.text':'0.8'};
			if (!rgba) return false;
			function hex(x) {
				return ("0"+parseInt(x).toString(16)).slice(-2);
			}
			color = "#"+hex(rgba[1])+hex(rgba[2])+hex(rgba[3]);
			alpha = rgba[4] ? rgba[4] : deAlpha[''+key+''];
		} else if (!color.match(/#?[0-9a-f]{6}/i)) return false;
		return new Array(color,alpha);
	}
	var HexToRGB = function (hex) {
		var hex = parseInt(((hex.indexOf('#')>-1) ? hex.substring(1) : hex),16);
		return {r:hex>>16,g:(hex & 0x00FF00)>>8,b:(hex & 0x0000FF)};
	}

	//更新json到隐藏域
	var updateInput = function () {
		colorInput.val(JSON.stringify(colorDatas));
	}

	$(init);
})(jQuery);