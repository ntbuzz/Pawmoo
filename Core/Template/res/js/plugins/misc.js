//=========================================================
// 諸々のJQueryプラグインを定義する
// 表示切替
$.fn.Visible = function (flag) {
	if (typeof flag === 'string') {
		this.css('display', flag);
	} else if (flag === true) this.show();
	else this.hide();
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
	// 中間タグのセレクトコールバック
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
//		alertDump({ SELF: my_box, PARENT: pbox, SIZE: [s_height, s_width] });
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
		async: false,		// asunc mode
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
// フォーム部品(INPUT,SELECT,TEXTAREA)の変更時にクラス属性をセットする
$.fn.onChangeFormItems = function(cls) {
	var self = this;
	self.on('change', 'input,select,textarea', function () {
		var ptag = $(this).parent();
		if (ptag.hasClass('combobox')) {
			// コンボボックス用のSELECTはmodifiedをつけない
		} else {
			$(this).addClass(cls);
			// チェックボックスとラジオボタンは label タグの色を変える
			if (['checkbox', 'radio'].is_exists($(this).prop('type'))) {
				ptag.addClass('changed');
			};
		};
	});
};
// カーソルを BUSY に変更
$.busy_cursor = function (disp) {
	$('body').css('cursor', (disp) ? 'wait' : 'default');
	if (disp) $('body').append('<div class="loader_icon"></div>');
	else $('.loader_icon').remove();
};
// Yes/No ダイアログボックスを開く
$.dialogBox = function (title, msg, callback) {
	var bk_panel = $('<div class="popup-BK"></div>');
	var dialog_box = '<div class="dialog-box"><dl class="title"><dt>'+title+'</dt><dd><span class="dialog-msg">'+msg+'</span></dd></dl><div class="buttonList">';
	var controlls = ["okButton:${#.core.Yes}", "cancelButton:${#.core.No}"];
	controlls.forEach(function (value) {
		var cls = value.split(':');
		dialog_box = dialog_box + '<span class="'+cls[0]+'">'+cls[1]+'</span>';
	});
	dialog_box = dialog_box + "</div></div>";
	bk_panel.append(dialog_box);
	$('body').append(bk_panel);
	// ボタン以外をクリックできないようにする
	bk_panel.fadeIn('fast');
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
		bk_panel.remove();
		callback(true);
	});
	dialog.find(".cancelButton").off().click(function () {
		dialog.fadeOut('fast');
		bk_panel.remove();
		callback(false);
	});
};
//==========================================================
// レイアウト内にメニューボックスが定義済の場合に備える
// ポップアップチェックリストボックスを表示する
$.fn.MenuSetup = function () {
	this.find('.menu-container').each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var kind = self.attr("data-value");
		var ref_obj = $("#" + self.attr("data-element"));  // 紐付けるID
		if (ref_obj instanceof jQuery) {
			var hint = self.attr('hint');
			if (kind === 'dropdown') {
				ref_obj.DropDownMenuBox(hint,function () { return self.html(); });
			} else {
				ref_obj.SingleCheckBox({ Hint: hint }, function () { return self.html(); });
			};
		};
	});
	return this;
};
// ポップアップドロップダウンメニューボックスを表示する
$.fn.DropDownMenuBox = function (param_obj,preload_func) {
	var self = this; // jQueryオブジェクトを変数に代入しておく
	var setting = {
		TargetObj: self.find('input:first-child'),	// 書き込むINPUT name
		ClearTag: '<span class="clear"></span>',
		DropDown: '<span class="arrow"></span>',
		Hint: '',
		Preload: function () { return '<div></div>'; },
		SetValue: function (val) {
			this.TargetObj.val(val);
			this.TargetObj.trigger('change');
		},
	};
	if (setting.TargetObj.length === 0) return this;
	if (typeof preload_func === 'function') setting.Preload = preload_func;
	switch (typeof param_obj) {
		case 'string': setting.Hint = param_obj; break;
		case 'object':
			if (param_obj !== null && param_obj !== undefined) {
				$.each(param_obj, function (key, value) { setting[key] = value; });
			};
			break;
	};
	// [X]マークと▼マークのタグが無ければ追加する
	var clearBtn = self.children('span.clear');
	if (clearBtn.length === 0) {
		clearBtn = $(setting.ClearTag).appendTo(self);
	};
	var dropBtn = self.children('span.arrow');
	if (dropBtn.length === 0) {
		dropBtn = $(setting.DropDown).appendTo(self);
	};
	clearBtn.off().on('click',function(e){
		e.stopPropagation();
		e.preventDefault();
		setting.SetValue('');
	});
	self.css("cursor", "pointer");
	self.off('click').on('click', function () {
		// テンプレート関数でメニューを取得
		$.busy_cursor(true);
		var Template = setting.Preload.call(this);
		$.busy_cursor(false);
		var data = '<div class="navi-menubox">'+Template+'</div>';
		var menu_box = $(data).appendTo('body');
		if (typeof setting.Hint === 'string') menu_box.attr('title', setting.Hint);
		menu_box.show();
		// 移動している可能性があるため、クリック時に位置計算
		var menuPos = new calcPosition(self, menu_box);
		// メニューを消すための領域を定義
		var bk_panel = $('<div class="popup-BK"></div>').appendTo('body');
		bk_panel.fadeIn('fast');
		// 閉じるためのカスタムイベントを定義する(trigger()で呼び出す)
		menu_box.off('close-me').on('close-me', function (e) {
			menu_box.fadeOut('fast');
			$(window).off('scroll.drop-menu');
			bk_panel.remove();
			menu_box.remove();
		});
		bk_panel.click( function() {
			menu_box.trigger('close-me');
		});
		// スクロールはリアルタイムで位置移動
		$(window).on('scroll.drop-menu', function () {
			menuPos.scrollPos();
		});
		// メニューコンテンツの表示位置をリンク先から取得して設定
		menuPos.scrollPos();
		menu_box.fadeIn('fast');
		menu_box.off('click').on('click','.item',function(e) {
			e.stopPropagation();
			e.preventDefault();
			setting.SetValue($(this).text());
			menu_box.trigger('close-me');
		});
	});
	return this;
};
// 動的コンテンツに対して、プラグイン要素を初期化する
$.fn.InitPopupSet = function () {
	// カレンダー設定
	this.find(".calendar").each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var date_form = {
			dateFormat: 'yy-mm-dd 00:00:00',
			monthNames: [ '${#.core.monthNames}' ],
			dayNamesMin: [ '${#.core.dayNames}' ],
			yearSuffix: "${#.core.YearSuffix}",
			buttonImage: "/res/images/calender_icon.png",   // カレンダーアイコン画像
			buttonImageOnly: true,           // 画像として表示
			showOn: "button",                   // カレンダー呼び出し元の定義
			buttonText: "${#.core.ToolTip}", // ツールチップ表示文言
			showMonthAfterYear: true,
		};
		if (self.hasClass('no_icon')) {
			delete date_form.buttonImage;
			delete date_form.buttonImageOnly;
			delete date_form.showOn;
			delete date_form.buttonText;
		};
		self.datepicker(date_form);
	});
	return this.PopupBaloonSetup().InfoBoxSetup().PopupBoxSetup().MenuSetup();
};
// FormSubmit用のオブジェクトを生成
$.fn.submitObject = function (false_check,callback,is_parent) {
	var self = this;	// Reminder jQuery Self Object
	var setobj = {};
	var top_opj = (is_parent === false) ? self : self.parent();
	// 兄弟要素を含めるため親要素に戻ってname属性を検索する
	top_opj.find('[name]').each(function () {
		var nm = $(this).attr('name');	// 検索済の要素なので必ず存在する
		if ($(this).prop('tagName') === 'UL') {
			value = $(this).text().trim();
		} else {
			var tt = $(this).attr('type');
			if (tt === 'checkbox' || tt === 'radio') {
				if ($(this).is(':checked')) value = $(this).val();
				else if (tt === 'checkbox' && false_check) value = false;   // チェックされていないときの値をセット
				else return true;
			} else value = $(this).val();
		};
		setobj[nm] = value;
	});
	if (callback !== undefined) callback.call(self, setobj);
	return self;
};
