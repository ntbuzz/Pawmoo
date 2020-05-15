//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	 PageTitle => ${#HOSTS}		// 引数は変数にセット
	 AdditionHeader => [
		css/appstyle.css      // app共通スタイル
		./css/common.css      // 結合出力
       	./js/common.js        // 結合出力
	]
]
// リスト表示のみのレイアウト
-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
.appWindow => [    // タグ名省略は DIVタグセクション
//a#dialog1-click => [ テスト1 ]
//a#dialog2-click => [ テスト2 ]
	@Toolbar
	&makePageLinks
	".list-view" => [
		&makeListTable =>  [
			category => 'content2'
			tableId => myTable
		]
	],
]
// フロートウィンドウのセクション定義方法
".floatWindow#dialog1" => [ size => "850,450,500,200"
	data#init => [		// 初期データの定義
		value => ""
	]
	dl => [ dt => [ "HOST %N の登録情報" ]	// タイトル定義
	dd => [							// データ表示領域
		div#datalist => []		// 表示テーブル
	] ]
]
.floatWindow#dialog2 => [
	data#init => [
		value => "<tr><th>No</th><th>操作</th><th>ファイル名</th><th>元文書</th><th>リンク表示</th></tr>"
	]
	dl => [ dt => [ "Q&A ${%1}の添付ファイル表示2" ]
	dd => [
		ここにはドラッグドロップできない
		span.button => [ Close ]		// ボタン表示
	] ]
]

@Footer
