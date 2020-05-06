//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	PageTitle => $#TITLE		// 引数は変数にセット
	AdditionHeader => [
//		css/appstyle.css      // app共通スタイル
		./css/common.css      // カスタマイズスタイル
      	./js/common.js        // カスタマイズスクリプト
	]
]
+style => ~
.fit-win {
	position:relative;
	padding-left:50px;
	height:100%;
    user-select:text;
    -moz-user-select: text;
    -webkit-user-select: text;
    -ms-user-select: text;
	background-color:white;
}
~
+jquery => ~
	// ウィンドウリサイズで高さ調整するエレメント
	$(".list-view").adjustHeight();
~

-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
.appWindow => [    // タグ名省略は DIVタグセクション
	@Toolbar
	".split-pane fixed-left" => [
		".split-pane-component sitemap#left-component" => [
				@SideMenu
		]
		".split-pane-divider#v-divider" => []
		".split-pane-component list-view fit-win#right-component" => [
			&MakeZoneFile
		]
	]
]

@Footer
