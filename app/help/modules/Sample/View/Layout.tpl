//  セクション・レイアウト定義
//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	 PageTitle => $#TITLE		// 引数は変数にセット
	 AdditionHeader => [
		 css/appstyle.css
		./css/common.css      // 結合出力
       	./js/common.js        // 結合出力
	]
]

-body.class#id =>  [ bgcolor => "white" ]       // HTMLタグ出力
+jquery => ~
	// ウィンドウリサイズで高さ調整するエレメント
	$(".list-view").adjustHeight();
//	$("#ContentBody").adjustHeight();
~
.appWindow => [    // タグ名省略は DIVタグセクション
	".split-pane fixed-left" => [
		".split-pane-component sitemap#left-component" => [
            @SideMenu
		]
		.split-pane-divider#v-divider => []
		.split-pane-component#right-component => [
		// 最上部に固定する
			@Toolbar
			".split-pane-component list-view#list-component" => [
				&makeListTable =>  [ category => 'content2' ]
			],
			".split-pane-component contents-view" => [
				// コンテンツを表示する領域
				#ContentBody => [
					@DocView
				]
			]
		]
	]
]

@Footer
