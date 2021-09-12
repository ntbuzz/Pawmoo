//
// バルーンヘルプの表示
// jquery => コマンドでインクルードすること
// ポップアップセレクター
$.fn.PopupBaloonSetup = function () {
	this.find(".popup-baloon").each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var ref = self.attr("data-element");  // 紐付けるID
		var act = ref.slice(0, 1);            // 先頭が＠ならmouseover
		if (act == "@") ref = ref.slice(1);
		var ev = (act == '@') ? "mouseover" : "click";
		if (ref != "") {
			var tag = ref.slice(0, 1);
			if (tag == "!") ref = ref.slice(1); // 先頭が！ならアイコン追加しない
			var icon = (tag == "!") ? ref : ref + "-help";
			if ($('#' + icon).length == 0) {
				$('#' + ref).after('<span class="help_icon" id="' + icon + '"></span>')
							.css("margin-right", '2px');
				ev = 'mouseover';   // ポップアップイベントが登録されていることがあるので、強制的にマウスオーバーにする
			};
			var icon_obj = $('#' + icon);
			if (ev == "click") icon_obj.css("cursor", "help");
			icon_obj.off(ev).on(ev, function () {
				// バルーンを消すための領域を定義
				$('body').append('<div class="baloon-BK"></div>');
				$('.baloon-BK').fadeIn('fast');
				var target = {
					top: parseInt(icon_obj.offset().top, 10),
					left: parseInt(icon_obj.offset().left, 10),
					width: parseInt(icon_obj.outerWidth(), 10),
					height: parseInt(icon_obj.outerHeight(), 10),
					centerX: function () {return this.left + (this.width / 2);},
					centerY: function () {return this.top + (this.height / 2);},
				};
				var Balloon = {
					horizontal: "center",
					vertical: "top-",
					top: target.centerY(),
					left: target.centerX() - (parseInt(self.outerWidth(true), 10)/2),
					width: parseInt(self.outerWidth(true), 10),
					height: parseInt(self.outerHeight(true), 10),
					inBound: function (x, y) {
							return (x >= this.left) && (x <= (this.left + this.width))
									&& (y >= this.top) && (y <= (this.top + this.height));
					},
					expand: function (e) {
						this.left = Math.min(this.left, e.left);
						this.top = Math.min(this.top, e.top);
						this.width = Math.max(this.left+this.width, e.left+e.width)-this.left;
						this.height = Math.max(this.top+this.height, e.top+e.height)-this.top;
					},
					outRange: function () {
						if ((this.left + this.width) > $(window).width()) {
							this.horizontal = "right";
							this.left = target.centerX() - this.width+4;
						};
						if (this.left < 0) {
							this.horizontal = "left";
							this.left = target.centerX() - 4;
						};
						if ((this.top + this.height) > $(window).height()) {
							this.vertical = "bottom-";
							this.top = target.top - this.height - 9;
						};
						return 'baloon-' + this.vertical + this.horizontal;
					},
				};
				var cls = 'popup-baloon ' + Balloon.outRange();
				self.attr('class', cls);
				self.css({
					'left': Balloon.left + 'px',
					'top': Balloon.top + 'px'
				});
				self.fadeIn('fast');
				Balloon.expand(target);
				$('.baloon-BK').off().mousemove(function (e) {
					if (!Balloon.inBound(e.clientX, e.clientY)) {
						self.fadeOut('fast');
						$('.baloon-BK').fadeOut('fast',function(){
							$('.baloon-BK').remove();
						});
					};
				});
			});
		};
	});
	return this;
};

