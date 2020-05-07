//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	PageTitle => $#NAMES		// 引数は変数にセット
	AdditionHeader => [
		css/appstyle.css      // app共通スタイル
		./css/common.css      // カスタマイズスタイル
      	./js/common.js        // カスタマイズスクリプト
	]
]

-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
+jquery => ~
	// ウィンドウリサイズで高さ調整するエレメント
	$(".list-view").adjustHeight();
~
.appWindow => [    // タグ名省略は DIVタグセクション
	@Toolbar
	".split-pane fixed-left" => [
		".split-pane-component sitemap#left-component" => [
				@SideMenu
		]
		.split-pane-divider#v-divider => []
		.split-pane-component#right-component => [
			&makePageLinks						// 右側固定表示
			".split-pane-component list-view" => [
				&makeListTable =>  [
					category => 'content2'
					tableId => myTable
				]
			]
		]
	]
]

.popup-baloon{@opup-dialog} => [
	$#.HELP1
]
.popup-baloon{pager_help} => [
	$#HELP2
]
// フロートウィンドウのセクション定義方法
".floatWindow#dialog1" => [ size => "850,450,500,250"
	data#init => [		// 初期データの定義
		value => ""
	]
	dl => [ dt => [ "${%0%}のデータ操作" ]	// タイトル定義
	dd => [							// データ表示領域
		div#datalist => []		// 表示テーブル
	] ]
]

@Footer
