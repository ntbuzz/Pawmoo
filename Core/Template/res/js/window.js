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
$(window).resize(function () {
	$('body').fitWindow();
});
// マークダウン外部リンク
$('.easy_markdown a[href^=http]:not(:has(img))').addClass("externalLink");
// externalLinkを別ウィンドウ表示
$('.externalLink').attr('target', '_blank');

$("body").InitPopupSet();
// コンボボックスの処理
$(document).on('change','.combobox>select',function () {
	var txt = $(this).find('option:selected').html();
	var inbox = $(this).parent().find('input');
	inbox.val(txt);
	inbox.addClass('modified');
});

// 文字種チェック
function InputCharsetCheck(target, kind, msg) {
	var str = target.val().hankaku();
	if(str.charsetCheck(kind)) {
		target.val(str);
		return false;
	};
	alert(msg);
	target.focus();
}
// INPUT 数字
$(document).on('change', 'input.numeric[type="text"]', function () {
	InputCharsetCheck($(this), '#', '${#core.NUMERIC}');
});
// INPUT 日付
$(document).on('change', 'input.date[type="text"]', function () {
	InputCharsetCheck($(this), '@', '${#core.DATEFMT}');
});
// INPUT 日時
$(document).on('change', 'input.datetime[type="text"]', function () {
	InputCharsetCheck($(this), ':', '${#core.TIMESTAMP}');
});
// ウィンドウサイズ調整
$(window).resize();
