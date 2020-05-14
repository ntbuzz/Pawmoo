//
// ポップアップメニュー定義
//
#ctxCopy => []	// 文字列コピー用のタグ
//=========================================
// パラグラフ・コンテキストメニュー
+ul.context-menu#popup_menu{.paragraph} => [
	#ctxEdit => [ 段落編集(ent) ]
	#ctxIns  => [ 段落挿入(Ins) ]
	.separate => [ <hr> ]
	#ctxCopy1 => [ コピー ]
	[ <hr> ]
	.ctxDel => [ 段落削除(del) ]
]
//=========================================
// パラグラフ・コンテキストメニュー
"+ul.context-menu#popup_menu{.section}" => [
	#ctxSecEdit => [ セクション編集(ent) ]
	#ctxSecAdd  => [ セクション追加(f11) ]
	.separate => [ <hr> ]
	#ctxSecDel => [ セクション削除(del) ]
	.separate => [ <hr> ]
	#ctxAdd  => [ 段落追加(f11) ]
	.separate => [ <hr> ]
	#ctxCopy2 => [ コピー ]
	.separate => [ <hr> ]
	#ctxClear => [ 段落クリア(clr) ]
]
//=========================================
// セクションタブ・コンテキストメニュー
//"+ul.context-menu#popup_tab{.tab li}" => [
//]
//=========================================
// インラインレイアウトセクションの定義
+inline.Para_data => [
	-input[section_id] => [ type => hidden ]
	table => [
		tr => [ th => [ 所属セクション: ] td => [ "span.boldtxt[section]" => [] ] ]
		tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ 内容: ] td => [ textarea[contents] => [ rows => 20 cols => 50 ] ] ]
	]
]
+inline.Sec_data => [
	table => [
		tr => [ th => [ 所属チャプター: ] td => [ &ChapterSelector ] ]
		tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ タブ表示: ] td => [ -input[short_title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ セクション説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
+inline.Chap_data => [
	-input[part_id] => [ type => hidden ]
	table => [
		tr => [ th => [ 所属パート: ] td => [ &PartSelector ] ]
		tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ チャプタ説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
+inline.Part_data => [
	table => [
		tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ パート説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
//=========================================

//=========================================
// パラグラフ編集・フロートウィンドウ
".floatWindow#edit_dialog" => [ size => "550,450,200,150"
	value => "更新,キャンセル"
	+dl => [ [ "データ編集"	// dt-inner
		.dialog-view => [	// dd.attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				@.Para_data		// インラインセクションの展開
			]
		]
	] ]
]
//=========================================
// パラグラフ追加・フロートウィンドウ
".floatWindow#add_dialog" => [ size => "550,450,200,150"
	value => "追加,キャンセル"
	+dl => [ [ "データ追加"	// dt-inner
		.dialog-view => [	// dd.attr
			.dialog-form => [	// div-section
				@.Para_data		// インラインセクションの展開
			]
		]
	] ]
]
//=========================================
// セクション編集・フロートウィンドウ
".floatWindow#edit_section_dialog" => [ size => "550,320,200,150"
	value => "更新,キャンセル"
	+dl => [ [ "セクション編集"	// dt-inner
		.dialog-view => [		// dd.attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				@.Sec_data		// インラインセクションの展開
			]
		]
	] ]
]
//=========================================
// セクション追加・フロートウィンドウ
".floatWindow#add_section_dialog" => [ size => "550,320,200,150"
	value => "追加,キャンセル"
	+dl => [ [ "セクション追加"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				@.Sec_data		// インラインセクションの展開
				.center => [ "span.execButton" => [ "追加" ] ]
			]
		]
	] ]
]
//=========================================
// チャプター編集・フロートウィンドウ
".floatWindow#edit_chapter_dialog" => [ size => "500,315,200,150"
	value => "更新,キャンセル"
	+dl => [ [ "チャプター編集"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				@.Chap_data
			]
		]
	] ]
]
//=========================================
// チャプター追加・フロートウィンドウ
".floatWindow#add_chapter_dialog" => [ size => "500,315,200,150"
	value => "追加,キャンセル"
	+dl => [ [ "チャプター追加"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				@.Chap_data
			]
		]
	] ]
]
//=========================================
// パート追加・フロートウィンドウ
".floatWindow#add_part_dialog" => [ size => "480,285,200,150"
	value => "追加,キャンセル"
	+dl => [ [ "パート追加"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				@.Part_data
			]
		]
	] ]
]
//=========================================
// パート編集・フロートウィンドウ
".floatWindow#edit_part_dialog" => [ size => "480,285,200,150"
	value => "更新,キャンセル"
	+dl => [ [ "パート編集"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				@.Part_data
			]
		]
	] ]
]
