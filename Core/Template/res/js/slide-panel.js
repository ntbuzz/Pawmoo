// スライドパネル
$(".slide-panel").each(function () {
	var self = $(this); // jQueryオブジェクトを変数に代入しておく
	// サイズ属性があればウィンドウサイズを指定する
	var default_sz = (self.is('[size]')) ? self.attr("size") : '50%';
	var tab_obj = self.find('.slide-tab');
	var class_arr = self.attr('class').split(" ");
	var direct = "none";
	['right', 'left', 'top', 'bottom'].forEach(function (val) {
		if (class_arr.indexOf("slider-"+val) >= 0) {
			direct = val;
			return false;		// break forEach
		};
		return true;
	});
	// サイズを設定
	switch (direct) {
		case "top":
		case "bottom":
			self.css({width: "100%", height: default_sz+"px"});break;
		case "left": case "right":
			self.css({ width: default_sz + "px", height: "100%" }); break;
		default:
			self.css({ width: "100%", height: "100%" });
	};
	// 隠すときのパラメータとタブ位置
	var tab_box = {
		width:		self.outerWidth(),
		height:		self.outerHeight(),
		tab_width:	tab_obj.outerWidth(),
		tab_height: tab_obj.outerHeight(),
		unit: function (val) { return val + "px" },
		top_pos: function (atr) { var tmp = {}; tmp[atr] = this.unit(-tab_box.height); return tmp;},
		left_pos: function (atr) { var tmp = {}; tmp[atr] = this.unit(-this.width); return tmp;},
		tabs_left: function () { return this.unit(this.width + this.tab_height - 2); },
		tab_top_bottom: function () {
			return {
				left:	this.unit(this.width - this.tab_width),
				bottom: this.unit(-this.tab_height),
				top:	'',		// remove attr
			};
		},
	};
	// スタイルオブジェクトを生成
	var slidetabs = {
		'top': {
			collapse: tab_box.top_pos('top'),
			expand: { top: 0 },
			tab_css: tab_box.tab_top_bottom(),
			radius: '0 0 5px 0',
		},
		'bottom': {
			collapse: tab_box.top_pos('bottom'),
			expand: { bottom: 0 },
			tab_css: tab_box.tab_top_bottom(),
			radius: '0 5px 0 0',
		},
		'left': {
			collapse: tab_box.left_pos('left'),
			expand: { left: 0 },
			tab_css: {
				left: tab_box.tabs_left(),
				top: 0,
				transform: 'rotate(90deg)',
			},
			radius: '0 5px 0 0',
		},
		'right': {
			collapse: tab_box.left_pos('right'),
			expand: { right: 0 },
			tab_css: {
				left: 0, top: 0,
				transform: 'rotate(90deg)',
			},
			radius: '0 0 5px 0',
		},
		'none': {
			collapse: {left: 0},
			expand: { left: 0 },
			tab_css: {	left: 0, top: 0},
			radius: '5px 5px 0 0',
		},
		// スライダーを折畳む
		collapse: function (dir, animate) {
			var tab = this[dir];
			if (animate) self.animate(tab.collapse, 180);
			else self.css(tab.collapse);
			tab_obj.css(tab.tab_css).find('li').css('border-radius', tab.radius);
		},
		// スライダーを開く
		expand: function (dir) {
			var tab = this[dir];
			self.animate(tab.collapse, 500);
			tab_obj.css({
				left: 0, top: 0,
				transform:'none',
			}).find('li').css('border-radius', '5px 5px 0 0');
		},
	};
	slidetabs.collapse(direct,false);
	tab_obj.children('li').on('click', function () {
		// クリックされたタブを表示
	    var menu = $(this).parent().children('li');
		var cont = self.find('ul.slide-contents').children('li');
		var index = menu.index($(this));
		menu.removeClass('selected');		// TabMenu selected delete
		cont.removeClass('selected');		// TabContents selected delete
		// スライダーを消す領域を付加
		var backwall = $('.slideBK');
		if (!backwall.length) {
			backwall = $('<div class="slideBK"></div>');
			$('body').append(backwall);
		}
		backwall.fadeIn('fast');
		backwall.off().click(function () {
			backwall.remove();
			menu.removeClass('selected');		// TabMenu selected delete
			cont.removeClass('selected');		// TabContents selected delete
			slidetabs.collapse(direct,true);
			self.animate(collapse, 180);
			tab_obj.css(tab_css).find('li').css('border-radius', radius);
		});
		$(this).addClass('selected');		// switch click TAB selected
		cont.eq(index).addClass('selected');	// switch TAB selected Contents
		// スライダーを開く
		slidetabs.expand(direct);
	});
});

