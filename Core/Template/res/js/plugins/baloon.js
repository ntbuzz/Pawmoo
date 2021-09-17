//
// バルーンヘルプの表示
// jquery => コマンドでインクルードすること
// ポップアップセレクター
$.fn.PopupBaloonSetup = function () {
	this.find(".popup-baloon").each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var onside = self.attr("class").existsWord('onside');
		var ref = self.attr("data-element");  // 紐付けるID
		var act = ref.slice(0, 1);
		var on_mouseover = (act == "@");	    // 先頭が＠ならmouseover
		if (on_mouseover) ref = ref.slice(1);
		var ev = (on_mouseover) ? "mouseover" : "click";
		if (ref != "") {
			var tag = ref.slice(0, 1);
			var no_icon = (tag == "!");			 // 先頭が！ならアイコン追加しない
			if (no_icon) ref = ref.slice(1);
			var icon = (no_icon) ? ref : ref + "-help";
			if ($('#' + icon).length == 0) {
				$('#' + ref).after('<span class="help_icon" id="' + icon + '"></span>')
							.css("margin-right", '2px');
			}; //else ev = 'mouseover';   // 既存要素の場合、clickイベントが登録されているかもしれない
			var icon_obj = $('#' + icon);
			if (ev == "click") icon_obj.css("cursor", "help");
			icon_obj.off(ev).on(ev, function () {
				// バルーンを消すための領域を定義
				$('body').append('<div class="baloon-BK"></div>');
				$('.baloon-BK').fadeIn('fast');
				var target = {
					width: parseInt(icon_obj.outerWidth(), 10)/2,
					height: parseInt(icon_obj.outerHeight(), 10)/2,
					fixPosition: function () {
						this.top = parseInt(icon_obj.offset().top, 10);
						this.left = parseInt(icon_obj.offset().left, 10);
						this.pointX = this.left + this.width - $(window).scrollLeft();
						this.pointY = this.top  + this.height - $(window).scrollTop();
					},
				};
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
						this.boundBox.right	= Math.max(this.left+this.width, x) + this.boundBox.margin;
						this.boundBox.bottom= Math.max(this.top+this.height, y) + this.boundBox.margin;
					},
					RangeSetup: function () {
						target.fixPosition();
						var rmargin = 4;
						if (onside) {
							var hz = "left";
							var vt = "";
							this.top = target.pointY - parseInt(this.height/2) - 8;
							this.left = target.pointX + 8;
							if (this.top < 0 || (this.top + this.height) > $(window).height()) {
								hz = "center";
								this.left = target.pointX - parseInt(this.width / 2);
							} else rmargin = -8;
						} else {
							var hz = "center";
							this.left = target.pointX - parseInt(this.width/2);
							if (target.pointY < parseInt($(window).height() / 2)) {
								var vt = "top-";
								this.top = target.pointY;
							} else {
								var vt = "bottom-";
								this.top = target.pointY - this.height - 9;
							}
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
				// リサイズは処理完了後に位置移動する
			    var resizeTimer = null;
				$(window).on('resize.balloon', function () {
					clearTimeout(resizeTimer);
					resizeTimer = setTimeout(function() {
						// リサイズ完了後の処理
						Balloon.RangeSetup();
					}, 200);
				});
				// スクロールはリアルタイムで位置移動
				$(window).on('scroll.balloon', function () {
					Balloon.RangeSetup();
				});
				$('.baloon-BK').off().mousemove(function (e) {
					e.stopPropagation();
            		e.preventDefault();
					if (!Balloon.boundBox.inBound(e.clientX, e.clientY)) {
						self.fadeOut('fast');
						$(window).off('scroll.balloon resize.balloon');
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

