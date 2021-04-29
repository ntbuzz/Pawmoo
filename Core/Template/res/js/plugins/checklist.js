// JQueryプラグインで実装
// チェックリスト選択ボックスを表示する
$.fn.popupCheckList = function (setupobj, callback) {
	var setting = {
		CheckBarLabel:"${#.core.CheckList}",
		DialogTitle: "${#.core.CheckTITLE}",
		CheckFlip: false,
		FlipLabel: "${#.core.CheckALL}",
		ConfirmLabel:"${#.core.CheckConfirm}",
		CheckLists: [],
		Columns: 2,
	};
	$.each(setupobj, function (key, value) { setting[key] = value;});
	var list_val = $('#' + this.attr('data-element'));
	this.html(setting.CheckBarLabel);
	var btn = $('<span></span>').appendTo(this);
	btn.on('click', function () {
		var self = $(this);
		// ダイアログ領域以外はクローズする
		var bk_panel = $('<div class="dialog-BK"></div>').appendTo($('body'));
		var dialog = $('<div class="checklist-dialog"></div>').appendTo(bk_panel);
		dialog.click(function (e) { e.stopPropagation(); });	// イベントの伝播を止める
		var title_bar = $('<div class="titleBar"></div>').appendTo(dialog);
		title_bar.append(setting.DialogTitle);
		// 全てをチェックするチェックボックスの追加
		if (setting.CheckFlip) {
			var button_bar = $('<span class="buttonBar"></span>').appendTo(title_bar);
			button_bar.append(setting.FlipLabel);
			var check_btn = $('<input type="checkbox" />').appendTo(button_bar);
		};
		// checklist-box を作成
		var make_checklist_box = function (appendObj, checklist_items) {
			var check_list = $('<ul class="checklist-box"></ul>').appendTo(appendObj);
			if (setting.Columns !== undefined) check_list.addClass('col' + setting.Columns);
			// リスト作成
			var target = list_val.val().split("\n").filter(function (v) { return (v.length); });
			var merge_list = checklist_items.mymerged(target);
			var all_check = false;
			$.each(merge_list, function (index, elem) {
				var li_tag = $('<li></li>').appendTo(check_list);
				if (parseInt(elem) < 0) {
					li_tag.append('<hr>');		// separator
				} else {
					var label_tag = $('<label></label>').appendTo(li_tag);
					var item = $('<input type="checkbox" class="multi-check" value="' + elem + '" />').appendTo(label_tag);
					if (target.is_exists(elem)) {
						item.prop('checked', true);
						all_check = true;
					};
					label_tag.append(elem);
				};
			});
		};
		if ($.isArray(setting.CheckLists)) {
			var check_contents = $('<ul class="checklist-contents"></ul>').appendTo(dialog);
			make_checklist_box(check_contents, setting.CheckLists);
		} else if (typeof setting.CheckLists === 'object') {
			var check_tabset = $('<ul class="checklist-tabs"></ul>').appendTo(dialog);
			var check_contents = $('<ul class="checklist-contents"></ul>').appendTo(dialog);
			setting.CheckLists.Labels.forEach(function (label) {
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
			});
			setting.CheckLists.dataList.forEach(function (value) {
				var content = $('<li></li>').appendTo(check_contents);
				make_checklist_box(content, value);
			});
			check_tabset.children().first().addClass('selected');
			check_contents.children().first().addClass('selected');
		};
		var close_btn = $('<span class="wbutton"></span>').appendTo(dialog);
		close_btn.append(setting.ConfirmLabel);
		// フリップチェックボックスがあればデフォルトのチェック状態を反映
		if (setting.CheckFlip) {
			check_btn.prop('checked', all_check);
			check_btn.change(function () {
				check_contents.find('.multi-check').prop('checked', $(this).prop('checked'));
				return false;
			});
		};
		bk_panel.fadeIn('fast');
		// ダイアログの位置
		var x = self.offset().left + self.width();
		var y = self.offset().top + 10;
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
			var vals = $('.multi-check:checked').map(function () { return $(this).val(); }).get();
			bk_panel.click();
			if (callback !== undefined) callback.call(list_val, vals.join("\n"));
			return false;
		});
		bk_panel.click(function (e) {
			bk_panel.fadeOut('fast');
			bk_panel.remove();		// ダイアログはバックパネルの子要素で道連れ削除
		});
	});
	return this;
};

