//
// ポップアップメニュー定義
//
#ctxCopy => []	// 文字列コピー用のタグ
//=========================================
// パラグラフ・コンテキストメニュー
+ul.context-menu#popup_menu{.paragraph} => [
	#ctxEdit => [ ${#.Popup.Para-Edit} ]
	#ctxIns  => [ ${#.Popup.Para-Ins} ]
	#ctxAdd  => [ ${#.Popup.Para-Add} ]
	.separate => [ ]
	#ctxCopy1 => [ ${#.Popup.Copy} ]
	.separate => [ ]
	#ctxDel => [ ${#.Popup.Para-Delete} ]
]
//=========================================
// パラグラフ・コンテキストメニュー
"+ul.context-menu#popup_menu{.section}" => [
	#ctxSecEdit => [ ${#.Popup.Sec-Edit} ]
	#ctxSecAdd  => [ ${#.Popup.Sec-Add} ]
	.separate => [ <hr> ]
	#ctxSecDel => [ ${#.Popup.Sec-Delete} ]
	.separate => [ <hr> ]
	#add-paragraph  => [ ${#.Popup.Para-Add} ]
	.separate => [ <hr> ]
	#ctxClear => [ ${#.Popup.Para-Clear} ]
	.separate => [ <hr> ]
	#ctxCopy2 => [ ${#.Popup.Copy} ]
]
//=========================================
// セクションタブ・コンテキストメニュー
"+ul.context-menu#popup_tab{.tab li}" => [
	#edit-section => [ ${#.Popup.Sec-Edit} ]
	#add_section  => [ ${#.Popup.Sec-Add} ]
	.separate => [ <hr> ]
	#delete-section => [ ${#.Popup.Sec-Delete} ]
]
//------------------------------------------------------------------------------
// パラグラフ操作・フロートウィンドウ
".floatWindow#paragraph_dialog" => [ size => "560,450,200,150"
	value => "${#.Button.Update},${#.Button.Cancel}"
	+dl => [ [ ${#.Dialog.Edit-Paragraph}		// ウィンドウタイトルは呼出時に決める
		[	.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				-input[section_id] => [ type => hidden ]
				table => [
					tr => [ th => [ ${#.Dialog.Belong-Sec}: ] td => [ "span.boldtxt[section]" => [] ] ]
					tr => [ th => [ ${#.Dialog.DispAt}: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ ${#.Dialog.Title}: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ ${#.Dialog.Desc-Para}: ] td => [ textarea[contents] => [ rows => 20 cols => 30 ] ] ]
				]
			]
		]
	] ]
]
//------------------------------------------------------------------------------
// セクション操作・フロートウィンドウ
".floatWindow#section_dialog" => [ size => "530,350,200,150"
	value => "${#.Button.Update},${#.Button.Cancel}"
	+dl => [ [ ${#.Dialog.Edit-Section}	// dt-inner
		[	.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				table => [
					tr => [ th => [ ${#.Dialog.Belong-Chap}: ] td => [ &ChapterSelector ] ]
					tr => [ th => [ ${#.Dialog.DispAt}: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ ${#.Dialog.Title}: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ タブ表示: ] td => [ -input[short_title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ ${#.Dialog.Desc-Sec}: ] td => [ textarea[contents] => [ rows => 10 cols => 30 ] ] ]
				]
			]
		]
	] ]
]
//------------------------------------------------------------------------------
// チャプター操作・フロートウィンドウ
".floatWindow#chapter_dialog" => [ size => "520,290,200,150"
	value => "${#.Button.Update},${#.Button.Cancel}"
	+dl => [ [ ${#.Dialog.Edit-Chapter}	// dt-inner
		[	.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				-input[part_id] => [ type => hidden ]
				table => [
					tr => [ th => [ ${#.Dialog.Belong-Part}: ] td => [ &PartSelector ] ]
					tr => [ th => [ ${#.Dialog.DispAt}: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ ${#.Dialog.Title}: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ ${#.Dialog.Desc-Chap}: ] td => [ textarea[contents] => [ rows => 8 cols => 28 ] ] ]
				]
			]
		]
	] ]
]
//------------------------------------------------------------------------------
// パート操作・フロートウィンドウ
".floatWindow#part_dialog" => [ size => "520,220,200,150"
	value => "${#.Button.Update},${#.Button.Cancel}"
	+dl => [ [ ${#.Dialog.Edit-Part}	// dt-inner
		[	.dialog-form => [	// div-section
				-input[id] => [ type => hidden ]
				table => [
					tr => [ th => [ ${#.Dialog.DispAt}: ] td => [ -input[disp_id] => [ type => text size => 8 ] ] ]
					tr => [ th => [ ${#.Dialog.Title}: ] td => [ -input[title] => [ type => text size => 48 ] ] ]
					tr => [ th => [ ${#.Dialog.Desc-Part}: ] td => [ textarea[contents] => [ rows => 5 cols => 28 ] ] ]
				]
			]
		]
	] ]
]
