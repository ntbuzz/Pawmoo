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
	$(".contents-view").adjustHeight();
//	$(".tabmenu").stickyOn('.contents-view');
~
.appWindow => [    // タグ名省略は DIVタグセクション
	".split-pane fixed-left" => [
		".split-pane-component sitemap#left-component" => [
			div => [
				style => "text-align:center;background-color:lightyellow;"
				-img#biscuit-help => [ src => "/newfw/res/images/biscuit.png" ]
			 ]
			@SideMenu
		]
		.split-pane-divider#v-divider => []
		.split-pane-component#right-component => [
		// 最上部に固定する
			@Toolbar
		// #ContentBody をスクロール表示(contents-view)するブロック
			".split-pane-component contents-view" => [
				// コンテンツを表示する領域
				#ContentBody => [
					@ContentView
				]
			]
		]
	]
]


@Footer
