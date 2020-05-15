//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	 PageTitle => ${#TITLE}		// 引数は変数にセット
	 AdditionHeader => [
		 css/appstyle.css
		./css/common.css      // 結合出力
       	./js/common.js        // 結合出力
	]
]
*コメント => [ 配列の場合は？ ]
-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
+jquery => ~
	// ウィンドウリサイズで高さ調整するエレメント
	$(".contents-view").adjustHeight();
~
.appWindow => [    // タグ名省略は DIVタグセクション
	".split-pane fixed-left" => [
		".split-pane-component sitemap#left-component" => [
			@TreeMenu
		]
		.split-pane-divider#v-divider => []
		.split-pane-component#right-component => [
		// 最上部に固定する
			@Toolbar
		// #ContentBody をスクロール表示(contents-view)するブロック
			".split-pane-component contents-view" => [
				// コンテンツを表示する領域
				#ContentBody => [
					@DocView
				]
			]
		]
	]
]
// ポップアップメニューやダイアログ定義
@MenuFunc

@Footer
