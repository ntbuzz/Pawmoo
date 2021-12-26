//
/* デバッグ用関数
function dump_size(method,obj) {
	alertDump({ method:method, w:obj.outerWidth(),h:obj.outerHeight() });
};
function ParentScroll(obj) {
	if (obj.prop('tagName') === 'BODY') return { x: 0, y: 0 };
	var pscroll = ParentScroll(obj.parent());
	var scroll = {
		x: obj.scrollLeft() + pscroll.x,
		y: obj.scrollTop() + pscroll.y,
	};
	return scroll;
};
function targetBox(obj) {
	var top = obj.offset().top;
	var left = obj.offset().left;
	return {
		left: left,
		top: top,
		right: left + obj.width(),
		bottom: top + obj.height(),
	};
};
*/
function balloonBox(top, left, right, bottom) {
	this.top = top;
	this.left = left;
	this.right = right;
	this.bottom = bottom;
	this.inRange = function (x, y, margin) {
		return (x >= (this.left - margin)) && (x <= (this.right + margin))
			&& (y >= (this.top - margin)) && (y <= (this.bottom + margin));
	};
};
// バルーンヘルプの表示
// ターゲット位置を元に自身のポジションを決定する
function balloonPosition(target, onside, margin, no_icon) {
	if (target.prop('tagName') === undefined) return;
	// ターゲットの中心位置
	var targetPos = {
		left:parseInt(target.offset().left),
		top:parseInt(target.offset().top),
		width:parseInt(target.outerWidth(true)),
		height: parseInt(target.outerHeight(true)),
		Left: function () {	return this.left - $(window).scrollLeft();},
		Right: function () { return this.Left() + this.width;},
		Top: function () { return this.top - $(window).scrollTop();},
		Bottom: function () { return this.Top()+this.height;},
		Box: function() {
			var px = this.Left() + parseInt(this.width/2);
			var py = this.Top() + parseInt(this.height/2);
			return {
				left: this.Left(),
				right: this.Right(),
				top: this.Top(),
				bottom: this.Bottom(),
				centerX: px,
				centerY: py,
			};
		},
		inRange: function (x, y, margin) {
			var tBox = new balloonBox(this.Top(),this.Left(),this.Right(),this.Bottom());
			return tBox.inRange(x, y, margin);
		},
	};
	// this.Onside = onside;
	// this.Margin = margin;
	var bBox = {
		width: 0, height: 0,
		left: 0, top: 0, right: 0, bottom: 0 ,
		pointX: '',pointY: '',
		newX: 0, newY: 0,
		PointSet: function(ypos,xpos) {
			if(xpos !== false) this.pointX = xpos;
			if(ypos !== false) this.pointY = ypos;
			var box = targetPos.Box();
			switch(this.pointY) {
			case "top":   this.top = box.bottom - 8; break;
			case "bottom":this.top = box.top - this.height; break;
			default:  	  this.top = box.centerY - (this.height/2);
			};
			this.bottom = this.top + this.height;
			switch(this.pointX) {
			case "left": this.left = box.right - 6; break;
			case "right":this.left = box.left - this.width + ((this.pointY==='')?-6:12); break;
			default:	 this.left = box.centerX - (this.width/2);
			};
			this.right = this.left + this.width;
			return this;
		},
		PointClass: function() {
			var cls = this.pointY + '-' + this.pointX;
			return cls.replace(/^-+|-+$/g,'');
		},
		setSize: function (w, h) {
			this.width = w;
			this.height = h;
			return this;
		},
		inRange: function (x, y, margin) {
			var tBox = new balloonBox(this.top, this.left, this.right, this.bottom);
			return tBox.inRange(x, y, margin);
		},
	};
	this.calcPosition = function (obj) {
		var w = parseInt(obj.outerWidth());
		var h = parseInt(obj.outerHeight());
		bBox.setSize(w, h);

		var parentBox = {
			left: 0,top: 0,
			right: $(window).width(),
			bottom: $(window).height(),
		};
		// default top-center default
		if (onside) {
			bBox.PointSet('','left');		// left
			if (bBox.right > parentBox.right || bBox.top < parentBox.top) {
				bBox.PointSet('top', 'center');
			};
			if (bBox.bottom > parentBox.bottom) {
				bBox.PointSet('bottom','center');
			};
			if (bBox.right > parentBox.right || bBox.top < parentBox.top) {
				bBox.PointSet('','right');
			};
			if (bBox.left < parentBox.left) {
				bBox.PointSet('top','left');
				if (bBox.bottom > parentBox.bottom) {
					bBox.PointSet('bottom',false);
				};
			} else {
				if (bBox.bottom > parentBox.bottom) {
					bBox.PointSet('bottom',false);
				};
				if (bBox.top < parentBox.top) {
					bBox.PointSet('top',false);
				};
			};
		} else {
			bBox.PointSet('top',"center");
			if (bBox.bottom > parentBox.bottom) {
				bBox.PointSet('bottom',false);
			};
			if (bBox.right > parentBox.right) {
				bBox.PointSet(false,"right");
			};
			if (bBox.left < parentBox.left) {
				bBox.PointSet(false,"left");
			};
			if (bBox.top < parentBox.top) {
				bBox.PointSet('',false);
			};
		};
		this.balloon = 'balloon-' + bBox.PointClass();
		// 'balloon-' で始まるclassをすべて削除してから
		obj.removeClass(function(index, className) {
			return (className.match(/\bballoon-\S+/g) || []).join(' ');
		}).addClass(this.balloon);
		obj.css({'left': bBox.left + 'px','top': bBox.top + 'px'});
	};
	this.inBalloon = function (x, y) {
		return bBox.inRange(x, y, margin) || targetPos.inRange(x, y, 2);
	};
	this.inTarget = function (x, y) {
		return targetPos.inRange(x, y, 2);
	};
};
//==============================================================================================
// ポップアップバルーンセットアップ
$.fn.PopupBaloonSetup = function () {
// 旧バルーンヘルプ
// .popup-balloon.onside{@!item-id} => [
//		Balloon Message
// ]
	this.find(".popup-balloon").each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var onside = self.attr('class').existsWord('onside');
		var ref = self.attr("data-element");  // 紐付けるID
		if (ref === undefined) return true;	// continue
		var ev = 'click';
		if (ref.charAt(0) == '@') { 
			ref = ref.slice(1);
			ev = 'mouseover';
		};
		var no_icon = (ref.charAt(0) == '!');
		if (no_icon) {		// ヘルプを付けない
			ref = ref.slice(1);
			var icon = ref;
		} else {
			var icon = ref + "-help";
			$('#' + ref).after('<span class="help_icon" id="' + icon + '"></span>')
							.css("margin-right", '2px');
		};
		var icon_obj = $('#' + icon);
		if (ev == "click") icon_obj.css("cursor", "help");
		icon_obj.off(ev).on(ev, function () {
			// 他要素の mouseover防止とバルーンを消すための領域設定
			var bk_panel = $('<div class="balloon-BK"></div>').appendTo('body');
			bk_panel.fadeIn('fast');
			var Balloon = new balloonPosition(icon_obj, onside, 3, true);
			icon_obj.addClass('active');
			self.fadeIn('fast');
			Balloon.calcPosition(self);
			// リサイズは処理完了後に位置移動する
			var resizeTimer = null;
			$(window).on('resize.balloon', function () {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function() {
					// リサイズ完了後の処理
					Balloon.calcPosition(self);
				}, 200);
			});
			// スクロールはリアルタイムで位置移動
			$(window).on('scroll.balloon', function () {
				Balloon.calcPosition(self);
			});
			if(no_icon) {
				bk_panel.on('click',function (e) {
					if (Balloon.inTarget(e.clientX, e.clientY)) {
						icon_obj.trigger('click');
					};
				});
			};
			bk_panel.on('mousemove',function (e) {
				e.stopPropagation();
				e.preventDefault();
				if (!Balloon.inBalloon(e.clientX, e.clientY)) {
					self.css('display','');	// fadeInで設定されたものを削除
					icon_obj.removeClass('active');
					self.removeClass(Balloon.balloon);
					$(window).off('scroll.balloon resize.balloon');
					bk_panel.remove();
				};
			});
		});
	});
