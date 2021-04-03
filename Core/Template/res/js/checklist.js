// チェックリスト選択ボックスを表示する
// JQueryプラグインで実装
(function ($) {
	$.fn.checkList = function (columns, select_list, callback) {
		var list_val = $('#' + $(this).attr('data-element'));
		$(this).html("${#.core.CHECKLIST}");
		var btn = $('<span></span>').appendTo($(this));
		btn.on('click', function () {
			var self = $(this);
			// ダイアログ領域以外はクローズする
			var bk_panel = $('<div class="dialog-BK"></div>').appendTo($('body'));
			var dialog = $('<div class="checklist-dialog"></div>').appendTo(bk_panel);
			dialog.click(function (e) { e.stopPropagation(); });	// イベントの伝播を止める
			var button_bar = $('<div class="buttonBar">全て:</div>').appendTo(dialog);
			var check_btn = $('<input type="checkbox" />').appendTo(button_bar);
			var check_list = $('<ul class="checklist-box"></ul>').appendTo(dialog);
			var close_btn = $('<span class="wbutton">確定</span>').appendTo(dialog);
			if (columns !== undefined) check_list.addClass('col' + columns);
			// リスト作成
			var target = list_val.val();
			var all_check = false;
			$.each(select_list, function (index, elem) {
				var li_tag = $('<li></li>').appendTo(check_list);
				var label_tag = $('<label></label>').appendTo(li_tag);
				var item = $('<input type="checkbox" class="multi-check" value="' + elem + '" />').appendTo(label_tag);
				if (target.indexOf(elem) != -1) {
					item.prop('checked', true);
					all_check = true;
				}
				label_tag.append(elem);
			});
			// デフォルトのチェック状態
			check_btn.prop('checked', all_check);
			bk_panel.fadeIn('fast');
			// ダイアログの位置
			var x = self.offset().left + self.width();
			var y = self.offset().top + 10;
			if ((x + dialog.outerWidth()) > $(window).innerWidth()) {
				x = self.offset().left - dialog.outerWidth(true);   // padding+margin込みの幅を差引く
			}
			if ((y + dialog.outerHeight()) > $(window).innerHeight()) {
				y = $(window).innerHeight() - dialog.outerHeight(true);   // padding+margin込みの高さを差引く;
				if (y <= 0) {
					y = 10;
					var h = $(window).innerHeight() - 40;   // 上下 20px
					dialog.css('height', h + 'px');
				}
			}
			dialog.css({ 'left': x + 'px', 'top': y + 'px' });
			// イベント処理
			check_btn.change(function () {
				check_list.find('.multi-check').prop('checked', $(this).prop('checked'));
				return false;
			});
			close_btn.click(function (e) {
				var vals = $('.multi-check:checked').map(function () { return $(this).val(); }).get();
				bk_panel.click();
				if (callback !== undefined) callback.call(list_val, vals.join("\n"));
				return false;
			});
			bk_panel.click(function (e) {
				bk_panel.fadeOut('fast');
				bk_panel.remove();
			});
		});
	};

})(jQuery);
