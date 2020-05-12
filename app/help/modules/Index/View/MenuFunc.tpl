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
	-input[chapter_id] => [ type => hidden ]
	table => [
		tr => [ th => [ 所属チャプター: ] td => [ "span.boldtxt[chapter]" => [] ] ]
		tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ タブ表示: ] td => [ -input[short_title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ セクション説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
+inline.Chap_data => [
	-input[part_id] => [ type => hidden ]
	table => [
		tr => [ th => [ 所属パート: ] td => [ "span.boldtxt[part]" => [] ] ]
		tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ セクション説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
+inline.Part_data => [
	table => [
		tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
		tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
		tr => [ th => [ セクション説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
	]
]
//=========================================

//=========================================
// パラグラフ編集・フロートウィンドウ
".floatWindow#edit_dialog" => [ size => "550,450,200,150"
	+dl => [ [ "データ編集"	// dt-inner
		.dialog-view => [	// dd.attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				@.Para_data		// インラインセクションの展開
				<hr>
				.center => [ "span.execButton" => [ "更新" ] ]
			]
		]
	] ]
]
//=========================================
// パラグラフ追加・フロートウィンドウ
".floatWindow#add_dialog" => [ size => "550,450,200,150"
	+dl => [ [ "データ追加"	// dt-inner
		.dialog-view => [	// dd.attr
			.dialog-form => [	// div-section
				@.Para_data		// インラインセクションの展開
				<hr>
				.center => [ "span.execButton" => [ "追加" ] ]
			]
		]
	] ]
]
//=========================================
// セクション編集・フロートウィンドウ
".floatWindow#edit_section_dialog" => [ size => "550,320,200,150"
	+dl => [ [ "セクション編集"	// dt-inner
		.dialog-view => [		// dd.attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				@.Sec_data		// インラインセクションの展開
				<hr>
				.center => [ "span.execButton" => [ "更新" ] ]
			]
		]
	] ]
]
//=========================================
// セクション追加・フロートウィンドウ
".floatWindow#add_section_dialog" => [ size => "550,320,200,150"
	+dl => [ [ "セクション追加"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				@.Sec_data		// インラインセクションの展開
				<hr>
				.center => [ "span.execButton" => [ "追加" ] ]
			]
		]
	] ]
]
//=========================================
// チャプター編集・フロートウィンドウ
".floatWindow#edit_chapter_dialog" => [ size => "480,230,200,150"
	+dl => [ [ "チャプター編集"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				table => [
					tr => [ th => [ 所属パート: ] td => [ &ChapterSelector ] ]
					tr => [ th => [ 表示位置: ] td => [ -input[dispno] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ 内容: ] td => [ textarea.contents[contents] => [ rows => 5 cols => 50 ] ] ]
				]
				<hr>
				.center => [ "span.execButton" => [ "更新" ] ]
			]
		]
	] ]
]
//=========================================
// チャプター追加・フロートウィンドウ
".floatWindow#add_chapter_dialog" => [ size => "480,230,200,150"
	+dl => [ [ "チャプター追加"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				table => [
					tr => [ th => [ 所属パート: ] td => [ &ChapterSelector ] ]
					tr => [ th => [ 表示位置: ] td => [ -input[dispno] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ 内容: ] td => [ textarea.contents[contents] => [ rows => 5 cols => 50 ] ] ]
				]
				<hr>
				.center => [ "span.execButton" => [ "追加" ] ]
			]
		]
	] ]
]
//=========================================
// パート追加・フロートウィンドウ
".floatWindow#add_part_dialog" => [ size => "450,200,200,150"
	+dl => [ [ "パート追加"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				table => [
					tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ 内容: ] td => [ textarea.contents[contents] => [ rows => 5 cols => 50 ] ] ]
				]
				<hr>
				.center => [ "span.execButton" => [ "追加" ] ]
			]
		]
	] ]
]
//=========================================
// パート編集・フロートウィンドウ
".floatWindow#edit_part_dialog" => [ size => "450,200,200,150"
	+dl => [ [ "パート編集"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				table => [
					tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ 内容: ] td => [ textarea[contents] => [ rows => 5 cols => 50 ] ] ]
				]
				<hr>
				.center => [ "span.execButton" => [ "更新" ] ]
			]
		]
	] ]
]
