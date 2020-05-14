//
// ポップアップメニュー定義
//
#ctxCopy => []	// 文字列コピー用のタグ
//=========================================
// パラグラフ・コンテキストメニュー
+ul.context-menu#popup_menu{.paragraph} => [
	#ctxEdit => [ 段落編集(ent) ]
	#ctxIns  => [ 段落挿入(Ins) ]
	#ctxAdd  => [ 段落追加(f11) ]
	.separate => [ <hr> ]
	#ctxCopy1 => [ コピー ]
	[ <hr> ]
	#ctxDel => [ 段落削除(del) ]
]
//=========================================
// パラグラフ・コンテキストメニュー
"+ul.context-menu#popup_menu{.section}" => [
	#ctxSecEdit => [ セクション編集(ent) ]
	#ctxSecAdd  => [ セクション追加(f11) ]
	.separate => [ <hr> ]
	#ctxSecDel => [ セクション削除(del) ]
	.separate => [ <hr> ]
	#add-paragraph  => [ 段落追加(f11) ]
	.separate => [ <hr> ]
	#ctxClear => [ 段落クリア(clr) ]
	.separate => [ <hr> ]
	#ctxCopy2 => [ コピー ]
]
//=========================================
// セクションタブ・コンテキストメニュー
"+ul.context-menu#popup_tab{.tab li}" => [
	#edit-section => [ セクション編集(ent) ]
	#add-section  => [ セクション追加(f11) ]
	.separate => [ <hr> ]
	#delete-section => [ セクション削除(del) ]
]
//------------------------------------------------------------------------------
// パラグラフ操作・フロートウィンドウ
".floatWindow#paragraph_dialog" => [ size => "550,450,200,150"
	value => "更新,キャンセル"
	+dl => [ [ 		// ウィンドウタイトルは呼出時に決める
		.dialog-view => [	// dd.attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				-input[section_id] => [ type => hidden ]
				table => [
					tr => [ th => [ 所属セクション: ] td => [ "span.boldtxt[section]" => [] ] ]
					tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ 内容: ] td => [ textarea[contents] => [ rows => 20 cols => 50 ] ] ]
				]
			]
		]
	] ]
]
//------------------------------------------------------------------------------
// セクション操作・フロートウィンドウ
".floatWindow#section_dialog" => [ size => "550,320,200,150"
	value => "更新,キャンセル"
	+dl => [ [ "セクション編集"	// dt-inner
		.dialog-view => [		// dd.attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				table => [
					tr => [ th => [ 所属チャプター: ] td => [ &ChapterSelector ] ]
					tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ タブ表示: ] td => [ -input[short_title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ セクション説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
				]
			]
		]
	] ]
]
//------------------------------------------------------------------------------
// チャプター操作・フロートウィンドウ
".floatWindow#chapter_dialog" => [ size => "500,315,200,150"
	value => "更新,キャンセル"
	+dl => [ [ "チャプター編集"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				-input[part_id] => [ type => hidden ]
				table => [
					tr => [ th => [ 所属パート: ] td => [ &PartSelector ] ]
					tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ チャプタ説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
				]
			]
		]
	] ]
]
//------------------------------------------------------------------------------
// パート操作・フロートウィンドウ
".floatWindow#part_dialog" => [ size => "480,285,200,150"
	value => "更新,キャンセル"
	+dl => [ [ "パート編集"	// dt-inner
		.dialog-view => [		// dd-attr
			.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				table => [
					tr => [ th => [ 表示位置: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ タイトル: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ パート説明: ] td => [ textarea[contents] => [ rows => 10 cols => 50 ] ] ]
				]
			]
		]
	] ]
]
