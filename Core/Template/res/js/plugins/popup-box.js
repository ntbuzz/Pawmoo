//
// 位置固定のポップアップボックスを表示する
// オプション設定のパネル表示などに使用する
$.fn.PopupBoxSetup = function () {
	this.find(".popup-box").each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var val = self.attr("value");
		var buttons = (val === undefined) ? [] : val.split(",");
		var ref = "#" + self.attr("data-element");  // 紐付けるID
		var self_id = "#" + self.attr("id");
		var resize_id = self_id + " .pw_resize";
		var message_id = self_id + " .pw_resize_message";
		if (ref != "#" && self_id != "#") {
			var ref_obj = $(ref);
			ref_obj.css({ 'cursor': "pointer", 'z-index': 10 });
			// サイズ属性があればウィンドウサイズを指定する
			if (self.is('[size]')) {
				var sz = self.attr("size").split(',');
				self.css({
					"width": sz[0] + "px",
					"height": sz[1] + "px",
				});
				if (sz.length == 4) {
					self.css({
						"min-width": sz[2] + "px",
						"min-height": sz[3] + "px"
					});
				};
			};
			var controlls = ["pw_resize_message:${#core.SizeDisplay}", "pw_resize:${#core.Resize}"];
			controlls.forEach(function (value) {
				var cls = value.split(':');
				if (self.find("." + cls[0]).length == 0) {
					var alt = (cls[1] != '') ? '" title="' + cls[1] : '';
					var tag = '<span class="' + cls[0] + alt + '"></span>';
					self.append(tag);
				};
			});
			if (buttons.length === 0) {
				// カスタムボタンが未定義のときだけ閉じるボタンを追加する
				if (self.find(".close").length === 0) {
					var tag = '<span class="close" title="${#core.Close}"></span>';
					self.append(tag);
				};
			} else {
				var panel = self.find(".custom-button");
				if (panel.length === 0) {
					panel = $("<div class='custom-button'></div>").appendTo(self);
				};
				panel.empty();
				$.each(buttons, function (index, val) {
					var label_class = val.split(":");
					var btn = $('<span class="button ' + label_class[1] + '">' + label_class[0] + '</span>').appendTo(panel);
					if (!["close", "cancel"].is_exists(label_class[1])) {
						btn.off().click(function () {
							self.submitObject(false, function (e) {
								btn.trigger("execute",e);
							});
						});
					};
				});
			};
			// バルーンを消すための領域を定義
			var backwall = $('<div class="popup-BK"></div>');
			// 閉じるためのカスタムイベントを定義する(trigger()で呼び出す)
			self.off('close-me').on('close-me', function (e) {
				e.stopPropagation();
				e.preventDefault();
				self.fadeOut('fast');
				backwall.fadeOut('fast', function () {
					backwall.remove();
					var init_content = self.attr('data-init');	// クリアするコンテンツID
					if (init_content !== undefined) {
						alert(init_content + " CLEAR");
						$('#' + init_content).empty();
					};
				});
			});
			// 起動ボタンのクリックで表示する
			ref_obj.off('click').on('click', function () {
				ref_obj.append(backwall);
				backwall.fadeIn('fast');
				// バルーンコンテンツの表示位置をリンク先から取得して設定
				var x = ref_obj.offset().left + ref_obj.width();
				var y = ref_obj.offset().top + self.getPaddingBox().top;
				if ((x + self.outerWidth()) > $(window).innerWidth()) {
					x = ref_obj.offset().left - self.outerWidth(true);   // padding+margin込みの幅を差引く
				};
				if ((y + self.outerHeight()) > $(window).innerHeight()) {
					y = $(window).innerHeight() - self.outerHeight();   // padding+margin込みの高さを差引く;
					if (y <= 0) {
						y = 10;
						var h = $(window).innerHeight() - 40;   // 上下 20px
						self.css('height', h + 'px');
					};
				};
				self.css({ 'left': x + 'px', 'top': y + 'px' });
				self.fadeIn('fast');
				// クローズイベントを登録
				self.off('click').on('click', '.close, .cancel', function (e) {
					e.stopPropagation();
            		e.preventDefault();
					self.trigger('close-me');
				});
				// フォーム内のINPUTでENTERが押下されたときの処理
				// data-value=??? に紐付けされたクラスのイベント発火
				var enter = self.attr("data-value");  // 紐付けるID
				if (enter !== undefined) {
					self.on('keypress', 'input', function (e) {
						if (e.key === 'Enter') {
							e.stopPropagation();
							e.preventDefault();
							$('.' + enter).click();
						};
					});
				};
			});
			// リサイズのドラッグ
			$(resize_id).on('mousedown', function (e) {
				self.data("clickPointX", e.pageX)
					.data("clickPointY", e.pageY);
				$(message_id).fadeIn('fast');
				self.css('user-select', 'none');    // テキスト選択不可
				$(document).mousemove(function (e) {
					var new_width = Math.floor(e.pageX - self.offset().left - 12);
					var new_height = Math.floor(e.pageY - self.offset().top - 12);
					self.css({
						width: new_width + "px",
						height: new_height + "px"
					});
					var txt = new_width + " x " + new_height;
					$(message_id).text(txt);
				}).mouseup(function (e) {
					self.css('user-select', '');    // テキスト選択可能
					$(message_id).fadeOut('fast');
					$(document).unbind("mousemove").unbind("mouseup");
					self.fitWindow();
				});
			});     // mousedown()
		};
	});
	return this;
};
