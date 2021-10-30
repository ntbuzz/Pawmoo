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
// ターゲット位置を元に自身のポジションを決定する
function calcPosition(target, self) {
	var target_left = target.offset().left;
	var target_width = target.innerWidth();
	var self_width = self.width();
	if ((target_left + self_width) > $(window).innerWidth()) {
		this.left = target_left + target_width - self_width;
	} else {
		this.left = target_left + Math.max(0,target_width - self_width);
	}
	this.top = target.offset().top + target.outerHeight();
	this.scrollPos = function () {
		var x = this.left - $(window).scrollLeft();
		var y = this.top - $(window).scrollTop();
		return { x: x, y: y };
	};
};
// ポップアップメニューボックスを表示する
$.fn.PopupMenuSetup = function () {
	this.find('.navi-menubox').each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var ref_obj = $("#" + self.attr("data-element"));  // 紐付けるID
		var target = $('[name="'+ref_obj.attr('data-element')+'"');  // 書き込むID
		ref_obj.css("cursor", "pointer");
		var menuPos = new calcPosition(target, self);
		ref_obj.off('click').on('click', function () {
			// メニューを消すための領域を定義
			var backwall = $('<div class="popup-BK"></div>');
			$('body').append(backwall);
			backwall.fadeIn('fast');
			// 閉じるためのカスタムイベントを定義する(trigger()で呼び出す)
			self.off('close-me').on('close-me', function (e) {
				self.fadeOut('fast');
				$(window).off('scroll.drop-menu');
				$('.popup-BK').remove();
			});
			backwall.click( function() {
				self.trigger('close-me');
			});
			// スクロールはリアルタイムで位置移動
			$(window).on('scroll.drop-menu', function () {
				var pp = menuPos.scrollPos();
				self.css({'left': pp.x + 'px','top': pp.y + 'px'});
			});
			// メニューコンテンツの表示位置をリンク先から取得して設定
			var pp = menuPos.scrollPos();
			self.css({'left': pp.x + 'px','top': pp.y + 'px'});
			self.fadeIn('fast');
			self.off('click').on('click','.item',function(e) {
				e.stopPropagation();
				e.preventDefault();
				target.val($(this).text());
				self.trigger('close-me');
				target.trigger('selected');
			});
		});
	});
	return this;
};
// ポップアップメニューボックスを表示する
$.fn.PopupCheckListSetup = function () {
	this.find('.navi-checklist').each(function () {
		var self = $(this); // jQueryオブジェクトを変数に代入しておく
		var ref_obj = $("#" + self.attr("data-element"));  // 紐付けるID
		var target = $('[name="' + ref_obj.attr("data-element") + '"');  	// 書き込むID
		var active_tab = self.find('.check-itemset:first');
		ref_obj.css("cursor", "pointer");
		var menuPos = new calcPosition(target, self);
		ref_obj.off('click').on('click', function () {
			// チェック項目をリストアップしておく
			var all_items = self.find('.check-item').map(function () { return $(this).val(); }).get();
			// 入力値をチェックリストに反映する
			var current = target.val();		// 現在の入力値
			self.find('.check-item').map(function () {
				$(this).prop('checked', (current.indexOf($(this).val()) !== -1));
			});
			self.trigger('check-flip');
			// メニューを消すための領域を定義
			var backwall = $('<div class="popup-BK"></div>');
			$('body').append(backwall);
			// 背景をクリックした閉じる
			backwall.fadeIn('fast').click(function() {self.trigger('close-me');});
			// close ボタンが定義されているときの処理
			self.find('.close').on('click', function () { self.trigger('close-me');	});
			// 閉じるためのカスタムイベントを定義する(trigger()で呼び出す)
			self.off('close-me').on('close-me', function (e) {
				self.fadeOut('fast');
				$(window).off('scroll.drop-menu');
				$('.popup-BK').remove();
			});
			// スクロールはリアルタイムで位置移動
			$(window).on('scroll.drop-menu', function () {
				var pp = menuPos.scrollPos();
				self.css({'left': pp.x + 'px','top': pp.y + 'px'});
			});
			// アイテムにチェックがあれば全チェックに反映する
			self.off('check-flip').on('check-flip', function () {
				var all_check = (active_tab.find('.check-item:checked').length !== 0);
				$('.flip_all').prop('checked', all_check);
			});
			// 全チェックのフラグをアイテムに反映する
			$('.flip_all').off('change').on('change', function () {
				active_tab.find('.check-item').prop('checked', $(this).prop('checked'));
				self.trigger('values-set');
			});
			// 全てのタブ内のチェック項目をリスト結合してターゲットに入力する
			self.off('values-set').on('values-set', function () {
				// 入力値がリストにあるかチェックし無ければ先頭にアイテム挿入
				var check_obj = self.find('.check-item:checked');
				if (check_obj.attr('type') === 'radio') {
					uniq = check_obj.val();
				} else {
					var current = target.val().split(" ");		// 区切り文字に置換予定
					var direct_data = current.filter(function (i) { return all_items.indexOf(i) === -1 });
					var vals = check_obj.map(function () { return $(this).val(); }).get();
					vals = direct_data.concat(vals);
					// IEでも動くようにfilterで重複を削除して結合
					uniq = vals.filter(function (x, i, self) { return self.indexOf(x) === i; }).join(" ");
				};
				target.val(uniq);
			});
			// タブ切り替えを処理
			self.find('.tabmenu>li').on('click').on('click', function () {
				var control = $(this).closest('div');
				var menu = control.children('.tabmenu').children('li');
				var cont = control.children('.tabcontents').children('li');
				var index = menu.index($(this));
				active_tab = cont.eq(index);
				menu.removeClass('selected');		// TabMenu selected delete
				$(this).addClass('selected');		// switch click TAB selected
				cont.removeClass('selected');		// TabContents selected delete
				active_tab.addClass('selected').fitWindow();	// switch TAB selected Contents
				self.trigger('check-flip');
			});
			// メニューコンテンツの表示位置をリンク先から取得して設定
			var pp = menuPos.scrollPos();
			self.css({'left': pp.x + 'px','top': pp.y + 'px'});
			self.fadeIn('fast');
			// チェックアイテムがクリックされたら
			self.find('input.check-item').off('change').on('change', function (e) {
				self.trigger('values-set');
			});
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
	return this.PopupBaloonSetup().InfoBoxSetup().PopupBoxSetup().PopupMenuSetup().PopupCheckListSetup();
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
