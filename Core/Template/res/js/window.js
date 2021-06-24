// ***************************************************************************
// セレクタを使う
// スティッキー動作設定
var selector = $(".stickyBar");
selector.each(function () {
	var self = $(this); // jQueryオブジェクトを変数に代入しておく
	var stickyWin = self.closest('.fitWindow');
	if (stickyWin.length == 0) stickyWin = self.parent();
	// 親要素 のスクロールに追従する
	stickyWin.on("scroll", function () {
		var top = stickyWin.scrollTop();
		self.css("top", top + "px");
	});
});
// カレンダー設定
$(".calendar").each(function () {
	var self = $(this); // jQueryオブジェクトを変数に代入しておく
	var date_form = {
		dateFormat: 'yy-mm-dd',
		monthNames: [${#.core.monthNames}],
		dayNamesMin: [${#.core.dayNames}],
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
// ウィンドウ高さ調整
$('body').fitWindow();
// マークダウン外部リンク
$('.easy_markdown a[href^=http]:not(:has(img))').addClass("externalLink").attr('target', '_blank');

$("body").PopupBaloonSetup().InfoBoxSetup().PopupBoxSetup();
// コンボボックスの処理
$(document).on('change','.combobox>select',function () {
	var txt = $(this).find('option:selected').html();
	var inbox = $(this).parent().find('input');
	inbox.val(txt);
	inbox.addClass('modified');
});