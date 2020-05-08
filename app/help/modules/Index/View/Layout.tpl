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
	$("#ContentBody").adjustHeight();
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
//					@ContentView
					@DocView
				]
			]
		]
	]
]
//=========================================
// パラグラフ・コンテキストメニュー
+ul.context-menu#popup_menu{.paragraph} => [
	#ctxEdit => [ 段落編集(ent) ]
	#ctxIns  => [ 段落挿入(Ins) ]
	#ctxUndo => [ 段落取消(esc) ]
	[ <hr> ]
	#ctxDel => [ 段落削除(del) ]
]
//=========================================
// パラグラフ・コンテキストメニュー
"+ul.context-menu#popup_menu{.section}" => [
	#ctxAdd  => [ 段落追加(f11) ]
	[ <hr> ]
	#ctxClear => [ 段落クリア(clr) ]
]
//=========================================
// セクションタブ・コンテキストメニュー
"+ul.context-menu#popup_tab{.tab li}" => [
	#ctxSecEdit => [ セクション編集(ent) ]
	#ctxSecAdd  => [ セクション追加(f11) ]
	[ <hr> ]
	#ctxSecDel => [ セクション削除(del) ]
]
//=========================================
// パラグラフ編集・フロートウィンドウ
".floatWindow#edit_dialog" => [ size => "550,350,250,200"
	+dl => [ "データ編集" => [
		div.dialog-data => [
			table.form => [
				tr => [ th => [ 所属セクション: ] td => [ span.boldtxt#section => [] ] ]
				tr => [ th => [ タイトル: ] td => [ input#title[title] => [ type => text size => 48 ] ] ]
				tr => [ th => [ 内容: ] td => [ textarea#contents[contents] => [ rows => 10 cols => 50 ] ] ]
			]
			<hr>
			.center => [ input => [ type => submit value => "更新" ] ]
		]
	] ]
]
//=========================================
// パラグラフ追加・フロートウィンドウ
".floatWindow#add_dialog" => [ size => "550,350,250,200"
	dl => [ dt => [ "データ追加" ]	// タイトル定義
	dd => [							// データ表示領域
		div.dialog-data => [
			table.form => [
				tr => [ th => [ 所属セクション: ] td => [ span.boldtxt#section => [] ] ]
				tr => [ th => [ タイトル: ] td => [ input#title[title] => [ type => text size => 48 ] ] ]
				tr => [ th => [ 内容: ] td => [ textarea#contents[contents] => [ rows => 10 cols => 50 ] ] ]
			]
			<hr>
			.center => [ input => [ type => submit value => "更新" ] ]
		]
	] ]
]
//=========================================
// セクション編集・フロートウィンドウ
".floatWindow#edit_section_dialog" => [ size => "500,250,300,150"
	dl => [ dt => [ "セクション編集" ]	// タイトル定義
	dd => [							// データ表示領域
		div.dialog-data => [
			Section:  input => [ type => text size => 50 ]
		]		// 表示テーブル
	] ]
]
//=========================================
// セクション追加・フロートウィンドウ
".floatWindow#add_section_dialog" => [ size => "500,250,300,150"
	dl => [ dt => [ "セクション追加" ]	// タイトル定義
	dd => [							// データ表示領域
		div.dialog-data => [
			Section:  input => [ type => text size => 50 ]
		]		// 表示テーブル
	] ]
]


@Footer
