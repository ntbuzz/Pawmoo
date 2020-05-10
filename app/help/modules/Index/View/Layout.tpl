//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	 PageTitle => $#TITLE		// 引数は変数にセット
	 AdditionHeader => [
		 css/appstyle.css
		./css/common.css      // 結合出力
       	./js/common.js        // 結合出力
	]
]

-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
+jquery => ~
	// ウィンドウリサイズで高さ調整するエレメント
	$(".contents-view").adjustHeight();
//	$("#ContentBody").adjustHeight();
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
//=========================================
// パラグラフ・コンテキストメニュー
+ul.context-menu#popup_menu{.paragraph} => [
	#ctxEdit => [ 段落編集(ent) ]
	#ctxIns  => [ 段落挿入(Ins) ]
	[ <hr> ]
	#ctxDel => [ 段落削除(del) ]
]
//=========================================
// パラグラフ・コンテキストメニュー
"+ul.context-menu#popup_menu{.section}" => [
	#ctxSecEdit => [ セクション編集(ent) ]
	#ctxAdd  => [ 段落追加(f11) ]
	[ <hr> ]
	#ctxClear => [ 段落クリア(clr) ]
]
//=========================================
// セクションタブ・コンテキストメニュー
"+ul.context-menu#popup_tab{.tab li}" => [
	#ctxSecAdd  => [ セクション追加(f11) ]
	[ <hr> ]
	#ctxSecDel => [ セクション削除(del) ]
]
//=========================================
// インラインレイアウトセクションの定義
+inline.Para_data => [
	table => [
		tr => [ th => [ 所属セクション: ] td => [ "span.boldtxt section" => [] ] ]
		tr => [ th => [ 表示位置: ] td => [ -input[dispno] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ 内容: ] td => [ textarea.contents[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
+inline.Sec_data => [
	table => [
		tr => [ th => [ 表示位置: ] td => [ -input[dispno] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ タブ表示: ] td => [ -input[short_title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ セクション説明: ] td => [ textarea.contents[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
//=========================================

//=========================================
// パラグラフ編集・フロートウィンドウ
".floatWindow#edit_dialog" => [ size => "550,300,200,150"
	+dl => [ [ "データ編集"	// dt-inner
		.dialog-view => [	// dd.attr
			.dialog-form => [	// div-section
				@.Para_data		// インラインセクションの展開
				<hr>
				.center => [ "span.button closeButton#update_paragraph" => [ "更新" ] ]
			]
		]
	] ]
]
//=========================================
// パラグラフ追加・フロートウィンドウ
".floatWindow#add_dialog" => [ size => "550,300,200,150"
	+dl => [ [ "データ追加"	// dt-inner
		.dialog-view => [	// dd.attr
			.dialog-form => [	// div-section
				@.Para_data		// インラインセクションの展開
				<hr>
				.center => [ "span.button closeButton#new_paragraph" => [ "追加" ] ]
			]
		]
	] ]
]
//=========================================
// セクション編集・フロートウィンドウ
".floatWindow#edit_section_dialog" => [ size => "550,300,200,150"
	+dl => [ [ "セクション編集"	// dt-inner
		.dialog-view => [		// dd.attr
			.dialog-form => [	// div-section
				@.Sec_data		// インラインセクションの展開
				<hr>
				.center => [ "span.button closeButton#update_section" => [ "更新" ] ]
			]
		]
	] ]
]
//=========================================
// セクション追加・フロートウィンドウ
".floatWindow#add_section_dialog" => [ size => "550,300,200,150"
	+dl => [ [ "セクション追加"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				@.Sec_data		// インラインセクションの展開
				<hr>
				.center => [ "span.button closeButton#new_section" => [ "追加" ] ]
			]
		]
	] ]
]

@Footer
