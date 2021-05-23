//=========================================================
// 諸々のJQueryプラグインを定義する
// 表示切替
$.fn.Visible = function (flag) {
	var mode = (typeof flag === 'string') ? flag : 'none';
	this.css('display', mode);
	return this;
};
// 連動セレクトタグ object, integer|string, boolean, function,
$.fn.ChainSelect = function () {
	var self = this;	// Reminder jQuery Self Object
	var target = {
		selObj: null,
		val: 0,
		first_call: false,
		callback: null,
		progress : null,
	};
	// 可変引数を解析
	$.each(arguments,function (key,argv) {
		if (typeof argv === 'boolean') target.first_call = argv;
		else if (typeof argv === 'function') target.callback = argv;
		else if (typeof argv === 'object') target.selObj = argv;
		else target.val = argv;
	});
	var id = this.attr('id');
	var sel_chain = new SelectLink(target.selObj, id, target.first_call, target.callback);
		// フェイルメソッドバージョン
	self.InProgress = function (callback) {
		target.progress = callback;
	};
	sel_chain.Select(target.val, function (v, id) {
		if (typeof target.progress === 'function')
			target.progress.call(this, v, id);
	});
	return self;
};
// 指定要素 e のスクロールに追従する
$.fn.stickyOn = function (e) {
	var self = this;	// Reminder jQuery Self Object
	$(e).on("scroll", function () {
		var top = $(e).scrollTop();
		self.css({
			'position': 'relative',
			'top': top + 'px',
			'z-index': 99
		});
	});
	return self;
};
// IE-11対策
$.fn.css_value = function (css_name) {
	return parseInt(this.css(css_name),10) || 0;
};
$.fn.debug_id = function () {
	return this.prop('tagName') + '.' + this.prop('class') + "#" + this.prop('id');
};
// ウィンドウ高さ調整
$.fn.fitWindow = function () {
	var self = this;	// Reminder jQuery Self Object
	function bound_box(obj) {
		this.tags = obj.debug_id();
		this.overflow = obj.css_value('overflow');
		this.TopLeft = {
			x: obj.offset().left + obj.css_value('padding-left'),
			y: obj.offset().top + obj.css_value('padding-top')
		};
		this.cssSize = {
			width: obj.css_value('width'),
			height:obj.css_value('height')
		};
		this.boxSpace = {
			right: obj.css_value('padding-right') + obj.css_value('border-right'),
			bottom:obj.css_value('padding-bottom')+ obj.css_value('border-bottom')
		};
		this.boxMargin = {
			right: obj.css_value('margin-right'),
			bottom:obj.css_value('margin-bottom')
		};
		this.BottomRight = {
			x: obj.offset().left + obj.outerWidth() - this.boxSpace.right,
			y: obj.offset().top + obj.outerHeight() - this.boxSpace.bottom
		};
		this.accSpace = { x: 0, y: 0 };
		this.ParentRect = function(stopper) {
			var p_obj = obj.parent();
			var p_box = new bound_box(p_obj);
			// 下側と右側は累積値が必要
			p_box.accSpace.x = this.accSpace.x + this.boxSpace.right + this.boxMargin.right;
			p_box.accSpace.y = this.accSpace.y + this.boxSpace.bottom + this.boxMargin.bottom;
			if (p_obj.prop('tagName') === 'BODY' || stopper.is(p_obj) ) {
				return p_box;
			};
			return p_box.ParentRect(stopper);
		};
	};
	self.find('.fitWindow').each(function () {
		var ref = $(this).attr("data-element");  // 紐付けるIDが指定されているか
		var fit_obj = (ref === undefined) ? self : $('#'+ref);
		var my_box = new bound_box($(this));	// self space will be with-margin
		var pbox = my_box.ParentRect(fit_obj);
		var s_height = pbox.BottomRight.y - pbox.accSpace.y  - my_box.TopLeft.y;
		var s_width  = pbox.BottomRight.x - pbox.accSpace.x  - my_box.TopLeft.x;
//		alert("SELF:"+objDump(my_box)+"\nPARENT:"+objDump(pbox)+"\nSIZE(H: "+s_height+", W:"+s_width+")");
		$(this).css({
//			'width': s_width + "px",
			'height': s_height + "px",
			'overflow': "auto"
		});
	});
	return self;
};
// 指定要素に読み込んだHTMLを書き込む
//  url, postObject, fitWindowObject, callback_func
$.fn.LoadContents = function () {
	var self = this;	// Reminder jQuery Self Object
	var target = {
		url: '',
		postObj: null,
		fitWin: null,
		callback: null,
	};
	// 可変引数を解析
	$.each(arguments,function (key,argv) {
		if (typeof argv === 'string') target.url = argv;
		else if (typeof argv === 'function') target.callback = argv;
		else if (argv instanceof jQuery) target.fitWin = argv;
		else if (typeof argv === 'object') target.postObj = argv;
	});
	var result = true;	// for callback fail
	// フェイルメソッドバージョン
	self.fail = function (callback_error) {
		if (result === false && callback_error !== undefined) {
			alert("CALL-FAIL");
			callback_error.call(self, false);
		};
	};
	$.busy_cursor(true);
	$.post(target.url,target.postObj,
		function(data) {		// POST success
			$.busy_cursor(false);
			self.result = false;
			self.html(data).InitPopupSet();
			if (self.find('.fitWindow').length > 0) {
				if (target.fitWin === null) self.fitWindow();
				else target.fitWin.fitWindow();
			};
			DebugSlider();
			if (target.callback !== null) target.callback.call(self);
		})
		.fail(function() {
			$.busy_cursor(false);
			DebugSlider();
			result = false;
		});
	return self;
};
// CSS の padding 値を取得する
$.fn.getPaddingBox = function() {
	var self = this;	// Reminder jQuery Self Object
	var widths = {
		top    : 0,
		bottom : 0,
		left   : 0,
		right: 0,
	};
	if (self.length > 0) {
		$.each(self, function() {
			widths = {
				top    : parseInt($(this).css('padding-top'), 10),
				bottom : parseInt($(this).css('padding-bottom'), 10),
				left   : parseInt($(this).css('padding-left'), 10),
				right  : parseInt($(this).css('padding-right'), 10)
			};
		});
	};
	return widths;
};
// カーソルを BUSY に変更
$.busy_cursor = function (disp) {
	$('body').css('cursor', (disp) ? 'wait' : 'default');
};
// Yes/No ダイアログボックスを開く
$.dialogBox = function (title, msg, callback) {
	var back_panel = $('<div class="popup-BK"></div>');
	var dialog_box = '<div class="dialog-box"><dl class="title"><dt>'+title+'</dt><dd><span class="dialog-msg">'+msg+'</span></dd></dl><div class="buttonList">';
	var controlls = ["okButton:${#.core.Yes}", "cancelButton:${#.core.No}"];
	controlls.forEach(function (value) {
		var cls = value.split(':');
		dialog_box = dialog_box + '<span class="'+cls[0]+'">'+cls[1]+'</span>';
	});
	dialog_box = dialog_box + "</div></div>";
	back_panel.append(dialog_box);
	$('body').append(back_panel);
	// ボタン以外をクリックできないようにする
	back_panel.fadeIn('fast');
	var dialog = $('.dialog-box');
	// バルーンコンテンツの表示位置をリンク先から取得して設定
	var x = ($(window).innerWidth() - dialog.width())/2;  // 中央
	var y = ($(window).innerHeight() - dialog.height())/3;    // 上部33%の位置
	if (x < 0) {
		x = 5;
		dialog.width($(window).innerWidth() - 20);
	};
	if (y < 0) {
		y = 5;
		dialog.width($(window).innerHeight() - 20 );
	};
	dialog.css({'left': x + 'px','top': y + 'px'});
	dialog.fadeIn('fast');
	// クローズイベントを登録
	dialog.find(".okButton").off().click(function () {
		dialog.fadeOut('fast');
		back_panel.remove();
		callback(true);
	});
	dialog.find(".cancelButton").off().click(function () {
		dialog.fadeOut('fast');
		back_panel.remove();
		callback(false);
	});
};
// 動的コンテンツに対して、プラグイン要素を初期化する
$.fn.InitPopupSet = function () {
	return this.PopupBaloonSetup().InfoBoxSetup().PopupBoxSetup();
};
// FormSubmit用のオブジェクトを生成
$.fn.submitObject = function (false_check,callback) {
	var self = this;	// Reminder jQuery Self Object
	var setobj = {};
	self.find("*").each(function () {
		var nm = $(this).attr('name');
		if (nm) {
			var tt = $(this).attr('type');
			if (tt == 'checkbox' || tt == 'radio') {
				if ($(this).is(':checked')) value = $(this).val();
				else if (false_check) value = 'f';   // チェックされていないときの値をセット
				else return true;
			} else value = $(this).val();
			setobj[nm] = value;
		};
	});
	if (callback !== undefined) callback.call(self, setobj);
	return self;
};
