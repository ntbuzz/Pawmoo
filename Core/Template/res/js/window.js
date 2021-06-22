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
});