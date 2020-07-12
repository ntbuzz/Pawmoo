//  セクション・レイアウト定義
@Header => [	          // @ ViewTemplate() 呼び出し
	 PageTitle => ${#TITLE}		// 引数は変数にセット
	 AdditionHeader => [
		 ./css/common.css
		 ./js/common.js
	]
]
-body =>  [ bgcolor => "white" ]       // HTMLタグ出力
+jquery => ~
	// ウィンドウリサイズで高さ調整するエレメント
	$(".blogContents").adjustHeight();
	$(".sideMenu").adjustHeight();
~
.appWindow => [    // タグ名省略は DIVタグセクション
	@BlogTop
	.blogContents => [
		.blogBody => [
			@BlogBody
		]
		.sideMenu => [
			@SideMenu
		]
	]
]

//------------------------------------------------------------------------------
// パラグラフ操作・フロートウィンドウ
".floatWindow#login_dialog" => [ size => "560,450,200,150"
	value => "Login,Cancel"
	+dl => [ [ Login		// ウィンドウタイトルは呼出時に決める
		[	.dialog-form => [	// div-section
				table => [
					tr => [ th => [ ユーザー名: ] td => [ -input[userid] => [ type => text size => 8 ] ] ]
					tr => [ th => [ パスワード: ] td => [ -input[password] => [ type => password size => 48 ] ] ]
				]
			]
		]
	] ]
]

@Footer
