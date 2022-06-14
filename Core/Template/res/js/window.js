// ***************************************************************************
// セレクタを使う
// スティッキー動作設定
var selector = $('.stickyBar');
selector.each(function () {
	var self = $(this); // jQueryオブジェクトを変数に代入しておく
	var stickyWin = self.closest('.fitWindow');
	if (stickyWin.length == 0) stickyWin = self.parent();
	// 親要素 のスクロールに追従する
	stickyWin.on('scroll', function () {
		var top = stickyWin.scrollTop();
		self.css('top', top + 'px');
	});
});

// ウィンドウ高さ調整
$(window).resize(function () {
	$('body').fitWindow();
});
// マークダウン外部リンク
$('.easy_markdown a[href^=http]:not(:has(img))').addClass('externalLink');
// externalLinkを別ウィンドウ表示
$('.externalLink').attr('target', '_blank');

$('body').InitPopupSet();
// コンボボックスの処理
$(document).on('change','.combobox>select',function () {
	var txt = $(this).find('option:selected').html();
	var inbox = $(this).parent().find('input');
	inbox.val(txt);
	inbox.addClass('modified');
});
// INPUT 文字種チェック
$(document).on('blur', 'input[type="text"]', function (e) {
	var TypeObj = {
		'numeric':	['#', '${#core.NUMERIC}'],		// INPUT 数字
		'number':	['0', '${#core.NUMERIC}'],		// INPUT 整数
		'date':		['@', '${#core.DATEFMT}'],		// INPUT 日付
		'datetime':	[':', '${#core.TIMESTAMP}'],	// INPUT 日時
		'alpha':	['Aa','${#core.ALPHABET}'],		// INPUT 英字
		'alnum':	['*','${#core.ALPHANUM}'],		// INPUT 英数字
	};
	var self = $(this);
	var str = self.val().hankaku();
	if (str.length === 0) return false;
	for (var k in TypeObj) {
		if (self.hasClass(k)) {
			switch (str.charsetCheck(TypeObj[k][0])) {
				case true: self.val(str); break;
				default:
					$.dialogBox('${#core.CAUTION}', TypeObj[k][1], false, function () { self.focus(); self.select();});
			};
			break;
		};
	};
	return false;
});
// // 文字種チェック
// function InputCharsetCheck(target, kind, msg) {
// 	var str = target.val().hankaku();
// 	switch (str.charsetCheck(kind)) {
// 		case true: target.val(str); break;
// 		case false:
// 			$.dialogBox('${#core.CAUTION}', msg, false, function () { target.focus(); target.select();});
// 			break;
// 	};
// 	return false;
// };
// // INPUT 数字
// $(document).on('blur', 'input.numeric[type="text"]', function (e) {
// 	InputCharsetCheck($(this), '#', '${#core.NUMERIC}');
// 	return false;
// });
// // INPUT 日付
// $(document).on('blur', 'input.date[type="text"]', function () {
// 	InputCharsetCheck($(this), '@', '${#core.DATEFMT}');
// });
// // INPUT 日時
// $(document).on('blur', 'input.datetime[type="text"]', function () {
// 	InputCharsetCheck($(this), ':', '${#core.TIMESTAMP}');
// });
// ウィンドウサイズ調整
$(window).resize();
