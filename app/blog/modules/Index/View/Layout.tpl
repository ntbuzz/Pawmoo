//  セクション・レイアウト定義
@Header => [	          // @ ViewTemplate() 呼び出し
	 PageTitle => ${#TITLE}		// 引数は変数にセット
	 AdditionHeader => [
		 ./css/common.css
		 ./js/common.js
	]
]
-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
+jquery => ~
	// ウィンドウリサイズで高さ調整するエレメント
	$(".sideMenu").adjustHeight();
~
.appWindow => [    // タグ名省略は DIVタグセクション
//	@Toolbar
	ブログヘッダー
	.blogContents => [
		.diaryIndex => [
			ブログインデックス
		]
		.sideMenu => [
			サイドメニュー
		]
	]
]


@Footer
