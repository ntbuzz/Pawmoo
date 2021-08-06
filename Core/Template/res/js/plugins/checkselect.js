// JQueryプラグインで実装
// ラジオセレクト、チェックリスト選択ボックスを表示する
$.fn.popupCheckSelect = function (setupobj, callback) {
	var setting = {
		DialogLabel: "${#.core.CheckList}",
		DialogTitle: "${#.core.CheckTITLE}",
		ConfirmLabel:"${#.core.CheckConfirm}",
		FlipLabel: "${#.core.CheckALL}",
		RadioType: true,
		CheckFlip: false,
		CheckJoin: "\n",
		Columns: 2,
		Rows: 0,
		ItemsList: [],
	};
	$.each(setupobj, function (key, value) { setting[key] = value;});
	var tag_name = this.attr('data-name');
	var tag_obj = this.find('[name='+tag_name+']');
	if (tag_obj.length == 0) {
		alert("ERROR");
		return false;
	};
	tag_obj.css("padding", "0 5px");
	var btn = $('<span class="dropdown"></span>');
	var label = this.find('label');
	if (label.length != 0) {
		label.text(setting.DialogLabel);
		label.after(btn);
		tag_obj.css("width", "100%");
	} else {
		var ww = this.innerWidth() - tag_obj.position().left - 20;
		tag_obj.css("width", ww);
		this.append(btn);
	}
	if (setting.RadioType) {
		var input_tag = "type='radio' name='"+tag_name+"'";
	} else {
		var input_tag = "type='checkbox' class='multi-check'";
	};
	// 要素配列と現在値配列から全リストを生成してコールバックする
	var setCheckList = function (base, addval, callback) {
		var label_val = {};
		// ベースの要素配列
		base.forEach(function (val) {
			if(typeof val === "string" &&  val.indexOf('=') >= 0) {
				var dat = val.split("=");
				label = dat[0];
				value = dat[1];
			} else label = value = val;
			label_val[label] = value;
		});
		// 現在登録されている値をマージ
		addval.forEach(function (val) {
			var exists = false;
			for (const [key, value] of Object.entries(label_val)) {
				if (value === val) {
					exists = true;
					break;
				};
			};
			if (!exists) label_val[val] = val;
		});
		// 出来上がったオブジェクトを要素ごとにコールバック
		for (const [key, value] of Object.entries(label_val)) {
			callback(key, value);
		};
	};
	btn.on('click', function () {
		var self = $(this);
		// ダイアログ領域以外はクローズする
		var bk_panel = $('<div class="dialog-BK"></div>').appendTo($('body'));
		var dialog = $('<div class="checklist-dialog"></div>').appendTo(bk_panel);
		dialog.click(function (e) { e.stopPropagation(); });	// イベントの伝播を止める
		var title_bar = $('<div class="titleBar"></div>').appendTo(dialog);
		title_bar.append(setting.DialogTitle);
		// ラジオボタンでなければ全てをチェックするチェックボックスの追加
		if (setting.RadioType == false && setting.CheckFlip) {
			var button_bar = $('<span class="checkBar"></span>').appendTo(title_bar);
			button_bar.append(setting.FlipLabel);
			var check_btn = $('<input type="checkbox" />').appendTo(button_bar);
		};
		// list-box を作成
		var all_check = false;
		var make_list_box = function (appendObj, check_items) {
			var check_list = $('<ul class="checklist-box"></ul>').appendTo(appendObj);
			if (setting.Columns !== undefined) check_list.addClass('col' + setting.Columns);
			// リスト作成
			var target = tag_obj.val().split(/;|\n/g).filter(function (v) { return (v.length); });
			setCheckList(check_items, target, function (label, value) {
				var li_tag = $('<li></li>').appendTo(check_list);
				if (parseInt(value) < 0) {
					li_tag.append('<hr>');		// separator
				} else {
					var label_tag = $('<label></label>').appendTo(li_tag);
					var item = $('<input '+input_tag+' value="' + value+ '" />').appendTo(label_tag);
					if (target.is_exists(value)) {
						item.prop('checked', true);
						all_check = true;
					};
					label_tag.append(label);
				};
			});
		};
		if ($.isArray(setting.ItemsList)) {
			var check_contents = $('<ul class="checklist-contents"></ul>').appendTo(dialog);
			make_list_box(check_contents, setting.ItemsList);
		} else if (typeof setting.ItemsList === 'object') {
			var check_tabset = $('<ul class="checklist-tabs"></ul>').appendTo(dialog);
			var check_contents = $('<ul class="checklist-contents"></ul>').appendTo(dialog);
			$.each(setting.ItemsList, function(label, value) {
				var tab = $('<li>' + label + '</li>').appendTo(check_tabset);
				tab.click(function(e) {
					var menu = check_tabset.children('li');
					var cont = check_contents.children('li');
					var index = $(this).index();
					menu.removeClass('selected');		// TabMenu selected delete
					cont.removeClass('selected');		// TabContents selected delete
					$(this).addClass('selected');		// switch click TAB selected
					cont.eq(index).addClass('selected');	// switch TAB selected Contents
				});
				var content = $('<li></li>').appendTo(check_contents);
				make_list_box(content, value);
			});
			check_tabset.children().first().addClass('selected');
			check_contents.children().first().addClass('selected');
		}
		if (setting.Rows > 0) {
			check_contents.css('max-height', setting.Rows*1.5 + "em");
		}
		var btn_bar = $('<div class="bottom-bar"></div>').appendTo(dialog);
		var close_btn = $('<span class="wbutton"></span>').appendTo(btn_bar);
		close_btn.append(setting.ConfirmLabel);
		// フリップチェックボックスがあればデフォルトのチェック状態を反映
		if (setting.RadioType == false && setting.CheckFlip) {
			check_btn.prop('checked', all_check);
			check_btn.change(function () {
				check_contents.find('.multi-check').prop('checked', $(this).prop('checked'));
				return false;
			});
		};
		bk_panel.fadeIn('fast');
		// ダイアログの位置
		var x = self.offset().left;
		var y = self.offset().top + self.outerHeight();
		if ((x + dialog.outerWidth()) > $(window).innerWidth()) {
			x = self.offset().left - dialog.outerWidth(true);   // padding+margin込みの幅を差引く
		};
		if ((y + dialog.outerHeight()) > $(window).innerHeight()) {
			y = $(window).innerHeight() - dialog.outerHeight(true);   // padding+margin込みの高さを差引く;
			if (y <= 0) {
				y = 10;
				var h = $(window).innerHeight() - 40;   // 上下 20px
				dialog.css('height', h + 'px');
			};
		};
		dialog.css({ 'left': x + 'px', 'top': y + 'px' });
		// イベント処理
		close_btn.click(function (e) {
			if (setting.RadioType) {
				uniq = $('input[name="'+tag_name+'"]:checked').val();
			} else {
				vals = $('.multi-check:checked').map(function () { return $(this).val(); }).get();
				uniq = Array.from(new Set(vals)).join(setting.CheckJoin);	// 重複を削除して結合
			};
			bk_panel.click();
			if (callback !== undefined) callback.call(tag_obj, uniq);
			return false;
		});
		bk_panel.click(function (e) {
			bk_panel.fadeOut('fast');
			bk_panel.remove();		// ダイアログはバックパネルの子要素で道連れ削除
		});
	});
	return this;
};
