(function ($) {
	var timer,
		fieldSelector,
		colorField,
		colorPicker,
		colorSwatch,
		currentColorField,
		player;
	
	var init = function () {
		
		fieldSelector = $("#ap_fieldselector");
		colorField = $("#ap_colorvalue");
		colorPicker = $("#ap_picker-btn");
		colorSwatch = $("#ap_colorsample");
		currentColorField = $("#ap_" + fieldSelector.val() + "color");
		
		fieldSelector.change(function () {
			currentColorField = $("#ap_" + fieldSelector.val() + "color");
			colorField.val(currentColorField.val());
			colorPicker.ColorPickerSetColor(currentColorField.val());
			colorSwatch.css("background-color", currentColorField.val());
		});
		
		colorField.keyup(function () {
			var color = colorField.val();
			if (color.match(/#?[0-9a-f]{6}/i)) {
				currentColorField.val(color);
				colorSwatch.css("background-color", color);
				colorPicker.ColorPickerSetColor(currentColorField.val());
				updatePlayer();
			}
		});
		
		var themeColorPicker = $("#ap_themecolor");
		if (themeColorPicker) {
			themeColorPicker.css("display", "none");
			//reorderThemeColors();
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
				currentColorField.val(color);
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
				currentColorField.val(color);
				colorSwatch.css("background-color", color);
				updatePlayer();
			},
			
			onShow: function () {
				themeColorPicker.hide();
			}
		});
		
		selectColorField();
	}

	var tabClick = function (evt) {
		var i;
		var target = $(this);
		var tab = target.parent();
		
		evt.preventDefault();
		
		if (tab.attr("class") == "current") {
			return;
		}
		
		tabs.removeClass("current");
		tab.addClass("current");
		
		panels.css("display", "none");
		
		var activeTabID = target.attr("href").replace(/[^#]*#/, "");
		
		$("#" + activeTabID).css("display", "block");
		
		if (activeTabID == "ap_panel-colour") {
			timer = setTimeout(updatePlayer, 100);
		} else if (timer) {
			clearTimeout(timer);
		}
	}
	
	var selectColorField = function () {
		currentColorField = $("#ap_" + fieldSelector.val() + "color");
		colorField.val(currentColorField.val());
		colorPicker.ColorPickerSetColor(currentColorField.val());
		colorSwatch.css("background-color", currentColorField.val());
	}
	
	var updatePlayer = function () {
		player = audioplayer_swfobject.getObjectById("ap_demoplayer");
		
		$(".typecho-option input[type=hidden]").each(function (i) {
			player.SetVariable($(this).attr("name").replace(/ap_(.+)color/, "$1"), $(this).val().replace("#", ""));
		});
		player.SetVariable("setcolors", 1);
	}
	
	/*var reorderThemeColors = function () {
		var swatchList = this.themeColorPicker.getElement("ul");
		var swatches = swatchList.getElements("li");
		swatches.sort(function (a, b) {
			var colorA = new Color(a.getProperty("title"));
			var colorB = new Color(b.getProperty("title"));
			colorA = colorA.rgbToHsb();
			colorB = colorB.rgbToHsb();
			if (colorA[2] < colorB[2]) {
				return 1;
			}
			if (colorA[2] > colorB[2]) {
				return -1;
			}
			return 0;
		});
		swatches.each(function (swatch) {
			swatch.injectTop(swatchList);
		});
	}*/
	
	var pickThemeColor = function (evt) {
		var color = target.attr("title");
		if (color.length == 4) {
			color = color.replace(/#(.)(.)(.)/, "#$1$1$2$2$3$3");
		}
		$("#ap_colorvalue").val(color);
		getCurrentColorField().val(color);
		updatePlayer();
		$("#ap_picker-btn").ColorPickerSetColor(color);
		$("ap_colorsample").css("background-color", color);
		$("#ap_themecolor").css("display", "none");
	}
	$(init);
})(jQuery);