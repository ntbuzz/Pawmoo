// スライドパネル
$(".slide-panel").each(function () {
	var self = $(this); // jQueryオブジェクトを変数に代入しておく
	// サイズ属性があればウィンドウサイズを指定する
	var default_sz = (self.is('[size]')) ? self.attr("size") : '50%';
	var tms = (self.is('[data-value]')) ? self.attr("data-value") : '220';
	let items = tms.split('-');
	var tm_expand   = parseInt(items[0]);
	var tm_collapse = (items.length < 2) ? ~~(tm_expand/2) : parseInt(items[1]);
	var tab_obj = self.find('.slide-tab');
	var tab_contents = self.find('.slide-contents');
	var class_arr = self.attr('class').split(" ");
	var direct = "none";
	['right', 'left', 'top', 'bottom','right-bottom','left-bottom','bottom-left','top-left'].forEach(function (val) {
		if (class_arr.indexOf("slider-"+val) >= 0) {
			direct = val;
			return false;		// break forEach
		};
		return true;
	});
	var direction = direct.split("-");
	var direct = direction[0];
	var position = direction[1];
	// サイズを設定
	switch (direct) {
		case "top": case "bottom":
			bottom_left = (position === "left");
			self.css({width: "100%", height: default_sz+"px"});break;
		case "left": case "right":
			bottom_left = (position === "bottom");
			self.css({ width: default_sz + "px", height: "100%" }); break;
		default:
			bottom_left = false;
			self.css({ width: "100%", height: "100%" });
	};
	// スライダーの操作オブジェクトを生成
	var slidetabs = {
		width:		self.outerWidth(),
		height:		self.outerHeight(),
		tab_width:	tab_obj.outerWidth(),
		tab_height: tab_obj.outerHeight(),
		unit: function (val) { return val + "px" },
		top_left: function (top, left) {
			return {
				top: this.unit(top), left: this.unit(left),
				transform: 'rotate(90deg)',
			};
		},
		bottom_left: function (bottom, left) {
			return {top: '',	// タブ移動していた場合の属性を消す
				bottom: this.unit(bottom),
				left: this.unit(left),
				transform: 'rotate(90deg)',
			};
		},
		// 操作スタイル、プロパティだと自関数呼出しができないので関数で対応
		param: function (dir) {
			switch (dir) {
			case "top":	return {
					collapse: {top: this.unit(-this.height)	},
					expand: { top: 0 },
					tab_css: {
						left: (bottom_left) ? 0 : this.unit(this.width - this.tab_width),
						bottom: this.unit(-this.tab_height),
					},
					radius: '0 0 5px 5px',
					moveTo: false,
				};
			case "bottom": return {
					collapse: {	right: 0,bottom: this.unit(-this.height)},
					expand: { bottom: 0 },
					tab_css: {
						left: (bottom_left) ? 0 : this.unit(this.width - this.tab_width),
						top: this.unit(-this.tab_height),
					},
					radius: '5px 5px 0 0',
					moveTo: false,
				};
			case "left": return {
					collapse: { left: this.unit(-this.width) },
					expand: { left: 0 },
					tab_css: (bottom_left) ?
						this.bottom_left(this.tab_width + 12, this.width + this.tab_height - 2) :
						this.top_left(0, this.width + this.tab_height - 2),
					radius: '5px 5px 0 0',
					moveTo: (!bottom_left),
				};
			case "right": return {
					collapse: { right: this.unit(-this.width) },
					expand: { right: 0, },
					tab_css: (bottom_left) ?
						this.bottom_left(this.tab_width + 12, 0) :
						this.top_left(0,0),
					radius: '0 0 5px 5px',
					moveTo: (!bottom_left),
				};
			default: return {
					collapse: { left: 0 },
					expand: { left: 0 },
					tab_css: { left: 0, top: 0 },
					radius: '5px 5px 0 0',
					moveTo: false,
				};
			};
		},
		moveTabTop: function (move) {
			if (move) {
				tab_obj.css({ left: 0, top: 0, transform: 'none' })
						.find('li').css('border-radius', '5px 5px 0 0');
				tab_contents.css('top', this.tab_height);
			};
		},
		// スライダーを折畳む
		tab_collapse: function (dir, animate) {
			var tab = this.param(dir);
			if (animate) self.animate(tab.collapse, tm_collapse);
			else self.css(tab.collapse);
			tab_obj.css(tab.tab_css).find('li').css('border-radius', tab.radius);
		},
		// スライダーを開く
		tab_expand: function (dir) {
			var tab = this.param(dir);
			this.moveTabTop(tab.moveTo);
			self.animate(tab.expand, tm_expand);
			self.fitWindow();
		},
	};
	slidetabs.tab_collapse(direct,false);
	tab_obj.children('li').on('click', function () {
		// クリックされたタブを表示
	    var menu = $(this).parent().children('li');
		var cont = tab_contents.children('li');
		var index = menu.index($(this));
		menu.removeClass('selected');		// TabMenu selected delete
		$(this).addClass('selected');		// switch click TAB selected
		cont.removeClass('selected').eq(index).addClass('selected');		// TabContents selected delete
		// スライダーを消す領域を付加
		var backwall = $('.slideBK');
		if (!backwall.length) {
			backwall = $('<div class="slideBK"></div>');
			$('body').append(backwall);
		};
		backwall.fadeIn('fast');
		backwall.off().click(function () {
			backwall.remove();
			menu.removeClass('selected');		// TabMenu selected delete
			cont.removeClass('selected');		// TabContents selected delete
			slidetabs.tab_collapse(direct,true);
		});
		// スライダーを開く
		slidetabs.tab_expand(direct);
//		self.fitWindow();	// switch TAB selected Contents
	});
});

