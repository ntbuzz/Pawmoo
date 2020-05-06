//  セクション・レイアウト定義

@Header => [	          // @ ViewTemplate() 呼び出し
	PageTitle => $#TITLE		// 引数は変数にセット
	AdditionHeader => [
		./css/common.css      // カスタマイズスタイル
      	./js/common.js        // カスタマイズスクリプト
	]
]

-body =>  [ bgcolor => "green" ]       // HTMLタグ出力
.appWindow => [    // タグ名省略は DIVタグセクション

	+echo => "TEST LAYOUT";
	+ul => [ attr => value    
      list => [ list-section -hr => [] ]
      list-value second-value
	]
// リストコマンド形式
	+ul#menu => [  class => Menu-CLASS
		ホームリンク	 => [ %${#Toolbar.home} => "/index" ]		 //  ハイパーリンク
		ファイルメニュー => [ %${#Toolbar.ファイル} => "#"
				+ul.sub => [ class => SUB-Menu
					[ %${#Toolbar.ファイル.新規} => "#" ]
					[ %${#Toolbar.ファイル.開く} => "#" ]
					[ %${#Toolbar.ファイル.終了} => "#" ]
				]
		]
	]
// 一般的なタグセクション形式
	ul#menu => [ class => Menu-CLASS
		li => [ %${#Toolbar.home} => "/index" ]		 //  ハイパーリンク
		li => [ 
			%${#Toolbar.ファイル} => "#"
			ul.sub => [ class => SUB-Menu
				li => [ %${#Toolbar.ファイル.新規} => "#" ]
				li => [ %${#Toolbar.ファイル.開く} => "#" ]
				li => [ %${#Toolbar.ファイル.終了} => "#" ]
			]
		]
	]
]

// @Footer
