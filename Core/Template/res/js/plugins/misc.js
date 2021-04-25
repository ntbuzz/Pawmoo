//=========================================================
// 諸々のJQueryプラグインを定義する
// 表示切替
$.fn.Visible = function (flag) {
	var mode = (typeof flag === 'string') ? flag : 'none';
	this.css('display', mode);
	return this;
};
// 連動セレクトタグ
$.fn.ChainSelect = function (selObj, val, first_call, callback) {
	var self = this;	// Reminder jQuery Self Object
	// allow first_call omit.
	if (typeof first_call === 'function') {
		callback = first_call;
		first_call = true;
	} else if (first_call === undefined) {
		first_call = false;
	};
	if (callback === undefined) callback = null;
	var id = this.attr('id');
	var sel_chain = new SelectLink(selObj, id, first_call, callback);
	sel_chain.Select(val);
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
// 要素のサイズを取得する
$.fn.bound_box = function () {
	var space = {
		border: {
			top: parseInt(this.css('border-top'),10),
			left: parseInt(this.css('border-left'),10),
			right: parseInt(this.css('border-right'),10),
			bottom: parseInt(this.css('border-bottom'),10),
		},
		margin: {
			top: parseInt(this.css('margin-top'),10),
			left: parseInt(this.css('margin-left'),10),
			right: parseInt(this.css('margin-right'),10),
			bottom: parseInt(this.css('margin-bottom'),10),
		},
		padding: {
			top: parseInt(this.css('padding-top'),10),
			left: parseInt(this.css('padding-left'),10),
			right: parseInt(this.css('padding-right'),10),
			bottom: parseInt(this.css('padding-bottom'),10),
		},
	};
	var target = {
		top: parseInt(this.offset().top, 10),
		left: parseInt(this.offset().left, 10),
		outerWidth: parseInt(this.outerWidth(), 10),
		outerHeight: parseInt(this.outerHeight(), 10),
		TopPos: parseInt(this.offset().top, 10) + space.border.top + space.padding.top,
		adjustRight: space.border.right + space.padding.right,
		adjustBottom:space.border.bottom + space.padding.bottom,
		withRightMargin: space.border.right + space.padding.right + space.margin.right,
		withBottomMargin: space.border.bottom + space.padding.bottom + space.margin.bottom,
	};
	target.BottomPos = target.top + target.outerHeight - target.adjustBottom;
	target.RightPos  = target.left+ target.outerWidth - target.adjustRight;
	return target;
};
// ウィンドウ高さ調整
$.fn.fitWindow = function (msg) {
	var self = this;	// Reminder jQuery Self Object
	var my_box = self.bound_box();
	var parent_bottom = function(obj,spc) {
		if (obj.length === 0 || obj.is(self)) {
			return my_box.BottomPos - spc;
		}
		var obj_box = obj.bound_box();
		if (obj.hasClass("fitWindow")) {
			return obj_box.BottomPos - spc;
		}
		spc = spc + obj_box.withBottomMargin;
		return parent_bottom(obj.parent(),spc);
	};
	self.find('.fitWindow').each(function () {
		var this_box = $(this).bound_box();
		var pbottom = parent_bottom($(this).parent(),this_box.adjustBottom);
		var my_height = pbottom - this_box.TopPos - this_box.withBottomMargin;
		alert($(this).debug_id('BOTTOM=')+pbottom);
		var s_height = my_height;//-my_margin;
		$(this).css({
			'min-height': s_height + "px",
			'max-height': s_height + "px",
			'overflow-y': "auto"
		});
	});
	return self;
};
// 親要素の高さに調整する
// $.fn.adjustWindow = function (msg) {
// 	var self = this;	// Reminder jQuery Self Object
// 	self.fitMe = function (msg) {
// 		var p = self.parent();	// 親オブジェクト
// 		var p_size = p.height();
// 		var s_top = self.position().top;			// 親要素からの相対位置
// 		var spc = self.outerHeight(true) - self.height();
// 		var s_height = p_size - s_top - spc;
// 		if (msg) alert("SELF:" + self.attr('class') + "\nPARENT-ID:" + p.attr('id') + "(" + p_size + ")" + "\nSIZE=(" + s_top + "," + s_height + ")\nSPC=" + spc);
// 		self.css({
// 			'width': '100%',
// 			'min-height': s_height + "px",
// 			'max-height': s_height + "px",
// 			'overflow-y': "auto"
// 		});
// 		return self;
// 	};
// 	$(window).on("load resize", function () { self.fitMe(); });
// 	self.fitMe(true).find('.fitWindow').each(function () { $(this).fitwindow(msg); });
// 	return self;
// };
// 指定要素に読み込んだHTMLを書き込む
// 
$.fn.LoadContents = function (url, obj, callback) {
	var self = this;	// Reminder jQuery Self Object
	var result = true;	// for callback fail
	if (typeof obj === 'function') {
		callback = obj;
		obj = null;
	};
	if (callback === undefined) callback = null;    // IE11で引数省略の不具合対応
	// フェイルメソッドバージョン
	self.fail = function (callback_error) {
		if (result === false && callback_error !== undefined) {
			alert("CALL-FAIL");
			callback_error.call(self, false);
		};
	};
	$.busy_cursor(true);
	$.post(url,obj,
		function(data){
			$.busy_cursor(false);
			//リクエストが成功した際に実行する関数
			self.result = false;
			self.html(data).InitPopupSet().fitWindow(false);
			DebugSlider();
			if (callback !== null) callback.call(self);
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
$.dialogBox = function (title,msg, callback) {
	var dialog_box = '<div class="dialog-box"><dl class="title"><dt>'+title+'</dt><dd><span class="dialog-msg">'+msg+'</span></dd></dl><div class="buttonList">';
	var controlls = ["okButton:${#.core.Yes}", "cancelButton:${#.core.No}"];
	controlls.forEach(function (value) {
		var cls = value.split(':');
		dialog_box = dialog_box + '<span class="'+cls[0]+'">'+cls[1]+'</span>';
	});
	dialog_box = dialog_box + "</div></div>";
	$('body').append(dialog_box);
	// ボタン以外をクリックできないようにする
	$('body').append('<div class="popup-BK"></div>');
	$('.popup-BK').fadeIn('fast');
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
		$('.popup-BK').remove();
		$(".dialog-box").remove();
		callback(true);
	});
	dialog.find(".cancelButton").off().click(function () {
		dialog.fadeOut('fast');
		$('.popup-BK').remove();
		$(".dialog-box").remove();
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
