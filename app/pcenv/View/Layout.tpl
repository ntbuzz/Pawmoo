//  セクション・レイアウト定義
+setvar => [
    varname => [
        data1.css
        data2.js
    ]
]
@Header => [
    PageTitle => ${#.TITLE}
    AdditionHeader => [
        css/style.css      // 
        res/css/common.min.css      // 結合出力
        res/js/common.min.js        // 結合出力
    ]
	Test => ${varname}
]
<body>
.appWindow => [    // タグ名省略は DIVタグセクション
	@Toolbar
    .pretty-split-pane-frame => [
	    ".split-pane fixed-left" => [
    	    .split-pane-component => [
        	    .pretty-split-pane-component-inner#left-component => [
            	    @SideMenu
              	]
	        ]
    	    .split-pane-divider#vertical-divider => []
        	.split-pane-component#right-component => [	&makePageLinks
                	".split-pane-component list-view#list-component" => [
						&makeListTable =>  [
							category => 'content2'
							tableId => myTable
						]
					]
    		]
		]
	]

]
// フロートウィンドウのセクション定義
".floatWindow#dialog1" => [ size => "650,380,500,250"
	data#init => [		// 初期データの定義
		value => ""
	]
	dl => [ dt => [ "${%0}のデータ操作" ]	// タイトル定義
	dd => [							// データ表示領域
		div#datalist => []		// 表示テーブル
	] ]
]

@Footer
