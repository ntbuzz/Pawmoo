// JQuery Plugin import
// Select RADIO-BUTTON, CHECKBOX list dialog
$.fn.popupCheckSelect = function (setupobj, callback) {
	var setting = {
		PopupLabel: false,
		DialogTitle: "${#.core.CheckTITLE}",
		ConfirmLabel: "${#.core.CheckConfirm}",
		MultiSelect: true,				// true or object-type use "checkbox",other will be radio
		Columns: 2,
		Rows: 0,
		ItemsList: [],
	};
	// checkbox select dialog options.
	var CheckListOption = {
		FlipCheck: true,
		FlipLabel: "${#.core.CheckALL}",
		Separate: "\n",
	};
	$.each(setupobj, function (key, value) { setting[key] = value; });
	// Is CheckList Dialog Type
	var multi_type = Object.prototype.toString.call(setting.MultiSelect).slice(8, -1).toLowerCase();
	if (multi_type === 'object') {
		$.each(setting.MultiSelect, function (key, value) { CheckListOption[key] = value; });
		setting.MultiSelect = true;
	} else if (multi_type !== 'boolean') {
		setting.MultiSelect = false;
	};
	var tag_name = this.attr('data-element');
	var tag_obj = this.find('[name='+tag_name+']');
	if (tag_obj.length == 0) {
		alert("CheckList:NOT FOUND="+tag_name);
		return false;
	};
	tag_obj.css("padding", "0 5px");
	var btn = $('<span class="dropdown"></span>');
	if (typeof setting.PopupLabel === "string") {
		var label = $('<label>' + setting.PopupLabel + '</label>');
		this.prepend(label);
		label.after(btn);
		tag_obj.css("width", "100%");
	} else {
		var ww = this.innerWidth() - tag_obj.position().left - 20;
		tag_obj.css("width", ww);
		this.append(btn);
	};
	var input_tag = "type='radio' name='" + tag_name + "'";
	if (setting.MultiSelect === true) {
		input_tag = "type='checkbox' class='multi-check'";
	};
	// set ALL-CHECK checkbox addition
	var check_all_flip = (setting.MultiSelect === true && CheckListOption.FlipCheck);
	btn.on('click', function () {
		var current_data = tag_obj.val().trim();
		var self = $(this);
		// for close click for out of dialog window area
		var bk_panel = $('<div class="dialog-BK"></div>').appendTo($('body'));
		var dialog = $('<div class="checklist-dialog"></div>').appendTo(bk_panel);
		dialog.click(function (e) { e.stopPropagation(); });	// イベントの伝播を止める
		if(typeof setting.DialogTitle === "string") {
			var title_bar = $('<div class="titleBar"></div>').appendTo(dialog);
			title_bar.append(setting.DialogTitle);
		};
		// button bar for confirm button and all checkbox
		var btn_bar = $('<div class="bottom-bar"></div>');
		if (check_all_flip) {
			var all_tag = $('<label>' + CheckListOption.FlipLabel + '</label>').appendTo(btn_bar);
			var check_btn = $('<input type="checkbox" />').appendTo(all_tag);
			check_btn.change(function () {
				active_list.find('.multi-check').prop('checked', $(this).prop('checked'));
				return false;
			});
			btn_bar.on('check-flip', function () {
				var all_check = (active_list.find('.multi-check:checked').length !== 0);
				check_btn.prop('checked', all_check);
			});
		};
		// create of check/radio list-box
		var make_list_box = function (check_list, check_items, target) {
			if (setting.Columns !== undefined) check_list.addClass('col' + setting.Columns);
			// item list convert to label object.
			var label_val = {};
			check_items.forEach(function (val) {
				if(typeof val === "string" &&  val.indexOf('=') >= 0) {
					var dat = val.split("=");
					label = dat[0];
					value = dat[1];
				} else label = value = val;
				label_val[label] = value;
			});
			// make check-list by label object element.
			for (label in label_val) {		// for...of の使えないIE11への対応
				var value = label_val[label];
				var li_tag = $('<li></li>').appendTo(check_list);
				if (parseInt(value) < 0) {
					li_tag.append('<hr>');		// separator
				} else {
					var label_tag = $('<label></label>').appendTo(li_tag);
					var item = $('<input '+input_tag+' value="' + value+ '" />').appendTo(label_tag);
					if ($.isArray(target)) {
						if (target.delete_exists(value)) {
							item.prop('checked', true);
						};
					};
					label_tag.append(label);
				};
			};
			return target;
		};
		var current_list = current_data.split(/;|\n/g).filter(function (v) { return (v.length); });
		var check_contents = $('<ul class="checklist-contents"></ul>');
		if ($.isArray(setting.ItemsList)) {
			check_contents.appendTo(dialog);
			var check_list = $('<ul class="checklist-box"></ul>').appendTo(check_contents);
			current_list = make_list_box(check_list, setting.ItemsList, current_list);
		} else if (typeof setting.ItemsList === 'object') {
			var check_tabset = $('<ul class="checklist-tabs"></ul>').appendTo(dialog);
			check_contents.appendTo(dialog);
			$.each(setting.ItemsList, function(label, value) {
				var tab = $('<li>' + label + '</li>').appendTo(check_tabset);
				tab.click(function(e) {
					var menu = check_tabset.children('li');
					var cont = check_contents.children('li');
					var index = $(this).index();
					menu.removeClass('selected');		// TabMenu selected delete
					cont.removeClass('selected');		// TabContents selected delete
					$(this).addClass('selected');		// switch click TAB selected
					active_list = cont.eq(index).addClass('selected').find('.checklist-box');
					btn_bar.trigger('check-flip');
				});
				var content = $('<li></li>').appendTo(check_contents);
				var check_list = $('<ul class="checklist-box"></ul>').appendTo(content);
				current_list = make_list_box(check_list, value, current_list);
			});
			check_tabset.children().first().addClass('selected');
			check_contents.children().first().addClass('selected');
		};
		// insert for existing data that was not checked
		var active_list = check_contents.find('.checklist-box').first();
		current_list = make_list_box(active_list, current_list, current_list);
		if (setting.Rows > 0) {
			check_contents.css('max-height', setting.Rows*1.5 + "em");
		};
		btn_bar.trigger('check-flip');
		btn_bar.appendTo(dialog);
		var close_btn = $('<span class="wbutton"></span>').appendTo(btn_bar);
		close_btn.append(setting.ConfirmLabel);
		bk_panel.fadeIn('fast');
		// dialog position
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
		// close event
		close_btn.click(function (e) {
			if (setting.MultiSelect === true) {
				vals = $('.multi-check:checked').map(function () { return $(this).val(); }).get();
				// IE11でも動作するようにprottype宣言の重複削除を使う
				uniq = vals.uniq().join(CheckListOption.Separate);	// 重複を削除して結合
			} else {
				uniq = $('input[name="'+tag_name+'"]:checked').val();
			};
			bk_panel.click();
			if (callback !== undefined) callback.call(tag_obj, uniq);
			return false;
		});
		bk_panel.click(function (e) {
			bk_panel.fadeOut('fast');
			bk_panel.remove();		// delete with dialog element
		});
	});
	return this;
};

