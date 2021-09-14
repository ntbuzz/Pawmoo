//
// バルーンヘルプの表示
// jquery => コマンドでインクルードすること
// ポップアップセレクター
$.fn.PopupBaloonSetup = function () {
	this.find(".popup-baloon").each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var onside = self.attr("class").existsWord('onside');
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
					width: parseInt(icon_obj.outerWidth(), 10)/2,
					height: parseInt(icon_obj.outerHeight(), 10)/2,
					fixPosition: function () {
						this.pointX = this.left + this.width - $(window).scrollLeft();
						this.pointY = this.top  + this.height - $(window).scrollTop();
					},
				};
//				target.fixPosition();
				var Balloon = {
					width: parseInt(self.outerWidth(true), 10),
					height: parseInt(self.outerHeight(true), 10),
					boundBox: {
						margin: 10,
						top: 0, left: 0, right: 0, bottom: 0,
						inBound: function (x, y) {
								return (x >= this.left) && (x <= this.right)
										&& (y >= this.top) && (y <= this.bottom);
						},
					},
					setBoundBox: function (x,y) {
						this.boundBox.top	= Math.min(this.top, y) - this.boundBox.margin;
						this.boundBox.left	= Math.min(this.left, x) - this.boundBox.margin;
						this.boundBox.right	= Math.max(this.left+this.width, x+this.width) + this.boundBox.margin;
						this.boundBox.bottom= Math.max(this.top+this.height, y+this.height)+ this.boundBox.margin;
					},
					RangeSetup: function () {
						target.fixPosition();
						var rmargin = 4;
						if (onside) {
							var hz = "left";
							var vt = "";
							this.top = target.pointY - (this.height/2) - 8;
							this.left = target.pointX + 8;
							if (this.top < 0 || (this.top + this.height) > $(window).height()) {
								hz = "center";
								this.left = target.pointX - (this.width / 2);
							} else rmargin = -8;
						} else {
							var hz = "center";
							var vt = "top-";
							this.top = target.pointY;
							this.left = target.pointX - (this.width/2);
						};
						// onside , free 共通
						if (this.top < 0) {
							vt = "top-";
							this.top = target.pointY;
						} else if ((this.top + this.height) > $(window).height()) {
							vt = "bottom-";
							this.top = target.pointY - this.height - 9;
						};
						if (this.left < 0) {
							hz = "left";
							this.left = target.pointX - 4;
						} else if ((this.left + this.width) > $(window).width()) {
							hz = "right";
							this.left = target.pointX - this.width + rmargin;
						};
						var cls = vt + hz;
						this.setBoundBox(target.pointX, target.pointY);
						self.attr('class', 'popup-baloon baloon-' + cls);
						self.css({
							'left': this.left + 'px',
							'top': this.top + 'px'
						});
					},
				};
				Balloon.RangeSetup();
				self.fadeIn('fast');
				$(window).on('scroll.balloon', function () {
					Balloon.RangeSetup();
				});
				$('.baloon-BK').off().mousemove(function (e) {
					e.stopPropagation();
            		e.preventDefault();
					if (!Balloon.boundBox.inBound(e.clientX, e.clientY)) {
						self.fadeOut('fast');
						$(window).off('scroll.balloon');
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