// 新バルーンヘルプ: マルチ・バルーン
// .multi-balloon => [
//		.onside{center-item} => [	 Balloon Message	]
//		.{right-item} => [	//  #right-item には 'sw1','sw2' を data-value に定義する
//			#sw1 => [ 	Balloon Message	]
//			#sw2 => [ 	Balloon Message	]
//		]
// ]
	this.find('.multi-balloon').each(function () {
		$(this).children().each(function () {
			var self = $(this); // jQueryオブジェクトを変数に代入しておく
			var cls = self.attr('class');
			var onside = (cls == undefined) ? false : cls.existsWord('onside');
			var ref = self.attr("data-element");  // 紐付けるID
			if (ref === undefined) return true;	// continue
			var ev = 'click';
			if (ref.charAt(0) == '@') { 
				ref = ref.slice(1);
				ev = 'mouseover';
			};
			var no_icon = (ref.charAt(0) == '!');
			if (no_icon) {		// ヘルプを付けない
				ref = ref.slice(1);
				var icon = ref;
			} else {
				var icon = ref + "-help";
				$('#' + ref).after('<span class="help_icon" id="' + icon + '"></span>')
								.css("margin-right", '2px');
			};
			var ref_obj = $('#' + ref);
			var icon_obj = $('#' + icon);
			if (ev == "click") icon_obj.css("cursor", "help");
			icon_obj.off(ev).on(ev, function () {
				// 他要素の mouseover防止とバルーンを消すための領域設定
				var bk_panel = $('<div class="balloon-BK"></div>').appendTo('body');
				bk_panel.fadeIn('fast');
				var Balloon = new balloonPosition(icon_obj,onside,3,true);
				var disp_id = ref_obj.attr('data-value');		// 表示するタグID
				// 選択タグがあればそれをバルーンにする、なければ自身がバルーン
				if (typeof disp_id === 'string') {
					ballon_obj = self.find('#' + disp_id);
					self.children('div').hide();					// 他の要素を非表示
					self.show();						// 親を表示
					self.css('display','block');
				} else ballon_obj = self;
				if (ballon_obj.text() == "") ballon_obj.text("${#core.EMPTY}");
				ballon_obj.addClass('popup-balloon');		// popup-balloon のスタイルを適用する
				ballon_obj.fadeIn('fast');		// 表示されていないとサイズが取得できない
				icon_obj.addClass('active');
				ballon_obj.show();			// fadeIn ではタイムラグが出るので確実に表示する
				Balloon.calcPosition(ballon_obj);
				// リサイズは処理完了後に位置移動する
				var resizeTimer = null;
				$(window).on('resize.mballoon', function () {
					clearTimeout(resizeTimer);
					resizeTimer = setTimeout(function() {
						// リサイズ完了後の処理
						Balloon.calcPosition(ballon_obj);
					}, 200);
				});
				// スクロールはリアルタイムで位置移動
				$(window).on('scroll.mballoon', function () {
					Balloon.calcPosition(ballon_obj);
				});
				if(no_icon) {
					bk_panel.on('click',function (e) {
						if (Balloon.inTarget(e.clientX, e.clientY)) {
							icon_obj.trigger('click');
						};
					});
				};
				bk_panel.on('mousemove',function (e) {
					e.stopPropagation();
					e.preventDefault();
					if (!Balloon.inBalloon(e.clientX, e.clientY)) {
						// popup-balloon と吹き出し用のクラスを削除
						ballon_obj.removeClass('popup-balloon ' + Balloon.balloon);
						self.css('display','');	// fadeInで設定されたものを削除
						icon_obj.removeClass('active');
						$(window).off('scroll.mballoon resize.mballoon');
						bk_panel.remove();
					};
				});
			});
		});
	});
	return this;
};

